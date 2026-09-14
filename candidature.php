<?php
/**
 * ===============================================================
 *  SE FORMER : la page du formulaire de candidature
 * ===============================================================
 *
 *  Une page, un formulaire. La candidate choisit sa formation dans
 *  le sélecteur, laisse ses coordonnées, et Camille reçoit sa fiche
 *  par e-mail pour la recontacter elle-même.
 *
 *  Le parcours :
 *
 *    formations.php, bouton « Se former »
 *      → candidature.php          le formulaire
 *      → demande-formation.php    enregistre, envoie les 2 e-mails
 *      → retour ici, avec la confirmation
 *
 *  Si la candidate arrive en cliquant sur une formation précise
 *  (candidature.php?f=epilation-au-fil), le sélecteur s'ouvre déjà
 *  sur celle-là. Elle peut toujours en changer.
 */

declare(strict_types=1);

// Pour h() et le chargement de la configuration.
require_once __DIR__ . '/lib.php';

$formations = require __DIR__ . '/formations-data.php';

// Formation présélectionnée, si elle vient d'un clic sur la liste.
// Une adresse fantaisiste ne casse rien : le sélecteur reste vide.
$slugChoisi = '';
$demande    = nettoyer_ligne($_GET['f'] ?? '', 60);
foreach ($formations as $uneFormation) {
    if ($uneFormation['slug'] === $demande) {
        $slugChoisi = $uneFormation['slug'];
        break;
    }
}

$pageTitle       = "Se former · L'atelier du cil à cil";
$pageDescription = 'Candidature aux formations de L\'atelier du cil à cil, à Feurs et à '
    . 'Saint-Étienne : réhaussement de cils, brow lift, épilation au fil, extensions '
    . 'cil à cil et volume russe.';
$pageImage = '/assets/formations/formation-cils.jpg';

// Affiché au retour de demande-formation.php, après un envoi réussi.
$reference = preg_match('/^DEM-[A-Z0-9]{4}-[A-Z0-9]{4}$/', (string) ($_GET['recue'] ?? ''))
    ? (string) $_GET['recue']
    : '';

require __DIR__ . '/partials/header.php';
?>

<section>
  <div class="wrap">
    <p style="margin-bottom:18px;">
      <a href="/formations.php" style="color:var(--ink-soft);font-size:14px;text-decoration:none;">&larr; Retour aux formations</a>
    </p>

    <div class="section-head">
      <p class="eyebrow">Se former · Feurs &amp; Saint-Étienne</p>
      <h1>Votre candidature</h1>
      <p>Laissez vos coordonnées : Camille vous répond personnellement,
         avec les dates possibles et le tarif exact.</p>
    </div>

    <?php if ($reference !== ''): ?>
      <div class="contact-card" style="max-width:520px;border-color:var(--pink);margin-bottom:28px;">
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

    <?php require __DIR__ . '/partials/formulaire-formation.php'; ?>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
