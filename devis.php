<?php
/**
 * ===============================================================
 *  LES DEVIS DE FORMATION — composition et envoi
 * ===============================================================
 *
 *  Trois moments, trois e-mails :
 *
 *    1. À la réception de la demande
 *         → accusé de réception pour la candidate
 *         → alerte pour Camille
 *
 *    2. Quand Camille valide depuis son espace
 *         → le devis lui-même, avec son prix et sa durée de validité
 *
 *  ┌─────────────────────────────────────────────────────────────┐
 *  │  POURQUOI UN GARDE-FOU SUR LES MENTIONS LÉGALES             │
 *  │                                                             │
 *  │  En France, un devis engage celui qui l'émet et doit        │
 *  │  porter certaines mentions : identité, SIRET, régime de     │
 *  │  TVA, durée de validité. Un devis incomplet peut être       │
 *  │  contesté.                                                  │
 *  │                                                             │
 *  │  Tant que ces informations manquent dans config.php, le     │
 *  │  site REFUSE d'envoyer le devis et affiche à Camille ce     │
 *  │  qu'il reste à renseigner. Mieux vaut un bouton qui         │
 *  │  explique qu'un document bancal envoyé à une cliente.       │
 *  └─────────────────────────────────────────────────────────────┘
 *
 *  Ce fichier ne s'affiche jamais seul : il est inclus par
 *  demande-devis.php et par admin.php.
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

function devis_par_id(int $id): ?array
{
    $req = bdd()->prepare('SELECT * FROM devis WHERE id = ?');
    $req->execute([$id]);
    return $req->fetch() ?: null;
}


/* ---------------------------------------------------------------
 *  LE GARDE-FOU
 * ------------------------------------------------------------- */

/**
 * Qu'est-ce qui empêche encore d'envoyer un devis ?
 *
 * Renvoie la liste, en français, de ce qu'il reste à renseigner.
 * Une liste vide signifie que tout est prêt.
 */
function devis_manque_quoi(): array
{
    $manques = [];

    if (!est_rempli('institut.siret')) {
        $manques[] = "le numéro SIRET de l'institut";
    }
    if (!est_rempli('institut.mention_tva')) {
        $manques[] = "le régime de TVA (assujettie, ou mention d'exonération)";
    }
    if (!est_rempli('institut.adresse')) {
        $manques[] = "l'adresse de l'institut";
    }

    // Sans clé d'envoi, le devis serait marqué « envoyé » alors qu'aucun
    // e-mail ne partirait : Camille attendrait une réponse qui ne viendrait
    // jamais. On bloque donc — sauf sur un ordinateur de test, où l'absence
    // de clé est normale et où l'on veut pouvoir dérouler le parcours.
    if (!est_rempli('resend.api_key') && !site_local()) {
        $manques[] = "la clé d'envoi des e-mails";
    }

    return $manques;
}


/* ---------------------------------------------------------------
 *  ENVOI 1 — L'ACCUSÉ DE RÉCEPTION
 * ------------------------------------------------------------- */

function envoyer_accuse_reception(array $demande): bool
{
    $prenom = $demande['nom'];

    $contenu = '<p style="margin:0 0 16px;">Bonjour ' . h($prenom) . ',</p>'
        . '<p style="margin:0 0 16px;">Votre demande de devis pour la formation '
        . '<strong>' . h($demande['formation_nom']) . '</strong> est bien arrivée.</p>'
        . '<p style="margin:0 0 20px;">Camille étudie votre projet et vous adressera '
        . 'votre devis personnalisé, accompagné des dates possibles.</p>'
        . bloc_reference($demande['reference'])
        . '<p style="margin:20px 0 0;font-size:14px;color:#6f5f63;">'
        . 'Une question d\'ici là ? Répondez simplement à cet e-mail.</p>';

    $texte = "Bonjour {$prenom},\n\n"
        . "Votre demande de devis pour la formation « {$demande['formation_nom']} » est bien arrivée.\n\n"
        . "Camille étudie votre projet et vous adressera votre devis personnalisé, "
        . "accompagné des dates possibles.\n\n"
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
    $destinataire = (string) reglage('resend.copie_institut', '');
    if (!est_rempli('resend.copie_institut')) {
        journal("Pas d'adresse institut : demande {$demande['reference']} non signalée.");
        return false;
    }

    $siteUrl = rtrim((string) reglage('site_url', ''), '/');

    $lignes = [
        'Formation'        => $demande['formation_nom'],
        'Nom'              => $demande['nom'],
        'E-mail'           => $demande['email'],
        'Téléphone'        => $demande['telephone'] !== '' ? $demande['telephone'] : '—',
        'Période souhaitée' => $demande['periode_souhaitee'] !== '' ? $demande['periode_souhaitee'] : '—',
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
        . bouton_email($siteUrl . '/admin.php?vue=devis', 'Voir la demande')
        . '<p style="margin:0;font-size:13px;color:#6f5f63;text-align:center;">'
        . 'Référence ' . h($demande['reference']) . '</p>';

    $texte = "Nouvelle demande de formation\n\n";
    foreach ($lignes as $cle => $valeur) {
        $texte .= "$cle : $valeur\n";
    }
    if (trim((string) $demande['message']) !== '') {
        $texte .= "\nSon projet :\n" . $demande['message'] . "\n";
    }
    $texte .= "\nRéférence : {$demande['reference']}\n"
        . "À traiter ici : {$siteUrl}/admin.php?vue=devis\n";

    return envoyer_email(
        $destinataire,
        'Demande de formation — ' . $demande['nom'],
        enveloppe_email($contenu),
        $texte
    );
}


/* ---------------------------------------------------------------
 *  ENVOI 3 — LE DEVIS, APRÈS VALIDATION DE CAMILLE
 * ------------------------------------------------------------- */

/**
 * Envoie le devis à la candidate et note la date d'envoi.
 *
 * Renvoie un message d'erreur en français, ou une chaîne vide si
 * tout s'est bien passé.
 */
function envoyer_devis(array $ligne): string
{
    $manques = devis_manque_quoi();
    if ($manques !== []) {
        return "Le devis ne peut pas être envoyé tant qu'il manque : "
            . implode(', ', $manques) . '.';
    }

    $validiteJours = max(1, (int) reglage('devis.validite_jours', 30));
    $expire        = gmdate('Y-m-d', strtotime("+{$validiteJours} days") ?: time());

    if (!envoyer_email(
        (string) $ligne['email'],
        'Votre devis de formation — ' . $ligne['formation_nom'],
        enveloppe_email(devis_html($ligne, $expire, $validiteJours)),
        devis_texte($ligne, $expire, $validiteJours)
    )) {
        return "L'envoi a échoué. Réessayez dans un instant.";
    }

    bdd()->prepare(
        "UPDATE devis SET statut = 'devis_envoye', devis_envoye_le = ? WHERE id = ?"
    )->execute([gmdate('Y-m-d H:i:s'), (int) $ligne['id']]);

    return '';
}

function devis_html(array $ligne, string $expire, int $validiteJours): string
{
    $institut = h((string) reglage('institut.nom', "L'atelier du cil à cil"));
    $adresse  = h((string) reglage('institut.adresse', ''));
    $siret    = h((string) reglage('institut.siret', ''));
    $tva      = h((string) reglage('institut.mention_tva', ''));
    $tel      = h((string) reglage('institut.telephone', ''));

    $prix = h(montant_lisible((int) $ligne['prix_propose_cents']));

    return '<p style="margin:0 0 6px;font-size:13px;letter-spacing:1.6px;'
        . 'text-transform:uppercase;color:#c9a24a;">Devis n° ' . h($ligne['reference']) . '</p>'
        . '<p style="margin:0 0 20px;">Bonjour ' . h($ligne['nom']) . ',</p>'
        . '<p style="margin:0 0 22px;">Voici votre devis pour la formation que vous avez '
        . 'demandée. Il vous est proposé sans engagement.</p>'

        // Le détail chiffré
        . '<table role="presentation" cellpadding="0" cellspacing="0" '
        . 'style="width:100%;border:1px solid #ecdcd8;border-radius:12px;border-collapse:separate;">'
        . '<tr><td style="padding:18px 20px;border-bottom:1px solid #ecdcd8;">'
        . '<div style="font-size:16px;font-weight:bold;">' . h($ligne['formation_nom']) . '</div>'
        . '<div style="font-size:13px;color:#6f5f63;padding-top:4px;">Kit de démarrage compris</div>'
        . '</td></tr>'
        . '<tr><td style="padding:18px 20px;">'
        . '<table role="presentation" width="100%"><tr>'
        . '<td style="font-size:14px;">Total</td>'
        . '<td align="right" style="font-family:Georgia,serif;font-size:24px;color:#FF216E;">'
        . $prix . '</td>'
        . '</tr></table>'
        . '<div style="font-size:12px;color:#6f5f63;padding-top:6px;">' . $tva . '</div>'
        . '</td></tr></table>'

        . '<p style="margin:22px 0 0;font-size:14px;">'
        . 'Ce devis est valable <strong>' . $validiteJours . ' jours</strong>, '
        . 'soit jusqu\'au <strong>' . h(date_lisible($expire)) . '</strong>.</p>'

        . '<p style="margin:16px 0 0;font-size:14px;">Pour l\'accepter, répondez simplement '
        . 'à cet e-mail en indiquant « bon pour accord ». Camille vous proposera alors '
        . 'les dates disponibles.</p>'

        // Mentions de l'émetteur
        . '<p style="margin:26px 0 0;padding-top:18px;border-top:1px solid #ecdcd8;'
        . 'font-size:12px;color:#6f5f63;line-height:1.7;">'
        . '<strong>' . $institut . '</strong><br>'
        . $adresse . '<br>'
        . ($tel !== '' ? 'Téléphone : ' . $tel . '<br>' : '')
        . 'SIRET ' . $siret
        . '</p>';
}

function devis_texte(array $ligne, string $expire, int $validiteJours): string
{
    $prix = montant_lisible((int) $ligne['prix_propose_cents']);

    return "DEVIS N° {$ligne['reference']}\n\n"
        . "Bonjour {$ligne['nom']},\n\n"
        . "Voici votre devis pour la formation que vous avez demandée. "
        . "Il vous est proposé sans engagement.\n\n"
        . "Formation : {$ligne['formation_nom']}\n"
        . "Kit de démarrage compris\n"
        . "Total : {$prix}\n"
        . reglage('institut.mention_tva', '') . "\n\n"
        . "Ce devis est valable {$validiteJours} jours, soit jusqu'au "
        . date_lisible($expire) . ".\n\n"
        . "Pour l'accepter, répondez simplement à cet e-mail en indiquant "
        . "« bon pour accord ». Camille vous proposera alors les dates disponibles.\n\n"
        . "---\n"
        . reglage('institut.nom', "L'atelier du cil à cil") . "\n"
        . reglage('institut.adresse', '') . "\n"
        . 'SIRET ' . reglage('institut.siret', '') . "\n";
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
