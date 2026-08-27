<?php
/**
 * ===============================================================
 *  BOÎTE À OUTILS COMMUNE — L'atelier du cil à cil
 * ===============================================================
 *
 *  Ce fichier ne s'affiche jamais tout seul. Il regroupe les
 *  fonctions utilisées par les autres pages (paiement, e-mail,
 *  espace admin) pour éviter d'écrire trois fois la même chose.
 *
 *  On y trouve : la lecture des réglages, l'accès au fichier des
 *  cartes cadeaux, la fabrication des codes, le nettoyage de ce
 *  que tapent les visiteurs, et l'appel aux services Stripe/Resend.
 */

declare(strict_types=1);

// Sécurité : si quelqu'un ouvre ce fichier directement dans son
// navigateur, on ne fait rien du tout.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

mb_internal_encoding('UTF-8');

const DOSSIER_DONNEES = __DIR__ . '/data';
const FICHIER_BDD     = DOSSIER_DONNEES . '/cartes.sqlite';
const FICHIER_LOG     = DOSSIER_DONNEES . '/erreurs.log';


/* ---------------------------------------------------------------
 *  1. LES RÉGLAGES (config.php)
 * ------------------------------------------------------------- */

/**
 * Lit config.php une seule fois et garde le résultat en mémoire.
 * Si config.php n'existe pas encore, on repart du modèle
 * config.example.php : le site tourne alors en mode simulation.
 */
function config(?string $chemin = null): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $fichier = __DIR__ . '/config.php';
    if (!is_file($fichier)) {
        $fichier = __DIR__ . '/config.example.php';
    }
    if (!is_file($fichier)) {
        page_erreur("Le fichier de configuration est introuvable.");
    }

    $config = require $fichier;
    if (!is_array($config)) {
        page_erreur("Le fichier de configuration est mal formé.");
    }
    return $config;
}

/**
 * Récupère un réglage en profondeur, ex. reglage('stripe.secret_key').
 */
function reglage(string $chemin, mixed $defaut = null): mixed
{
    $valeur = config();
    foreach (explode('.', $chemin) as $cle) {
        if (!is_array($valeur) || !array_key_exists($cle, $valeur)) {
            return $defaut;
        }
        $valeur = $valeur[$cle];
    }
    return $valeur;
}

/**
 * Un réglage est « rempli » s'il ne vaut pas À_REMPLIR ni vide.
 */
function est_rempli(string $chemin): bool
{
    $v = reglage($chemin);
    return is_string($v) && $v !== '' && !str_starts_with($v, 'À_REMPLIR');
}

/**
 * Le site tourne-t-il sur un ordinateur de test, et non en ligne ?
 *
 * Sert de garde-fou : certaines facilités réservées aux essais ne
 * doivent jamais s'activer sur le site public.
 */
function site_local(): bool
{
    $hote = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $hote = explode(':', $hote)[0];

    return $hote === 'localhost'
        || $hote === '127.0.0.1'
        || $hote === '::1'
        || str_ends_with($hote, '.local')
        || str_ends_with($hote, '.test');
}

/**
 * MODE SIMULATION : tant que la clé Stripe n'est pas renseignée,
 * on peut tester tout le parcours sans paiement ni e-mail réels.
 *
 * ATTENTION : ce mode délivre de vraies cartes cadeaux sans faire
 * payer. Il est donc strictement réservé aux essais sur un ordinateur
 * de test. Sur le site en ligne, il reste désactivé même si la clé
 * Stripe manque — sinon n'importe qui pourrait repartir avec une carte
 * gratuite. Dans ce cas, c'est la carte cadeau qui est mise en pause,
 * pas la sécurité (voir create-checkout.php).
 */
function mode_simulation(): bool
{
    return !est_rempli('stripe.secret_key') && site_local();
}


/* ---------------------------------------------------------------
 *  2. LE FICHIER DES CARTES CADEAUX (base SQLite)
 * ------------------------------------------------------------- */

/**
 * Ouvre (et crée au premier passage) la base des cartes cadeaux.
 * C'est un simple fichier posé dans data/, rien à configurer.
 */
function bdd(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    if (!is_dir(DOSSIER_DONNEES) && !@mkdir(DOSSIER_DONNEES, 0755, true)) {
        journal("Impossible de créer le dossier " . DOSSIER_DONNEES);
        page_erreur("Le site n'arrive pas à enregistrer la commande.");
    }

    try {
        $pdo = new PDO('sqlite:' . FICHIER_BDD, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // Évite les blocages si deux achats arrivent en même temps.
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        creer_tables($pdo);
    } catch (Throwable $e) {
        journal('Ouverture base impossible : ' . $e->getMessage());
        page_erreur("Le site n'arrive pas à enregistrer la commande.");
    }

    return $pdo;
}

function creer_tables(PDO $pdo): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS cartes (
            id                INTEGER PRIMARY KEY AUTOINCREMENT,
            code              TEXT    NOT NULL UNIQUE,
            montant_cents     INTEGER NOT NULL,
            restant_cents     INTEGER,
            de_nom            TEXT    NOT NULL,
            de_email          TEXT    NOT NULL,
            pour_nom          TEXT    NOT NULL,
            pour_email        TEXT    NOT NULL,
            message           TEXT    NOT NULL DEFAULT '',
            statut            TEXT    NOT NULL DEFAULT 'en_attente',
            stripe_session_id TEXT,
            email_envoye      INTEGER NOT NULL DEFAULT 0,
            ip                TEXT,
            cree_le           TEXT    NOT NULL,
            paye_le           TEXT,
            utilise_le        TEXT,
            expire_le         TEXT
        )
    SQL);
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_session ON cartes (stripe_session_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_statut  ON cartes (statut)');

    // Journal des tentatives de connexion ratées à l'espace admin,
    // pour bloquer quelqu'un qui essaierait des mots de passe en série.
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS connexions_ratees (
            id    INTEGER PRIMARY KEY AUTOINCREMENT,
            ip    TEXT NOT NULL,
            quand TEXT NOT NULL
        )
    SQL);

    // Mise à jour douce : ajoute la colonne du solde restant si une
    // base créée avant cette version ne l'a pas encore.
    $colonnes = $pdo->query('PRAGMA table_info(cartes)')->fetchAll();
    if (!in_array('restant_cents', array_column($colonnes, 'name'), true)) {
        $pdo->exec('ALTER TABLE cartes ADD COLUMN restant_cents INTEGER');
        $pdo->exec('UPDATE cartes SET restant_cents = montant_cents WHERE restant_cents IS NULL');
    }
}

/**
 * Fabrique un code de carte cadeau lisible et impossible à deviner,
 * du type ACC-7K2P-9XQ4. Les lettres prêtant à confusion (O, 0, I, 1)
 * sont volontairement exclues pour éviter les erreurs de recopie.
 */
function code_unique(PDO $pdo): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($essai = 0; $essai < 20; $essai++) {
        $lettres = '';
        for ($i = 0; $i < 8; $i++) {
            $lettres .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $code = 'ACC-' . substr($lettres, 0, 4) . '-' . substr($lettres, 4, 4);

        $req = $pdo->prepare('SELECT 1 FROM cartes WHERE code = ?');
        $req->execute([$code]);
        if (!$req->fetchColumn()) {
            return $code;
        }
    }
    journal('Impossible de générer un code unique après 20 essais.');
    page_erreur("Le site n'arrive pas à créer la carte cadeau.");
}


/* ---------------------------------------------------------------
 *  3. NETTOYAGE DE CE QUE TAPENT LES VISITEURS
 * ------------------------------------------------------------- */

/** Retire les caractères invisibles dangereux. */
function sans_caracteres_de_controle(string $v): string
{
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
}

/** Nettoie un champ sur une seule ligne (nom, e-mail) et le raccourcit. */
function nettoyer_ligne(mixed $v, int $max = 100): string
{
    $v = is_string($v) ? $v : '';
    $v = str_replace(["\r", "\n"], ' ', $v);
    $v = sans_caracteres_de_controle($v);
    $v = preg_replace('/\s+/u', ' ', $v) ?? '';
    return mb_substr(trim($v), 0, $max);
}

/** Nettoie un texte libre (le petit mot) en gardant les retours à la ligne. */
function nettoyer_texte(mixed $v, int $max = 500): string
{
    $v = is_string($v) ? $v : '';
    $v = str_replace("\r\n", "\n", $v);
    $v = sans_caracteres_de_controle($v);
    return mb_substr(trim($v), 0, $max);
}

/** Vérifie qu'une adresse e-mail est plausible. */
function email_valide(string $email): bool
{
    return $email !== ''
        && mb_strlen($email) <= 254
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Protège un texte avant de l'afficher dans une page HTML. */
function h(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Adresse IP du visiteur (sert uniquement au garde-fou anti-abus). */
function ip_visiteur(): string
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? 'inconnue');
}


/* ---------------------------------------------------------------
 *  4. APPELS AUX SERVICES EXTÉRIEURS (Stripe, Resend)
 * ------------------------------------------------------------- */

/**
 * Envoie une requête à une API et renvoie [code HTTP, réponse décodée].
 * On passe par cURL directement : aucune librairie à installer, ce qui
 * est indispensable sur un hébergement mutualisé sans Composer.
 */
function appel_api(
    string $url,
    array $entetes,
    ?string $corps = null,
    string $methode = 'POST'
): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $methode,
        CURLOPT_HTTPHEADER     => $entetes,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    if ($corps !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $corps);
    }

    $reponse = curl_exec($ch);
    $code    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erreur  = curl_error($ch);
    curl_close($ch);

    if ($reponse === false) {
        journal("Appel à $url impossible : $erreur");
        return [0, []];
    }

    $decode = json_decode((string)$reponse, true);
    return [$code, is_array($decode) ? $decode : []];
}

/** Appel à l'API Stripe (données envoyées au format formulaire). */
function stripe(string $chemin, array $donnees = [], ?string $cleIdempotence = null): array
{
    $entetes = [
        'Authorization: Bearer ' . reglage('stripe.secret_key'),
        'Content-Type: application/x-www-form-urlencoded',
    ];
    if ($cleIdempotence !== null) {
        // Garantit qu'un double clic ne crée pas deux paiements.
        $entetes[] = 'Idempotency-Key: ' . $cleIdempotence;
    }

    return appel_api(
        'https://api.stripe.com/v1/' . ltrim($chemin, '/'),
        $entetes,
        $donnees === [] ? null : http_build_query($donnees),
        $donnees === [] ? 'GET' : 'POST'
    );
}


/**
 * Envoie un e-mail via Resend (données envoyées au format JSON).
 * Renvoie true si Resend a bien accepté le message.
 *
 * En mode simulation, rien n'est envoyé : le message est simplement
 * écrit dans data/erreurs.log pour qu'on puisse vérifier son contenu.
 */
function envoyer_email(string $destinataire, string $sujet, string $html, string $texte): bool
{
    if (!est_rempli('resend.api_key')) {
        journal("SIMULATION e-mail -> $destinataire | sujet : $sujet");
        return true;
    }

    $corps = [
        'from'    => (string) reglage('resend.expediteur'),
        'to'      => [$destinataire],
        'subject' => $sujet,
        'html'    => $html,
        'text'    => $texte,
    ];

    // Les réponses des clients arrivent directement chez l'institut.
    if (est_rempli('resend.copie_institut')) {
        $corps['reply_to'] = [(string) reglage('resend.copie_institut')];
    }

    [$httpCode, $reponse] = appel_api(
        'https://api.resend.com/emails',
        [
            'Authorization: Bearer ' . reglage('resend.api_key'),
            'Content-Type: application/json',
        ],
        json_encode($corps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
    );

    if ($httpCode < 200 || $httpCode >= 300) {
        journal(
            "Envoi e-mail à $destinataire refusé (HTTP $httpCode) : "
            . ($reponse['message'] ?? 'réponse vide')
        );
        return false;
    }

    return true;
}


/* ---------------------------------------------------------------
 *  5. AFFICHAGE
 * ------------------------------------------------------------- */

/** 3500 (centimes) devient « 35 € ». */
function montant_lisible(int $cents): string
{
    return $cents % 100 === 0
        ? number_format($cents / 100, 0, ',', ' ') . ' €'
        : number_format($cents / 100, 2, ',', ' ') . ' €';
}

/** « 2027-08-31 » devient « 31 août 2027 ». */
function date_lisible(?string $date): string
{
    if (!$date) {
        return '';
    }
    $t = strtotime($date);
    if ($t === false) {
        return '';
    }
    $mois = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
    ];
    return (int) date('j', $t) . ' ' . $mois[(int) date('n', $t)] . ' ' . date('Y', $t);
}


/* ---------------------------------------------------------------
 *  6. ERREURS
 * ------------------------------------------------------------- */

/** Écrit le détail technique d'un problème dans data/erreurs.log. */
function journal(string $message): void
{
    if (!is_dir(DOSSIER_DONNEES)) {
        @mkdir(DOSSIER_DONNEES, 0755, true);
    }
    @file_put_contents(
        FICHIER_LOG,
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

/**
 * Affiche une page d'erreur soignée, aux couleurs du site, et arrête tout.
 * Le visiteur ne voit jamais le détail technique : il reste dans le log.
 */
function page_erreur(string $message, int $codeHttp = 400): never
{
    if (!headers_sent()) {
        http_response_code($codeHttp);
        header('Content-Type: text/html; charset=UTF-8');
    }
    $msg = h($message);
    echo <<<HTML
    <!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Un souci est survenu — L'atelier du cil à cil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
      body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
        background:#F7EDE9;color:#1A1416;font-family:'Poppins',sans-serif;padding:26px;}
      .carte{background:#fff;border:1px solid #ecdcd8;border-radius:14px;
        padding:44px 38px;max-width:520px;text-align:center;}
      h1{font-family:'Playfair Display',serif;font-weight:500;font-size:26px;margin:0 0 14px;}
      p{color:#6f5f63;font-weight:300;line-height:1.6;margin:0 0 26px;}
      a{display:inline-flex;padding:14px 26px;border-radius:50px;background:#FF216E;
        color:#fff;text-decoration:none;font-size:14px;font-weight:500;}
      a:hover{background:#D2135A;}
    </style></head><body>
      <div class="carte">
        <h1>Un souci est survenu</h1>
        <p>$msg<br>Aucun montant n'a été débité. Merci de réessayer dans un instant.</p>
        <a href="/#carte-cadeau">Revenir à la carte cadeau</a>
      </div>
    </body></html>
    HTML;
    exit;
}
