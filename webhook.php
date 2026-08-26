<?php
/**
 * ===============================================================
 *  WEBHOOK STRIPE — le filet de sécurité
 * ===============================================================
 *
 *  Le client qui paie puis ferme son onglet trop vite ne repasse
 *  jamais par merci.php : sans ce fichier, sa carte cadeau ne
 *  partirait pas alors qu'il a payé.
 *
 *  Stripe prévient donc directement le serveur, de machine à
 *  machine, dès qu'un paiement aboutit. Ce fichier écoute ce
 *  message et envoie la carte, même si plus personne n'est devant
 *  son écran.
 *
 *  Ce n'est pas une page à visiter : elle ne s'adresse qu'à Stripe.
 *
 *  Point de sécurité : n'importe qui peut envoyer un faux message
 *  « c'est payé ! » à cette adresse pour se faire offrir une carte.
 *  On vérifie donc la SIGNATURE de chaque message avec le secret
 *  partagé avec Stripe. Sans signature valable, on refuse.
 */

declare(strict_types=1);

require __DIR__ . '/carte.php';

header('Content-Type: text/plain; charset=UTF-8');

/** Répond à Stripe et arrête le script. */
function repondre(int $code, string $message): never
{
    http_response_code($code);
    echo $message;
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    repondre(405, 'Methode non autorisee');
}

$charge    = file_get_contents('php://input') ?: '';
$signature = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
$secret    = (string) reglage('stripe.webhook_secret', '');

if (!est_rempli('stripe.webhook_secret')) {
    journal('Webhook reçu alors que le secret de signature n\'est pas configuré.');
    repondre(503, 'Webhook non configure');
}


/* ---------------------------------------------------------------
 *  1. VÉRIFICATION DE LA SIGNATURE
 * ------------------------------------------------------------- */

/**
 * Stripe envoie un en-tête du type :  t=1712345678,v1=abc123...
 * On recalcule la signature de notre côté et on compare.
 */
function signature_valable(string $charge, string $entete, string $secret): bool
{
    $horodatage = null;
    $signatures = [];

    foreach (explode(',', $entete) as $morceau) {
        $paire = explode('=', trim($morceau), 2);
        if (count($paire) !== 2) {
            continue;
        }
        if ($paire[0] === 't') {
            $horodatage = $paire[1];
        } elseif ($paire[0] === 'v1') {
            $signatures[] = $paire[1];
        }
    }

    if ($horodatage === null || $signatures === []) {
        return false;
    }

    // Refuse un message trop vieux : empêche de rejouer un ancien
    // message valable intercepté par quelqu'un.
    if (abs(time() - (int) $horodatage) > 300) {
        journal('Webhook refusé : message trop ancien.');
        return false;
    }

    $attendue = hash_hmac('sha256', $horodatage . '.' . $charge, $secret);

    foreach ($signatures as $fournie) {
        // hash_equals compare sans laisser deviner la bonne valeur.
        if (hash_equals($attendue, $fournie)) {
            return true;
        }
    }

    return false;
}

if (!signature_valable($charge, $signature, $secret)) {
    journal('Webhook refusé : signature invalide (IP ' . ip_visiteur() . ').');
    repondre(400, 'Signature invalide');
}


/* ---------------------------------------------------------------
 *  2. TRAITEMENT DE L'ÉVÉNEMENT
 * ------------------------------------------------------------- */

$evenement = json_decode($charge, true);
if (!is_array($evenement)) {
    repondre(400, 'Message illisible');
}

$type = (string) ($evenement['type'] ?? '');

// Seuls ces deux événements nous intéressent : le paiement immédiat,
// et le paiement différé qui finit par aboutir.
$typesSuivis = ['checkout.session.completed', 'checkout.session.async_payment_succeeded'];

if (!in_array($type, $typesSuivis, true)) {
    repondre(200, 'Evenement ignore');
}

$session   = $evenement['data']['object'] ?? [];
$sessionId = (string) ($session['id'] ?? '');

if ($sessionId === '') {
    repondre(200, 'Session absente');
}

$carte = carte_par_session($sessionId);
if ($carte === null) {
    journal("Webhook : aucune carte ne correspond à la session $sessionId");
    repondre(200, 'Carte inconnue');
}

// On revérifie le paiement auprès de Stripe plutôt que de croire
// le contenu du message sur parole.
if (!paiement_confirme($sessionId, $carte)) {
    journal("Webhook : paiement non confirmé pour {$carte['code']}");
    repondre(200, 'Paiement non confirme');
}

$carte = finaliser_carte($carte);

if ((int) $carte['email_envoye'] !== 1) {
    // On renvoie une erreur pour que Stripe retente l'envoi plus tard.
    journal("Webhook : e-mail non parti pour {$carte['code']}, Stripe réessaiera.");
    repondre(500, 'Email non envoye');
}

repondre(200, 'OK');
