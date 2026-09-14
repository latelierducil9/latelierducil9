<?php
/**
 * ===============================================================
 *  FICHE D'UNE FORMATION, AVEC SON FORMULAIRE D'INSCRIPTION
 * ===============================================================
 *
 *  Affiche une seule formation, retrouvée grâce à son "slug" dans
 *  l'adresse (ex. formation.php?f=epilation-au-fil), suivie du petit
 *  formulaire de candidature déjà réglé sur cette formation.
 *
 *  Le parcours complet :
 *
 *    accueil, bouton « Se former »
 *      → formations.php   la liste cliquable des cinq formations
 *      → formation.php    celle qu'on a choisie, et le formulaire
 *      → demande-formation.php  enregistre et envoie les e-mails
 *      → retour ici, avec le message de confirmation
 *
 *  Camille reçoit la candidature dans sa boîte mail et répond
 *  elle-même.
 *
 *  Comme pour les prestations : tant qu'un champ est vide dans
 *  formations-data.php, la page ne l'affiche pas. Rien n'est inventé
 *  à la place de Camille.
 */

declare(strict_types=1);

// Pour montant_lisible() et h() : les tarifs sont stockés en centimes.
require_once __DIR__ . '/lib.php';

$formations = require __DIR__ . '/formations-data.php';

$slug = (string) ($_GET['f'] ?? '');

$formation = null;
foreach ($formations as $uneFormation) {
    if ($uneFormation['slug'] === $slug) {
        $formation = $uneFormation;
        break;
    }
}

// Adresse inconnue ou trafiquée : on ramène à la liste plutôt que
// d'afficher une page d'erreur, la visiteuse retombe sur ses pieds.
if ($formation === null) {
    header('Location: /formations.php', true, 303);
    exit;
}

// Affiché « 355€ », collé, comme les autres tarifs du site.
$prix = str_replace(' €', '€', montant_lisible((int) $formation['prix_cents']));

$pageTitle = 'Formation ' . $formation['nom'] . " · L'atelier du cil à cil";
$pageDescription = 'Formation ' . $formation['nom'] . ', à Feurs et à Saint-Étienne. '
    . (trim((string) $formation['resume']) !== ''
        ? $formation['resume'] . ' '
        : '')
    . 'À partir de ' . $prix . ', kit compris.';
$pageImage = '/assets/formations/formation-cils.jpg';

// Affiché au retour de demande-formation.php, après un envoi réussi.
$reference = preg_match('/^DEM-[A-Z0-9]{4}-[A-Z0-9]{4}$/', (string) ($_GET['recue'] ?? ''))
    ? (string) $_GET['recue']
    : '';

// Repris tel quel par le formulaire, pour présélectionner le bon choix.
$slugChoisi = $formation['slug'];

require __DIR__ . '/partials/header.php';
?>

<section>
  <div class="wrap" style="max-width:720px;">
    <p style="margin-bottom:18px;">
      <a href="/formations.php" style="color:var(--ink-soft);font-size:14px;text-decoration:none;">&larr; Toutes les formations</a>
    </p>

    <div class="section-head">
      <p class="eyebrow">Se former · Feurs &amp; Saint-Étienne</p>
      <h1><?= h($formation['nom']) ?></h1>
    </div>

    <?php if ($reference !== ''): ?>
      <div class="contact-card" style="max-width:100%;border-color:var(--pink);margin-bottom:28px;">
        <h2 style="font-family:'Playfair Display',serif;font-weight:500;font-size:20px;margin:0 0 10px;">
          Votre candidature est bien arrivée
        </h2>
        <p style="margin:0 0 8px;">
          Elle porte la référence <strong><?= h($reference) ?></strong>.
          Un e-mail de confirmation vient de vous être envoyé.
        </p>
        <p style="margin:0;color:var(--ink-soft);font-size:14.5px;">
          Camille vous recontacte personnellement pour parler de votre projet
          et des dates possibles.
        </p>
      </div>
    <?php endif; ?>

    <div class="contact-card" style="max-width:100%;">
      <?php if (trim((string) $formation['resume']) !== ''): ?>
        <p><?= h($formation['resume']) ?></p>
      <?php endif; ?>

      <?php if (!empty($formation['programme'])): ?>
        <h3 style="font-family:'Playfair Display',serif;font-weight:500;font-size:17px;margin:22px 0 10px;">
          Au programme
        </h3>
        <ul class="services-list" style="grid-template-columns:1fr;margin-bottom:10px;">
          <?php foreach ($formation['programme'] as $point): ?>
            <li><?= h($point) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <h3 style="font-family:'Playfair Display',serif;font-weight:500;font-size:17px;margin:22px 0 10px;">
        Ce qui est compris
      </h3>
      <ul class="services-list" style="grid-template-columns:1fr;margin-bottom:10px;">
        <li>Un carnet de formation</li>
        <li>Des modèles réels</li>
        <li>Un accompagnement par Camille</li>
        <li>Un kit pour débuter votre activité</li>
      </ul>

      <p style="margin-top:20px;">
        <strong>Tarif :</strong> à partir de <?= h($prix) ?>, kit compris
        <?php if (trim((string) $formation['duree']) !== ''): ?>
          <br><strong>Durée :</strong> <?= h($formation['duree']) ?>
        <?php endif; ?>
        <br><strong>Où :</strong> à Feurs et à Saint-Étienne
      </p>

      <p style="margin:14px 0 0;color:var(--ink-soft);font-size:14px;">
        Le tarif exact dépend de votre niveau de départ. Camille vous le
        précise en vous répondant.
      </p>
    </div>
  </div>
</section>

<section id="inscription">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Sans engagement</p>
      <h2>Postuler à cette formation</h2>
      <p>Laissez vos coordonnées : Camille vous répond personnellement,
         avec les dates possibles et le tarif exact.</p>
    </div>

    <?php require __DIR__ . '/partials/formulaire-formation.php'; ?>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
