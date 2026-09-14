<?php
/**
 * ===============================================================
 *  LES E-MAILS D'UNE DEMANDE DE FORMATION
 * ===============================================================
 *
 *  Deux envois, au moment où une candidate remplit le formulaire :
 *
 *    1. Un accusé de réception pour elle, avec sa référence
 *    2. Une alerte pour Camille, avec toutes les coordonnées
 *
 *  C'est tout. Camille répond ensuite elle-même, par e-mail ou par
 *  téléphone : le site ne propose aucun tarif et n'envoie aucun
 *  devis. C'est son choix, et il tient : une formation se discute
 *  au cas par cas.
 *
 *  Ce fichier ne s'affiche jamais seul : il est inclus par
 *  demande-formation.php et par admin.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/carte.php';   // pour enveloppe_email() et bouton_email()

// Sécurité : ouvert directement dans un navigateur, ce fichier ne fait rien.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(404);
    exit;
}


/* ---------------------------------------------------------------
 *  LECTURE
 * ------------------------------------------------------------- */

function demande_formation_par_id(int $id): ?array
{
    $req = bdd()->prepare('SELECT * FROM demandes_formation WHERE id = ?');
    $req->execute([$id]);
    return $req->fetch() ?: null;
}


/* ---------------------------------------------------------------
 *  ENVOI 1 — L'ACCUSÉ DE RÉCEPTION
 * ------------------------------------------------------------- */

function envoyer_accuse_reception(array $demande): bool
{
    $contenu = '<p style="margin:0 0 16px;">Bonjour ' . h($demande['nom']) . ',</p>'
        . '<p style="margin:0 0 16px;">Votre demande concernant la formation '
        . '<strong>' . h($demande['formation_nom']) . '</strong> est bien arrivée.</p>'
        . '<p style="margin:0 0 20px;">Camille vous recontacte personnellement pour '
        . 'parler de votre projet, des dates possibles et du tarif adapté à votre '
        . 'situation.</p>'
        . bloc_reference($demande['reference'])
        . '<p style="margin:20px 0 0;font-size:14px;color:#6f5f63;">'
        . 'Une question d\'ici là ? Répondez simplement à cet e-mail.</p>';

    $texte = "Bonjour {$demande['nom']},\n\n"
        . "Votre demande concernant la formation « {$demande['formation_nom']} » "
        . "est bien arrivée.\n\n"
        . "Camille vous recontacte personnellement pour parler de votre projet, "
        . "des dates possibles et du tarif adapté à votre situation.\n\n"
        . "Référence de votre demande : {$demande['reference']}\n\n"
        . "Une question d'ici là ? Répondez simplement à cet e-mail.\n";

    return envoyer_email(
        $demande['email'],
        'Votre demande de formation est bien arrivée',
        enveloppe_email($contenu),
        $texte
    );
}


/* ---------------------------------------------------------------
 *  ENVOI 2 — L'ALERTE POUR CAMILLE
 * ------------------------------------------------------------- */

function prevenir_institut_demande(array $demande): bool
{
    if (!est_rempli('resend.copie_institut')) {
        journal("Pas d'adresse institut : demande {$demande['reference']} non signalée.");
        return false;
    }

    $destinataire = (string) reglage('resend.copie_institut');
    $siteUrl      = rtrim((string) reglage('site_url', ''), '/');

    $lignes = [
        'Formation'         => $demande['formation_nom'],
        'Nom'               => $demande['nom'],
        'E-mail'            => $demande['email'],
        'Téléphone'         => $demande['telephone'] !== '' ? $demande['telephone'] : 'non renseigné',
        'Période souhaitée' => $demande['periode_souhaitee'] !== '' ? $demande['periode_souhaitee'] : 'non précisée',
    ];

    $tableau = '';
    foreach ($lignes as $cle => $valeur) {
        $tableau .= '<tr>'
            . '<td style="padding:7px 14px 7px 0;color:#6f5f63;font-size:13px;white-space:nowrap;vertical-align:top;">'
            . h($cle) . '</td>'
            . '<td style="padding:7px 0;font-size:14px;font-weight:bold;">' . h($valeur) . '</td>'
            . '</tr>';
    }

    $motProjet = trim((string) $demande['message']) !== ''
        ? '<p style="margin:18px 0 0;padding:14px 16px;background:#F7EDE9;border-radius:10px;'
          . 'font-size:14px;line-height:1.6;">' . nl2br(h($demande['message'])) . '</p>'
        : '';

    $contenu = '<p style="margin:0 0 6px;font-size:13px;letter-spacing:1.6px;'
        . 'text-transform:uppercase;color:#c9a24a;">Nouvelle demande</p>'
        . '<p style="margin:0 0 20px;font-size:17px;font-weight:bold;">'
        . h($demande['nom']) . ' souhaite se former</p>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;">'
        . $tableau . '</table>'
        . $motProjet
        . '<p style="margin:20px 0 0;font-size:14px;">Pour lui répondre, il suffit de '
        . 'cliquer sur « Répondre » : le message partira directement vers son adresse.</p>'
        . bouton_email($siteUrl . '/admin.php?vue=formations', 'Voir toutes les demandes')
        . '<p style="margin:0;font-size:13px;color:#6f5f63;text-align:center;">'
        . 'Référence ' . h($demande['reference']) . '</p>';

    $texte = "Nouvelle demande de formation\n\n";
    foreach ($lignes as $cle => $valeur) {
        $texte .= "$cle : $valeur\n";
    }
    if (trim((string) $demande['message']) !== '') {
        $texte .= "\nSon projet :\n" . $demande['message'] . "\n";
    }
    $texte .= "\nPour lui répondre, cliquez sur « Répondre ».\n\n"
        . "Référence : {$demande['reference']}\n"
        . "Toutes les demandes : {$siteUrl}/admin.php?vue=formations\n";

    return envoyer_email(
        $destinataire,
        'Demande de formation : ' . $demande['nom'],
        enveloppe_email($contenu),
        $texte
    );
}


/* ---------------------------------------------------------------
 *  PETIT MORCEAU PARTAGÉ
 * ------------------------------------------------------------- */

/** Le pavé beige qui rappelle la référence d'une demande. */
function bloc_reference(string $reference): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" '
        . 'style="width:100%;background:#F7EDE9;border-radius:12px;"><tr>'
        . '<td align="center" style="padding:18px;">'
        . '<div style="font-size:11px;letter-spacing:1.8px;text-transform:uppercase;color:#6f5f63;">'
        . 'Référence de votre demande</div>'
        . '<div style="font-family:\'Courier New\',monospace;font-size:19px;font-weight:bold;'
        . 'letter-spacing:2px;color:#1A1416;padding-top:7px;">' . h($reference) . '</div>'
        . '</td></tr></table>';
}
