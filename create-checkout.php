<?php
/**
 * ===============================================================
 *  ÉTAPE 1 DE L'ACHAT — Vérifier la commande et lancer le paiement
 * ===============================================================
 *
 *  Cette page reçoit le formulaire « carte cadeau » du site, puis :
 *
 *    1. elle nettoie et vérifie TOUT ce qui arrive du navigateur ;
 *    2. elle revérifie le montant côté serveur (règle d'or : on ne
 *       fait jamais confiance au prix envoyé par le navigateur, il
 *       est modifiable en deux clics) ;
 *    3. elle enregistre la commande « en attente » avec son code ;
 *    4. elle envoie le client payer sur la page sécurisée de Stripe.
 *
 *  Aucune donnée de carte bancaire ne transite jamais par ce site :
 *  c'est Stripe qui affiche le formulaire de paiement, chez lui.
 */

declare(strict_types=1);

require __DIR__ . '/lib.php';

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

// On n'accepte que l'envoi du formulaire. Une visite directe est
// simplement renvoyée vers la section carte cadeau du site.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /#carte-cadeau', true, 303);
    exit;
}


/* ---------------------------------------------------------------
 *  1. NETTOYAGE DES CHAMPS
 * ------------------------------------------------------------- */

$deNom     = nettoyer_ligne($_POST['fromName']  ?? '');
$deEmail   = mb_strtolower(nettoyer_ligne($_POST['fromEmail'] ?? '', 254));
$pourNom   = nettoyer_ligne($_POST['toName']    ?? '');
$pourEmail = mb_strtolower(nettoyer_ligne($_POST['toEmail']   ?? '', 254));
$message   = nettoyer_texte($_POST['message']   ?? '');


/* ---------------------------------------------------------------
 *  2. LE MONTANT — la vérification la plus importante
 * ------------------------------------------------------------- */

$min = (int) reglage('carte_cadeau.montant_min', 10);
$max = (int) reglage('carte_cadeau.montant_max', 200);

$montantBrut = trim((string)($_POST['amount'] ?? ''));

// Uniquement des chiffres : ni virgule, ni signe moins, ni texte.
if (!preg_match('/^[0-9]{1,4}$/', $montantBrut)) {
    page_erreur("Le montant indiqué n'est pas valable.");
}

$montant = (int) $montantBrut;

if ($montant < $min || $montant > $max) {
    page_erreur("Le montant d'une carte cadeau doit être compris entre {$min} € et {$max} €.");
}

// Stripe raisonne en centimes.
$montantCents = $montant * 100;


/* ---------------------------------------------------------------
 *  3. LES AUTRES CHAMPS
 * ------------------------------------------------------------- */

if ($deNom === '' || $pourNom === '') {
    page_erreur("Merci d'indiquer votre nom et celui du destinataire.");
}
if (!email_valide($deEmail)) {
    page_erreur("Votre adresse e-mail ne semble pas valide.");
}
if (!email_valide($pourEmail)) {
    page_erreur("L'adresse e-mail du destinataire ne semble pas valide.");
}


/* ---------------------------------------------------------------
 *  4. GARDE-FOU ANTI-ABUS
 * ------------------------------------------------------------- */

$pdo = bdd();
$ip  = ip_visiteur();

$req = $pdo->prepare(
    "SELECT COUNT(*) FROM cartes
      WHERE ip = ? AND cree_le > datetime('now', '-1 hour')"
);
$req->execute([$ip]);

if ((int) $req->fetchColumn() >= 8) {
    journal("Trop de commandes depuis l'IP $ip");
    page_erreur("Trop de commandes ont été lancées depuis cet appareil. Merci de réessayer dans une heure.", 429);
}


/* ---------------------------------------------------------------
 *  5. ENREGISTREMENT DE LA COMMANDE (statut « en attente »)
 * ------------------------------------------------------------- */

$code = code_unique($pdo);

try {
    $req = $pdo->prepare(
        'INSERT INTO cartes
           (code, montant_cents, de_nom, de_email, pour_nom, pour_email,
            message, statut, ip, cree_le)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $req->execute([
        $code, $montantCents, $deNom, $deEmail, $pourNom, $pourEmail,
        $message, 'en_attente', $ip, gmdate('Y-m-d H:i:s'),
    ]);
    $carteId = (int) $pdo->lastInsertId();
} catch (Throwable $e) {
    journal('Enregistrement de la commande impossible : ' . $e->getMessage());
    page_erreur("Le site n'arrive pas à enregistrer votre commande.");
}

$siteUrl = rtrim((string) reglage('site_url', ''), '/');


/* ---------------------------------------------------------------
 *  6a. MODE SIMULATION (tant que la clé Stripe n'est pas remplie)
 * ------------------------------------------------------------- */

if (mode_simulation()) {
    journal("SIMULATION : commande $code de {$montant} € pour $pourEmail");
    header('Location: ' . $siteUrl . '/merci.php?simulation=1&code=' . urlencode($code), true, 303);
    exit;
}

/* ---------------------------------------------------------------
 *  6a bis. GARDE-FOU : PAS DE CLÉ STRIPE SUR LE SITE EN LIGNE
 * ------------------------------------------------------------- */

// Sans clé Stripe, aucun paiement ne peut être encaissé. Plutôt que de
// laisser passer la commande (ce qui reviendrait à offrir la carte), on
// arrête ici et on l'annonce poliment à la visiteuse. La commande déjà
// enregistrée reste en attente de paiement, elle ne vaut rien tant que
// Stripe ne l'a pas confirmée.
if (!est_rempli('stripe.secret_key')) {
    journal("Commande $code refusée : la clé Stripe n'est pas configurée.");
    page_erreur(
        "L'achat de carte cadeau en ligne n'est pas encore disponible. "
        . "Contactez l'institut, nous vous l'établirons directement.",
        503
    );
}


/* ---------------------------------------------------------------
 *  6b. CRÉATION DE LA PAGE DE PAIEMENT STRIPE
 * ------------------------------------------------------------- */

[$httpCode, $session] = stripe('checkout/sessions', [
    'mode'                => 'payment',
    'locale'              => 'fr',
    'success_url'         => $siteUrl . '/merci.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'          => $siteUrl . '/#carte-cadeau',
    'customer_email'      => $deEmail,
    'client_reference_id' => $code,
    'metadata'            => [
        'code'        => $code,
        'destinataire'=> $pourNom,
    ],
    'payment_intent_data' => [
        'description' => "Carte cadeau {$code} — L'atelier du cil à cil",
    ],
    'line_items' => [[
        'quantity'   => 1,
        'price_data' => [
            'currency'     => 'eur',
            'unit_amount'  => $montantCents,
            'product_data' => [
                'name'        => "Carte cadeau — L'atelier du cil à cil",
                'description' => "Carte cadeau de {$montant} € pour {$pourNom}",
            ],
        ],
    ]],
], $code); // le code sert de clé anti-doublon : un double clic ne paie qu'une fois

if ($httpCode !== 200 || empty($session['url'])) {
    journal(
        "Stripe a refusé la création du paiement (HTTP $httpCode) pour $code : "
        . ($session['error']['message'] ?? 'réponse vide')
    );
    page_erreur("La page de paiement n'a pas pu être ouverte.");
}

// On mémorise la référence Stripe : elle servira à confirmer le paiement.
try {
    $req = $pdo->prepare('UPDATE cartes SET stripe_session_id = ? WHERE id = ?');
    $req->execute([$session['id'] ?? null, $carteId]);
} catch (Throwable $e) {
    journal("Référence Stripe non enregistrée pour $code : " . $e->getMessage());
}

// Direction la page de paiement sécurisée de Stripe.
header('Location: ' . $session['url'], true, 303);
exit;
