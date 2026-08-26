<?php
/**
 * ===============================================================
 *  FICHE D'UNE PRESTATION
 * ===============================================================
 *
 *  Affiche le détail d'une seule prestation, retrouvée grâce à son
 *  "slug" dans l'adresse (ex. prestation.php?p=rehaussement-de-cils).
 *
 *  Tant que Camille n'a pas encore fourni le texte de présentation
 *  d'une prestation, la fiche l'indique simplement : rien n'est
 *  inventé à sa place.
 */

declare(strict_types=1);

$categories = require __DIR__ . '/prestations-data.php';

$slug = (string) ($_GET['p'] ?? '');

$prestation = null;
$categorieTitre = '';
foreach ($categories as $categorie) {
    foreach ($categorie['prestations'] as $p) {
        if ($p['slug'] === $slug) {
            $prestation = $p;
            $categorieTitre = $categorie['titre'];
            break 2;
        }
    }
}

if ($prestation === null) {
    header('Location: /prestations.php', true, 303);
    exit;
}

$pageTitle = $prestation['nom'] . " — L'atelier du cil à cil";
$pageDescription = $prestation['nom'] . " à Salvizinet, à partir de " . $prestation['prix'] . '.';

require __DIR__ . '/partials/header.php';
?>

<section>
  <div class="wrap" style="max-width:720px;">
    <p style="margin-bottom:18px;">
      <a href="/prestations.php" style="color:var(--ink-soft);font-size:14px;text-decoration:none;">&larr; Toutes les prestations</a>
    </p>

    <div class="section-head">
      <p class="eyebrow"><?= h($categorieTitre) ?></p>
      <h2>
        <?= h($prestation['nom']) ?>
        <?php if (!empty($prestation['etiquette'])): ?>
          <span class="menu-tag" style="vertical-align:middle;"><?= h($prestation['etiquette']) ?></span>
        <?php endif; ?>
      </h2>
    </div>

    <div class="contact-card" style="max-width:100%;">
      <p style="font-family:'Playfair Display',serif;font-size:28px;color:var(--pink);margin:0 0 20px;">
        <?= h($prestation['prix']) ?>
      </p>

      <?php if (trim((string) $prestation['description']) !== ''): ?>
        <p><?= nl2br(h($prestation['description'])) ?></p>
      <?php else: ?>
        <p style="color:var(--ink-soft);font-style:italic;">
          La description détaillée de cette prestation arrive très bientôt.
        </p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
