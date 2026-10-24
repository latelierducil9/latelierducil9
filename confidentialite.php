<?php
/**
 * ===============================================================
 *  POLITIQUE DE CONFIDENTIALITÉ
 * ===============================================================
 *
 *  Ce que le RGPD demande : dire clairement quelles données sont
 *  collectées, pourquoi, combien de temps, qui les voit, et quels
 *  droits la personne possède.
 *
 *  ┌─────────────────────────────────────────────────────────────┐
 *  │  CETTE PAGE DÉCRIT CE QUE LE CODE FAIT VRAIMENT             │
 *  │                                                             │
 *  │  Chaque donnée citée ici a été relevée dans les tables de   │
 *  │  la base (lib.php, creer_tables) et chaque durée correspond │
 *  │  exactement à menage_donnees() dans lib.php.                │
 *  │                                                             │
 *  │  Si une durée change dans le code, elle doit changer ici.   │
 *  │  Une politique qui ment est pire que pas de politique.      │
 *  └─────────────────────────────────────────────────────────────┘
 */

declare(strict_types=1);

require __DIR__ . '/partials/legal.php';

$pageTitle = "Confidentialité · L'atelier du cil à cil";
$pageDescription = "Comment L'atelier du cil à cil traite vos données personnelles : "
    . "ce qui est collecté, pourquoi, combien de temps, et vos droits.";

require __DIR__ . '/partials/header.php';
?>

<section>
  <div class="wrap" style="max-width:720px;">
    <div class="section-head">
      <p class="eyebrow">Vos données</p>
      <h1>Confidentialité</h1>
      <p>Ce que ce site collecte, pourquoi, et ce que vous pouvez exiger.</p>
    </div>

    <div class="contact-card texte-legal" style="max-width:100%;">

      <h2>L'essentiel en trois phrases</h2>
      <p>
        Ce site ne vous piste pas. Il n'a ni statistiques de fréquentation, ni
        publicité, ni bouton de réseau social qui vous suit : aucun cookie ne
        vous est posé, et c'est pourquoi vous ne voyez aucun bandeau à
        accepter.
      </p>
      <p>
        Les seules informations recueillies sont celles que vous écrivez
        vous-même dans un formulaire, pour acheter une carte cadeau ou
        candidater à une formation. Elles servent à vous répondre, à rien
        d'autre, et ne sont jamais revendues.
      </p>

      <h2>Qui est responsable</h2>
      <p>
        <?= h($infos['nom_commercial']) ?>, <?= h($infos['responsable']) ?>,
        <?= h($infos['adresse']) ?>.<br>
        Pour toute question sur vos données :
        <a href="mailto:<?= h($infos['email']) ?>"><?= h($infos['email']) ?></a>.
      </p>

      <h2>Ce qui est collecté, et pourquoi</h2>

      <h3>Si vous offrez une carte cadeau</h3>
      <p>
        Votre nom et votre adresse e-mail, le nom et l'adresse e-mail de la
        personne à qui vous l'offrez, et le petit mot que vous écrivez si vous
        en écrivez un. Sans ces informations, la carte ne peut ni être créée ni
        être envoyée. Le paiement, lui, se fait chez Stripe : nous ne voyons ni
        ne conservons votre numéro de carte bancaire.
      </p>

      <h3>Si vous candidatez à une formation</h3>
      <p>
        Votre prénom, votre nom, votre adresse e-mail et votre numéro de
        téléphone si vous le donnez. Ils servent uniquement à permettre à
        Camille de vous recontacter.
      </p>

      <h3>Dans les deux cas</h3>
      <p>
        Votre adresse IP est enregistrée. Elle ne sert qu'à repérer un appareil
        qui enverrait le même formulaire en boucle, et elle est effacée au bout
        de trente jours.
      </p>

      <h2>Combien de temps</h2>
      <p>
        Une donnée ne se garde que le temps de l'usage qui a justifié sa
        collecte. Un nettoyage automatique s'en charge, sans intervention :
      </p>
      <ul class="services-list" style="grid-template-columns:1fr;">
        <li><strong>Adresses IP</strong> : effacées après 30 jours.</li>
        <li><strong>Candidatures aux formations</strong> : supprimées après 3 ans.</li>
        <li><strong>Cartes cadeaux</strong> : les noms, adresses e-mail et messages
            sont effacés 3 ans après l'expiration de la carte. Le montant et la
            date de la vente sont conservés, parce que la comptabilité l'exige.</li>
      </ul>

      <h2>Qui d'autre voit ces données</h2>
      <p>
        Trois prestataires techniques, chacun pour une tâche précise, et aucun
        n'a le droit de s'en servir pour autre chose :
      </p>
      <ul class="services-list" style="grid-template-columns:1fr;">
        <li><strong>Stripe</strong> : encaisse les paiements des cartes cadeaux.</li>
        <li><strong>Resend</strong> : envoie les e-mails (votre carte, votre accusé
            de réception).</li>
        <li><strong>Hostinger</strong> : héberge le site et sa base de données.</li>
      </ul>
      <p>
        En dehors d'eux, vos données ne sont transmises à personne. Elles ne
        sont ni vendues, ni échangées, ni utilisées pour de la publicité.
      </p>

      <h2>Les cookies</h2>
      <p>
        Le site ne dépose aucun cookie sur votre appareil pendant votre visite.
        Un seul cookie existe, et il ne concerne que Camille : celui qui la
        maintient connectée à son espace de suivi des cartes cadeaux. Il est
        strictement nécessaire au fonctionnement de cet espace, et ne demande
        donc aucun consentement.
      </p>

      <h2>Les liens vers d'autres sites</h2>
      <p>
        Les liens vers Instagram, TikTok et l'agenda de réservation Square sont
        de simples liens : rien n'est transmis à ces sites tant que vous ne
        cliquez pas. Si vous cliquez, ce sont leurs propres règles qui
        s'appliquent, pas les nôtres.
      </p>

      <h2>Vos droits</h2>
      <p>
        Vous pouvez à tout moment demander à <strong>consulter</strong> les
        données vous concernant, à les faire <strong>corriger</strong>, à les
        faire <strong>effacer</strong>, à en <strong>recevoir une copie</strong>,
        ou à <strong>vous opposer</strong> à leur utilisation.
      </p>
      <p>
        Il suffit d'écrire à
        <a href="mailto:<?= h($infos['email']) ?>"><?= h($infos['email']) ?></a>.
        La réponse vous parviendra sous un mois. Aucune justification n'est à
        fournir pour demander l'effacement de vos coordonnées.
      </p>
      <p>
        Si la réponse ne vous satisfait pas, vous pouvez saisir la CNIL,
        l'autorité française chargée de la protection des données :
        <a href="https://www.cnil.fr/fr/plaintes" target="_blank" rel="noopener">cnil.fr</a>.
      </p>

      <h2>La sécurité</h2>
      <p>
        Le site est servi en HTTPS : ce que vous écrivez dans un formulaire
        voyage chiffré. L'espace de suivi de Camille est protégé par mot de
        passe et se bloque après plusieurs essais ratés. Les formulaires sont
        protégés contre les envois automatisés.
      </p>

      <p class="petit-detail" style="margin-top:26px;">
        Dernière mise à jour : octobre 2026.
      </p>

    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
