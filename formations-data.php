<?php
/**
 * ===============================================================
 *  LES FORMATIONS — une seule liste, utilisée à trois endroits
 * ===============================================================
 *
 *  Ce fichier ne contient que des données (aucun HTML) : le nom de
 *  chaque formation, sa durée, son tarif et son programme.
 *
 *  Il est utilisé par :
 *    - formations.php     (la page publique et son formulaire)
 *    - demande-devis.php  (pour vérifier la formation demandée)
 *    - devis.php          (pour composer le devis envoyé)
 *
 *  ┌─────────────────────────────────────────────────────────────┐
 *  │  LE TARIF NE VIENT JAMAIS DU NAVIGATEUR                     │
 *  │                                                             │
 *  │  Le prix affiché sur la page sert seulement à informer la   │
 *  │  candidate. Celui qui compte est lu ICI, sur le serveur,    │
 *  │  au moment d'enregistrer la demande. Quelqu'un qui          │
 *  │  modifierait la page dans son navigateur ne changerait      │
 *  │  donc rien au devis reçu.                                   │
 *  └─────────────────────────────────────────────────────────────┘
 *
 *  Les tarifs sont en CENTIMES (49500 = 495 €), comme partout
 *  ailleurs sur le site : cela évite les erreurs d'arrondi.
 *
 *  ─────────────────────────────────────────────────────────────
 *  CE QUI VIENT DE CAMILLE, CE QUI RESTE À REMPLIR
 *  ─────────────────────────────────────────────────────────────
 *
 *  Sont exacts, car déjà publiés sur la page d'accueil :
 *    - les cinq formations proposées
 *    - le tarif de 495 €, kit compris
 *    - la durée « deux jours (ou plus) »
 *
 *  Sont VOLONTAIREMENT VIDES, en attendant ses réponses :
 *    - 'resume'    : la phrase de présentation de chaque formation
 *    - 'programme' : ce qui est réellement enseigné
 *
 *  Rien n'a été inventé à sa place : une formation est un
 *  engagement commercial, annoncer un contenu approximatif
 *  l'exposerait. Tant qu'un champ est vide, la page ne l'affiche
 *  tout simplement pas.
 */

declare(strict_types=1);

return [

    [
        'slug'       => 'rehaussement-de-cils',
        'nom'        => 'Réhaussement de cils',
        'prix_cents' => 49500,
        'duree'      => '2 jours (ou plus)',
        'resume'     => '',
        'programme'  => [],
    ],

    [
        'slug'       => 'extension-cil-a-cil',
        'nom'        => "Pose d'extensions cil à cil",
        'prix_cents' => 49500,
        'duree'      => '2 jours (ou plus)',
        'resume'     => '',
        'programme'  => [],
    ],

    [
        'slug'       => 'extension-volume-russe',
        'nom'        => "Pose d'extensions volume russe",
        'prix_cents' => 49500,
        'duree'      => '2 jours (ou plus)',
        'resume'     => '',
        'programme'  => [],
    ],

    [
        'slug'       => 'brow-lift',
        'nom'        => 'Brow lift',
        'prix_cents' => 49500,
        'duree'      => '2 jours (ou plus)',
        'resume'     => '',
        'programme'  => [],
    ],

    [
        'slug'       => 'epilation-au-fil',
        'nom'        => 'Épilation au fil',
        'prix_cents' => 49500,
        'duree'      => '2 jours (ou plus)',
        'resume'     => '',
        'programme'  => [],
    ],

];
