<?php
/**
 * ===============================================================
 *  MENTIONS LÉGALES
 * ===============================================================
 *
 *  Obligatoires pour tout site professionnel : la loi pour la
 *  confiance dans l'économie numérique impose d'indiquer qui édite le
 *  site et qui l'héberge, pour qu'une visiteuse sache à qui elle a
 *  affaire.
 *
 *  Les informations viennent de mentions-data.php. Celles qui y sont
 *  vides s'affichent comme manquantes : rien n'est inventé.
 */

declare(strict_types=1);

require __DIR__ . '/partials/legal.php';

$pageTitle = "Mentions légales · L'atelier du cil à cil";
$pageDescription = "Mentions légales de L'atelier du cil à cil, institut de cils et "
    . "sourcils à Feurs : éditeur du site, hébergeur et contact.";

require __DIR__ . '/partials/header.php';
?>

<section>
  <div class="wrap" style="max-width:720px;">
    <div class="section-head">
      <p class="eyebrow">Informations légales</p>
      <h1>Mentions légales</h1>
    </div>

    <div class="contact-card texte-legal" style="max-width:100%;">

      <h2>L'éditeur du site</h2>
      <p>
        <strong><?= h($infos['nom_commercial']) ?></strong><br>
        Responsable de la publication : <?= h($infos['responsable']) ?><br>
        <?= h($infos['adresse']) ?><br>
        Téléphone : <a href="tel:<?= h(preg_replace('/\s+/', '', $infos['telephone'])) ?>"><?= h($infos['telephone']) ?></a><br>
        E-mail : <a href="mailto:<?= h($infos['email']) ?>"><?= h($infos['email']) ?></a>
      </p>
      <p>
        Statut juridique : <?= info_legale($infos['statut_juridique'], 'la forme juridique de l\'entreprise') ?><br>
        Numéro SIRET : <?= info_legale($infos['siret'], 'le numéro SIRET à 14 chiffres') ?><br>
        TVA : <?= info_legale($infos['tva'], 'le numéro de TVA, ou la mention « TVA non applicable, article 293 B du CGI »') ?>
      </p>

      <h2>L'hébergeur du site</h2>
      <p>
        <?= h($infos['hebergeur']['nom']) ?><br>
        <?= h($infos['hebergeur']['adresse']) ?><br>
        Téléphone : <?= h($infos['hebergeur']['telephone']) ?><br>
        <a href="<?= h($infos['hebergeur']['site']) ?>" target="_blank" rel="noopener"><?= h($infos['hebergeur']['site']) ?></a>
      </p>

      <h2>Vos données personnelles</h2>
      <p>
        Les données que vous confiez au site, et ce qu'elles deviennent, sont
        décrites en détail sur la page
        <a href="/confidentialite.php">confidentialité</a>.
      </p>

      <h2>Le contenu du site</h2>
      <p>
        Les textes et les photographies de ce site appartiennent à
        <?= h($infos['nom_commercial']) ?>. Les reproduire ailleurs sans accord
        écrit n'est pas autorisé.
      </p>
      <p>
        Les polices d'écriture utilisées (Poppins, Playfair Display et Dancing
        Script) sont distribuées sous licence SIL Open Font License et servies
        depuis ce site, sans passer par un service extérieur.
      </p>

      <h2>Un problème, une question</h2>
      <p>
        Écrivez à <a href="mailto:<?= h($infos['email']) ?>"><?= h($infos['email']) ?></a>
        ou appelez le <a href="tel:<?= h(preg_replace('/\s+/', '', $infos['telephone'])) ?>"><?= h($infos['telephone']) ?></a>.
        Camille répond elle-même.
      </p>

    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
