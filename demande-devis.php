<?php
/**
 * ===============================================================
 *  RÉCEPTION D'UNE DEMANDE DE DEVIS
 * ===============================================================
 *
 *  Reçoit le formulaire de la page Formations, vérifie tout côté
 *  serveur, enregistre la demande, puis :
 *    - envoie un accusé de réception à la candidate
 *    - prévient Camille qu'une demande l'attend
 *
 *  ⚠️ AUCUN DEVIS N'EST ENVOYÉ ICI. Le devis ne part que lorsque
 *  Camille le décide depuis son espace de suivi : c'est elle qui
 *  s'engage sur un prix, pas le site.
 *
 *  Le tarif enregistré est lu dans formations-data.php, sur le
 *  serveur. Rien de ce que la candidate envoie ne peut le modifier.
 */

declare(strict_types=1);

require __DIR__ . '/lib.php';

header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

// Une visite directe, sans passer par le formulaire, revient à la page.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /formations.php#devis', true, 303);
    exit;
}


/* ---------------------------------------------------------------
 *  1. LE PIÈGE À ROBOTS
 * ------------------------------------------------------------- */

// Ce champ est invisible sur la page : seule une machine le remplit.
// On fait semblant d'accepter, sans rien enregistrer ni envoyer — un
// robot à qui on annonce une erreur réessaie, un robot satisfait s'en va.
if (nettoyer_ligne($_POST['site_web'] ?? '') !== '') {
    journal('Demande de devis écartée : champ piège rempli (robot).');
    header('Location: /formations.php#devis', true, 303);
    exit;
}


/* ---------------------------------------------------------------
 *  2. LA FORMATION DEMANDÉE — et donc son tarif
 * ------------------------------------------------------------- */

$formations = require __DIR__ . '/formations-data.php';
$slugDemande = nettoyer_ligne($_POST['formation'] ?? '', 60);

$formation = null;
foreach ($formations as $f) {
    if ($f['slug'] === $slugDemande) {
        $formation = $f;
        break;
    }
}

if ($formation === null) {
    page_erreur("Merci de choisir l'une des formations proposées.");
}

// Le prix vient d'ici, jamais du navigateur.
$prixCents = (int) $formation['prix_cents'];


/* ---------------------------------------------------------------
 *  3. LES COORDONNÉES
 * ------------------------------------------------------------- */

$nom       = nettoyer_ligne($_POST['nom'] ?? '', 80);
$email     = mb_strtolower(nettoyer_ligne($_POST['email'] ?? '', 254));
$telephone = nettoyer_ligne($_POST['telephone'] ?? '', 30);
$periode   = nettoyer_ligne($_POST['periode'] ?? '', 80);
$message   = nettoyer_texte($_POST['message'] ?? '', 1000);

if ($nom === '') {
    page_erreur("Merci d'indiquer votre nom.");
}
if (!email_valide($email)) {
    page_erreur("Votre adresse e-mail ne semble pas valide.");
}


/* ---------------------------------------------------------------
 *  4. GARDE-FOU ANTI-ABUS
 * ------------------------------------------------------------- */

$pdo = bdd();
$ip  = ip_visiteur();

if (trop_de_demandes($pdo, 'devis', $ip, 5)) {
    journal("Trop de demandes de devis depuis l'IP $ip");
    page_erreur(
        "Plusieurs demandes ont déjà été envoyées depuis cet appareil. "
        . "Merci de réessayer dans une heure, ou d'appeler directement l'institut.",
        429
    );
}


/* ---------------------------------------------------------------
 *  5. ENREGISTREMENT
 * ------------------------------------------------------------- */

$reference = code_unique($pdo, 'devis');

try {
    $req = $pdo->prepare(
        'INSERT INTO devis
           (reference, formation_slug, formation_nom, prix_propose_cents,
            nom, email, telephone, periode_souhaitee, message,
            statut, ip, cree_le)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $req->execute([
        $reference,
        $formation['slug'],
        $formation['nom'],
        $prixCents,
        $nom, $email, $telephone, $periode, $message,
        'nouvelle', $ip, gmdate('Y-m-d H:i:s'),
    ]);
} catch (Throwable $e) {
    journal('Enregistrement de la demande de devis impossible : ' . $e->getMessage());
    page_erreur("Le site n'arrive pas à enregistrer votre demande.");
}


/* ---------------------------------------------------------------
 *  6. LES DEUX E-MAILS
 * ------------------------------------------------------------- */

/*
 * Si un envoi échoue, la demande reste enregistrée et visible dans
 * l'espace de suivi : Camille la traitera quand même. On ne renvoie
 * donc pas la candidate sur une page d'erreur — sa demande est bien
 * arrivée, c'est le seul message qui compte pour elle.
 */

$demande = [
    'reference'         => $reference,
    'formation_nom'     => $formation['nom'],
    'duree'             => (string) ($formation['duree'] ?? ''),
    'nom'               => $nom,
    'email'             => $email,
    'telephone'         => $telephone,
    'periode_souhaitee' => $periode,
    'message'           => $message,
];

require_once __DIR__ . '/devis.php';

if (!envoyer_accuse_reception($demande)) {
    journal("Accusé de réception non envoyé pour $reference.");
}
if (!prevenir_institut_demande($demande)) {
    journal("Camille non prévenue de la demande $reference.");
}


/* ---------------------------------------------------------------
 *  7. RETOUR SUR LA PAGE
 * ------------------------------------------------------------- */

// Une redirection plutôt qu'un affichage direct : ainsi, si la
// candidate actualise la page, elle ne renvoie pas sa demande.
header('Location: /formations.php?recue=' . urlencode($reference) . '#devis', true, 303);
exit;
