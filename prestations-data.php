<?php
/**
 * ===============================================================
 *  LES PRESTATIONS — une seule liste, utilisée à deux endroits
 * ===============================================================
 *
 *  Ce fichier ne contient que des données (aucun HTML) : le nom de
 *  chaque prestation, sa catégorie, son prix et sa description.
 *
 *  Il est utilisé par :
 *    - prestations.php  (la liste complète, par catégorie)
 *    - prestation.php   (la fiche détaillée d'une seule prestation)
 *
 *  Ainsi, le jour où Camille change un prix ou ajoute un soin,
 *  il n'y a qu'un seul endroit à modifier.
 *
 *  IMPORTANT : ces prix doivent rester identiques à ceux affichés
 *  dans la section "Prestations & tarifs" de la page d'accueil.
 *  Le "slug" est la partie de l'adresse web (ex. rehaussement-de-cils) :
 *  ne pas le changer une fois la page en ligne, sinon un lien déjà
 *  partagé ou enregistré par une cliente ne fonctionnerait plus.
 *
 *  Le champ "description" est vide pour l'instant : le texte de
 *  chaque prestation sera ajouté par Camille, prestation par
 *  prestation. Tant qu'il est vide, la fiche affiche simplement
 *  qu'elle est à venir.
 */

declare(strict_types=1);

return [

    'cils' => [
        'titre' => 'Cils',
        'prestations' => [
            [
                'slug' => 'rehaussement-de-cils',
                'nom' => 'Réhaussement de cils',
                'prix' => '35€',
                'duree' => '1h',
                'image' => '/assets/prestations/rehaussement-de-cils.jpg',
                'description' => "Le réhaussement de cils se pratique sur vos cils naturels. "
                    . "La courbure de vos cils est révélée grâce à l'application d'une permanente "
                    . "sur les poils des cils. Ensuite, les cils sont teintés en noir afin d'avoir "
                    . "un effet mascara. Le réhaussement tient durant 5 semaines et la teinture "
                    . "durant 3 semaines. Il est aussi possible de réhausser les cils du bas si la "
                    . "longueur de vos cils le permet.",
            ],
            [
                'slug' => 'offre-duo-rehaussement',
                'nom' => 'Offre Duo réhaussement',
                'etiquette' => 'Duo',
                'prix' => '60€',
                'duree' => '1h15',
                'description' => "Il est possible de faire un réhaussement de cils avec une amie "
                    . "ou quelqu'un de votre famille grâce à un réhaussement de cils DUO.",
            ],
            [
                'slug' => 'teinture-cils',
                'nom' => 'Teinture',
                'prix' => '5€',
                'description' => '',
            ],
            [
                'slug' => 'lash-botox',
                'nom' => "Lash'Botox (Kératine)",
                'prix' => '5€',
                'description' => '',
            ],
            [
                'slug' => 'extension-cil-a-cil',
                'nom' => 'Extension de cils — Cil à cil',
                'prix' => '70€',
                'description' => '',
            ],
            [
                'slug' => 'extension-mixte',
                'nom' => 'Extension de cils — Mixte',
                'prix' => '75€',
                'description' => '',
            ],
            [
                'slug' => 'extension-volume-russe',
                'nom' => 'Extension de cils — Volume russe',
                'prix' => '80€',
                'description' => '',
            ],
            [
                'slug' => 'remplissage-cil-a-cil',
                'nom' => 'Remplissage — Cil à cil',
                'prix' => '40€',
                'description' => '',
            ],
            [
                'slug' => 'remplissage-mixte',
                'nom' => 'Remplissage — Mixte',
                'prix' => '45€',
                'description' => '',
            ],
            [
                'slug' => 'remplissage-volume-russe',
                'nom' => 'Remplissage — Volume russe',
                'prix' => '45€',
                'description' => '',
            ],
            [
                'slug' => 'depose-cliente',
                'nom' => 'Dépose cliente',
                'prix' => '10€',
                'description' => '',
            ],
        ],
    ],

    'sourcils-visage' => [
        'titre' => 'Sourcils & visage',
        'prestations' => [
            [
                'slug' => 'brow-lift-avec-teinture',
                'nom' => 'Brow lift (avec teinture)',
                'prix' => '45€',
                'description' => '',
            ],
            [
                'slug' => 'brow-lift-sans-epilation',
                'nom' => 'Brow lift sans épilation',
                'prix' => '45€',
                'description' => '',
            ],
            [
                'slug' => 'teinture-sourcils',
                'nom' => 'Teinture sourcils',
                'prix' => '10€',
                'description' => '',
            ],
            [
                'slug' => 'epilation-fil-sourcils',
                'nom' => 'Épilation au fil : sourcils',
                'prix' => '12€',
                'description' => '',
            ],
            [
                'slug' => 'epilation-fil-visage',
                'nom' => 'Épilation au fil : visage',
                'prix' => '18€',
                'description' => '',
            ],
            [
                'slug' => 'blanchiment-dentaire',
                'nom' => 'Blanchiment dentaire',
                'etiquette' => 'Nouveau',
                'prix' => 'Sur RDV',
                'description' => '',
            ],
            [
                'slug' => 'pose-strass-dentaire',
                'nom' => 'Pose de strass dentaire',
                'prix' => 'Sur RDV',
                'description' => '',
            ],
        ],
    ],

];
