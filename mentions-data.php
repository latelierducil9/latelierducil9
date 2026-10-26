<?php
/**
 * ===============================================================
 *  LES INFORMATIONS LÉGALES DE L'ENTREPRISE
 * ===============================================================
 *
 *  Rassemblées ici plutôt que recopiées dans les trois pages
 *  obligatoires : une information légale qui se contredit d'une page
 *  à l'autre est pire que pas d'information du tout.
 *
 *  ┌─────────────────────────────────────────────────────────────┐
 *  │  CE QUI EST VIDE N'EST PAS OUBLIÉ, IL MANQUE                │
 *  │                                                             │
 *  │  Les champs laissés vides ci-dessous sont des informations  │
 *  │  que seule Camille possède. Rien n'a été inventé : un faux  │
 *  │  SIRET ou un médiateur imaginaire l'exposerait bien plus    │
 *  │  qu'une ligne manquante.                                    │
 *  │                                                             │
 *  │  Les pages affichent clairement ce qui manque, pour que     │
 *  │  cela se voie et soit complété, plutôt que de passer        │
 *  │  inaperçu.                                                  │
 *  └─────────────────────────────────────────────────────────────┘
 */

declare(strict_types=1);

return [

    /* ---------------------------------------------------------------
     *  L'ENTREPRISE
     * ------------------------------------------------------------- */

    'nom_commercial' => "L'atelier du cil à cil",
    'responsable'    => 'Camille Pigeon',
    'adresse'        => '20 rue Camille Pariat, 42110 Feurs',
    'telephone'      => '06 12 42 26 57',
    'email'          => 'latelierducil9@gmail.com',

    // Le numéro à 14 chiffres, consultable sur l'avis de situation INSEE.
    // Vérifié : 14 chiffres, et la clé de contrôle du SIREN tombe juste.
    'siret' => '98027007800010',

    // Micro-entreprise, entreprise individuelle, SASU, EURL…
    'statut_juridique' => 'Micro-entreprise',

    // Camille ne facture pas la TVA (franchise en base). La formule
    // ci-dessous est imposée au mot près par l'article 293 B du code
    // général des impôts : elle ne se reformule pas, et son absence sur
    // un document commercial est sanctionnable.
    'tva' => 'TVA non applicable, article 293 B du CGI',

    /* ---------------------------------------------------------------
     *  L'HÉBERGEUR DU SITE
     * ------------------------------------------------------------- */

    //  À vérifier auprès d'Hostinger : ces coordonnées sont celles
    //  publiées par la société, elles peuvent changer.
    'hebergeur' => [
        'nom'       => 'Hostinger International Ltd',
        'adresse'   => '61 Lordou Vironos Street, 6023 Larnaca, Chypre',
        'telephone' => '+370 645 03378',
        'site'      => 'https://www.hostinger.fr',
    ],

    /* ---------------------------------------------------------------
     *  LA VENTE AUX PARTICULIERS
     * ------------------------------------------------------------- */

    //  Obligatoire pour qui vend à des particuliers (article L.612-1 du
    //  code de la consommation). Il faut adhérer à un médiateur agréé
    //  et indiquer son nom et son adresse, y compris son site.
    'mediateur' => [
        'nom'     => '',
        'adresse' => '',
        'site'    => '',
    ],

    //  L'assurance responsabilité civile professionnelle, à mentionner
    //  pour une prestation de service : le nom de l'assureur et
    //  l'étendue géographique de la garantie.
    'assurance' => [
        'nom' => 'MAIF',
        // Camille a donné l'assureur, pas l'étendue géographique de la
        // garantie. Le champ reste vide plutôt que d'affirmer une zone
        // inconnue : la phrase des CGV reste juste sans lui.
        'couverture' => '',
    ],

    //  Durée de validité d'une carte cadeau, telle qu'annoncée à
    //  l'achat. Reprise de config.php pour rester cohérente.
    'validite_carte_mois' => 12,
];
