<?php
/**
 * ===============================================================
 *  LES FORMATIONS : liste cliquable
 * ===============================================================
 *
 *  Présente les formations de Camille, chacune menant à sa propre
 *  fiche (formation.php) où se trouvent le détail et le petit
 *  formulaire de candidature.
 *
 *  Même motif que prestations.php → prestation.php : une liste de
 *  noms et de prix, cliquable, sans description dupliquée. La
 *  description de chaque formation ne vit qu'à un seul endroit, sur
 *  sa fiche.
 */

declare(strict_types=1);

// Pour montant_lisible() : les tarifs sont stockés en centimes et
// mis en forme ici, avec la même présentation que partout ailleurs.
require_once __DIR__ . '/lib.php';

$formations = require __DIR__ . '/formations-data.php';

// Le tarif annoncé est le plus bas de la liste : Camille a écrit
// « formation à partir de 355€ ». Il se met donc à jour tout seul si
// un tarif change dans formations-data.php.
//
// montant_lisible() écrit « 355 € », avec une espace. Partout ailleurs
// sur le site les tarifs s'écrivent « 35€ », collés : on garde cette
// présentation pour que la page Formations ne détonne pas.
$prixDepart = str_replace(' €', '€', montant_lisible(min(array_column($formations, 'prix_cents'))));

$pageTitle = "Formations cils & sourcils · L'atelier du cil à cil";
$pageDescription = 'Formations professionnelles aux techniques du regard, à Feurs et à '
    . 'Saint-Étienne : réhaussement de cils, extensions cil à cil, volume russe, brow lift '
    . 'et épilation au fil. Formations à partir de ' . $prixDepart . '.';
$pageImage = '/assets/formations/formation-cils.jpg';

require __DIR__ . '/partials/header.php';
?>

<section>
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Se former · Feurs &amp; Saint-Étienne</p>
      <h1>Mes formations</h1>
      <p>Devenir technicienne du regard, accompagnée pas à pas.</p>
    </div>

    <div class="contact-card" style="max-width:680px;">
      <p>Hello my girl !</p>
      <p>Tu souhaites être indépendante et créer ta propre entreprise d'extension de cils ? Tu souhaites être technicienne de cils ?</p>
      <p>Viens te former au sein de L'atelier du cil à cil, les formations s'étalent sur deux jours (ou plus). Tu bénéficiras d'un carnet de formation, de modèles réels et d'un accompagnement par mes soins. Les formations ont lieu à Feurs et à Saint-Étienne, à partir de <?= h($prixDepart) ?>.</p>
      <p>Un kit pour débuter au sein de ton activité te sera également mis à disposition, il est compris dans le prix de la formation, celui-ci contient :</p>
      <ul class="services-list" style="grid-template-columns:1fr;max-width:420px;">
        <li>Une colle professionnelle London Lash</li>
        <li>Un ensemble de pinces professionnelles pour cils</li>
        <li>Une boîte de cils</li>
        <li>Un shampoing pour cils</li>
        <li>Des goupillons</li>
        <li>Des patchs isolants</li>
      </ul>
      <?php /* La photo et le bouton côte à côte, empilés sur téléphone. */ ?>
      <div style="display:flex;flex-wrap:wrap;align-items:center;gap:26px;margin-top:20px;">
        <img src="/assets/formations/formation-cils.jpg" alt="Formation à la pose de cils à l'atelier"
             width="225" height="225" loading="lazy" decoding="async"
             style="width:225px;max-width:100%;border-radius:12px;">
        <a class="btn btn-primary" href="/candidature.php">Se former</a>
      </div>
    </div>
  </div>
</section>

<section id="les-formations">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Au choix</p>
      <h2>Les formations proposées</h2>
      <p>À Feurs et à Saint-Étienne, à partir de <?= h($prixDepart) ?>, kit compris. Cliquez sur une formation pour vous y inscrire.</p>
    </div>

    <div class="tarif-grid">
      <div class="tarif-col" style="max-width:520px;">
        <?php foreach ($formations as $f): ?>
          <?php $prixLigne = str_replace(' €', '€', montant_lisible((int) $f['prix_cents'])); ?>
          <a class="menu-item menu-item-link" href="/candidature.php?f=<?= urlencode($f['slug']) ?>">
            <span class="menu-name"><?= h($f['nom']) ?></span>
            <span class="menu-lead"></span>
            <span class="menu-price"><?= h($prixLigne) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <p class="petit-detail" style="margin-top:20px;">
      Le tarif dépend de la formation choisie et de votre niveau de départ.
      Camille vous le précise en vous répondant.
    </p>

    <p style="margin-top:22px;">
      <a class="btn btn-primary" href="/candidature.php">Se former</a>
    </p>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
