<?php

/**
 * Règles normatives pour le NormesEngine
 *
 * CLASSIFICATION DES SOURCES :
 * - norme : Obligation NF C 15-100 ou réglementaire
 * - reco : Recommandation professionnelle
 * - pratique : Bonne pratique métier
 *
 * NIVEAUX DE SÉVÉRITÉ :
 * - bloquant : Empêche la génération du devis
 * - alerte : Avertissement affiché mais n'empêche pas la génération
 * - info : Information contextuelle
 *
 * COHÉRENCE :
 * - Les travail_id correspondent aux IDs de travaux_definitions.php
 * - Les sous_travail_id correspondent aux sous-travaux de travaux_definitions.php
 */

return [
    /**
     * ═══════════════════════════════════════════════════════════════════
     * TABLES DE RÉFÉRENCE
     * ═══════════════════════════════════════════════════════════════════
     */
    'tables' => [
        /**
         * Sections minimales selon usage (NF C 15-100)
         */
        'sections_minimales' => [
            'eclairage' => 1.5,
            'prises_standard' => 2.5,
            'prises_commandees' => 1.5,
            'chauffage_4500w_max' => 2.5,
            'chauffage_7500w_max' => 6,
            'chauffe_eau' => 2.5,
            'cuisson' => 6,
            'four' => 2.5,
            'lave_linge' => 2.5,
            'lave_vaisselle' => 2.5,
            'seche_linge' => 2.5,
            'congelateur' => 2.5,
            'vmc' => 1.5,
            'volet_roulant' => 1.5,
            'borne_irve_mono' => 10,
            'borne_irve_tri_11kw' => 6,
            'borne_irve_tri_22kw' => 10,
        ],

        /**
         * Protection maximale par section (clés en string pour éviter conversion float->int)
         */
        'protection_max_par_section' => [
            '1.5' => 16,
            '2.5' => 20,
            '4' => 25,
            '6' => 32,
            '10' => 40,
            '16' => 50,
        ],

        /**
         * Sections câble IRVE selon puissance et distance
         */
        'sections_irve' => [
            '7.4kW' => [
                ['distance_max' => 25, 'section' => 6],
                ['distance_max' => 50, 'section' => 10],
                ['distance_max' => 100, 'section' => 16],
            ],
            '11kW' => [
                ['distance_max' => 25, 'section' => 6],
                ['distance_max' => 50, 'section' => 10],
                ['distance_max' => 100, 'section' => 16],
            ],
            '22kW' => [
                ['distance_max' => 25, 'section' => 10],
                ['distance_max' => 50, 'section' => 16],
                ['distance_max' => 100, 'section' => 25],
            ],
        ],

        /**
         * Débits VMC minimaux selon type de logement
         */
        'debits_vmc' => [
            'T1' => ['cuisine' => 75, 'sdb' => 15, 'wc' => 15],
            'T2' => ['cuisine' => 90, 'sdb' => 15, 'wc' => 15],
            'T3' => ['cuisine' => 105, 'sdb' => 30, 'wc' => 15],
            'T4' => ['cuisine' => 120, 'sdb' => 30, 'wc' => 30],
            'T5' => ['cuisine' => 135, 'sdb' => 30, 'wc' => 30],
            'T6' => ['cuisine' => 135, 'sdb' => 30, 'wc' => 30],
        ],

        /**
         * Prises minimum par pièce (NF C 15-100 neuf)
         */
        'prises_par_piece' => [
            'sejour' => ['min' => 5, 'formule' => 'ceil(surface / 4)'],
            'chambre' => ['min' => 3, 'formule' => null],
            'cuisine' => ['min' => 6, 'plan_travail_min' => 4],
            'entree' => ['min' => 1, 'formule' => null],
            'couloir' => ['min' => 1, 'formule' => null],
            'wc' => ['min' => 0, 'formule' => null],
            'sdb' => ['min' => 1, 'hors_volume' => true],
            'buanderie' => ['min' => 1, 'formule' => null],
            'garage' => ['min' => 1, 'formule' => null],
            'cave' => ['min' => 1, 'formule' => null],
        ],

        /**
         * Volumes salle de bain
         */
        'volumes_sdb' => [
            'volume_0' => [
                'description' => 'Intérieur baignoire/receveur',
                'materiel_autorise' => 'IPX7, TBTS 12V max',
            ],
            'volume_1' => [
                'description' => 'Au-dessus baignoire/receveur jusqu\'à 2.25m',
                'materiel_autorise' => 'IPX4, TBTS 12V max, chauffe-eau horizontal',
            ],
            'volume_2' => [
                'description' => '60cm autour volume 1, hauteur 3m',
                'materiel_autorise' => 'IPX4, Classe II, luminaires, prises rasoir',
            ],
            'hors_volume' => [
                'description' => 'Au-delà du volume 2',
                'materiel_autorise' => 'Appareillage standard, protection 30mA obligatoire',
            ],
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÈGLES : PROTECTION DIFFÉRENTIELLE
     * ═══════════════════════════════════════════════════════════════════
     */
    'protection_differentielle' => [
        'max_circuits_par_differentiel' => [
            'id' => 'max_circuits_par_differentiel',
            'label' => 'Maximum 8 circuits par différentiel',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §10.1.3.2',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'comparison',
                'gauche' => 'nb_circuits_par_differentiel',
                'operateur' => 'lte',
                'droite' => 8,
            ],
            'action_si_non_conforme' => [
                'type' => 'ajout_differentiel',
                'message' => 'Ajouter un interrupteur différentiel',
            ],
            'impacte' => [
                'travail_id' => 'tableau_electrique',
                'sous_travail_id' => 'installation_protections_differentielles',
            ],
        ],

        'type_a_circuits_specifiques' => [
            'id' => 'type_a_circuits_specifiques',
            'label' => 'Différentiel type A pour circuits spécifiques',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §531.2.1.4',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'circuit_in_list',
                'liste' => ['plaque_cuisson', 'lave_linge', 'borne_irve'],
            ],
            'action_si_non_conforme' => [
                'type' => 'imposer_type_differentiel',
                'type_differentiel' => 'type_a',
                'message' => 'Circuit nécessitant un différentiel type A',
            ],
            'impacte' => [
                'travail_id' => 'tableau_electrique',
                'sous_travail_id' => 'installation_protections_differentielles',
            ],
        ],

        'type_f_recommande_irve' => [
            'id' => 'type_f_recommande_irve',
            'label' => 'Différentiel type F recommandé pour IRVE',
            'source' => 'reco',
            'reference' => 'Guide UTE C 15-722',
            'severite' => 'info',
            'condition' => [
                'type' => 'has_circuit',
                'circuit' => 'borne_irve',
            ],
            'action_si_non_conforme' => [
                'type' => 'proposer_type_differentiel',
                'type_differentiel' => 'type_f',
                'message' => 'Type F recommandé pour meilleure immunité',
            ],
            'impacte' => [
                'travail_id' => 'protection_borne',
                'sous_travail_id' => 'installation_protection_differentielle_dediee',
            ],
        ],

        'protection_30ma_obligatoire' => [
            'id' => 'protection_30ma_obligatoire',
            'label' => 'Protection 30mA obligatoire tous circuits',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §411.3.3',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'all_circuits_protected',
                'sensibilite' => 30,
            ],
            'action_si_non_conforme' => [
                'type' => 'ajouter_protection',
                'message' => 'Tous les circuits doivent être protégés par 30mA',
            ],
            'impacte' => [
                'travail_id' => 'tableau_electrique',
                'sous_travail_id' => 'installation_protections_differentielles',
            ],
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÈGLES : SECTIONS CÂBLES
     * ═══════════════════════════════════════════════════════════════════
     */
    'sections_cables' => [
        'section_eclairage' => [
            'id' => 'section_eclairage',
            'label' => 'Section minimum éclairage 1.5mm²',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §524',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'circuit_type',
                'circuit' => 'eclairage',
            ],
            'valeur_imposee' => [
                'section_min' => 1.5,
                'protection_max' => 16,
            ],
            'impacte' => [
                'travail_id' => 'distribution_eclairage',
                'besoin_technique' => 'cable_1_5mm2',
            ],
        ],

        'section_prises' => [
            'id' => 'section_prises',
            'label' => 'Section minimum prises 2.5mm²',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §524',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'circuit_type',
                'circuit' => 'prises',
            ],
            'valeur_imposee' => [
                'section_min' => 2.5,
                'protection_max' => 20,
            ],
            'impacte' => [
                'travail_id' => 'distribution_prises',
                'besoin_technique' => 'cable_2_5mm2',
            ],
        ],

        'section_cuisson' => [
            'id' => 'section_cuisson',
            'label' => 'Section minimum cuisson 6mm²',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §524',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'circuit_type',
                'circuit' => 'cuisson',
            ],
            'valeur_imposee' => [
                'section_min' => 6,
                'protection_max' => 32,
            ],
            'impacte' => [
                'travail_id' => 'circuits_specialises',
                'sous_travail_id' => 'installation_circuit_cuisson',
                'besoin_technique' => 'cable_6mm2',
            ],
        ],

        'section_irve' => [
            'id' => 'section_irve',
            'label' => 'Section câble IRVE selon puissance et distance',
            'source' => 'norme',
            'reference' => 'Guide UTE C 15-722',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'circuit_type',
                'circuit' => 'borne_irve',
            ],
            'calcul' => [
                'type' => 'table_lookup',
                'table' => 'sections_irve',
                'cle_primaire' => 'puissance_borne',
                'cle_secondaire' => 'distance_tableau',
            ],
            'impacte' => [
                'travail_id' => 'alimentation_borne',
                'sous_travail_id' => 'tirage_cable_alimentation',
                'besoin_technique' => 'cable_irve',
            ],
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÈGLES : CUISINE
     * ═══════════════════════════════════════════════════════════════════
     */
    'cuisine' => [
        'nb_prises_plan_travail' => [
            'id' => 'nb_prises_plan_travail',
            'label' => 'Minimum 4 prises plan de travail',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §10.1.3.3.3',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'context_has',
                'champs' => ['cuisine'],
            ],
            'valeur_imposee' => [
                'minimum' => 4,
            ],
            'impacte' => [
                'travail_id' => 'distribution_prises_cuisine',
                'sous_travail_id' => 'installation_prises_plan_travail',
            ],
        ],

        'circuit_cuisson_32a' => [
            'id' => 'circuit_cuisson_32a',
            'label' => 'Circuit cuisson 32A obligatoire',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §10.1.3.3.3',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'context_has',
                'champs' => ['cuisine'],
            ],
            'action_si_absent' => [
                'type' => 'ajouter_sous_travail',
                'sous_travail_id' => 'installation_circuit_cuisson',
            ],
            'impacte' => [
                'travail_id' => 'circuits_specialises',
                'sous_travail_id' => 'installation_circuit_cuisson',
            ],
        ],

        'circuit_four_20a' => [
            'id' => 'circuit_four_20a',
            'label' => 'Circuit four 20A si séparé',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §10.1.3.3.3',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'field_eq',
                'champ' => 'four_separe',
                'valeur' => true,
            ],
            'action_si_absent' => [
                'type' => 'ajouter_sous_travail',
                'sous_travail_id' => 'installation_circuit_four',
            ],
            'impacte' => [
                'travail_id' => 'circuits_specialises',
                'sous_travail_id' => 'installation_circuit_four',
            ],
        ],

        'differentiel_type_a_induction' => [
            'id' => 'differentiel_type_a_induction',
            'label' => 'Différentiel type A si plaque induction',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §531.2.1.4',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'field_eq',
                'champ' => 'type_cuisson',
                'valeur' => 'induction',
            ],
            'action_si_non_conforme' => [
                'type' => 'imposer_type_differentiel',
                'type_differentiel' => 'type_a',
                'message' => 'Plaque induction nécessite différentiel type A',
            ],
            'impacte' => [
                'travail_id' => 'tableau_electrique',
                'sous_travail_id' => 'installation_protections_differentielles',
            ],
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÈGLES : SALLE DE BAIN
     * ═══════════════════════════════════════════════════════════════════
     */
    'salle_de_bain' => [
        'liaison_equipotentielle' => [
            'id' => 'liaison_equipotentielle',
            'label' => 'Liaison équipotentielle locale obligatoire',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §701.415.2',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'context_has',
                'champs' => ['salle_de_bain'],
            ],
            'action_si_absent' => [
                'type' => 'ajouter_travail',
                'travail_id' => 'liaison_equipotentielle',
            ],
            'impacte' => [
                'travail_id' => 'liaison_equipotentielle',
                'sous_travail_id' => 'realisation_liaison_equipotentielle_locale',
            ],
        ],

        'volumes_appareillage' => [
            'id' => 'volumes_appareillage',
            'label' => 'Respect des volumes',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §701',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'context_has',
                'champs' => ['salle_de_bain'],
            ],
            'verification' => [
                'type' => 'volumes_sdb',
                'table' => 'volumes_sdb',
            ],
            'impacte' => [
                'travail_id' => 'eclairage_sdb',
            ],
        ],

        'protection_30ma_sdb' => [
            'id' => 'protection_30ma_sdb',
            'label' => 'Protection 30mA obligatoire SDB',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §701.411.3.3',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'context_has',
                'champs' => ['salle_de_bain'],
            ],
            'action_si_non_conforme' => [
                'type' => 'verifier_protection',
                'sensibilite' => 30,
                'message' => 'Tous les circuits SDB doivent être protégés par 30mA',
            ],
            'impacte' => [
                'travail_id' => 'tableau_electrique',
            ],
        ],

        'ip_adapte_volume' => [
            'id' => 'ip_adapte_volume',
            'label' => 'IP adapté au volume',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §701.512.2',
            'severite' => 'alerte',
            'verification' => [
                'type' => 'ip_par_volume',
                'volume_0' => 'IPX7',
                'volume_1' => 'IPX4',
                'volume_2' => 'IPX4',
                'hors_volume' => 'IP20',
            ],
            'impacte' => [
                'travail_id' => 'eclairage_sdb',
            ],
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÈGLES : BORNE IRVE
     * ═══════════════════════════════════════════════════════════════════
     */
    'irve' => [
        'certification_installateur' => [
            'id' => 'certification_installateur',
            'label' => 'Certification IRVE obligatoire',
            'source' => 'norme',
            'reference' => 'Décret 2017-26',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'field_gt',
                'champ' => 'puissance_borne_kw',
                'valeur' => 3.7,
            ],
            'action_si_absent' => [
                'type' => 'ajouter_besoin',
                'besoin_technique' => 'certificat_irve',
            ],
            'impacte' => [
                'travail_id' => 'installation_borne',
                'sous_travail_id' => 'mise_en_service_borne',
            ],
        ],

        'differentiel_dedie' => [
            'id' => 'differentiel_dedie',
            'label' => 'Différentiel dédié type A ou F',
            'source' => 'norme',
            'reference' => 'Guide UTE C 15-722',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'context_has',
                'champs' => ['borne_irve'],
            ],
            'action_si_absent' => [
                'type' => 'ajouter_sous_travail',
                'sous_travail_id' => 'installation_protection_differentielle_dediee',
            ],
            'impacte' => [
                'travail_id' => 'protection_borne',
                'sous_travail_id' => 'installation_protection_differentielle_dediee',
            ],
        ],

        'section_cable_irve' => [
            'id' => 'section_cable_irve',
            'label' => 'Section câble selon puissance et distance',
            'source' => 'norme',
            'reference' => 'Guide UTE C 15-722',
            'severite' => 'bloquant',
            'calcul' => [
                'type' => 'table_lookup',
                'table' => 'sections_irve',
                'cle_primaire' => 'puissance_borne',
                'cle_secondaire' => 'distance_tableau',
            ],
            'impacte' => [
                'travail_id' => 'alimentation_borne',
                'sous_travail_id' => 'tirage_cable_alimentation',
            ],
        ],

        'coffret_secondaire_distance' => [
            'id' => 'coffret_secondaire_distance',
            'label' => 'Coffret secondaire si distance > 25m',
            'source' => 'pratique',
            'reference' => 'Bonne pratique métier',
            'severite' => 'info',
            'condition' => [
                'type' => 'field_gt',
                'champ' => 'distance_tableau',
                'valeur' => 25,
            ],
            'action_si_vrai' => [
                'type' => 'ajouter_travail',
                'travail_id' => 'coffret_secondaire',
            ],
            'impacte' => [
                'travail_id' => 'coffret_secondaire',
            ],
        ],

        'protection_cheminement_exterieur' => [
            'id' => 'protection_cheminement_exterieur',
            'label' => 'Protection IP si passage extérieur',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §522',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'context_has',
                'champs' => ['passage_exterieur'],
            ],
            'action_si_vrai' => [
                'type' => 'ajouter_travail',
                'travail_id' => 'cheminement_exterieur',
            ],
            'impacte' => [
                'travail_id' => 'cheminement_exterieur',
            ],
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÈGLES : VMC
     * ═══════════════════════════════════════════════════════════════════
     */
    'vmc' => [
        'bouche_par_piece_humide' => [
            'id' => 'bouche_par_piece_humide',
            'label' => 'Une bouche par pièce humide',
            'source' => 'norme',
            'reference' => 'Arrêté 24 mars 1982',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'has_travail',
                'travail' => 'installation_groupe',
            ],
            'verification' => [
                'type' => 'comparison',
                'gauche' => 'nb_bouches_extraction',
                'operateur' => 'gte',
                'droite' => ['type' => 'field', 'champ' => 'nb_pieces_humides'],
            ],
            'impacte' => [
                'travail_id' => 'reseau_extraction',
                'sous_travail_id' => 'pose_bouches_extraction',
            ],
        ],

        'entrees_air_simple_flux' => [
            'id' => 'entrees_air_simple_flux',
            'label' => 'Entrées d\'air si simple flux',
            'source' => 'norme',
            'reference' => 'Arrêté 24 mars 1982',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'field_neq',
                'champ' => 'type_vmc',
                'valeur' => 'double_flux',
            ],
            'action_si_absent' => [
                'type' => 'ajouter_travail',
                'travail_id' => 'entrees_air',
            ],
            'impacte' => [
                'travail_id' => 'entrees_air',
                'sous_travail_id' => 'installation_entrees_air',
            ],
        ],

        'debit_extraction_minimum' => [
            'id' => 'debit_extraction_minimum',
            'label' => 'Débit d\'extraction minimum',
            'source' => 'norme',
            'reference' => 'Arrêté 24 mars 1982',
            'severite' => 'bloquant',
            'calcul' => [
                'type' => 'table_lookup',
                'table' => 'debits_vmc',
                'cle' => 'type_logement',
            ],
            'impacte' => [
                'travail_id' => 'installation_groupe',
                'sous_travail_id' => 'pose_groupe_vmc',
            ],
        ],

        'protection_2a_vmc' => [
            'id' => 'protection_2a_vmc',
            'label' => 'Disjoncteur 2A pour VMC',
            'source' => 'norme',
            'reference' => 'NF C 15-100',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'has_travail',
                'travail' => 'installation_groupe',
            ],
            'valeur_imposee' => [
                'calibre_disjoncteur' => 2,
            ],
            'impacte' => [
                'travail_id' => 'installation_groupe',
                'sous_travail_id' => 'raccordement_electrique_vmc',
            ],
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÈGLES : TABLEAU ÉLECTRIQUE
     * ═══════════════════════════════════════════════════════════════════
     */
    'tableau' => [
        'reserve_20_pourcent' => [
            'id' => 'reserve_20_pourcent',
            'label' => 'Réserve 20% tableau',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §10.1.3.1',
            'severite' => 'bloquant',
            'calcul' => [
                'type' => 'formula',
                'expression' => 'ceil(nb_modules_utilises * 1.2)',
            ],
            'action_si_non_conforme' => [
                'type' => 'augmenter_taille_coffret',
                'message' => 'Coffret trop petit pour respecter réserve 20%',
            ],
            'impacte' => [
                'travail_id' => 'tableau_electrique',
                'sous_travail_id' => 'installation_coffret',
            ],
        ],

        'reperage_circuits' => [
            'id' => 'reperage_circuits',
            'label' => 'Repérage obligatoire des circuits',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §514.5',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'has_travail',
                'travail' => 'tableau_electrique',
            ],
            'action_si_absent' => [
                'type' => 'ajouter_sous_travail',
                'sous_travail_id' => 'reperage_etiquetage',
            ],
            'impacte' => [
                'travail_id' => 'tableau_electrique',
                'sous_travail_id' => 'reperage_etiquetage',
            ],
        ],

        'schema_unifilaire' => [
            'id' => 'schema_unifilaire',
            'label' => 'Schéma unifilaire obligatoire',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §514.5',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'has_travail',
                'travail' => 'tableau_electrique',
            ],
            'action_si_absent' => [
                'type' => 'ajouter_besoin',
                'besoin_technique' => 'schema_unifilaire',
            ],
            'impacte' => [
                'travail_id' => 'tableau_electrique',
                'sous_travail_id' => 'reperage_etiquetage',
            ],
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÈGLES : MISE À LA TERRE
     * ═══════════════════════════════════════════════════════════════════
     */
    'terre' => [
        'resistance_max' => [
            'id' => 'resistance_max',
            'label' => 'Résistance terre max 100Ω',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §411.5.3',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'has_travail',
                'travail' => 'mise_a_la_terre',
            ],
            'valeur_imposee' => [
                'resistance_max' => 100,
            ],
            'impacte' => [
                'travail_id' => 'mise_a_la_terre',
                'travail_id_alt' => 'verification_terre',
            ],
        ],

        'verification_obligatoire' => [
            'id' => 'verification_obligatoire',
            'label' => 'Vérification terre obligatoire',
            'source' => 'norme',
            'reference' => 'NF C 15-100',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'or',
                'conditions' => [
                    ['type' => 'has_travail', 'travail' => 'tableau_electrique'],
                    ['type' => 'has_travail', 'travail' => 'installation_borne'],
                ],
            ],
            'action_si_absent' => [
                'type' => 'ajouter_sous_travail',
                'sous_travail_id' => 'verification_terre_existante',
            ],
            'impacte' => [
                'travail_id' => 'mise_a_la_terre',
                'travail_id_alt' => 'verification_terre',
            ],
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÈGLES : ÉCLAIRAGE
     * ═══════════════════════════════════════════════════════════════════
     */
    'eclairage' => [
        'point_lumineux_par_piece' => [
            'id' => 'point_lumineux_par_piece',
            'label' => 'Minimum 1 point lumineux par pièce',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §10.1.3.2',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'always',
            ],
            'valeur_imposee' => [
                'minimum_par_piece' => 1,
            ],
            'impacte' => [
                'travail_id' => 'distribution_eclairage',
                'travail_id_cuisine' => 'eclairage_cuisine',
                'travail_id_sdb' => 'eclairage_sdb',
            ],
        ],

        'dcl_obligatoire' => [
            'id' => 'dcl_obligatoire',
            'label' => 'DCL obligatoire point lumineux',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §559.1.2',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'has_point_lumineux',
            ],
            'action_si_absent' => [
                'type' => 'ajouter_besoin',
                'besoin_technique' => 'dcl_plafond',
            ],
            'impacte' => [
                'travail_id' => 'distribution_eclairage',
                'sous_travail_id' => 'pose_points_lumineux',
            ],
        ],

        'max_8_points_par_circuit' => [
            'id' => 'max_8_points_par_circuit',
            'label' => 'Maximum 8 points par circuit éclairage',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §10.1.3.2',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'circuit_type',
                'circuit' => 'eclairage',
            ],
            'valeur_imposee' => [
                'maximum_points' => 8,
            ],
            'impacte' => [
                'travail_id' => 'distribution_eclairage',
            ],
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÈGLES : PRISES
     * ═══════════════════════════════════════════════════════════════════
     */
    'prises' => [
        'max_8_prises_par_circuit' => [
            'id' => 'max_8_prises_par_circuit',
            'label' => 'Maximum 8 prises par circuit',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §10.1.3.3',
            'severite' => 'bloquant',
            'condition' => [
                'type' => 'circuit_type',
                'circuit' => 'prises',
            ],
            'valeur_imposee' => [
                'maximum_prises' => 8,
            ],
            'impacte' => [
                'travail_id' => 'distribution_prises',
            ],
        ],

        'nb_prises_par_piece' => [
            'id' => 'nb_prises_par_piece',
            'label' => 'Nombre de prises minimum par pièce',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §10.1.3.3',
            'severite' => 'bloquant',
            'calcul' => [
                'type' => 'table_lookup',
                'table' => 'prises_par_piece',
                'cle' => 'type_piece',
            ],
            'impacte' => [
                'travail_id' => 'distribution_prises',
                'sous_travail_id' => 'pose_appareillage_prises',
            ],
        ],

        'hauteur_minimum' => [
            'id' => 'hauteur_minimum',
            'label' => 'Hauteur minimum prises 5cm',
            'source' => 'norme',
            'reference' => 'NF C 15-100 §555.1.8',
            'severite' => 'info',
            'valeur_imposee' => [
                'hauteur_min_cm' => 5,
            ],
            'impacte' => [
                'travail_id' => 'distribution_prises',
            ],
        ],
    ],
];
