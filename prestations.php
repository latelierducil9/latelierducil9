<?php
/**
 * ===============================================================
 *  LISTE DES PRESTATIONS
 * ===============================================================
 *
 *  Toutes les prestations, groupées par catégorie. Chaque ligne est
 *  cliquable et amène sur la fiche détaillée de cette prestation
 *  (prestation.php).
 */

declare(strict_types=1);

$categories = require __DIR__ . '/prestations-data.php';

$pageTitle = "Prestations — L'atelier du cil à cil";
$pageDescription = 'Toutes les prestations de cils, sourcils et blanchiment dentaire '
    . "de L'atelier du cil à cil à Salvizinet, avec leurs tarifs.";

require __DIR__ . '/partials/header.php';
?>

<section id="tarifs">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">La carte</p>
      <h1>Prestations &amp; tarifs</h1>
      <p>Tous les soins du regard, des cils au sourire. Cliquez sur une prestation pour en voir le détail.</p>
    </div>

    <div class="tarif-grid">
      <?php foreach ($categories as $categorie): ?>
        <div class="tarif-col">
          <h3><?= h($categorie['titre']) ?></h3>
          <?php foreach ($categorie['prestations'] as $p): ?>
            <a class="menu-item menu-item-link" href="/prestation.php?p=<?= urlencode($p['slug']) ?>">
              <span class="menu-name">
                <?= h($p['nom']) ?><?php if (!empty($p['etiquette'])): ?><span class="menu-tag"><?= h($p['etiquette']) ?></span><?php endif; ?>
              </span>
              <span class="menu-lead"></span>
              <span class="menu-price"><?= h($p['prix']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
