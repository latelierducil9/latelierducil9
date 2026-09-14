<?php
/**
 * ===============================================================
 *  LES E-MAILS D'UNE CANDIDATURE À UNE FORMATION
 * ===============================================================
 *
 *  Deux envois, au moment où une candidate remplit le petit
 *  formulaire d'une fiche formation :
 *
 *    1. Un accusé de réception pour elle, avec sa référence
 *    2. Sa fiche pour Camille : formation, nom, e-mail, téléphone
 *
 *  C'est tout. Camille répond ensuite elle-même, depuis sa boîte
 *  mail : le site ne propose aucun tarif, n'envoie aucun devis, et
 *  il n'y a pas d'espace à consulter. Une candidature, pour elle,
 *  c'est un e-mail auquel elle répond.
 *
 *  Ce fichier ne s'affiche jamais seul : il est inclus par
 *  demande-formation.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/carte.php';   // pour enveloppe_email()

// Sécurité : ouvert directement dans un navigateur, ce fichier ne fait rien.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(404);
    exit;
}


/* ---------------------------------------------------------------
 *  ENVOI 1 : L'ACCUSÉ DE RÉCEPTION
 * ------------------------------------------------------------- */

function envoyer_accuse_reception(array $demande): bool
{
    $contenu = '<p style="margin:0 0 16px;">Bonjour ' . h($demande['prenom']) . ',</p>'
        . '<p style="margin:0 0 16px;">Votre candidature pour la formation '
        . '<strong>' . h($demande['formation_nom']) . '</strong> est bien arrivée.</p>'
        . '<p style="margin:0 0 20px;">Camille vous recontacte personnellement pour '
        . 'parler de votre projet, des dates possibles et du tarif adapté à votre '
        . 'situation.</p>'
        . bloc_reference($demande['reference'])
        . '<p style="margin:20px 0 0;font-size:14px;color:#6f5f63;">'
        . 'Une question d\'ici là ? Répondez simplement à cet e-mail.</p>';

    $texte = "Bonjour {$demande['prenom']},\n\n"
        . "Votre candidature pour la formation « {$demande['formation_nom']} » "
        . "est bien arrivée.\n\n"
        . "Camille vous recontacte personnellement pour parler de votre projet, "
        . "des dates possibles et du tarif adapté à votre situation.\n\n"
        . "Référence de votre candidature : {$demande['reference']}\n\n"
        . "Une question d'ici là ? Répondez simplement à cet e-mail.\n";

    return envoyer_email(
        $demande['email'],
        'Votre candidature est bien arrivée',
        enveloppe_email($contenu),
        $texte
    );
}


/* ---------------------------------------------------------------
 *  ENVOI 2 : LA FICHE DE LA CANDIDATE, POUR CAMILLE
 * ------------------------------------------------------------- */

function prevenir_institut_demande(array $demande): bool
{
    if (!est_rempli('resend.copie_institut')) {
        journal("Pas d'adresse institut : candidature {$demande['reference']} non signalée.");
        return false;
    }

    $destinataire = (string) reglage('resend.copie_institut');

    $lignes = [
        'Formation' => $demande['formation_nom'],
        'Nom'       => $demande['nom_complet'],
        'E-mail'    => $demande['email'],
        'Téléphone' => $demande['telephone'] !== '' ? $demande['telephone'] : 'non renseigné',
    ];

    $tableau = '';
    foreach ($lignes as $cle => $valeur) {
        $tableau .= '<tr>'
            . '<td style="padding:7px 14px 7px 0;color:#6f5f63;font-size:13px;white-space:nowrap;vertical-align:top;">'
            . h($cle) . '</td>'
            . '<td style="padding:7px 0;font-size:14px;font-weight:bold;">' . h($valeur) . '</td>'
            . '</tr>';
    }

    $contenu = '<p style="margin:0 0 6px;font-size:13px;letter-spacing:1.6px;'
        . 'text-transform:uppercase;color:#c9a24a;">Nouvelle candidature</p>'
        . '<p style="margin:0 0 20px;font-size:17px;font-weight:bold;">'
        . h($demande['nom_complet']) . ' souhaite se former</p>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;">'
        . $tableau . '</table>'
        . '<p style="margin:20px 0 0;font-size:14px;">Pour la recontacter, il suffit de '
        . 'cliquer sur « Répondre » : le message partira directement vers son adresse.</p>'
        . '<p style="margin:16px 0 0;font-size:13px;color:#6f5f63;">'
        . 'Référence ' . h($demande['reference']) . '</p>';

    $texte = "Nouvelle candidature\n\n";
    foreach ($lignes as $cle => $valeur) {
        $texte .= "$cle : $valeur\n";
    }
    $texte .= "\nPour la recontacter, cliquez sur « Répondre ».\n\n"
        . "Référence : {$demande['reference']}\n";

    return envoyer_email(
        $destinataire,
        'Nouvelle candidature : ' . $demande['nom_complet'],
        enveloppe_email($contenu),
        $texte
    );
}


/* ---------------------------------------------------------------
 *  PETIT MORCEAU PARTAGÉ
 * ------------------------------------------------------------- */

/** Le pavé beige qui rappelle la référence d'une candidature. */
function bloc_reference(string $reference): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" '
        . 'style="width:100%;background:#F7EDE9;border-radius:12px;"><tr>'
        . '<td align="center" style="padding:18px;">'
        . '<div style="font-size:11px;letter-spacing:1.8px;text-transform:uppercase;color:#6f5f63;">'
        . 'Référence de votre candidature</div>'
        . '<div style="font-family:\'Courier New\',monospace;font-size:19px;font-weight:bold;'
        . 'letter-spacing:2px;color:#1A1416;padding-top:7px;">' . h($reference) . '</div>'
        . '</td></tr></table>';
}
