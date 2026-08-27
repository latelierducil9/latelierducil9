<?php
/**
 * ===============================================================
 *  PLAN DU SITE (sitemap)
 * ===============================================================
 *
 *  Cette page n'est pas faite pour être lue par une visiteuse : elle
 *  liste toutes les pages du site dans un format que Google comprend,
 *  pour qu'il n'en oublie aucune.
 *
 *  Elle se construit toute seule à partir de prestations-data.php :
 *  le jour où Camille ajoute une prestation, elle apparaît ici sans
 *  qu'on ait rien à faire.
 */

declare(strict_types=1);

header('Content-Type: application/xml; charset=UTF-8');

$categories = require __DIR__ . '/prestations-data.php';

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$hote = preg_replace('/[^A-Za-z0-9.\-:]/', '', $_SERVER['HTTP_HOST'] ?? '') ?: 'latelierducilacil.com';
$base = ($https ? 'https://' : 'http://') . $hote;

// La date du fichier de données sert de « dernière mise à jour » pour les
// fiches : elle change dès que Camille modifie une prestation.
$majPrestations = @filemtime(__DIR__ . '/prestations-data.php') ?: time();
$majAccueil = @filemtime(__DIR__ . '/index.php') ?: time();

/** @var array<int, array{0:string, 1:int, 2:string}> $pages url, date, priorité */
$pages = [
    ['/', $majAccueil, '1.0'],
    ['/prestations.php', $majPrestations, '0.9'],
];

foreach ($categories as $categorie) {
    foreach ($categorie['prestations'] as $prestation) {
        $pages[] = ['/prestation.php?p=' . rawurlencode($prestation['slug']), $majPrestations, '0.8'];
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as [$chemin, $date, $priorite]): ?>
  <url>
    <loc><?= htmlspecialchars($base . $chemin, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></loc>
    <lastmod><?= gmdate('Y-m-d', $date) ?></lastmod>
    <priority><?= $priorite ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
