<?php
/**
 * ===============================================================
 *  LE SOCLE DES TROIS PAGES LÉGALES
 * ===============================================================
 *
 *  Charge les informations de l'entreprise et fournit de quoi les
 *  afficher honnêtement : une information absente se voit, elle ne
 *  disparaît pas en silence.
 *
 *  Inclus par mentions-legales.php, confidentialite.php et cgv.php.
 */

declare(strict_types=1);

// Ce fichier vit dans partials/, les deux autres à la racine du site :
// dirname remonte d'un cran.
require_once dirname(__DIR__) . '/lib.php';

$infos = require dirname(__DIR__) . '/mentions-data.php';

/**
 * Affiche une information légale, ou dit clairement qu'elle manque.
 *
 * Laisser un blanc donnerait l'illusion d'une page complète. Afficher
 * « à compléter » est moins joli, mais c'est la vérité, et cela pousse
 * à finir le travail plutôt qu'à l'oublier.
 */
function info_legale(string $valeur, string $quoi): string
{
    $valeur = trim($valeur);
    if ($valeur !== '') {
        return h($valeur);
    }
    return '<span class="a-completer">information à compléter : ' . h($quoi) . '</span>';
}

/** Vrai si l'information est renseignée. */
function info_presente(string $valeur): bool
{
    return trim($valeur) !== '';
}

$pageImage = '/assets/logo.jpg';

// Ces pages n'ont aucun intérêt dans les résultats de recherche, mais
// elles doivent rester accessibles : on les laisse indexables, c'est
// d'ailleurs ce que vérifie une autorité qui contrôle un site.
?>
