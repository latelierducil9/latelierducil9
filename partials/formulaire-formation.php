<?php
/**
 * ===============================================================
 *  LE FORMULAIRE DE CANDIDATURE À UNE FORMATION
 * ===============================================================
 *
 *  Un formulaire de contact simple : la candidate choisit sa
 *  formation dans un sélecteur, laisse ses coordonnées, et Camille
 *  reçoit sa fiche par e-mail.
 *
 *  Deux variables peuvent exister avant de l'inclure :
 *
 *    $formations   la liste complète (formations-data.php)
 *    $slugChoisi   la formation déjà sélectionnée, si la candidate
 *                  est arrivée en cliquant sur l'une d'elles
 *
 *  Ce fichier ne s'affiche jamais seul.
 */

declare(strict_types=1);

if (!isset($formations)) {
    return;
}
$slugChoisi = (string) ($slugChoisi ?? '');
?>

<form class="formulaire" action="/demande-formation.php" method="post"
      style="max-width:520px;background:var(--paper);border:1px solid var(--line);
             border-radius:20px;padding:30px;">

  <div class="field">
    <label for="formation">La formation qui vous intéresse</label>
    <select id="formation" name="formation" required>
      <option value="">Choisissez une formation</option>
      <?php foreach ($formations as $uneFormation): ?>
        <option value="<?= h($uneFormation['slug']) ?>"
                <?= $uneFormation['slug'] === $slugChoisi ? 'selected' : '' ?>>
          <?= h($uneFormation['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

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
    revendues ni utilisées pour autre chose, et vous pouvez demander leur
    effacement à tout moment.
    <a href="/confidentialite.php" style="color:var(--pink);">En savoir plus</a>.
  </p>
</form>

<p style="margin-top:24px;font-size:14.5px;">
  Vous préférez en parler de vive voix ? Écrivez sur Instagram
  <a href="https://www.instagram.com/latelierducilacil" target="_blank" rel="noopener"
     style="color:var(--pink);font-weight:500;">@latelierducilacil</a>
  ou appelez le
  <a href="tel:0612422657" style="color:var(--pink);font-weight:500;">06 12 42 26 57</a>.
</p>
