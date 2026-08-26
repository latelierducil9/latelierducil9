<?php
/**
 * ===============================================================
 *  MODÈLE DE CONFIGURATION — L'atelier du cil à cil
 * ===============================================================
 *
 *  Ce fichier est un EXEMPLE. Il ne contient aucun secret et il est
 *  publié sur GitHub, c'est normal.
 *
 *  POUR L'UTILISER :
 *    1. Faire une copie de ce fichier et la nommer  config.php
 *    2. Remplacer chaque valeur « À_REMPLIR » par la vraie valeur
 *    3. Ne jamais publier config.php (il est déjà protégé par .gitignore)
 *
 *  Tant que les clés ne sont pas remplies, le site fonctionne en
 *  MODE SIMULATION : on peut tester le parcours complet sans qu'aucun
 *  paiement réel ni aucun e-mail réel ne parte.
 */

return [

    // -----------------------------------------------------------
    //  ADRESSE DU SITE
    // -----------------------------------------------------------
    // L'adresse publique du site, sans le / final.
    // Sert à construire les liens de retour après le paiement Stripe.
    // En test sur votre ordinateur : 'http://localhost:8000'
    'site_url' => 'https://latelierducilacil.fr',


    // -----------------------------------------------------------
    //  STRIPE — l'encaissement des paiements
    // -----------------------------------------------------------
    // À récupérer sur dashboard.stripe.com > Développeurs > Clés API.
    // Commencer avec les clés de TEST (elles commencent par sk_test_),
    // puis basculer sur les clés réelles (sk_live_) une fois tout validé.
    'stripe' => [
        'secret_key' => 'À_REMPLIR',   // sk_test_... puis sk_live_...
    ],


    // -----------------------------------------------------------
    //  RESEND — l'envoi des e-mails de carte cadeau
    // -----------------------------------------------------------
    // À récupérer sur resend.com > API Keys.
    // Important : le domaine de l'adresse « expediteur » doit être
    // vérifié dans Resend, sinon les e-mails partiront en spam.
    'resend' => [
        'api_key'     => 'À_REMPLIR',  // re_...
        'expediteur'  => "L'atelier du cil à cil <cartecadeau@latelierducilacil.fr>",
        // Adresse de l'institut : reçoit une copie de chaque vente.
        'copie_institut' => 'À_REMPLIR',
    ],


    // -----------------------------------------------------------
    //  RÈGLES DES CARTES CADEAUX
    // -----------------------------------------------------------
    'carte_cadeau' => [
        // Garde-fous : le serveur refusera tout montant hors de ces bornes,
        // même si quelqu'un tente de tricher depuis son navigateur.
        'montant_min'   => 10,   // en euros
        'montant_max'   => 200,  // en euros
        // Durée de validité affichée sur la carte, en mois.
        'validite_mois' => 12,
    ],


    // -----------------------------------------------------------
    //  ESPACE ADMIN (pour Camille)
    // -----------------------------------------------------------
    // Mot de passe pour consulter la liste des cartes vendues.
    // À choisir long et unique — il protège des données clients.
    'admin' => [
        'mot_de_passe' => 'À_REMPLIR',
    ],


    // -----------------------------------------------------------
    //  COORDONNÉES DE L'INSTITUT (affichées sur la carte cadeau)
    // -----------------------------------------------------------
    'institut' => [
        'nom'       => "L'atelier du cil à cil",
        'adresse'   => '13 place Garibaldi, 42000 Saint-Étienne',
        'telephone' => 'À_REMPLIR',
        'instagram' => 'https://www.instagram.com/latelierducilacil',
        // Lien de prise de rendez-vous Square (Saint-Étienne)
        'reservation' => 'https://book.squareup.com/appointments/6gl1qk9h445g0l/location/LBS84WWAYQ6ES/services',
    ],

];
