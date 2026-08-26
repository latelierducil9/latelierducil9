<?php
/**
 * ===============================================================
 *  ESPACE DE SUIVI — réservé à l'institut
 * ===============================================================
 *
 *  La page où Camille retrouve les cartes cadeaux vendues :
 *  qui a acheté, pour qui, quel code, combien il reste dessus.
 *  Elle peut y solder une carte le jour du rendez-vous, déduire
 *  un montant partiel, ou renvoyer l'e-mail s'il s'est perdu.
 *
 *  Accès par mot de passe (celui de config.php). Comme cette page
 *  affiche des données de clientes, elle est protégée contre :
 *   - les essais de mots de passe en série (blocage après 5 essais) ;
 *   - les pièges qui feraient cliquer sur une action à l'insu de
 *     la personne connectée (jeton anti-piège sur chaque bouton).
 */

declare(strict_types=1);

require __DIR__ . '/carte.php';

/* ---------------------------------------------------------------
 *  SESSION
 * ------------------------------------------------------------- */

$enHttps = (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'httponly' => true,   // le cookie reste invisible aux scripts
    'samesite' => 'Strict',
    'secure'   => $enHttps,
]);
session_name('acc_suivi');
session_start();

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');          // empêche l'affichage dans un cadre piégé
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex, nofollow'); // jamais dans Google

$pdo      = bdd();
$connecte = ($_SESSION['connecte'] ?? false) === true;
$message  = '';
$erreur   = '';


/* ---------------------------------------------------------------
 *  DÉCONNEXION
 * ------------------------------------------------------------- */

if (isset($_GET['deconnexion'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php', true, 303);
    exit;
}


/* ---------------------------------------------------------------
 *  CONNEXION
 * ------------------------------------------------------------- */

/** Nombre d'essais ratés depuis cette adresse ces 15 dernières minutes. */
function essais_rates(PDO $pdo): int
{
    $req = $pdo->prepare(
        "SELECT COUNT(*) FROM connexions_ratees
          WHERE ip = ? AND quand > datetime('now', '-15 minutes')"
    );
    $req->execute([ip_visiteur()]);
    return (int) $req->fetchColumn();
}

if (!$connecte && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['mot_de_passe'])) {

    if (essais_rates($pdo) >= 5) {
        $erreur = "Trop d'essais. Merci de patienter un quart d'heure.";
    } elseif (!est_rempli('admin.mot_de_passe')) {
        $erreur = "Le mot de passe n'est pas encore configuré dans config.php.";
    } else {
        $fourni  = (string) $_POST['mot_de_passe'];
        $attendu = (string) reglage('admin.mot_de_passe');

        // hash_equals compare sans laisser deviner le bon mot de passe.
        if (hash_equals($attendu, $fourni)) {
            session_regenerate_id(true);   // évite le vol de session
            $_SESSION['connecte'] = true;
            $_SESSION['jeton']    = bin2hex(random_bytes(32));
            header('Location: admin.php', true, 303);
            exit;
        }

        $pdo->prepare('INSERT INTO connexions_ratees (ip, quand) VALUES (?, ?)')
            ->execute([ip_visiteur(), gmdate('Y-m-d H:i:s')]);
        journal('Mot de passe admin refusé (IP ' . ip_visiteur() . ')');
        $erreur = 'Mot de passe incorrect.';
    }
}


/* ---------------------------------------------------------------
 *  ACTIONS SUR UNE CARTE
 * ------------------------------------------------------------- */

if ($connecte && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {

    // Jeton anti-piège : l'action doit venir d'un vrai clic sur cette page.
    if (!hash_equals((string) ($_SESSION['jeton'] ?? ''), (string) ($_POST['jeton'] ?? ''))) {
        $erreur = 'Action refusée. Merci de recharger la page.';
    } else {
        $carte = carte_par_id((int) ($_POST['id'] ?? 0));

        if ($carte === null) {
            $erreur = 'Carte introuvable.';
        } else {
            switch ($_POST['action']) {

                case 'solder':
                    $pdo->prepare(
                        "UPDATE cartes SET restant_cents = 0, statut = 'utilisee', utilise_le = ?
                          WHERE id = ?"
                    )->execute([gmdate('Y-m-d H:i:s'), $carte['id']]);
                    $message = "Carte {$carte['code']} soldée.";
                    break;

                case 'deduire':
                    $euros = (string) ($_POST['montant'] ?? '');
                    if (!preg_match('/^[0-9]{1,4}([.,][0-9]{1,2})?$/', trim($euros))) {
                        $erreur = 'Montant à déduire invalide.';
                        break;
                    }
                    $cents   = (int) round((float) str_replace(',', '.', $euros) * 100);
                    $restant = (int) $carte['restant_cents'];

                    if ($cents <= 0 || $cents > $restant) {
                        $erreur = 'Le montant doit être compris entre 0 et '
                            . montant_lisible($restant) . '.';
                        break;
                    }

                    $nouveau = $restant - $cents;
                    $pdo->prepare(
                        'UPDATE cartes SET restant_cents = ?, statut = ?, utilise_le = ?
                          WHERE id = ?'
                    )->execute([
                        $nouveau,
                        $nouveau === 0 ? 'utilisee' : 'payee',
                        // La date d'utilisation ne doit être posée que lorsque la carte
                        // est vraiment épuisée : une déduction partielle la laisse active.
                        $nouveau === 0 ? gmdate('Y-m-d H:i:s') : null,
                        $carte['id'],
                    ]);
                    $message = montant_lisible($cents) . " déduits de {$carte['code']}"
                        . ($nouveau > 0 ? ' — il reste ' . montant_lisible($nouveau) . '.' : ' — carte soldée.');
                    break;

                case 'rouvrir':
                    $pdo->prepare(
                        "UPDATE cartes SET restant_cents = montant_cents, statut = 'payee',
                                utilise_le = NULL
                          WHERE id = ?"
                    )->execute([$carte['id']]);
                    $message = "Carte {$carte['code']} remise à son montant d'origine.";
                    break;

                case 'renvoyer':
                    $message = renvoyer_carte($carte)
                        ? "Carte {$carte['code']} renvoyée à {$carte['pour_email']}."
                        : '';
                    if ($message === '') {
                        $erreur = "L'envoi a échoué. Réessayez dans un instant.";
                    }
                    break;
            }
        }
    }
}


/* ---------------------------------------------------------------
 *  LECTURE DES DONNÉES À AFFICHER
 * ------------------------------------------------------------- */

$filtre    = (string) ($_GET['filtre'] ?? 'actives');
$recherche = nettoyer_ligne($_GET['q'] ?? '', 60);
$cartes    = [];
$stats     = ['mois' => 0, 'encours' => 0, 'actives' => 0, 'a_renvoyer' => 0];

if ($connecte) {

    $conditions = [];
    $valeurs    = [];

    switch ($filtre) {
        case 'utilisees': $conditions[] = "statut = 'utilisee'";  break;
        case 'attente':   $conditions[] = "statut = 'en_attente'"; break;
        case 'toutes':    break;
        default:          $conditions[] = "statut = 'payee'";      break;
    }

    if ($recherche !== '') {
        $conditions[] = '(code LIKE ? OR pour_nom LIKE ? OR de_nom LIKE ?'
                      . ' OR pour_email LIKE ? OR de_email LIKE ?)';
        $motif = '%' . $recherche . '%';
        array_push($valeurs, $motif, $motif, $motif, $motif, $motif);
    }

    $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
    $req   = $pdo->prepare("SELECT * FROM cartes$where ORDER BY id DESC LIMIT 300");
    $req->execute($valeurs);
    $cartes = $req->fetchAll();

    $stats['mois'] = (int) $pdo->query(
        "SELECT COALESCE(SUM(montant_cents),0) FROM cartes
          WHERE statut IN ('payee','utilisee')
            AND paye_le >= datetime('now','start of month')"
    )->fetchColumn();

    $stats['encours'] = (int) $pdo->query(
        "SELECT COALESCE(SUM(restant_cents),0) FROM cartes WHERE statut = 'payee'"
    )->fetchColumn();

    $stats['actives'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM cartes WHERE statut = 'payee'"
    )->fetchColumn();

    $stats['a_renvoyer'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM cartes WHERE statut = 'payee' AND email_envoye = 0"
    )->fetchColumn();
}

$jeton = (string) ($_SESSION['jeton'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Suivi des cartes cadeaux — L'atelier du cil à cil</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&family=Playfair+Display:wght@500&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box;}
  body{margin:0;background:#F7EDE9;color:#1A1416;font-family:'Poppins',sans-serif;
    font-weight:400;line-height:1.6;-webkit-font-smoothing:antialiased;}
  a{color:inherit;}
  .wrap{max-width:1120px;margin:0 auto;padding:26px;}

  /* --- Connexion --- */
  .centre{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:26px;}
  .boite{background:#fff;border:1px solid #ecdcd8;border-radius:14px;padding:44px 38px;
    max-width:420px;width:100%;}
  .boite h1{font-family:'Playfair Display',serif;font-weight:500;font-size:25px;margin:0 0 6px;text-align:center;}
  .boite p.sous{color:#6f5f63;font-weight:300;font-size:14px;text-align:center;margin:0 0 26px;}

  label{display:block;font-size:13px;font-weight:500;margin-bottom:7px;}
  input[type=password],input[type=text],input[type=search]{
    width:100%;padding:13px 15px;border:1px solid #ecdcd8;border-radius:10px;
    font-family:inherit;font-size:14px;background:#fff;color:#1A1416;}
  input:focus{outline:2px solid #FF216E;outline-offset:1px;border-color:transparent;}

  .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;
    padding:12px 22px;border-radius:50px;text-decoration:none;font-size:13px;font-weight:500;
    border:1.5px solid transparent;cursor:pointer;font-family:inherit;transition:.2s;}
  .btn-primary{background:#FF216E;color:#fff;}
  .btn-primary:hover{background:#D2135A;}
  .btn-ghost{border-color:#ecdcd8;color:#6f5f63;background:#fff;}
  .btn-ghost:hover{border-color:#FF216E;color:#FF216E;}
  .btn-petit{padding:7px 14px;font-size:12px;}

  /* --- En-tête --- */
  .tete{display:flex;justify-content:space-between;align-items:center;gap:16px;
    flex-wrap:wrap;margin-bottom:24px;}
  .tete h1{font-family:'Playfair Display',serif;font-weight:500;font-size:26px;margin:0;}
  .tete .sous{color:#6f5f63;font-weight:300;font-size:13px;}

  /* --- Chiffres --- */
  .chiffres{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:24px;}
  .chiffre{background:#fff;border:1px solid #ecdcd8;border-radius:12px;padding:18px 20px;}
  .chiffre .cle{font-size:11px;letter-spacing:1.6px;text-transform:uppercase;color:#6f5f63;}
  .chiffre .val{font-family:'Playfair Display',serif;font-size:27px;color:#FF216E;margin-top:5px;}
  .chiffre.alerte .val{color:#c9a24a;}

  /* --- Barre d'outils --- */
  .outils{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:18px;}
  .onglets{display:flex;gap:7px;flex-wrap:wrap;}
  .onglet{padding:8px 16px;border-radius:50px;background:#fff;border:1px solid #ecdcd8;
    font-size:13px;text-decoration:none;color:#6f5f63;}
  .onglet.actif{background:#FF216E;border-color:#FF216E;color:#fff;font-weight:500;}
  form.chercher{display:flex;gap:8px;margin-left:auto;}
  form.chercher input{min-width:210px;}

  /* --- Messages --- */
  .avis{border-radius:10px;padding:13px 18px;font-size:14px;margin-bottom:18px;}
  .avis.ok{background:#fff;border:1px solid #FF216E;}
  .avis.ko{background:#FBEFF0;border:1px solid #D2135A;color:#D2135A;}

  /* --- Cartes --- */
  .liste{display:flex;flex-direction:column;gap:12px;}
  .ligne{background:#fff;border:1px solid #ecdcd8;border-radius:12px;padding:18px 20px;
    display:grid;grid-template-columns:1.1fr 1.4fr .8fr auto;gap:18px;align-items:center;}
  .code{font-family:'Courier New',monospace;font-weight:700;font-size:15px;letter-spacing:1.4px;}
  .petit{font-size:12px;color:#6f5f63;font-weight:300;}
  .sommes{text-align:right;}
  .restant{font-family:'Playfair Display',serif;font-size:21px;color:#FF216E;}
  .barre{font-size:12px;color:#6f5f63;text-decoration:line-through;}
  .etiq{display:inline-block;font-size:11px;letter-spacing:.6px;text-transform:uppercase;
    padding:3px 10px;border-radius:50px;font-weight:500;}
  .etiq.active{background:#FBEFF0;color:#D2135A;}
  .etiq.finie{background:#F7EDE9;color:#6f5f63;}
  .etiq.attente{background:#fdf6e6;color:#a8842c;}
  .etiq.expiree{background:#f3f3f3;color:#8a8a8a;}
  .actions{display:flex;gap:7px;flex-wrap:wrap;justify-content:flex-end;}
  .actions form{display:flex;gap:6px;align-items:center;margin:0;}
  .actions input[type=text]{width:78px;padding:7px 10px;font-size:12px;}
  .vide{background:#fff;border:1px dashed #ecdcd8;border-radius:12px;padding:44px;
    text-align:center;color:#6f5f63;font-weight:300;}

  @media(max-width:820px){
    .ligne{grid-template-columns:1fr;gap:12px;}
    .sommes{text-align:left;}
    .actions{justify-content:flex-start;}
    form.chercher{margin-left:0;width:100%;}
    form.chercher input{flex:1;min-width:0;}
  }
</style>
</head>
<body>

<?php if (!$connecte): ?>

  <div class="centre">
    <div class="boite">
      <h1>Suivi des cartes cadeaux</h1>
      <p class="sous">L'atelier du cil à cil</p>

      <?php if ($erreur !== ''): ?>
        <div class="avis ko"><?= h($erreur) ?></div>
      <?php endif; ?>

      <form method="POST" action="admin.php">
        <label for="mdp">Mot de passe</label>
        <input type="password" name="mot_de_passe" id="mdp" required autofocus autocomplete="current-password">
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:18px;">Entrer</button>
      </form>
    </div>
  </div>

<?php else: ?>

  <div class="wrap">

    <div class="tete">
      <div>
        <h1>Cartes cadeaux</h1>
        <div class="sous">L'atelier du cil à cil — 53 chemin de Jalloussier, Salvizinet</div>
      </div>
      <a class="btn btn-ghost" href="admin.php?deconnexion=1">Se déconnecter</a>
    </div>

    <?php if (mode_simulation()): ?>
      <div class="avis ok"><strong>Mode simulation.</strong> Aucun paiement ni e-mail réel
        n'est traité tant que les clés Stripe ne sont pas renseignées.</div>
    <?php endif; ?>

    <?php if ($message !== ''): ?><div class="avis ok"><?= h($message) ?></div><?php endif; ?>
    <?php if ($erreur  !== ''): ?><div class="avis ko"><?= h($erreur)  ?></div><?php endif; ?>

    <div class="chiffres">
      <div class="chiffre">
        <div class="cle">Vendu ce mois-ci</div>
        <div class="val"><?= h(montant_lisible($stats['mois'])) ?></div>
      </div>
      <div class="chiffre">
        <div class="cle">Reste à honorer</div>
        <div class="val"><?= h(montant_lisible($stats['encours'])) ?></div>
      </div>
      <div class="chiffre">
        <div class="cle">Cartes actives</div>
        <div class="val"><?= $stats['actives'] ?></div>
      </div>
      <?php if ($stats['a_renvoyer'] > 0): ?>
      <div class="chiffre alerte">
        <div class="cle">E-mails à renvoyer</div>
        <div class="val"><?= $stats['a_renvoyer'] ?></div>
      </div>
      <?php endif; ?>
    </div>

    <div class="outils">
      <div class="onglets">
        <?php
        $onglets = [
            'actives'   => 'À utiliser',
            'utilisees' => 'Terminées',
            'attente'   => 'Paiements abandonnés',
            'toutes'    => 'Toutes',
        ];
        foreach ($onglets as $cle => $libelle) {
            $actif = $filtre === $cle || ($cle === 'actives' && !isset($onglets[$filtre]));
            echo '<a class="onglet' . ($actif ? ' actif' : '') . '" href="admin.php?filtre='
                . h($cle) . '">' . h($libelle) . '</a>';
        }
        ?>
      </div>

      <form class="chercher" method="GET" action="admin.php">
        <input type="hidden" name="filtre" value="toutes">
        <input type="search" name="q" placeholder="Chercher un code, un nom, un e-mail…"
               value="<?= h($recherche) ?>">
        <button type="submit" class="btn btn-ghost">Chercher</button>
      </form>
    </div>

    <?php if ($cartes === []): ?>
      <div class="vide">
        <?= $recherche !== ''
            ? 'Aucune carte ne correspond à cette recherche.'
            : 'Aucune carte dans cette catégorie pour le moment.' ?>
      </div>
    <?php else: ?>
      <div class="liste">
      <?php foreach ($cartes as $c):
          $restant  = (int) ($c['restant_cents'] ?? $c['montant_cents']);
          $montant  = (int) $c['montant_cents'];
          $expiree  = $c['expire_le'] && $c['expire_le'] < date('Y-m-d');
          $entamee  = $restant !== $montant && $restant > 0;

          if ($c['statut'] === 'en_attente')      { $etiq = ['attente', 'Non payée']; }
          elseif ($c['statut'] === 'utilisee')    { $etiq = ['finie',   'Terminée']; }
          elseif ($expiree)                       { $etiq = ['expiree', 'Expirée']; }
          else                                    { $etiq = ['active',  'À utiliser']; }
      ?>
        <div class="ligne">

          <div>
            <div class="code"><?= h($c['code']) ?></div>
            <div class="petit">
              <span class="etiq <?= $etiq[0] ?>"><?= $etiq[1] ?></span>
              <?php if ((int) $c['email_envoye'] === 0 && $c['statut'] === 'payee'): ?>
                <span class="etiq attente">E-mail non parti</span>
              <?php endif; ?>
            </div>
          </div>

          <div>
            <div>Pour <strong><?= h($c['pour_nom']) ?></strong></div>
            <div class="petit"><?= h($c['pour_email']) ?></div>
            <div class="petit">De <?= h($c['de_nom']) ?> — <?= h($c['de_email']) ?></div>
          </div>

          <div class="sommes">
            <?php if ($entamee): ?>
              <div class="restant"><?= h(montant_lisible($restant)) ?></div>
              <div class="barre"><?= h(montant_lisible($montant)) ?></div>
            <?php else: ?>
              <div class="restant"><?= h(montant_lisible($restant)) ?></div>
            <?php endif; ?>
            <div class="petit">
              <?php if ($c['paye_le']): ?>Payée le <?= h(date_lisible($c['paye_le'])) ?><br><?php endif; ?>
              <?php if ($c['expire_le']): ?>Jusqu'au <?= h(date_lisible($c['expire_le'])) ?><?php endif; ?>
            </div>
          </div>

          <div class="actions">
            <?php if ($c['statut'] === 'payee'): ?>

              <form method="POST" action="admin.php" onsubmit="return confirm('Déduire ce montant de la carte <?= h($c['code']) ?> ?');">
                <input type="hidden" name="jeton" value="<?= h($jeton) ?>">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <input type="hidden" name="action" value="deduire">
                <input type="text" name="montant" placeholder="€" inputmode="decimal"
                       aria-label="Montant à déduire de la carte <?= h($c['code']) ?>" required>
                <button type="submit" class="btn btn-ghost btn-petit">Déduire</button>
              </form>

              <form method="POST" action="admin.php" onsubmit="return confirm('Solder entièrement la carte <?= h($c['code']) ?> ?');">
                <input type="hidden" name="jeton" value="<?= h($jeton) ?>">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <input type="hidden" name="action" value="solder">
                <button type="submit" class="btn btn-primary btn-petit">Tout solder</button>
              </form>

              <form method="POST" action="admin.php">
                <input type="hidden" name="jeton" value="<?= h($jeton) ?>">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <input type="hidden" name="action" value="renvoyer">
                <button type="submit" class="btn btn-ghost btn-petit">Renvoyer l'e-mail</button>
              </form>

            <?php elseif ($c['statut'] === 'utilisee'): ?>

              <form method="POST" action="admin.php" onsubmit="return confirm('Remettre la carte <?= h($c['code']) ?> à son montant d\'origine ?');">
                <input type="hidden" name="jeton" value="<?= h($jeton) ?>">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <input type="hidden" name="action" value="rouvrir">
                <button type="submit" class="btn btn-ghost btn-petit">Annuler l'utilisation</button>
              </form>

            <?php else: ?>
              <span class="petit">Paiement jamais finalisé</span>
            <?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>

<?php endif; ?>

</body>
</html>
