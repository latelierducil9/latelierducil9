<?php
/**
 * ===============================================================
 *  LES FORMATIONS — une seule liste, utilisée à deux endroits
 * ===============================================================
 *
 *  Ce fichier ne contient que des données (aucun HTML) : le nom de
 *  chaque formation, sa durée et son tarif de départ.
 *
 *  Il est utilisé par :
 *    - formations.php          (la page publique et son formulaire)
 *    - demande-formation.php   (pour vérifier la formation demandée)
 *
 *  ┌─────────────────────────────────────────────────────────────┐
 *  │  LE TARIF NE VIENT JAMAIS DU NAVIGATEUR                     │
 *  │                                                             │
 *  │  Le prix affiché sur la page sert seulement à informer la   │
 *  │  candidate. Celui qui compte est lu ICI, sur le serveur,    │
 *  │  au moment d'enregistrer la demande. Quelqu'un qui          │
 *  │  modifierait la page dans son navigateur ne changerait      │
 *  │  donc rien à ce que Camille reçoit.                         │
 *  └─────────────────────────────────────────────────────────────┘
 *
 *  Les tarifs sont en CENTIMES (35500 = 355 €), comme partout
 *  ailleurs sur le site : cela évite les erreurs d'arrondi.
 *
 *  ─────────────────────────────────────────────────────────────
 *  CE QUI VIENT DE CAMILLE, CE QUI RESTE À REMPLIR
 *  ─────────────────────────────────────────────────────────────
 *
 *  Viennent d'elle, par message du 14 septembre :
 *    - les cinq intitulés ci-dessous, recopiés tels qu'elle les a
 *      écrits
 *    - « Formation à partir de 355€ »
 *    - « les formations se font à Feurs et à saint Étienne »
 *
 *  Restent VOLONTAIREMENT VIDES, en attendant ses réponses :
 *    - 'resume'    : la phrase qui présente chaque formation
 *    - 'programme' : ce qui est réellement enseigné
 *    - 'duree'     : elle n'a pas confirmé la durée de chacune
 *
 *  Elle seule les connaît. Une formation est un engagement
 *  commercial : annoncer un contenu approximatif l'exposerait. Tant
 *  qu'un champ est vide, la page ne l'affiche tout simplement pas.
 *
 *  355 € est présenté comme un prix de DÉPART (« à partir de »).
 *  Si les formations ont des tarifs différents, il suffit de
 *  corriger la ligne 'prix_cents' concernée.
 */

declare(strict_types=1);

return [

    [
        'slug'       => 'rehaussement-de-cils-et-teinture',
        'nom'        => 'Réhaussement de cils et teinture',
        'prix_cents' => 35500,
        'duree'      => '',
        'resume'     => '',
        'programme'  => [],
    ],

    [
        'slug'       => 'brow-lift-epilation-et-teinture',
        'nom'        => 'Brow lift, épilation et teinture',
        'prix_cents' => 35500,
        'duree'      => '',
        'resume'     => '',
        'programme'  => [],
    ],

    [
        'slug'       => 'epilation-au-fil',
        'nom'        => 'Épilation au fil',
        'prix_cents' => 35500,
        'duree'      => '',
        'resume'     => '',
        'programme'  => [],
    ],

    [
        'slug'       => 'extension-de-cil-a-cil',
        'nom'        => 'Extension de cil à cil',
        'prix_cents' => 35500,
        'duree'      => '',
        'resume'     => '',
        'programme'  => [],
    ],

    [
        'slug'       => 'extension-de-cil-volume-russe',
        'nom'        => 'Extension de cil volume russe',
        'prix_cents' => 35500,
        'duree'      => '',
        'resume'     => '',
        'programme'  => [],
    ],

];
