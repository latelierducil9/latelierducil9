<?php
/**
 * ===============================================================
 *  EN-TÊTE COMMUN — inclus en haut de chaque page du site
 * ===============================================================
 *
 *  Avant d'inclure ce fichier, chaque page peut définir :
 *    $pageTitle       (le titre affiché dans l'onglet du navigateur)
 *    $pageDescription (la description utilisée par Google)
 *    $pageImage       (l'image montrée quand on partage le lien)
 *
 *  S'ils ne sont pas définis, des valeurs par défaut sont utilisées.
 *
 *  Toute modification du menu (ajouter/renommer un lien) se fait
 *  ICI UNE SEULE FOIS, et s'applique automatiquement à toutes les
 *  pages du site qui incluent ce fichier.
 */

declare(strict_types=1);

$pageTitle ??= "L'atelier du cil à cil — Cils, sourcils & beauté du regard, Salvizinet";
$pageDescription ??= 'Institut de cils, sourcils et blanchiment dentaire à Salvizinet. '
    . 'Réhaussement, extensions cil à cil, volume russe, brow lift. Réservation en ligne et carte cadeau.';

// Image utilisée quand une cliente partage un lien du site sur Instagram,
// Facebook ou WhatsApp. Chaque page peut la remplacer par sa propre photo.
$pageImage ??= '/assets/hero.jpg';

if (!function_exists('h')) {
    function h(?string $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * Adresse complète du site (https://latelierducilacil.com), reconstituée
 * à partir de la requête en cours. Les réseaux sociaux et Google exigent
 * des adresses complètes, pas des raccourcis comme /assets/hero.jpg.
 */
$siteBase = (static function (): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $hote = $_SERVER['HTTP_HOST'] ?? 'latelierducilacil.com';

    // On ne garde que les caractères valides d'un nom de domaine, pour qu'un
    // en-tête Host trafiqué ne puisse pas injecter d'adresse étrangère.
    $hote = preg_replace('/[^A-Za-z0-9.\-:]/', '', $hote) ?: 'latelierducilacil.com';

    return ($https ? 'https://' : 'http://') . $hote;
})();

$pageUrl = $siteBase . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$pageImageUrl = $siteBase . $pageImage;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($pageDescription) ?>">
<link rel="canonical" href="<?= h($pageUrl) ?>">

<!-- Icônes affichées dans l'onglet du navigateur et sur l'écran d'accueil des téléphones -->
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<meta name="theme-color" content="#FF216E">

<!-- Aperçu affiché quand une cliente partage un lien du site
     sur Instagram, Facebook, WhatsApp ou par SMS -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="L'atelier du cil à cil">
<meta property="og:locale" content="fr_FR">
<meta property="og:title" content="<?= h($pageTitle) ?>">
<meta property="og:description" content="<?= h($pageDescription) ?>">
<meta property="og:url" content="<?= h($pageUrl) ?>">
<meta property="og:image" content="<?= h($pageImageUrl) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($pageTitle) ?>">
<meta name="twitter:description" content="<?= h($pageDescription) ?>">
<meta name="twitter:image" content="<?= h($pageImageUrl) ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,500;1,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>

<header>
  <div class="nav">
    <a href="/" class="nav-brand">
      <img src="/assets/logo.jpg" alt="Logo L'atelier du cil à cil" width="500" height="500" decoding="async">
      <span>l'atelier du cil à cil</span>
    </a>
    <div class="nav-mobile-bar">
      <a class="nav-social" href="https://www.instagram.com/latelierducilacil" target="_blank" rel="noopener" aria-label="Instagram">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
      </a>
      <a class="nav-social" href="https://www.tiktok.com/@latelierducil4" target="_blank" rel="noopener" aria-label="TikTok">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M16.6 5.82s.51.5 0 0A4.278 4.278 0 0 1 15.54 3h-3.09v12.4a2.592 2.592 0 0 1-2.59 2.5c-1.42 0-2.6-1.16-2.6-2.6 0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.14 0 5.69-2.55 5.69-5.7V9.01a7.35 7.35 0 0 0 4.31 1.38V7.3s-1.88.09-3.24-1.48z"/></svg>
      </a>
      <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navLinks">Menu</button>
    </div>
    <ul class="nav-links" id="navLinks">
      <li><a href="/#apropos">L'atelier</a></li>
      <li><a href="/prestations.php">Prestations</a></li>
      <li><a href="/#formations">Formations</a></li>
      <li><a href="/#tendances">Tendances</a></li>
      <li><a href="/#carte-cadeau">Carte cadeau</a></li>
      <li><a href="/#contact">Contact</a></li>
      <li><a class="nav-cta" href="https://book.squareup.com/appointments/6gl1qk9h445g0l/location/LBS84WWAYQ6ES/services" target="_blank" rel="noopener">Prendre RDV</a></li>
    </ul>
  </div>
</header>

<main>
