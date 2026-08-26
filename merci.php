<?php
/**
 * ===============================================================
 *  PAGE DE RETOUR — le client revient de Stripe après avoir payé
 * ===============================================================
 *
 *  Stripe renvoie le client ici avec une référence de session.
 *  On redemande à Stripe si le paiement est bien passé, puis on
 *  finalise la carte (envoi des e-mails) et on remercie le client.
 *
 *  Cette page ne fait jamais confiance à l'adresse tapée dans le
 *  navigateur : c'est Stripe qui a le dernier mot.
 */

declare(strict_types=1);

require __DIR__ . '/carte.php';

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

$carte = null;

/* -- Cas normal : retour de Stripe --------------------------- */
$sessionId = nettoyer_ligne($_GET['session_id'] ?? '', 200);
if ($sessionId !== '') {
    $carte = carte_par_session($sessionId);
    if ($carte && !paiement_confirme($sessionId, $carte)) {
        journal("Retour sur merci.php sans paiement confirmé pour {$carte['code']}");
        page_erreur("Votre paiement n'a pas encore été confirmé par la banque.");
    }
}

/* -- Cas test : mode simulation uniquement ------------------- */
if ($carte === null && mode_simulation() && isset($_GET['simulation'])) {
    $carte = carte_par_code(nettoyer_ligne($_GET['code'] ?? '', 20));
}

if ($carte === null) {
    header('Location: /#carte-cadeau', true, 303);
    exit;
}

// Marque la carte comme payée et envoie les e-mails (une seule fois).
$carte = finaliser_carte($carte);

$montant   = h(montant_lisible((int) $carte['montant_cents']));
$pourNom   = h($carte['pour_nom']);
$pourEmail = h($carte['pour_email']);
$deEmail   = h($carte['de_email']);
$code      = h($carte['code']);
$expire    = h(date_lisible($carte['expire_le']));
$emailOk   = (int) $carte['email_envoye'] === 1;
$resa      = h((string) reglage('institut.reservation', ''));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Merci ! — L'atelier du cil à cil</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600&family=Poppins:wght@300;400;500&family=Playfair+Display:wght@500&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box;}
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
    background:#F7EDE9;color:#1A1416;font-family:'Poppins',sans-serif;font-weight:400;
    line-height:1.6;padding:34px 22px;-webkit-font-smoothing:antialiased;}
  .carte{background:#fff;border:1px solid #ecdcd8;border-radius:14px;
    padding:46px 40px;max-width:560px;width:100%;text-align:center;}
  .coche{width:60px;height:60px;border-radius:50%;background:#FF216E;color:#fff;
    display:flex;align-items:center;justify-content:center;font-size:30px;margin:0 auto 20px;}
  h1{font-family:'Playfair Display',serif;font-weight:500;font-size:29px;margin:0 0 12px;}
  .intro{color:#6f5f63;font-weight:300;margin:0 0 28px;}
  .recap{background:#F7EDE9;border-radius:12px;padding:24px;text-align:left;margin:0 0 26px;}
  .ligne{display:flex;justify-content:space-between;gap:16px;padding:7px 0;font-size:14px;}
  .ligne span:first-child{color:#6f5f63;font-weight:300;}
  .ligne span:last-child{font-weight:500;text-align:right;}
  .code{font-family:'Courier New',monospace;font-size:19px;font-weight:700;letter-spacing:2px;color:#FF216E;}
  .avis{font-size:13px;color:#6f5f63;font-weight:300;margin:0 0 26px;}
  .avis strong{color:#1A1416;font-weight:500;}
  .alerte{background:#FBEFF0;border:1px solid #FF216E;border-radius:12px;padding:16px 20px;
    font-size:13px;color:#1A1416;margin:0 0 26px;text-align:left;}
  .boutons{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
  .btn{display:inline-flex;align-items:center;padding:14px 26px;border-radius:50px;
    text-decoration:none;font-size:14px;font-weight:500;border:1.5px solid transparent;transition:.2s;}
  .btn-primary{background:#FF216E;color:#fff;}
  .btn-primary:hover{background:#D2135A;}
  .btn-ghost{border-color:#1A1416;color:#1A1416;}
  .btn-ghost:hover{border-color:#FF216E;color:#FF216E;}
  @media(max-width:520px){.carte{padding:34px 24px;}.ligne{flex-direction:column;gap:2px;}
    .ligne span:last-child{text-align:left;}}
</style>
</head>
<body>
  <main class="carte">
    <div class="coche" aria-hidden="true">&check;</div>
    <h1>Merci pour votre commande !</h1>
    <p class="intro">Votre paiement est bien enregistré.</p>

    <div class="recap">
      <div class="ligne"><span>Montant</span><span><?= $montant ?></span></div>
      <div class="ligne"><span>Destinataire</span><span><?= $pourNom ?></span></div>
      <div class="ligne"><span>Code de la carte</span><span class="code"><?= $code ?></span></div>
      <?php if ($expire !== ''): ?>
      <div class="ligne"><span>Valable jusqu'au</span><span><?= $expire ?></span></div>
      <?php endif; ?>
    </div>

    <?php if ($emailOk): ?>
      <p class="avis">
        La carte cadeau vient d'être envoyée à <strong><?= $pourEmail ?></strong>,
        et une copie vous attend sur <strong><?= $deEmail ?></strong>.<br>
        Si vous ne voyez rien d'ici quelques minutes, pensez au dossier
        « courrier indésirable ».
      </p>
    <?php else: ?>
      <div class="alerte">
        <strong>Votre paiement est bien reçu</strong>, mais l'envoi de l'e-mail
        n'a pas encore abouti. Nous nous en occupons&nbsp;: la carte partira très vite.
        Notez son code ci-dessus, il est déjà valable à l'institut.
      </div>
    <?php endif; ?>

    <div class="boutons">
      <?php if ($resa !== ''): ?>
        <a class="btn btn-primary" href="<?= $resa ?>">Prendre rendez-vous</a>
      <?php endif; ?>
      <a class="btn btn-ghost" href="/">Retour au site</a>
    </div>
  </main>
</body>
</html>
