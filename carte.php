<?php
/**
 * ===============================================================
 *  FINALISATION D'UNE CARTE CADEAU PAYÉE + E-MAILS
 * ===============================================================
 *
 *  Ce fichier contient le « après-paiement », appelé depuis DEUX
 *  endroits différents :
 *
 *    - merci.php   quand le client revient de la page Stripe ;
 *    - webhook.php quand Stripe prévient le serveur directement.
 *
 *  Les deux peuvent arriver en même temps, ou l'un sans l'autre.
 *  Tout est donc écrit pour pouvoir être exécuté plusieurs fois
 *  sans jamais envoyer l'e-mail en double.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib.php';


/* ---------------------------------------------------------------
 *  1. RETROUVER UNE CARTE
 * ------------------------------------------------------------- */

function carte_par_session(string $sessionId): ?array
{
    $req = bdd()->prepare('SELECT * FROM cartes WHERE stripe_session_id = ?');
    $req->execute([$sessionId]);
    return $req->fetch() ?: null;
}

function carte_par_code(string $code): ?array
{
    $req = bdd()->prepare('SELECT * FROM cartes WHERE code = ?');
    $req->execute([$code]);
    return $req->fetch() ?: null;
}

function carte_par_id(int $id): ?array
{
    $req = bdd()->prepare('SELECT * FROM cartes WHERE id = ?');
    $req->execute([$id]);
    return $req->fetch() ?: null;
}


/* ---------------------------------------------------------------
 *  2. VÉRIFIER AUPRÈS DE STRIPE QUE C'EST VRAIMENT PAYÉ
 * ------------------------------------------------------------- */

/**
 * On ne croit jamais le navigateur sur parole : on redemande à
 * Stripe lui-même si la session a bien été réglée, et on compare
 * le montant encaissé à celui qu'on avait enregistré.
 */
function paiement_confirme(string $sessionId, array $carte): bool
{
    if (mode_simulation()) {
        return true;
    }

    [$httpCode, $session] = stripe('checkout/sessions/' . urlencode($sessionId));

    if ($httpCode !== 200) {
        journal("Stripe injoignable pour la session $sessionId (HTTP $httpCode)");
        return false;
    }

    if (($session['payment_status'] ?? '') !== 'paid') {
        return false;
    }

    // Sécurité : le montant encaissé doit correspondre à la commande.
    $encaisse = (int) ($session['amount_total'] ?? 0);
    if ($encaisse !== (int) $carte['montant_cents']) {
        journal(
            "ALERTE montant : carte {$carte['code']} attendait {$carte['montant_cents']} "
            . "centimes, Stripe a encaissé $encaisse"
        );
        return false;
    }

    return true;
}


/* ---------------------------------------------------------------
 *  3. FINALISER : marquer payée puis envoyer les e-mails
 * ------------------------------------------------------------- */

/**
 * Renvoie la carte à jour. Peut être appelée autant de fois qu'on
 * veut : les e-mails ne partiront qu'une seule fois.
 */
function finaliser_carte(array $carte): array
{
    $pdo = bdd();
    $id  = (int) $carte['id'];

    /* -- a. passage en « payée » -------------------------------- */
    if ($carte['statut'] === 'en_attente') {
        $moisValidite = (int) reglage('carte_cadeau.validite_mois', 12);
        $expire       = date('Y-m-d', strtotime("+{$moisValidite} months"));

        $req = $pdo->prepare(
            "UPDATE cartes
                SET statut = 'payee', paye_le = ?, expire_le = ?, restant_cents = montant_cents
              WHERE id = ? AND statut = 'en_attente'"
        );
        $req->execute([gmdate('Y-m-d H:i:s'), $expire, $id]);

        $carte = carte_par_id($id) ?? $carte;
    }

    /* -- b. envoi des e-mails ----------------------------------- *
     * On « réserve » l'envoi en base AVANT d'envoyer. Si merci.php
     * et webhook.php se déclenchent en même temps, un seul des deux
     * obtient la réservation : impossible d'envoyer deux fois.     */
    $req = $pdo->prepare('UPDATE cartes SET email_envoye = 1 WHERE id = ? AND email_envoye = 0');
    $req->execute([$id]);

    if ($req->rowCount() === 1) {
        if (!envoyer_emails_carte($carte)) {
            // Échec : on relâche la réservation pour pouvoir réessayer
            // (depuis l'espace admin, ou au prochain passage).
            $pdo->prepare('UPDATE cartes SET email_envoye = 0 WHERE id = ?')->execute([$id]);
        }
        $carte = carte_par_id($id) ?? $carte;
    }

    return $carte;
}

/**
 * Envoie les e-mails d'une carte payée :
 *   1. la carte cadeau au destinataire (indispensable) ;
 *   2. la confirmation à l'acheteur ;
 *   3. l'avis de vente à l'institut.
 * Ne renvoie true que si le destinataire a bien été servi.
 */
function envoyer_emails_carte(array $carte): bool
{
    $institut = (string) reglage('institut.nom', "L'atelier du cil à cil");

    // 1. Le destinataire — c'est celui qui compte.
    $ok = envoyer_email(
        $carte['pour_email'],
        $carte['de_nom'] . " vous offre une carte cadeau — " . $institut,
        email_carte_html($carte),
        email_carte_texte($carte)
    );

    // 2. L'acheteur.
    envoyer_email(
        $carte['de_email'],
        'Votre carte cadeau a bien été envoyée — ' . $institut,
        email_acheteur_html($carte),
        email_acheteur_texte($carte)
    );

    // 3. L'institut.
    if (est_rempli('resend.copie_institut')) {
        envoyer_email(
            (string) reglage('resend.copie_institut'),
            'Nouvelle carte cadeau vendue : ' . montant_lisible((int) $carte['montant_cents']),
            email_institut_html($carte),
            email_institut_texte($carte)
        );
    }

    if (!$ok) {
        journal("E-mail carte {$carte['code']} NON remis à {$carte['pour_email']}");
    }

    return $ok;
}


/**
 * Renvoie la carte au destinataire, depuis l'espace de suivi.
 * On ne renvoie que l'e-mail du destinataire : inutile de déranger
 * à nouveau l'acheteur et l'institut.
 */
function renvoyer_carte(array $carte): bool
{
    $ok = envoyer_email(
        $carte['pour_email'],
        $carte['de_nom'] . ' vous offre une carte cadeau — '
            . reglage('institut.nom', "L'atelier du cil à cil"),
        email_carte_html($carte),
        email_carte_texte($carte)
    );

    if ($ok) {
        bdd()->prepare('UPDATE cartes SET email_envoye = 1 WHERE id = ?')
             ->execute([(int) $carte['id']]);
    } else {
        journal("Renvoi manuel de la carte {$carte['code']} en échec.");
    }

    return $ok;
}


/* ---------------------------------------------------------------
 *  4. LES E-MAILS
 * ------------------------------------------------------------- *
 *  Les logiciels de messagerie (Gmail, Outlook…) ne savent pas
 *  lire les mises en page modernes ni les polices Google. On écrit
 *  donc ces e-mails « à l'ancienne » : des tableaux et des styles
 *  posés sur chaque ligne. C'est le seul moyen d'obtenir le même
 *  rendu partout.
 */

function enveloppe_email(string $contenu): string
{
    $institut  = h((string) reglage('institut.nom', "L'atelier du cil à cil"));
    $adresse   = h((string) reglage('institut.adresse', ''));
    $instagram = h((string) reglage('institut.instagram', ''));

    $lienInsta = $instagram !== ''
        ? '<a href="' . $instagram . '" style="color:#6f5f63;text-decoration:underline;">Instagram</a>'
        : '';

    return <<<HTML
    <!DOCTYPE html>
    <html lang="fr"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1"></head>
    <body style="margin:0;padding:0;background:#F7EDE9;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7EDE9;padding:28px 12px;">
      <tr><td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #ecdcd8;border-radius:14px;overflow:hidden;">

          <tr><td align="center" style="padding:32px 30px 8px;">
            <div style="font-family:Georgia,'Times New Roman',serif;font-style:italic;font-size:25px;color:#FF216E;">
              $institut
            </div>
            <div style="font-family:Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:2.4px;text-transform:uppercase;color:#c9a24a;padding-top:7px;">
              Cils &middot; Sourcils &middot; Beauté du regard
            </div>
          </td></tr>

          <tr><td style="padding:16px 30px 34px;font-family:Helvetica,Arial,sans-serif;color:#1A1416;font-size:15px;line-height:1.62;">
            $contenu
          </td></tr>

          <tr><td style="background:#FBEFF0;padding:20px 30px;font-family:Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;color:#6f5f63;" align="center">
            $adresse<br>$lienInsta
          </td></tr>

        </table>
      </td></tr>
    </table>
    </body></html>
    HTML;
}

/** Le bouton rose « Prendre rendez-vous ». */
function bouton_email(string $lien, string $libelle): string
{
    if ($lien === '') {
        return '';
    }
    return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px auto;"><tr>'
        . '<td style="background:#FF216E;border-radius:50px;">'
        . '<a href="' . h($lien) . '" style="display:inline-block;padding:14px 32px;'
        . 'font-family:Helvetica,Arial,sans-serif;font-size:14px;font-weight:bold;'
        . 'color:#ffffff;text-decoration:none;">' . h($libelle) . '</a>'
        . '</td></tr></table>';
}


/* ---- E-mail 1 : la carte cadeau, pour le destinataire --------- */

function email_carte_html(array $carte): string
{
    $pour     = h($carte['pour_nom']);
    $de       = h($carte['de_nom']);
    $montant  = h(montant_lisible((int) $carte['montant_cents']));
    $code     = h($carte['code']);
    $expire   = h(date_lisible($carte['expire_le']));
    $resa     = (string) reglage('institut.reservation', '');

    $motPerso = '';
    if (trim((string) $carte['message']) !== '') {
        $motPerso =
            '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">'
            . '<tr><td style="border-left:3px solid #FF216E;padding:4px 0 4px 16px;'
            . 'font-family:Georgia,\'Times New Roman\',serif;font-style:italic;font-size:15px;color:#6f5f63;line-height:1.6;">'
            . nl2br(h($carte['message']))
            . '<div style="font-family:Helvetica,Arial,sans-serif;font-style:normal;font-size:12px;color:#6f5f63;padding-top:9px;">— ' . $de . '</div>'
            . '</td></tr></table>';
    }

    $contenu = <<<HTML
        <p style="margin:0 0 6px;">Bonjour $pour,</p>
        <p style="margin:0 0 4px;"><strong>$de</strong> vous offre un moment rien que pour vous.</p>

        $motPerso

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               style="margin:26px 0;background:#F7EDE9;border:2px dashed #FF216E;border-radius:14px;">
          <tr><td align="center" style="padding:30px 20px;">
            <div style="font-family:Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:2.4px;text-transform:uppercase;color:#6f5f63;">
              Carte cadeau
            </div>
            <div style="font-family:Georgia,'Times New Roman',serif;font-size:46px;color:#FF216E;padding:10px 0 4px;">
              $montant
            </div>
            <div style="font-family:Helvetica,Arial,sans-serif;font-size:12px;color:#6f5f63;padding-bottom:16px;">
              à valoir sur la prestation de votre choix
            </div>
            <div style="font-family:Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:1.6px;text-transform:uppercase;color:#6f5f63;">
              Code à présenter
            </div>
            <div style="font-family:'Courier New',Courier,monospace;font-size:23px;font-weight:bold;letter-spacing:3px;color:#1A1416;padding-top:7px;">
              $code
            </div>
          </td></tr>
        </table>

        <p style="margin:0 0 4px;">Pour en profiter, réservez votre rendez-vous et indiquez simplement ce code à votre arrivée.</p>

        HTML
        . bouton_email($resa, 'Prendre rendez-vous')
        . <<<HTML
        <p style="margin:22px 0 0;font-size:13px;color:#6f5f63;">
          Carte valable jusqu'au <strong>$expire</strong>. Utilisable en une ou plusieurs fois.
          Si le montant de votre prestation est inférieur, le solde reste disponible.
        </p>
        HTML;

    return enveloppe_email($contenu);
}

function email_carte_texte(array $carte): string
{
    $montant = montant_lisible((int) $carte['montant_cents']);
    $mot     = trim((string) $carte['message']) !== ''
        ? "\n\"" . $carte['message'] . "\"\n— " . $carte['de_nom'] . "\n"
        : '';

    return "Bonjour {$carte['pour_nom']},\n\n"
        . "{$carte['de_nom']} vous offre une carte cadeau de {$montant} "
        . "chez " . reglage('institut.nom') . ".\n"
        . $mot
        . "\nVotre code : {$carte['code']}\n"
        . "Valable jusqu'au " . date_lisible($carte['expire_le']) . ".\n\n"
        . "Prendre rendez-vous : " . reglage('institut.reservation') . "\n"
        . reglage('institut.adresse') . "\n";
}


/* ---- E-mail 2 : la confirmation, pour l'acheteur -------------- */

function email_acheteur_html(array $carte): string
{
    $de      = h($carte['de_nom']);
    $pour    = h($carte['pour_nom']);
    $email   = h($carte['pour_email']);
    $montant = h(montant_lisible((int) $carte['montant_cents']));
    $code    = h($carte['code']);
    $expire  = h(date_lisible($carte['expire_le']));

    $contenu = <<<HTML
        <p style="margin:0 0 6px;">Bonjour $de,</p>
        <p style="margin:0 0 18px;">Votre paiement est bien reçu, merci ! La carte cadeau vient
        d'être envoyée à <strong>$pour</strong> ($email).</p>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               style="background:#F7EDE9;border:1px solid #ecdcd8;border-radius:12px;">
          <tr><td style="padding:20px 24px;font-family:Helvetica,Arial,sans-serif;font-size:14px;color:#1A1416;line-height:1.9;">
            Montant : <strong>$montant</strong><br>
            Code de la carte : <strong style="font-family:'Courier New',Courier,monospace;letter-spacing:1.6px;">$code</strong><br>
            Valable jusqu'au : <strong>$expire</strong>
          </td></tr>
        </table>

        <p style="margin:20px 0 0;font-size:13px;color:#6f5f63;">
          Conservez ce message : si $pour ne trouve pas son e-mail, ce code suffit
          pour utiliser la carte à l'institut. Pensez à faire vérifier le dossier
          « courrier indésirable ».
        </p>
        <p style="margin:14px 0 0;font-size:13px;color:#6f5f63;">
          Une question ? Répondez simplement à cet e-mail.
        </p>
        HTML;

    return enveloppe_email($contenu);
}

function email_acheteur_texte(array $carte): string
{
    return "Bonjour {$carte['de_nom']},\n\n"
        . "Votre paiement est bien reçu, merci !\n"
        . "La carte cadeau a été envoyée à {$carte['pour_nom']} ({$carte['pour_email']}).\n\n"
        . "Montant : " . montant_lisible((int) $carte['montant_cents']) . "\n"
        . "Code : {$carte['code']}\n"
        . "Valable jusqu'au " . date_lisible($carte['expire_le']) . "\n\n"
        . "Conservez ce message. Une question ? Répondez à cet e-mail.\n";
}


/* ---- E-mail 3 : l'avis de vente, pour l'institut -------------- */

function email_institut_html(array $carte): string
{
    $montant = h(montant_lisible((int) $carte['montant_cents']));
    $code    = h($carte['code']);
    $de      = h($carte['de_nom'] . ' (' . $carte['de_email'] . ')');
    $pour    = h($carte['pour_nom'] . ' (' . $carte['pour_email'] . ')');
    $expire  = h(date_lisible($carte['expire_le']));

    $contenu = <<<HTML
        <p style="margin:0 0 16px;"><strong>Une carte cadeau vient d'être vendue.</strong></p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               style="background:#F7EDE9;border:1px solid #ecdcd8;border-radius:12px;">
          <tr><td style="padding:20px 24px;font-family:Helvetica,Arial,sans-serif;font-size:14px;color:#1A1416;line-height:1.9;">
            Montant : <strong>$montant</strong><br>
            Code : <strong style="font-family:'Courier New',Courier,monospace;letter-spacing:1.6px;">$code</strong><br>
            Achetée par : $de<br>
            Pour : $pour<br>
            Valable jusqu'au : $expire
          </td></tr>
        </table>
        <p style="margin:18px 0 0;font-size:13px;color:#6f5f63;">
          La carte apparaît dans l'espace de suivi, où elle pourra être marquée
          « utilisée » le jour du rendez-vous.
        </p>
        HTML;

    return enveloppe_email($contenu);
}

function email_institut_texte(array $carte): string
{
    return "Nouvelle carte cadeau vendue.\n\n"
        . "Montant : " . montant_lisible((int) $carte['montant_cents']) . "\n"
        . "Code : {$carte['code']}\n"
        . "Achetée par : {$carte['de_nom']} ({$carte['de_email']})\n"
        . "Pour : {$carte['pour_nom']} ({$carte['pour_email']})\n"
        . "Valable jusqu'au " . date_lisible($carte['expire_le']) . "\n";
}
