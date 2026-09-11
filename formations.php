<?php
/**
 * ===============================================================
 *  LES FORMATIONS — page publique et demande de devis
 * ===============================================================
 *
 *  Présente les formations de Camille et permet à une candidate de
 *  demander un devis sans passer par Instagram.
 *
 *  Le formulaire envoie vers demande-devis.php, qui vérifie tout
 *  côté serveur avant d'enregistrer quoi que ce soit.
 *
 *  Les textes de présentation sont ceux de Camille, repris tels
 *  quels depuis la page d'accueil. Les résumés et programmes de
 *  chaque formation viendront d'elle : tant qu'ils sont vides dans
 *  formations-data.php, cette page ne les affiche pas.
 */

declare(strict_types=1);

// Pour montant_lisible() : les tarifs sont stockés en centimes et
// mis en forme ici, avec la même présentation que partout ailleurs.
require_once __DIR__ . '/lib.php';

$formations = require __DIR__ . '/formations-data.php';

$pageTitle = "Formations cils & sourcils — L'atelier du cil à cil";
$pageDescription = 'Formations professionnelles aux techniques du regard à Salvizinet : '
    . 'réhaussement de cils, extensions cil à cil, volume russe, brow lift et épilation au fil. '
    . 'Demande de devis en ligne.';
$pageImage = '/assets/formations/formation-cils.jpg';

// Message affiché au retour de demande-devis.php, après un envoi réussi.
$reference = preg_match('/^DEV-[A-Z0-9]{4}-[A-Z0-9]{4}$/', (string) ($_GET['recue'] ?? ''))
    ? (string) $_GET['recue']
    : '';

require __DIR__ . '/partials/header.php';
?>

<section>
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Se former</p>
      <h1>Mes formations</h1>
      <p>Devenir technicienne du regard, accompagnée pas à pas.</p>
    </div>

    <?php if ($reference !== ''): ?>
      <div class="contact-card" style="max-width:680px;border-color:var(--pink);margin-bottom:32px;">
        <h2 style="font-family:'Playfair Display',serif;font-weight:500;font-size:20px;margin:0 0 10px;">
          Votre demande est bien arrivée
        </h2>
        <p style="margin:0 0 8px;">
          Elle porte la référence <strong><?= h($reference) ?></strong>.
          Un e-mail de confirmation vient de vous être envoyé.
        </p>
        <p style="margin:0;color:var(--ink-soft);font-size:14.5px;">
          Camille étudie votre projet et vous adresse votre devis personnalisé.
        </p>
      </div>
    <?php endif; ?>

    <div class="contact-card" style="max-width:680px;">
      <p>Hello my girl !</p>
      <p>Tu souhaites être indépendante et créer ta propre entreprise d'extension de cils ? Tu souhaites être technicienne de cils ?</p>
      <p>Viens te former au sein de L'atelier du cil à cil, les formations s'étalent sur deux jours (ou plus). Tu bénéficiras d'un carnet de formation, de modèles réels et d'un accompagnement par mes soins. Le montant de la formation s'élève à 495€.</p>
      <p>Un kit pour débuter au sein de ton activité te sera également mis à disposition, il est compris dans le prix de la formation, celui-ci contient :</p>
      <ul class="services-list" style="grid-template-columns:1fr;max-width:420px;">
        <li>Une colle professionnelle London Lash</li>
        <li>Un ensemble de pinces professionnelles pour cils</li>
        <li>Une boîte de cils</li>
        <li>Un shampoing pour cils</li>
        <li>Des goupillons</li>
        <li>Des patchs isolants</li>
      </ul>
      <p style="font-size:13px;color:var(--ink-soft);">(exemple pour le kit réhaussement de cils)</p>
      <img src="/assets/formations/formation-cils.jpg" alt="Formation à la pose de cils à l'atelier"
           width="225" height="225" loading="lazy" decoding="async"
           style="max-width:280px;border-radius:12px;margin:16px 0;">
    </div>
  </div>
</section>

<section id="les-formations">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Au choix</p>
      <h2>Les formations proposées</h2>
    </div>

    <div class="tarif-grid">
      <?php foreach ($formations as $formation): ?>
        <div class="tarif-col">
          <div class="menu-item" style="border-bottom:none;padding-bottom:4px;">
            <span class="menu-name" style="font-weight:500;"><?= h($formation['nom']) ?></span>
            <span class="menu-lead"></span>
            <span class="menu-price"><?= h(montant_lisible((int) $formation['prix_cents'])) ?></span>
          </div>
          <p class="petit-detail"><?= h($formation['duree']) ?> · kit compris</p>

          <?php if (trim((string) $formation['resume']) !== ''): ?>
            <p style="font-size:14.5px;color:var(--ink-soft);margin:0 0 10px;">
              <?= h($formation['resume']) ?>
            </p>
          <?php endif; ?>

          <?php if (!empty($formation['programme'])): ?>
            <ul class="services-list" style="grid-template-columns:1fr;margin-bottom:18px;">
              <?php foreach ($formation['programme'] as $point): ?>
                <li><?= h($point) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="devis">
  <div class="wrap">
    <div class="section-head">
      <p class="eyebrow">Sans engagement</p>
      <h2>Demander un devis</h2>
      <p>Dites-moi ce qui vous intéresse : Camille vous répond avec un devis
         personnalisé et des dates possibles.</p>
    </div>

    <form class="formulaire" action="/demande-devis.php" method="post"
          style="max-width:680px;background:var(--paper);border:1px solid var(--line);
                 border-radius:20px;padding:30px;">

      <div class="field">
        <label for="formation">La formation qui vous intéresse</label>
        <select id="formation" name="formation" required>
          <option value="">— Choisissez une formation —</option>
          <?php foreach ($formations as $formation): ?>
            <option value="<?= h($formation['slug']) ?>"><?= h($formation['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="nom">Votre nom</label>
          <input type="text" id="nom" name="nom" required maxlength="80" autocomplete="name">
        </div>
        <div class="field">
          <label for="telephone">Votre téléphone</label>
          <input type="tel" id="telephone" name="telephone" maxlength="30" autocomplete="tel">
        </div>
      </div>

      <div class="field">
        <label for="email">Votre e-mail</label>
        <input type="email" id="email" name="email" required maxlength="254" autocomplete="email">
      </div>

      <div class="field">
        <label for="periode">Quand souhaiteriez-vous vous former ?</label>
        <input type="text" id="periode" name="periode" maxlength="80"
               placeholder="Par exemple : courant mars, ou dès que possible">
      </div>

      <div class="field">
        <label for="message">Votre projet en quelques mots</label>
        <textarea id="message" name="message" rows="4" maxlength="1000"
                  placeholder="Débutante ou déjà technicienne ? En reconversion ? Racontez-moi."></textarea>
      </div>

      <?php
      /*
       * Champ piège. Invisible pour une visiteuse, mais les robots
       * remplissent automatiquement tous les champs qu'ils trouvent.
       * Une demande arrivant avec ce champ rempli est donc un robot :
       * demande-devis.php l'écarte sans rien dire.
       *
       * aria-hidden et tabindex l'excluent aussi des lecteurs d'écran
       * et de la navigation au clavier : une personne non voyante ne
       * risque pas de le remplir par erreur.
       */
      ?>
      <div style="position:absolute;left:-9999px;" aria-hidden="true">
        <label for="site_web">Ne remplissez pas ce champ</label>
        <input type="text" id="site_web" name="site_web" tabindex="-1" autocomplete="off">
      </div>

      <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center;margin-top:8px;">
        Demander mon devis
      </button>

      <p style="font-size:12px;color:var(--ink-soft);margin:14px 0 0;line-height:1.5;">
        Vos coordonnées servent uniquement à vous répondre. Elles ne sont ni
        revendues ni utilisées pour autre chose.
      </p>
    </form>

    <p style="margin-top:24px;font-size:14.5px;">
      Vous préférez en parler de vive voix ? Écrivez sur Instagram
      <a href="https://www.instagram.com/latelierducilacil" target="_blank" rel="noopener"
         style="color:var(--pink);font-weight:500;">@latelierducilacil</a>
      ou appelez le
      <a href="tel:0612422657" style="color:var(--pink);font-weight:500;">06 12 42 26 57</a>.
    </p>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
