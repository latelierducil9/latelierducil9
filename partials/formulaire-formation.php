<?php
/**
 * ===============================================================
 *  LE PETIT FORMULAIRE DE CANDIDATURE À UNE FORMATION
 * ===============================================================
 *
 *  Un seul formulaire, affiché sur la fiche de chaque formation.
 *  Il est écrit ici plutôt que recopié sur chaque page : le jour où
 *  une question change, elle ne change qu'à un seul endroit.
 *
 *  Volontairement réduit à quatre champs : prénom, nom, e-mail,
 *  téléphone. La formation n'a pas à être choisie ici, elle l'est
 *  déjà en cliquant dessus depuis formations.php : elle voyage dans
 *  un champ caché.
 *
 *  Une variable doit exister avant de l'inclure :
 *
 *    $slugChoisi   la formation demandée, pour le champ caché
 *
 *  Ce fichier ne s'affiche jamais seul.
 */

declare(strict_types=1);

if (!isset($slugChoisi) || $slugChoisi === '') {
    return;
}
?>

<form class="formulaire" action="/demande-formation.php" method="post"
      style="max-width:520px;background:var(--paper);border:1px solid var(--line);
             border-radius:20px;padding:30px;">

  <input type="hidden" name="formation" value="<?= h($slugChoisi) ?>">

  <div class="field-row">
    <div class="field">
      <label for="prenom">Prénom</label>
      <input type="text" id="prenom" name="prenom" required maxlength="80" autocomplete="given-name">
    </div>
    <div class="field">
      <label for="nom">Nom</label>
      <input type="text" id="nom" name="nom" required maxlength="80" autocomplete="family-name">
    </div>
  </div>

  <div class="field">
    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" required maxlength="254" autocomplete="email">
  </div>

  <div class="field">
    <label for="telephone">Téléphone</label>
    <input type="tel" id="telephone" name="telephone" maxlength="30" autocomplete="tel">
  </div>

  <?php
  /*
   * Champ piège. Invisible pour une visiteuse, mais les robots
   * remplissent automatiquement tous les champs qu'ils trouvent.
   * Une candidature arrivant avec ce champ rempli est donc un robot :
   * demande-formation.php l'écarte sans rien dire.
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
    Envoyer ma candidature
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
