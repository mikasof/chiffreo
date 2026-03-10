<?php

declare(strict_types=1);

/**
 * Détails de présentation des besoins techniques pour le devis
 *
 * Ce fichier est COMPLÉMENTAIRE à travaux_definitions.php.
 * Il contient UNIQUEMENT les règles de présentation / affichage devis.
 *
 * Les besoins techniques (type, code, quantité, specifications...) restent
 * définis dans travaux_definitions.php.
 *
 * Champs disponibles :
 * - visible_devis     : bool
 * - mode_facturation  : 'ligne' | 'inclus'
 * - categorie_devis   : string
 * - ordre_affichage   : int
 * - label_devis       : string|null
 * - regroupement      : string|null
 *
 * Règles :
 * - 'ligne'  = affiché sur le devis
 * - 'inclus' = masqué / inclus dans un autre poste
 *
 * IMPORTANT :
 * Le regroupement NE DOIT JAMAIS être la seule clé de fusion.
 * La clé réelle de fusion côté moteur devrait intégrer a minima :
 *   (type + code + specifications_normalisées + categorie_devis + mode_facturation + regroupement)
 */

return [
    '_meta' => [
        'version' => '1.0.0',
        'description' => 'Détails de présentation des besoins techniques pour le devis',

        'defaults' => [
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'categorie_devis' => 'divers',
            'ordre_affichage' => 100,
            'label_devis' => null,
            'regroupement' => null,
        ],

        'categories_devis' => [
            'tableau' => ['label' => 'Tableau électrique', 'ordre' => 10],
            'distribution' => ['label' => 'Distribution électrique', 'ordre' => 20],
            'eclairage' => ['label' => 'Éclairage', 'ordre' => 30],
            'securite' => ['label' => 'Sécurité', 'ordre' => 40],
            'ventilation' => ['label' => 'Ventilation', 'ordre' => 50],
            'irve' => ['label' => 'Borne de recharge', 'ordre' => 60],
            'communication' => ['label' => 'Communication', 'ordre' => 70],
            'chauffage' => ['label' => 'Chauffage', 'ordre' => 80],
            'prestations' => ['label' => 'Prestations', 'ordre' => 90],
            'divers' => ['label' => 'Divers', 'ordre' => 100],

            // Main d'œuvre ventilée par poste
            'mo_tableau' => ['label' => 'Main d\'œuvre - Tableau', 'ordre' => 110],
            'mo_distribution' => ['label' => 'Main d\'œuvre - Distribution', 'ordre' => 120],
            'mo_eclairage' => ['label' => 'Main d\'œuvre - Éclairage', 'ordre' => 130],
            'mo_securite' => ['label' => 'Main d\'œuvre - Sécurité', 'ordre' => 140],
            'mo_ventilation' => ['label' => 'Main d\'œuvre - Ventilation', 'ordre' => 150],
            'mo_irve' => ['label' => 'Main d\'œuvre - IRVE', 'ordre' => 160],
            'mo_communication' => ['label' => 'Main d\'œuvre - Communication', 'ordre' => 170],
            'mo_chauffage' => ['label' => 'Main d\'œuvre - Chauffage', 'ordre' => 180],
        ],

        'modes_facturation' => [
            'ligne' => 'Ligne affichée sur le devis',
            'inclus' => 'Inclus dans un autre poste',
        ],

        // Agrégation automatique autorisée uniquement pour les types peu lisibles côté client
        'regroupement_par_type' => [
            'consommable' => 'auto',
            'fourniture' => 'auto',
            'materiel' => null,
            'main_oeuvre' => null,
            'service' => null,
            'prestation' => null,
        ],
    ],

    // =========================================================================
    // TABLEAU ÉLECTRIQUE
    // =========================================================================

    'coffret_tableau' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 10,
        'label_devis' => 'Coffret électrique',
    ],

    'disjoncteur_branchement' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 20,
        'label_devis' => 'Disjoncteur de branchement',
    ],

    'interrupteur_differentiel' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 30,
        'label_devis' => 'Interrupteur différentiel 30mA',
    ],

    'id_type_ac_40a' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 31,
        'label_devis' => 'Interrupteur différentiel type AC 40A',
    ],

    'disjoncteur_divisionnaire' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 40,
        'label_devis' => 'Disjoncteur divisionnaire',
    ],

    'disjoncteur_32a' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 41,
        'label_devis' => 'Disjoncteur 32A cuisson',
    ],

    'disjoncteur_20a' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 42,
        'label_devis' => 'Disjoncteur 20A',
    ],

    'disjoncteur_2a' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 43,
        'label_devis' => 'Disjoncteur 2A VMC',
    ],

    'parafoudre' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 50,
        'label_devis' => 'Parafoudre',
    ],

    'contacteur_hphc' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 60,
        'label_devis' => 'Contacteur heures creuses',
    ],

    'telerupteur_16a' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 70,
        'label_devis' => 'Télérupteur',
    ],

    'delesteur' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 80,
        'label_devis' => 'Délesteur',
    ],

    'schema_unifilaire' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 90,
        'label_devis' => 'Schéma unifilaire',
    ],

    'lot_etiquettes' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 91,
    ],

    'cable_alimentation_tableau' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 92,
    ],

    'bornes_connexion' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'tableau',
        'ordre_affichage' => 93,
        'regroupement' => 'consommables_tableau',
    ],

    // =========================================================================
    // DISTRIBUTION
    // =========================================================================

    'prise_2pt_complete' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'distribution',
        'ordre_affichage' => 10,
        'label_devis' => 'Prise de courant 2P+T',
    ],

    'boite_sol' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'distribution',
        'ordre_affichage' => 20,
        'label_devis' => 'Boîte de sol',
    ],

    'sortie_cable_32a' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'distribution',
        'ordre_affichage' => 30,
        'label_devis' => 'Sortie câble cuisson 32A',
    ],

    'sortie_cable' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'distribution',
        'ordre_affichage' => 31,
        'label_devis' => 'Sortie de câble',
    ],

    'sortie_cable_classe2' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'distribution',
        'ordre_affichage' => 32,
        'label_devis' => 'Sortie câble classe II',
    ],

    'boite_derivation_etanche' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'distribution',
        'ordre_affichage' => 40,
        'label_devis' => 'Boîte de dérivation étanche',
    ],

    'cable_2_5mm2' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'distribution',
        'ordre_affichage' => 50,
        'regroupement' => 'cables_distribution',
    ],

    'cable_6mm2' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'distribution',
        'ordre_affichage' => 51,
        'regroupement' => 'cables_distribution',
    ],

    'gaine_icta_20' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'distribution',
        'ordre_affichage' => 60,
        'regroupement' => 'gaines_distribution',
    ],

    'boite_encastrement' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'distribution',
        'ordre_affichage' => 70,
        'regroupement' => 'boites_distribution',
    ],

    // =========================================================================
    // ÉCLAIRAGE
    // =========================================================================

    'interrupteur_ou_poussoir' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'eclairage',
        'ordre_affichage' => 10,
        'label_devis' => 'Interrupteur ou bouton poussoir',
    ],

    'interrupteur_simple' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'eclairage',
        'ordre_affichage' => 11,
        'label_devis' => 'Interrupteur simple',
    ],

    'dcl_plafond' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'eclairage',
        'ordre_affichage' => 20,
        'label_devis' => 'Point lumineux DCL',
    ],

    'reglette_led_60cm' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'eclairage',
        'ordre_affichage' => 30,
        'label_devis' => 'Réglette LED sous meuble',
    ],

    'cable_1_5mm2' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'eclairage',
        'ordre_affichage' => 40,
        'regroupement' => 'cables_eclairage',
    ],

    'gaine_icta_16' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'eclairage',
        'ordre_affichage' => 41,
        'regroupement' => 'gaines_eclairage',
    ],

    'fiche_dcl' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'eclairage',
        'ordre_affichage' => 42,
    ],

    // =========================================================================
    // SÉCURITÉ
    // =========================================================================

    'piquet_terre_1m50' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'securite',
        'ordre_affichage' => 10,
        'label_devis' => 'Piquet de terre 1m50',
    ],

    'regard_terre' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'securite',
        'ordre_affichage' => 11,
        'label_devis' => 'Regard de visite terre',
    ],

    'barrette_coupure' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'securite',
        'ordre_affichage' => 20,
        'label_devis' => 'Barrette de coupure',
    ],

    'cable_terre_16mm2' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'securite',
        'ordre_affichage' => 30,
        'regroupement' => 'cables_terre',
    ],

    'cable_terre_4mm2' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'securite',
        'ordre_affichage' => 31,
        'regroupement' => 'cables_terre',
    ],

    'borne_equipotentielle' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'securite',
        'ordre_affichage' => 40,
    ],

    'colliers_terre' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'securite',
        'ordre_affichage' => 41,
        'regroupement' => 'consommables_terre',
    ],

    // =========================================================================
    // VENTILATION
    // =========================================================================

    'groupe_vmc' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'ventilation',
        'ordre_affichage' => 10,
        'label_devis' => 'Groupe VMC',
    ],

    'bouche_extraction' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'ventilation',
        'ordre_affichage' => 20,
        'label_devis' => 'Bouche d\'extraction',
    ],

    'bouche_hygro' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'ventilation',
        'ordre_affichage' => 21,
        'label_devis' => 'Bouche hygroréglable',
    ],

    'entree_air' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'ventilation',
        'ordre_affichage' => 30,
        'label_devis' => 'Entrée d\'air',
    ],

    'sortie_vmc' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'ventilation',
        'ordre_affichage' => 40,
        'label_devis' => 'Sortie VMC toiture/murale',
    ],

    'extracteur_air' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'ventilation',
        'ordre_affichage' => 50,
        'label_devis' => 'Extracteur d\'air',
    ],

    'temporisateur' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'ventilation',
        'ordre_affichage' => 51,
        'label_devis' => 'Temporisateur',
    ],

    'gaine_souple_80' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'ventilation',
        'ordre_affichage' => 60,
        'regroupement' => 'gaines_ventilation',
    ],

    'colliers_serrage' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'ventilation',
        'ordre_affichage' => 61,
        'regroupement' => 'consommables_ventilation',
    ],

    // =========================================================================
    // IRVE
    // =========================================================================

    'borne_recharge' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'irve',
        'ordre_affichage' => 10,
        'label_devis' => 'Borne de recharge',
    ],

    'disjoncteur_irve' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'irve',
        'ordre_affichage' => 20,
        'label_devis' => 'Disjoncteur IRVE',
    ],

    'differentiel_irve' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'irve',
        'ordre_affichage' => 21,
        'label_devis' => 'Différentiel IRVE dédié',
    ],

    'goulotte_etanche' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'irve',
        'ordre_affichage' => 30,
        'label_devis' => 'Goulotte étanche',
    ],

    'protection_cable' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'irve',
        'ordre_affichage' => 31,
        'label_devis' => 'Protection mécanique câble',
    ],

    'coffret_secondaire_irve' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'irve',
        'ordre_affichage' => 40,
        'label_devis' => 'Coffret secondaire',
    ],

    'support_borne' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'irve',
        'ordre_affichage' => 41,
    ],

    'cable_irve' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'irve',
        'ordre_affichage' => 42,
        'regroupement' => 'cables_irve',
    ],

    // =========================================================================
    // PRESTATIONS
    // =========================================================================

    'certificat_irve' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'prestations',
        'ordre_affichage' => 10,
        'label_devis' => 'Certificat IRVE',
    ],

    // =========================================================================
    // COMMUNICATION
    // =========================================================================

    'coffret_communication' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'communication',
        'ordre_affichage' => 10,
        'label_devis' => 'Coffret de communication',
    ],

    'prise_rj45' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'communication',
        'ordre_affichage' => 20,
        'label_devis' => 'Prise RJ45',
    ],

    'cable_rj45' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'communication',
        'ordre_affichage' => 21,
        'regroupement' => 'cables_communication',
    ],

    // =========================================================================
    // CHAUFFAGE
    // =========================================================================

    'programmateur_chauffage' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'chauffage',
        'ordre_affichage' => 10,
        'label_devis' => 'Programmateur chauffage',
    ],

    'cable_fil_pilote' => [
        'visible_devis' => false,
        'mode_facturation' => 'inclus',
        'categorie_devis' => 'chauffage',
        'ordre_affichage' => 11,
        'regroupement' => 'cables_chauffage',
    ],

    // =========================================================================
    // MAIN D'ŒUVRE - TABLEAU
    // =========================================================================

    'mo_depose_tableau' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 10,
        'label_devis' => 'Dépose ancien tableau',
    ],

    'mo_pose_coffret' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 20,
        'label_devis' => 'Pose coffret',
    ],

    'mo_pose_db' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 30,
        'label_devis' => 'Pose disjoncteur de branchement',
    ],

    'mo_pose_id' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 40,
        'label_devis' => 'Pose interrupteur différentiel',
    ],

    'mo_pose_disjoncteur' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 50,
        'label_devis' => 'Pose disjoncteur',
    ],

    'mo_raccordement_alimentation' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 60,
        'label_devis' => 'Raccordement alimentation',
    ],

    'mo_raccordement_circuit' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 70,
        'label_devis' => 'Raccordement circuit',
    ],

    'mo_reperage' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 80,
        'label_devis' => 'Repérage et étiquetage',
    ],

    'mo_pose_parafoudre' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 90,
        'label_devis' => 'Pose parafoudre',
    ],

    'mo_pose_contacteur' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 91,
        'label_devis' => 'Pose contacteur',
    ],

    'mo_pose_telerupteur' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 92,
        'label_devis' => 'Pose télérupteur',
    ],

    'mo_pose_delesteur' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 93,
        'label_devis' => 'Pose délesteur',
    ],

    'mo_pose_protection' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_tableau',
        'ordre_affichage' => 94,
        'label_devis' => 'Pose protection',
    ],

    // =========================================================================
    // MAIN D'ŒUVRE - DISTRIBUTION
    // =========================================================================

    'mo_tirage_cable' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_distribution',
        'ordre_affichage' => 10,
        'label_devis' => 'Tirage câble',
    ],

    'mo_pose_prise' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_distribution',
        'ordre_affichage' => 20,
        'label_devis' => 'Pose prise',
    ],

    'mo_pose_prise_sol' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_distribution',
        'ordre_affichage' => 21,
        'label_devis' => 'Pose prise sol',
    ],

    'mo_circuit_specialise' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_distribution',
        'ordre_affichage' => 30,
        'label_devis' => 'Installation circuit spécialisé',
    ],

    'mo_sortie_cable' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_distribution',
        'ordre_affichage' => 31,
        'label_devis' => 'Pose sortie de câble',
    ],

    'mo_passage_sol' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_distribution',
        'ordre_affichage' => 32,
        'label_devis' => 'Passage sol',
    ],

    // =========================================================================
    // MAIN D'ŒUVRE - ÉCLAIRAGE
    // =========================================================================

    'mo_pose_interrupteur' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_eclairage',
        'ordre_affichage' => 10,
        'label_devis' => 'Pose interrupteur',
    ],

    'mo_pose_dcl' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_eclairage',
        'ordre_affichage' => 20,
        'label_devis' => 'Pose point lumineux DCL',
    ],

    'mo_pose_reglette' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_eclairage',
        'ordre_affichage' => 30,
        'label_devis' => 'Pose réglette LED',
    ],

    // =========================================================================
    // MAIN D'ŒUVRE - SÉCURITÉ
    // =========================================================================

    'mo_mesure_terre' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_securite',
        'ordre_affichage' => 10,
        'label_devis' => 'Mesure résistance de terre',
    ],

    'mo_creation_terre' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_securite',
        'ordre_affichage' => 20,
        'label_devis' => 'Création prise de terre',
    ],

    'mo_pose_barrette' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_securite',
        'ordre_affichage' => 30,
        'label_devis' => 'Pose barrette de coupure',
    ],

    'mo_verification_barrette' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_securite',
        'ordre_affichage' => 31,
        'label_devis' => 'Vérification barrette',
    ],

    'mo_tirage_terre' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_securite',
        'ordre_affichage' => 40,
        'label_devis' => 'Tirage conducteur de terre',
    ],

    'mo_liaison_equipotentielle' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_securite',
        'ordre_affichage' => 50,
        'label_devis' => 'Liaison équipotentielle',
    ],

    // =========================================================================
    // MAIN D'ŒUVRE - VENTILATION
    // =========================================================================

    'mo_pose_groupe_vmc' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_ventilation',
        'ordre_affichage' => 10,
        'label_devis' => 'Pose groupe VMC',
    ],

    'mo_raccordement_vmc' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_ventilation',
        'ordre_affichage' => 20,
        'label_devis' => 'Raccordement électrique VMC',
    ],

    'mo_pose_bouche' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_ventilation',
        'ordre_affichage' => 30,
        'label_devis' => 'Pose bouche extraction',
    ],

    'mo_tirage_gaine' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_ventilation',
        'ordre_affichage' => 40,
        'label_devis' => 'Tirage gaine VMC',
    ],

    'mo_pose_entree_air' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_ventilation',
        'ordre_affichage' => 50,
        'label_devis' => 'Pose entrée d\'air',
    ],

    'mo_pose_sortie_vmc' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_ventilation',
        'ordre_affichage' => 60,
        'label_devis' => 'Pose sortie VMC',
    ],

    'mo_reseau_vmc' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_ventilation',
        'ordre_affichage' => 70,
        'label_devis' => 'Installation réseau VMC',
    ],

    'mo_pose_extracteur' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_ventilation',
        'ordre_affichage' => 80,
        'label_devis' => 'Pose extracteur',
    ],

    'mo_pose_temporisateur' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_ventilation',
        'ordre_affichage' => 81,
        'label_devis' => 'Pose temporisateur',
    ],

    // =========================================================================
    // MAIN D'ŒUVRE - IRVE
    // =========================================================================

    'mo_pose_borne' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_irve',
        'ordre_affichage' => 10,
        'label_devis' => 'Pose borne de recharge',
    ],

    'mo_tirage_cable_irve' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_irve',
        'ordre_affichage' => 20,
        'label_devis' => 'Tirage câble IRVE',
    ],

    'mo_raccordement_irve' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_irve',
        'ordre_affichage' => 30,
        'label_devis' => 'Raccordement IRVE',
    ],

    'mo_parametrage_borne' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_irve',
        'ordre_affichage' => 40,
        'label_devis' => 'Paramétrage borne',
    ],

    'mo_mise_en_service_irve' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_irve',
        'ordre_affichage' => 50,
        'label_devis' => 'Mise en service IRVE',
    ],

    'mo_pose_goulotte' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_irve',
        'ordre_affichage' => 60,
        'label_devis' => 'Pose goulotte',
    ],

    'mo_protection_cable' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_irve',
        'ordre_affichage' => 70,
        'label_devis' => 'Protection câble',
    ],

    // =========================================================================
    // MAIN D'ŒUVRE - COMMUNICATION
    // =========================================================================

    'mo_pose_coffret_com' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_communication',
        'ordre_affichage' => 10,
        'label_devis' => 'Pose coffret communication',
    ],

    'mo_tirage_cable_rj45' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_communication',
        'ordre_affichage' => 20,
        'label_devis' => 'Tirage câble RJ45',
    ],

    'mo_pose_prise_rj45' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_communication',
        'ordre_affichage' => 30,
        'label_devis' => 'Pose prise RJ45',
    ],

    // =========================================================================
    // MAIN D'ŒUVRE - CHAUFFAGE
    // =========================================================================

    'mo_circuit_chauffage' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_chauffage',
        'ordre_affichage' => 10,
        'label_devis' => 'Installation circuit chauffage',
    ],

    'mo_fil_pilote' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_chauffage',
        'ordre_affichage' => 20,
        'label_devis' => 'Installation fil pilote',
    ],

    'mo_pose_programmateur' => [
        'visible_devis' => true,
        'mode_facturation' => 'ligne',
        'categorie_devis' => 'mo_chauffage',
        'ordre_affichage' => 30,
        'label_devis' => 'Pose programmateur',
    ],
];