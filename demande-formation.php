<?php
/**
 * ===============================================================
 *  RÉCEPTION D'UNE CANDIDATURE À UNE FORMATION
 * ===============================================================
 *
 *  Reçoit le petit formulaire d'une fiche formation, vérifie tout
 *  côté serveur, enregistre la candidature, puis :
 *    - envoie un accusé de réception à la candidate
 *    - prévient Camille qu'une candidature l'attend
 *
 *  Le site ne propose aucun tarif et n'envoie aucun devis. Camille
 *  reçoit l'e-mail et répond elle-même : c'est elle qui s'engage sur
 *  un prix et sur des dates, pas le site.
 */

declare(strict_types=1);

require __DIR__ . '/lib.php';

header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

// Une visite directe, sans passer par le formulaire, revient à la liste.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /formations.php', true, 303);
    exit;
}


/* ---------------------------------------------------------------
 *  1. LE PIÈGE À ROBOTS
 * ------------------------------------------------------------- */

// Ce champ est invisible sur la page : seule une machine le remplit.
// On fait semblant d'accepter, sans rien enregistrer ni envoyer : un
// robot à qui on annonce une erreur réessaie, un robot satisfait s'en va.
if (nettoyer_ligne($_POST['site_web'] ?? '') !== '') {
    journal('Candidature écartée : champ piège rempli (robot).');
    header('Location: /formations.php', true, 303);
    exit;
}


/* ---------------------------------------------------------------
 *  2. LA FORMATION CONCERNÉE
 * ------------------------------------------------------------- */

// Le nom enregistré est celui de formations-data.php, sur le serveur.
// Le navigateur n'envoie qu'un identifiant, jamais un libellé libre :
// personne ne peut donc faire arriver à Camille une candidature pour
// une formation qu'elle ne propose pas.
$formations  = require __DIR__ . '/formations-data.php';
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


/* ---------------------------------------------------------------
 *  3. LES COORDONNÉES
 * ------------------------------------------------------------- */

$prenom    = nettoyer_ligne($_POST['prenom'] ?? '', 80);
$nom       = nettoyer_ligne($_POST['nom'] ?? '', 80);
$email     = mb_strtolower(nettoyer_ligne($_POST['email'] ?? '', 254));
$telephone = nettoyer_ligne($_POST['telephone'] ?? '', 30);

if ($prenom === '') {
    page_erreur("Merci d'indiquer votre prénom.");
}
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

if (trop_de_demandes($pdo, 'formation', $ip, 5)) {
    journal("Trop de candidatures de formation depuis l'IP $ip");
    page_erreur(
        "Plusieurs candidatures ont déjà été envoyées depuis cet appareil. "
        . "Merci de réessayer dans une heure, ou d'appeler directement l'institut.",
        429
    );
}


/* ---------------------------------------------------------------
 *  5. ENREGISTREMENT
 * ------------------------------------------------------------- */

$reference = code_unique($pdo, 'formation');

try {
    $req = $pdo->prepare(
        'INSERT INTO demandes_formation
           (reference, formation_slug, formation_nom,
            prenom, nom, email, telephone,
            statut, ip, cree_le)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $req->execute([
        $reference,
        $formation['slug'],
        $formation['nom'],
        $prenom, $nom, $email, $telephone,
        'nouvelle', $ip, gmdate('Y-m-d H:i:s'),
    ]);
} catch (Throwable $e) {
    journal('Enregistrement de la candidature impossible : ' . $e->getMessage());
    page_erreur("Le site n'arrive pas à enregistrer votre candidature.");
}


/* ---------------------------------------------------------------
 *  6. LES DEUX E-MAILS
 * ------------------------------------------------------------- */

/*
 * Si un envoi échoue, la candidature reste tout de même enregistrée
 * sur le serveur, récupérable si besoin. On ne renvoie donc pas la
 * candidate sur une page d'erreur : sa candidature est bien arrivée,
 * c'est le seul message qui compte pour elle.
 */

$demande = [
    'reference'     => $reference,
    'formation_nom' => $formation['nom'],
    'prenom'        => $prenom,
    'nom'           => $nom,
    'nom_complet'   => trim($prenom . ' ' . $nom),
    'email'         => $email,
    'telephone'     => $telephone,
];

require_once __DIR__ . '/formation-emails.php';

if (!envoyer_accuse_reception($demande)) {
    journal("Accusé de réception non envoyé pour $reference.");
}
if (!prevenir_institut_demande($demande)) {
    journal("Camille non prévenue de la candidature $reference.");
}


/* ---------------------------------------------------------------
 *  7. RETOUR SUR LA FICHE
 * ------------------------------------------------------------- */

// Une redirection plutôt qu'un affichage direct : ainsi, si la
// candidate actualise la page, elle ne renvoie pas sa candidature.
// Elle retombe sur la fiche de LA formation choisie, pas sur la
// liste générale : c'est là que se trouvait le formulaire.
header(
    'Location: /formation.php?f=' . urlencode($formation['slug'])
        . '&recue=' . urlencode($reference) . '#inscription',
    true,
    303
);
exit;
