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

// Les prestations sans tarif fixe affichent « Sur RDV » : écrire
// « à partir de Sur RDV » n'aurait aucun sens, on tourne la phrase autrement.
$prixFixe = (bool) preg_match('/\d/', $prestation['prix']);
$pageDescription = $prixFixe
    ? $prestation['nom'] . " à Salvizinet, à partir de " . $prestation['prix'] . '.'
    : $prestation['nom'] . " à Salvizinet, sur rendez-vous.";

// Quand une cliente partage le lien d'une prestation, c'est la photo de
// cette prestation qui doit apparaître dans l'aperçu.
if (!empty($prestation['image'])) {
    $pageImage = $prestation['image'];
}

require __DIR__ . '/partials/header.php';
?>

<section>
  <div class="wrap" style="max-width:720px;">
    <p style="margin-bottom:18px;">
      <a href="/prestations.php" style="color:var(--ink-soft);font-size:14px;text-decoration:none;">&larr; Toutes les prestations</a>
    </p>

    <div class="section-head">
      <p class="eyebrow"><?= h($categorieTitre) ?></p>
      <h1>
        <?= h($prestation['nom']) ?>
        <?php if (!empty($prestation['etiquette'])): ?>
          <span class="menu-tag" style="vertical-align:middle;"><?= h($prestation['etiquette']) ?></span>
        <?php endif; ?>
      </h1>
    </div>

    <div class="contact-card" style="max-width:100%;">
      <?php if (!empty($prestation['image'])): ?>
        <?php
        // On lit les dimensions réelles du fichier pour les déclarer dans la
        // page : le navigateur réserve ainsi la bonne place avant même que
        // la photo soit chargée, et le texte ne saute pas sous les yeux
        // de la visiteuse. Si le fichier manque, on n'affiche rien de faux.
        $fichierImage = __DIR__ . '/' . ltrim($prestation['image'], '/');
        $dimensions = is_file($fichierImage) ? @getimagesize($fichierImage) : false;
        ?>
        <img src="<?= h($prestation['image']) ?>" alt="<?= h($prestation['nom']) ?>"
             <?php if ($dimensions): ?>width="<?= (int) $dimensions[0] ?>" height="<?= (int) $dimensions[1] ?>"<?php endif; ?>
             fetchpriority="high" decoding="async"
             style="width:100%;border-radius:12px;margin-bottom:24px;object-fit:cover;max-height:420px;">
      <?php endif; ?>

      <?php if (!empty($prestation['sections'])): ?>
        <?php foreach ($prestation['sections'] as $section): ?>
          <h3 style="font-family:'Playfair Display',serif;font-weight:500;font-size:17px;margin:0 0 8px;"><?= h($section['titre']) ?></h3>
          <p style="margin-bottom:20px;"><?= nl2br(h($section['texte'])) ?></p>
        <?php endforeach; ?>
      <?php elseif (trim((string) $prestation['description']) !== ''): ?>
        <p><?= nl2br(h($prestation['description'])) ?></p>
      <?php else: ?>
        <p style="color:var(--ink-soft);font-style:italic;">
          La description détaillée de cette prestation arrive très bientôt.
        </p>
      <?php endif; ?>

      <p style="margin-top:20px;">
        <strong>Prix :</strong> <?= h($prestation['prix']) ?>
        <?php if (!empty($prestation['duree'])): ?>
          <br><strong>Durée :</strong> <?= h($prestation['duree']) ?>
        <?php endif; ?>
      </p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
