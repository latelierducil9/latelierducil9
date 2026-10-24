<?php
/**
 * ===============================================================
 *  CONDITIONS GÉNÉRALES DE VENTE
 * ===============================================================
 *
 *  Obligatoires dès qu'on vend à des particuliers. Elles couvrent ici
 *  deux choses bien distinctes :
 *
 *    - les cartes cadeaux, vendues en ligne et payées par Stripe
 *    - les soins en institut, réservés en ligne mais payés sur place
 *
 *  Les montants et la durée de validité viennent de config.php et de
 *  mentions-data.php : ils ne sont pas recopiés ici, pour qu'une
 *  modification ne laisse pas deux chiffres contradictoires.
 */

declare(strict_types=1);

require __DIR__ . '/partials/legal.php';

$validiteMois = (int) ($infos['validite_carte_mois'] ?? 12);
$montantMin   = (int) reglage('carte.montant_min', 10);
$montantMax   = (int) reglage('carte.montant_max', 200);

$pageTitle = "Conditions générales de vente · L'atelier du cil à cil";
$pageDescription = "Conditions générales de vente de L'atelier du cil à cil : cartes "
    . "cadeaux, rendez-vous, paiement et droit de rétractation.";

require __DIR__ . '/partials/header.php';
?>

<section>
  <div class="wrap" style="max-width:720px;">
    <div class="section-head">
      <p class="eyebrow">Conditions</p>
      <h1>Conditions générales de vente</h1>
    </div>

    <div class="contact-card texte-legal" style="max-width:100%;">

      <h2>Qui vend</h2>
      <p>
        <strong><?= h($infos['nom_commercial']) ?></strong>, <?= h($infos['responsable']) ?>,
        <?= h($infos['adresse']) ?>.<br>
        <?= info_legale($infos['statut_juridique'], 'la forme juridique de l\'entreprise') ?>,
        SIRET <?= info_legale($infos['siret'], 'le numéro SIRET') ?>.<br>
        <?= info_legale($infos['tva'], 'le régime de TVA') ?>.
      </p>

      <h2>Ce qui est vendu</h2>
      <p>
        <strong>Les soins</strong> réalisés à l'institut : cils, sourcils,
        blanchiment et pose de strass dentaire. Ils se réservent en ligne ou
        par téléphone, et se règlent sur place le jour du rendez-vous. Les
        tarifs sont ceux affichés sur la page
        <a href="/prestations.php">prestations</a>, en euros, toutes taxes
        comprises.
      </p>
      <p>
        <strong>Les cartes cadeaux</strong>, achetées et payées directement sur
        ce site, d'un montant libre compris entre <?= h(montant_lisible($montantMin * 100)) ?>
        et <?= h(montant_lisible($montantMax * 100)) ?>.
      </p>
      <p>
        <strong>Les formations</strong> ne se paient pas sur ce site. Le
        formulaire sert à se faire connaître ; Camille répond ensuite
        personnellement avec les dates et le tarif. Rien n'est engagé tant
        qu'aucun accord n'a été échangé entre vous et elle.
      </p>

      <h2>Les cartes cadeaux</h2>
      <p>
        Une fois le paiement accepté, la carte part par e-mail à l'adresse
        indiquée, avec un code unique. Elle est valable
        <strong><?= $validiteMois ?> mois</strong> à compter de son achat, et
        s'utilise sur l'ensemble des soins de l'institut.
      </p>
      <p>
        Elle peut être utilisée en plusieurs fois : si le soin coûte moins cher
        que le montant de la carte, le reste demeure disponible pour une
        prochaine visite. Une carte n'est ni échangeable contre de l'argent, ni
        remboursable au-delà du délai de rétractation ci-dessous.
      </p>
      <p>
        L'e-mail n'est pas arrivé ? Vérifiez vos courriers indésirables, puis
        écrivez à <a href="mailto:<?= h($infos['email']) ?>"><?= h($infos['email']) ?></a> :
        la carte sera renvoyée.
      </p>

      <h2>Le paiement</h2>
      <p>
        Les paiements en ligne sont traités par Stripe. Votre numéro de carte
        bancaire ne transite jamais par ce site et n'y est jamais conservé. La
        commande n'est validée qu'une fois le paiement accepté.
      </p>

      <h2>Le droit de rétractation</h2>
      <p>
        Pour une carte cadeau achetée en ligne, vous disposez de
        <strong>quatorze jours</strong> à compter de l'achat pour changer
        d'avis, sans avoir à vous justifier, à condition que la carte n'ait pas
        encore été utilisée. Écrivez simplement à
        <a href="mailto:<?= h($infos['email']) ?>"><?= h($infos['email']) ?></a> :
        le remboursement intervient dans les quatorze jours suivant votre
        demande, par le même moyen de paiement.
      </p>
      <p>
        Ce droit ne s'applique pas à un soin déjà réalisé : une prestation
        exécutée avec votre accord avant la fin du délai ne peut plus être
        annulée.
      </p>

      <h2>Les rendez-vous</h2>
      <p>
        Un empêchement arrive. Prévenez simplement Camille le plus tôt
        possible, par téléphone ou par message, pour que le créneau puisse être
        proposé à quelqu'un d'autre.
      </p>
      <p>
        Un retard important peut raccourcir le soin ou obliger à le reporter :
        chaque rendez-vous est calé sur une durée précise, et le suivant ne
        peut pas attendre.
      </p>

      <h2>L'assurance</h2>
      <p>
        <?php if (info_presente($infos['assurance']['nom'])): ?>
          Les prestations sont couvertes par une assurance responsabilité
          civile professionnelle souscrite auprès de
          <?= h($infos['assurance']['nom']) ?><?php
          if (info_presente($infos['assurance']['couverture'])): ?>,
          valable pour <?= h($infos['assurance']['couverture']) ?><?php endif; ?>.
        <?php else: ?>
          <?= info_legale('', 'le nom de l\'assurance responsabilité civile professionnelle et sa couverture géographique') ?>
        <?php endif; ?>
      </p>

      <h2>En cas de désaccord</h2>
      <p>
        Parlons-en d'abord directement : écrivez à
        <a href="mailto:<?= h($infos['email']) ?>"><?= h($infos['email']) ?></a>,
        la plupart des situations se règlent ainsi.
      </p>
      <p>
        Si cela ne suffit pas, vous pouvez saisir gratuitement un médiateur de
        la consommation :
        <?php if (info_presente($infos['mediateur']['nom'])): ?>
          <strong><?= h($infos['mediateur']['nom']) ?></strong><?php
          if (info_presente($infos['mediateur']['adresse'])): ?>,
          <?= h($infos['mediateur']['adresse']) ?><?php endif; ?><?php
          if (info_presente($infos['mediateur']['site'])): ?>,
          <a href="<?= h($infos['mediateur']['site']) ?>" target="_blank" rel="noopener"><?= h($infos['mediateur']['site']) ?></a><?php endif; ?>.
        <?php else: ?>
          <?= info_legale('', 'le nom, l\'adresse et le site du médiateur de la consommation (adhésion obligatoire)') ?>
        <?php endif; ?>
      </p>
      <p>
        Vous pouvez également utiliser la plateforme européenne de règlement
        des litiges :
        <a href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener">ec.europa.eu/consumers/odr</a>.
      </p>

      <h2>Vos données</h2>
      <p>
        Les informations recueillies lors d'un achat ou d'une candidature sont
        décrites sur la page <a href="/confidentialite.php">confidentialité</a>.
      </p>

      <p class="petit-detail" style="margin-top:26px;">
        Dernière mise à jour : octobre 2026. Le droit applicable est le droit
        français.
      </p>

    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
