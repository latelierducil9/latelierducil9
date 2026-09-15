<?php
/**
 * ===============================================================
 *  LA FICHE D'UNE FORMATION
 * ===============================================================
 *
 *  Décrit une seule formation, retrouvée grâce à son identifiant dans
 *  l'adresse (ex. formation.php?f=epilation-au-fil), et propose un
 *  bouton « Se former » qui mène au formulaire, déjà réglé sur cette
 *  formation.
 *
 *  Le parcours complet :
 *
 *    formations.php    la liste des cinq formations, cliquable
 *      → formation.php   celle qu'on a choisie, et son détail
 *      → candidature.php le formulaire, la bonne formation déjà
 *                        sélectionnée dans le menu
 *
 *  Même principe que les prestations (prestations.php →
 *  prestation.php) : la liste sert à choisir, la fiche à se décider.
 *
 *  Tant qu'un champ est vide dans formations-data.php, la page ne
 *  l'affiche pas. Rien n'est inventé à la place de Camille.
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
// d'afficher une erreur, la visiteuse retombe sur ses pieds.
if ($formation === null) {
    header('Location: /formations.php', true, 303);
    exit;
}

// Affiché « 355€ », collé, comme les autres tarifs du site.
$prix = str_replace(' €', '€', montant_lisible((int) $formation['prix_cents']));

$pageTitle = 'Formation ' . $formation['nom'] . " · L'atelier du cil à cil";
$pageDescription = 'Formation ' . $formation['nom'] . ', à Feurs et à Saint-Étienne. '
    . (trim((string) $formation['resume']) !== '' ? $formation['resume'] . ' ' : '')
    . 'À partir de ' . $prix . ', kit compris.';
$pageImage = '/assets/formations/formation-cils.jpg';

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

      <h3 style="font-family:'Playfair Display',serif;font-weight:500;font-size:17px;margin:0 0 10px;">
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

      <p style="margin:14px 0 22px;color:var(--ink-soft);font-size:14px;">
        Le tarif exact dépend de votre niveau de départ. Camille vous le
        précise en vous répondant.
      </p>

      <?php /* Le bouton mène au formulaire avec cette formation déjà
               choisie dans le menu déroulant. */ ?>
      <a class="btn btn-primary" href="/candidature.php?f=<?= urlencode($formation['slug']) ?>">Se former</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
