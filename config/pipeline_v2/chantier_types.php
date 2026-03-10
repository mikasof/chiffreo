<?php

/**
 * Définition des 6 macro-chantiers prioritaires
 *
 * PRINCIPES DE CE FICHIER :
 * - Centré sur la logique CHANTIER (pas sur les normes détaillées)
 * - Les sous-travaux sont des ACTIONS MÉTIER (pas des produits)
 * - Les conditions sont STRUCTURÉES (pas de parsing de chaînes)
 * - La logique normative détaillée est déléguée au NormesEngine
 *
 * ORIGINES POSSIBLES :
 * - chantier : inhérent au type de chantier
 * - norme : imposé par NF C 15-100 (géré par NormesEngine)
 * - commercial : ajouté pour valeur commerciale
 * - audit : détecté par l'audit IA
 */

return [
    'renovation_complete_maison' => [
        'id' => 'renovation_complete_maison',
        'label' => 'Rénovation complète maison',
        'description' => 'Rénovation électrique complète d\'une maison individuelle',
        'contexte_tva_defaut' => 'renovation_plus_2_ans',

        'travaux_base' => [
            'tableau_electrique' => [
                'sous_travaux' => [
                    'installation_coffret',
                    'installation_protection_generale',
                    'installation_protections_differentielles',
                    'installation_protections_circuits',
                    'raccordement_alimentation',
                    'reperage_etiquetage',
                ],
                'origine' => 'chantier',
            ],
            'mise_a_la_terre' => [
                'sous_travaux' => [
                    'verification_terre_existante',
                    'installation_barrette_coupure',
                    'tirage_conducteur_principal',
                ],
                'origine' => 'norme',
            ],
            'distribution_prises' => [
                'sous_travaux' => [
                    'tirage_lignes_prises',
                    'pose_appareillage_prises',
                ],
                'multiplicateur' => 'par_piece',
                'origine' => 'chantier',
            ],
            'distribution_eclairage' => [
                'sous_travaux' => [
                    'tirage_lignes_eclairage',
                    'pose_commandes',
                    'pose_points_lumineux',
                ],
                'multiplicateur' => 'par_piece',
                'origine' => 'chantier',
            ],
        ],

        'travaux_conditionnels' => [
            'circuits_specialises_cuisine' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['cuisine'],
                ],
                'sous_travaux' => [
                    'installation_circuit_cuisson',
                    'installation_circuit_four',
                    'installation_circuits_electromenager',
                ],
                'origine' => 'norme',
            ],
            'ventilation_mecanique' => [
                'condition' => [
                    'type' => 'or',
                    'conditions' => [
                        ['type' => 'field_gt', 'champ' => 'surface', 'valeur' => 30],
                        ['type' => 'field_gte', 'champ' => 'nb_pieces_humides', 'valeur' => 2],
                    ],
                ],
                'sous_travaux' => [
                    'installation_groupe_vmc',
                    'installation_reseau_extraction',
                    'installation_entrees_air',
                ],
                'origine' => 'norme',
            ],
            'reseau_communication' => [
                'condition' => [
                    'type' => 'or',
                    'conditions' => [
                        ['type' => 'field_gte', 'champ' => 'grade_communication', 'valeur' => 2],
                        ['type' => 'context_has', 'champs' => ['demande_rj45']],
                    ],
                ],
                'sous_travaux' => [
                    'installation_coffret_communication',
                    'tirage_cables_rj45',
                    'pose_prises_rj45',
                ],
                'origine' => 'norme',
            ],
            'chauffage_electrique' => [
                'condition' => [
                    'type' => 'field_eq',
                    'champ' => 'type_chauffage',
                    'valeur' => 'electrique',
                ],
                'sous_travaux' => [
                    'installation_circuits_chauffage',
                    'installation_fil_pilote',
                    'installation_programmation',
                ],
                'origine' => 'chantier',
            ],
            'protection_surtension' => [
                'condition' => [
                    'type' => 'or',
                    'conditions' => [
                        ['type' => 'context_has', 'champs' => ['zone_foudre']],
                        ['type' => 'field_lt', 'champ' => 'distance_transfo', 'valeur' => 500],
                    ],
                ],
                'sous_travaux' => [
                    'installation_protection_surtension',
                ],
                'origine' => 'norme',
            ],
        ],

        'domaines_obligatoires_a_couvrir' => [
            'tableau' => [
                'label' => 'Tableau électrique',
                'verification' => [
                    'type' => 'has_travail',
                    'travail' => 'tableau_electrique',
                ],
                'action_si_absent' => 'ajouter_travail',
                'origine' => 'chantier',
            ],
            'protection_differentielle' => [
                'label' => 'Protection différentielle 30mA',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'installation_protections_differentielles',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'norme',
            ],
            'terre' => [
                'label' => 'Mise à la terre',
                'verification' => [
                    'type' => 'has_travail',
                    'travail' => 'mise_a_la_terre',
                ],
                'action_si_absent' => 'ajouter_travail',
                'origine' => 'norme',
            ],
            'prises_minimum' => [
                'label' => 'Prises conformes par pièce',
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'prises_par_piece',
                ],
                'action_si_absent' => 'completer_quantites',
                'origine' => 'norme',
            ],
            'eclairage_minimum' => [
                'label' => 'Point lumineux par pièce',
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'eclairage_par_piece',
                ],
                'action_si_absent' => 'completer_quantites',
                'origine' => 'norme',
            ],
            'circuits_dedies_cuisine' => [
                'label' => 'Circuits spécialisés cuisine',
                'verification' => [
                    'type' => 'conditional_has_travail',
                    'condition' => ['type' => 'context_has', 'champs' => ['cuisine']],
                    'travail' => 'circuits_specialises_cuisine',
                ],
                'action_si_absent' => 'ajouter_travail',
                'origine' => 'norme',
            ],
            'ventilation' => [
                'label' => 'Ventilation',
                'verification' => [
                    'type' => 'or',
                    'conditions' => [
                        ['type' => 'has_travail', 'travail' => 'ventilation_mecanique'],
                        ['type' => 'context_has', 'champs' => ['vmc_existante_conforme']],
                    ],
                ],
                'action_si_absent' => 'question_audit',
                'origine' => 'norme',
            ],
        ],

        'oublis_frequents' => [
            'consommables_gaines' => [
                'label' => 'Gaines ICTA',
                'besoin_technique' => 'gaines_icta',
                'calcul' => [
                    'type' => 'formula',
                    'expression' => 'metrage_cables * 1.1',
                ],
                'origine' => 'chantier',
            ],
            'consommables_cables' => [
                'label' => 'Fils et câbles',
                'besoin_technique' => 'cables_installation',
                'calcul' => [
                    'type' => 'delegate',
                    'methode' => 'calculer_metrage_cables',
                ],
                'origine' => 'chantier',
            ],
            'boites_derivation' => [
                'label' => 'Boîtes de dérivation',
                'besoin_technique' => 'boites_derivation',
                'calcul' => [
                    'type' => 'formula',
                    'expression' => 'ceil(nb_circuits * 0.5)',
                ],
                'origine' => 'chantier',
            ],
            'boites_encastrement' => [
                'label' => 'Boîtes d\'encastrement',
                'besoin_technique' => 'boites_encastrement',
                'calcul' => [
                    'type' => 'formula',
                    'expression' => 'nb_prises + nb_interrupteurs',
                ],
                'origine' => 'chantier',
            ],
            'detecteur_fumee' => [
                'label' => 'Détecteur de fumée (DAAF)',
                'besoin_technique' => 'daaf',
                'calcul' => [
                    'type' => 'formula',
                    'expression' => 'max(1, nb_etages)',
                ],
                'origine' => 'norme',
            ],
            'attestation_conformite' => [
                'label' => 'Attestation Consuel',
                'besoin_technique' => 'consuel',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'norme',
            ],
            'mise_en_service' => [
                'label' => 'Mise en service et tests',
                'besoin_technique' => 'mise_en_service',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'commercial',
            ],
        ],

        'controles_finals' => [
            'capacite_differentiels' => [
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'ratio_circuits_differentiels',
                ],
                'message' => 'Nombre d\'interrupteurs différentiels insuffisant',
                'origine' => 'norme',
            ],
            'sections_cables' => [
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'sections_cables_conformes',
                ],
                'message' => 'Sections de câbles non conformes',
                'origine' => 'norme',
            ],
            'equilibrage_phases' => [
                'verification' => [
                    'type' => 'conditional_delegate',
                    'condition' => ['type' => 'context_has', 'champs' => ['triphase']],
                    'delegate' => ['type' => 'delegate_normes_engine', 'regle' => 'equilibrage_phases'],
                ],
                'message' => 'Répartition des circuits non équilibrée',
                'origine' => 'norme',
            ],
            'coherence_puissance' => [
                'verification' => [
                    'type' => 'comparison',
                    'gauche' => 'puissance_totale_installee',
                    'operateur' => 'lte',
                    'droite' => ['type' => 'formula', 'expression' => 'puissance_abonnement * 1.2'],
                ],
                'message' => 'Puissance installée supérieure à l\'abonnement',
                'origine' => 'commercial',
            ],
        ],

        'questions_qualification' => [
            'surface' => [
                'question' => 'Quelle est la surface habitable ?',
                'type' => 'number',
                'unite' => 'm²',
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['metrage_cables', 'nb_circuits'],
                'origine' => 'chantier',
            ],
            'nb_pieces' => [
                'question' => 'Combien de pièces principales ?',
                'type' => 'number',
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['nb_prises', 'nb_points_lumineux'],
                'origine' => 'chantier',
            ],
            'nb_etages' => [
                'question' => 'Combien d\'étages ?',
                'type' => 'number',
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => 1,
                'impact' => ['metrage_cables', 'nb_daaf'],
                'origine' => 'chantier',
            ],
            'annee_construction' => [
                'question' => 'Année de construction ?',
                'type' => 'number',
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['contexte_tva'],
                'origine' => 'commercial',
            ],
            'puissance_abonnement' => [
                'question' => 'Puissance d\'abonnement souhaitée ?',
                'type' => 'select',
                'options' => ['6kVA', '9kVA', '12kVA', '15kVA', '18kVA', '36kVA'],
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => '9kVA',
                'impact' => ['calibre_protection_generale'],
                'origine' => 'chantier',
            ],
            'type_chauffage' => [
                'question' => 'Type de chauffage prévu ?',
                'type' => 'select',
                'options' => ['electrique', 'gaz', 'pompe_chaleur', 'autre'],
                'priorite' => 3,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => 'autre',
                'impact' => ['circuits_chauffage'],
                'origine' => 'chantier',
            ],
        ],
    ],

    'remplacement_tableau' => [
        'id' => 'remplacement_tableau',
        'label' => 'Remplacement tableau électrique',
        'description' => 'Mise aux normes ou remplacement du tableau électrique existant',
        'contexte_tva_defaut' => 'renovation_plus_2_ans',

        'travaux_base' => [
            'tableau_electrique' => [
                'sous_travaux' => [
                    'depose_ancien_tableau',
                    'installation_coffret',
                    'installation_protection_generale',
                    'installation_protections_differentielles',
                    'installation_protections_circuits',
                    'raccordement_circuits_existants',
                    'reperage_etiquetage',
                ],
                'origine' => 'chantier',
            ],
            'verification_terre' => [
                'sous_travaux' => [
                    'verification_terre_existante',
                    'verification_barrette_coupure',
                ],
                'origine' => 'norme',
            ],
        ],

        'travaux_conditionnels' => [
            'protection_surtension' => [
                'condition' => [
                    'type' => 'or',
                    'conditions' => [
                        ['type' => 'context_has', 'champs' => ['zone_foudre']],
                        ['type' => 'context_has', 'champs' => ['demande_parafoudre']],
                    ],
                ],
                'sous_travaux' => [
                    'installation_protection_surtension',
                ],
                'origine' => 'norme',
            ],
            'gestion_chauffe_eau' => [
                'condition' => [
                    'type' => 'and',
                    'conditions' => [
                        ['type' => 'context_has', 'champs' => ['chauffe_eau_electrique']],
                        ['type' => 'context_has', 'champs' => ['abonnement_hphc']],
                    ],
                ],
                'sous_travaux' => [
                    'installation_contacteur_hphc',
                ],
                'origine' => 'chantier',
            ],
            'commandes_centralisees' => [
                'condition' => [
                    'type' => 'field_gt',
                    'champ' => 'nb_va_et_vient',
                    'valeur' => 2,
                ],
                'sous_travaux' => [
                    'installation_telerupteurs',
                ],
                'origine' => 'chantier',
            ],
            'gestion_puissance' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['risque_depassement_puissance'],
                ],
                'sous_travaux' => [
                    'installation_delesteur',
                ],
                'origine' => 'commercial',
            ],
        ],

        'domaines_obligatoires_a_couvrir' => [
            'coffret_reserve' => [
                'label' => 'Coffret avec réserve 20%',
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'reserve_tableau_20_pourcent',
                ],
                'action_si_absent' => 'augmenter_taille_coffret',
                'origine' => 'norme',
            ],
            'protection_differentielle' => [
                'label' => 'Protection différentielle 30mA',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'installation_protections_differentielles',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'norme',
            ],
            'protection_generale' => [
                'label' => 'Disjoncteur de branchement',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'installation_protection_generale',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'norme',
            ],
            'reperage' => [
                'label' => 'Repérage des circuits',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'reperage_etiquetage',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'norme',
            ],
        ],

        'oublis_frequents' => [
            'cache_bornes' => [
                'label' => 'Cache-bornes',
                'besoin_technique' => 'cache_bornes',
                'calcul' => [
                    'type' => 'field',
                    'champ' => 'nb_rangees',
                ],
                'origine' => 'chantier',
            ],
            'peignes_verticaux' => [
                'label' => 'Peignes verticaux',
                'besoin_technique' => 'peignes_verticaux',
                'calcul' => [
                    'type' => 'conditional_formula',
                    'condition' => ['type' => 'field_gt', 'champ' => 'nb_rangees', 'valeur' => 1],
                    'si_vrai' => ['type' => 'formula', 'expression' => 'nb_rangees - 1'],
                    'si_faux' => ['type' => 'fixed', 'valeur' => 0],
                ],
                'origine' => 'chantier',
            ],
            'obturateurs' => [
                'label' => 'Obturateurs modules vides',
                'besoin_technique' => 'obturateurs',
                'calcul' => [
                    'type' => 'field',
                    'champ' => 'nb_modules_libres',
                ],
                'origine' => 'chantier',
            ],
            'etiquettes' => [
                'label' => 'Étiquettes de repérage',
                'besoin_technique' => 'etiquettes_reperage',
                'calcul' => [
                    'type' => 'field',
                    'champ' => 'nb_circuits',
                ],
                'origine' => 'chantier',
            ],
            'schema_unifilaire' => [
                'label' => 'Schéma unifilaire',
                'besoin_technique' => 'schema_unifilaire',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'commercial',
            ],
        ],

        'controles_finals' => [
            'reserve_tableau' => [
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'reserve_tableau_20_pourcent',
                ],
                'message' => 'Réserve de 20% non respectée',
                'origine' => 'norme',
            ],
            'repartition_differentiels' => [
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'max_circuits_par_differentiel',
                ],
                'message' => 'Trop de circuits par différentiel (max 8)',
                'origine' => 'norme',
            ],
            'type_differentiel_special' => [
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'type_differentiel_requis',
                ],
                'message' => 'Type de différentiel non conforme',
                'origine' => 'norme',
            ],
        ],

        'questions_qualification' => [
            'nb_circuits_existants' => [
                'question' => 'Combien de circuits existants ?',
                'type' => 'number',
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['taille_coffret', 'nb_disjoncteurs'],
                'origine' => 'chantier',
            ],
            'puissance_abonnement' => [
                'question' => 'Puissance d\'abonnement ?',
                'type' => 'select',
                'options' => ['6kVA', '9kVA', '12kVA', '15kVA', '18kVA', '36kVA'],
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['calibre_protection_generale'],
                'origine' => 'chantier',
            ],
            'chauffe_eau' => [
                'question' => 'Type de chauffe-eau ?',
                'type' => 'select',
                'options' => ['electrique_hphc', 'electrique_permanent', 'gaz', 'thermodynamique', 'aucun'],
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => 'aucun',
                'impact' => ['contacteur_hphc'],
                'origine' => 'chantier',
            ],
            'equipements_specifiques' => [
                'question' => 'Équipements nécessitant circuit dédié ?',
                'type' => 'multiselect',
                'options' => ['plaque_induction', 'four', 'lave_linge', 'seche_linge', 'climatisation', 'piscine', 'borne_recharge'],
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => [],
                'impact' => ['nb_circuits', 'type_differentiel'],
                'origine' => 'chantier',
            ],
        ],
    ],

    'borne_irve' => [
        'id' => 'borne_irve',
        'label' => 'Installation borne de recharge IRVE',
        'description' => 'Installation d\'une borne de recharge pour véhicule électrique',
        'contexte_tva_defaut' => 'tva_reduite_irve',

        'travaux_base' => [
            'installation_borne' => [
                'sous_travaux' => [
                    'pose_borne_murale',
                    'parametrage_borne',
                    'mise_en_service_borne',
                ],
                'origine' => 'chantier',
            ],
            'alimentation_borne' => [
                'sous_travaux' => [
                    'tirage_cable_alimentation',
                    'raccordement_tableau',
                ],
                'origine' => 'chantier',
            ],
            'protection_borne' => [
                'sous_travaux' => [
                    'installation_protection_circuit',
                    'installation_protection_differentielle_dediee',
                ],
                'origine' => 'norme',
            ],
        ],

        'travaux_conditionnels' => [
            'augmentation_puissance' => [
                'condition' => [
                    'type' => 'field_gt',
                    'champ' => 'puissance_borne',
                    'champ_compare' => 'puissance_disponible',
                ],
                'sous_travaux' => [
                    'demande_augmentation_abonnement',
                ],
                'origine' => 'chantier',
            ],
            'passage_triphase' => [
                'condition' => [
                    'type' => 'field_gte',
                    'champ' => 'puissance_borne_kw',
                    'valeur' => 11,
                ],
                'sous_travaux' => [
                    'verification_alimentation_triphase',
                    'equilibrage_phases',
                ],
                'origine' => 'norme',
            ],
            'coffret_secondaire' => [
                'condition' => [
                    'type' => 'field_gt',
                    'champ' => 'distance_tableau',
                    'valeur' => 25,
                ],
                'sous_travaux' => [
                    'installation_coffret_secondaire',
                ],
                'origine' => 'norme',
            ],
            'cheminement_exterieur' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['passage_exterieur'],
                ],
                'sous_travaux' => [
                    'installation_cheminement_etanche',
                    'protection_mecanique_cable',
                ],
                'origine' => 'chantier',
            ],
        ],

        'domaines_obligatoires_a_couvrir' => [
            'borne_conforme' => [
                'label' => 'Borne certifiée',
                'verification' => [
                    'type' => 'has_besoin_in_list',
                    'liste' => ['borne_7kw', 'borne_11kw', 'borne_22kw'],
                ],
                'action_si_absent' => 'ajouter_besoin',
                'origine' => 'norme',
            ],
            'protection_differentielle_adaptee' => [
                'label' => 'Protection différentielle type A ou F',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'installation_protection_differentielle_dediee',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'norme',
            ],
            'section_cable_adaptee' => [
                'label' => 'Section de câble conforme',
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'section_cable_irve',
                ],
                'action_si_absent' => 'ajuster_section',
                'origine' => 'norme',
            ],
            'certification_installateur' => [
                'label' => 'Certification IRVE',
                'verification' => [
                    'type' => 'has_besoin',
                    'besoin' => 'certificat_irve',
                ],
                'action_si_absent' => 'ajouter_besoin',
                'origine' => 'norme',
            ],
        ],

        'oublis_frequents' => [
            'cheminement_cable' => [
                'label' => 'Cheminement câble',
                'besoin_technique' => 'goulotte_ou_tube',
                'calcul' => [
                    'type' => 'field',
                    'champ' => 'distance_tableau',
                ],
                'origine' => 'chantier',
            ],
            'support_borne' => [
                'label' => 'Support mural ou pied',
                'besoin_technique' => 'support_borne',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'chantier',
            ],
            'verification_terre' => [
                'label' => 'Vérification terre',
                'besoin_technique' => 'mesure_terre',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'norme',
            ],
            'certificat_conformite' => [
                'label' => 'Certificat IRVE',
                'besoin_technique' => 'certificat_irve',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'norme',
            ],
        ],

        'controles_finals' => [
            'puissance_disponible' => [
                'verification' => [
                    'type' => 'comparison',
                    'gauche' => 'puissance_abonnement',
                    'operateur' => 'gte',
                    'droite' => ['type' => 'formula', 'expression' => 'puissance_borne + puissance_existante'],
                ],
                'message' => 'Puissance d\'abonnement insuffisante',
                'origine' => 'chantier',
            ],
            'section_cable_conforme' => [
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'section_cable_irve',
                ],
                'message' => 'Section de câble non conforme',
                'origine' => 'norme',
            ],
            'longueur_cable_max' => [
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'longueur_max_cable_irve',
                ],
                'message' => 'Longueur de câble excessive',
                'origine' => 'norme',
            ],
        ],

        'questions_qualification' => [
            'puissance_borne' => [
                'question' => 'Puissance de borne souhaitée ?',
                'type' => 'select',
                'options' => ['7.4kW (mono)', '11kW (tri)', '22kW (tri)'],
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['borne', 'cable', 'protection'],
                'origine' => 'chantier',
            ],
            'marque_borne' => [
                'question' => 'Préférence de marque ?',
                'type' => 'select',
                'options' => ['Wallbox', 'Schneider', 'Legrand', 'Hager', 'sans_preference'],
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => 'sans_preference',
                'impact' => ['reference_borne'],
                'origine' => 'commercial',
            ],
            'distance_tableau' => [
                'question' => 'Distance tableau - emplacement borne ?',
                'type' => 'number',
                'unite' => 'm',
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['metrage_cable', 'section_cable'],
                'origine' => 'chantier',
            ],
            'passage_cable' => [
                'question' => 'Type de passage pour le câble ?',
                'type' => 'select',
                'options' => ['interieur_existant', 'exterieur', 'enterrement', 'apparent'],
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => 'interieur_existant',
                'impact' => ['cheminement'],
                'origine' => 'chantier',
            ],
            'installation_existante' => [
                'question' => 'Type d\'installation existante ?',
                'type' => 'select',
                'options' => ['monophase', 'triphase'],
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['puissance_max', 'travaux_triphase'],
                'origine' => 'chantier',
            ],
        ],
    ],

    'cuisine' => [
        'id' => 'cuisine',
        'label' => 'Électricité cuisine',
        'description' => 'Installation ou rénovation électrique d\'une cuisine',
        'contexte_tva_defaut' => 'renovation_plus_2_ans',

        'travaux_base' => [
            'circuits_specialises' => [
                'sous_travaux' => [
                    'installation_circuit_cuisson',
                    'installation_circuit_four',
                    'installation_circuit_lave_vaisselle',
                ],
                'origine' => 'norme',
            ],
            'distribution_prises_cuisine' => [
                'sous_travaux' => [
                    'installation_prises_plan_travail',
                    'installation_prise_refrigerateur',
                ],
                'origine' => 'norme',
            ],
            'eclairage_cuisine' => [
                'sous_travaux' => [
                    'installation_eclairage_general',
                    'installation_eclairage_plan_travail',
                    'installation_commande_eclairage',
                ],
                'origine' => 'chantier',
            ],
        ],

        'travaux_conditionnels' => [
            'alimentation_hotte' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['hotte'],
                ],
                'sous_travaux' => [
                    'installation_alimentation_hotte',
                    'installation_commande_hotte',
                ],
                'origine' => 'chantier',
            ],
            'equipement_ilot' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['ilot_central'],
                ],
                'sous_travaux' => [
                    'installation_prises_ilot',
                    'passage_sol',
                ],
                'origine' => 'chantier',
            ],
            'circuit_lave_linge' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['lave_linge_cuisine'],
                ],
                'sous_travaux' => [
                    'installation_circuit_lave_linge',
                ],
                'origine' => 'norme',
            ],
            'circuit_congelateur' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['congelateur_separe'],
                ],
                'sous_travaux' => [
                    'installation_circuit_congelateur',
                ],
                'origine' => 'chantier',
            ],
        ],

        'domaines_obligatoires_a_couvrir' => [
            'circuit_cuisson' => [
                'label' => 'Circuit cuisson 32A',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'installation_circuit_cuisson',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'norme',
            ],
            'circuit_four' => [
                'label' => 'Circuit four 20A',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'installation_circuit_four',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'norme',
            ],
            'prises_plan_travail' => [
                'label' => 'Minimum 4 prises plan de travail',
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'nb_prises_plan_travail_cuisine',
                ],
                'action_si_absent' => 'completer_quantites',
                'origine' => 'norme',
            ],
            'eclairage' => [
                'label' => 'Éclairage cuisine',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'installation_eclairage_general',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'norme',
            ],
            'differentiel_induction' => [
                'label' => 'Différentiel type A si induction',
                'verification' => [
                    'type' => 'conditional_delegate',
                    'condition' => ['type' => 'field_eq', 'champ' => 'type_cuisson', 'valeur' => 'induction'],
                    'delegate' => ['type' => 'delegate_normes_engine', 'regle' => 'differentiel_type_a_requis'],
                ],
                'action_si_absent' => 'ajouter_besoin_type_a',
                'origine' => 'norme',
            ],
        ],

        'oublis_frequents' => [
            'prise_micro_ondes' => [
                'label' => 'Prise micro-ondes',
                'besoin_technique' => 'prise_supplementaire',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'commercial',
            ],
            'prises_petit_electro' => [
                'label' => 'Prises petit électroménager',
                'besoin_technique' => 'prises_plan_travail',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 2,
                ],
                'origine' => 'commercial',
            ],
            'eclairage_sous_meubles' => [
                'label' => 'Éclairage sous meubles hauts',
                'besoin_technique' => 'reglettes_led',
                'calcul' => [
                    'type' => 'formula',
                    'expression' => 'ceil(longueur_plan_travail / 0.6)',
                ],
                'origine' => 'commercial',
            ],
            'sortie_cable_hotte' => [
                'label' => 'Sortie câble hotte',
                'besoin_technique' => 'sortie_cable',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'chantier',
            ],
        ],

        'controles_finals' => [
            'nb_prises_plan_travail' => [
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'nb_prises_plan_travail_cuisine',
                ],
                'message' => 'Nombre de prises plan de travail insuffisant',
                'origine' => 'norme',
            ],
            'protection_cuisson' => [
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'protection_circuit_cuisson',
                ],
                'message' => 'Protection cuisson non conforme',
                'origine' => 'norme',
            ],
        ],

        'questions_qualification' => [
            'type_cuisson' => [
                'question' => 'Type de plaque de cuisson ?',
                'type' => 'select',
                'options' => ['induction', 'vitroceramique', 'gaz', 'mixte'],
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['circuit_cuisson', 'type_differentiel'],
                'origine' => 'chantier',
            ],
            'four_separe' => [
                'question' => 'Four séparé de la plaque ?',
                'type' => 'boolean',
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'valeur_defaut' => true,
                'impact' => ['circuit_four'],
                'origine' => 'chantier',
            ],
            'electromenager' => [
                'question' => 'Électroménager à alimenter ?',
                'type' => 'multiselect',
                'options' => ['lave_vaisselle', 'lave_linge', 'seche_linge', 'congelateur', 'cave_a_vin'],
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => ['lave_vaisselle'],
                'impact' => ['circuits_dedies'],
                'origine' => 'chantier',
            ],
            'ilot_central' => [
                'question' => 'Présence d\'un îlot central ?',
                'type' => 'boolean',
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => false,
                'impact' => ['prises_ilot'],
                'origine' => 'chantier',
            ],
            'hotte' => [
                'question' => 'Type de hotte ?',
                'type' => 'select',
                'options' => ['aspirante', 'recyclage', 'plan_travail', 'aucune'],
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => 'aucune',
                'impact' => ['alimentation_hotte'],
                'origine' => 'chantier',
            ],
        ],
    ],

    'salle_de_bain' => [
        'id' => 'salle_de_bain',
        'label' => 'Électricité salle de bain',
        'description' => 'Installation ou rénovation électrique d\'une salle de bain',
        'contexte_tva_defaut' => 'renovation_plus_2_ans',

        'travaux_base' => [
            'eclairage_sdb' => [
                'sous_travaux' => [
                    'installation_point_lumineux_hors_volume',
                    'installation_commande_hors_volume',
                ],
                'origine' => 'norme',
            ],
            'liaison_equipotentielle' => [
                'sous_travaux' => [
                    'realisation_liaison_equipotentielle_locale',
                ],
                'origine' => 'norme',
            ],
        ],

        'travaux_conditionnels' => [
            'chauffage_sdb' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['seche_serviettes'],
                ],
                'sous_travaux' => [
                    'installation_alimentation_seche_serviettes',
                ],
                'origine' => 'chantier',
            ],
            'circuit_lave_linge' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['lave_linge_sdb'],
                ],
                'sous_travaux' => [
                    'installation_circuit_lave_linge',
                    'installation_prise_hors_volume',
                ],
                'origine' => 'norme',
            ],
            'circuit_seche_linge' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['seche_linge_sdb'],
                ],
                'sous_travaux' => [
                    'installation_circuit_seche_linge',
                    'installation_prise_hors_volume',
                ],
                'origine' => 'norme',
            ],
            'eclairage_miroir' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['miroir_eclairant'],
                ],
                'sous_travaux' => [
                    'installation_alimentation_miroir',
                ],
                'origine' => 'chantier',
            ],
            'equipement_balneo' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['baignoire_balneo'],
                ],
                'sous_travaux' => [
                    'installation_alimentation_balneo',
                    'installation_protection_dediee_balneo',
                ],
                'origine' => 'norme',
            ],
            'ventilation_forcee' => [
                'condition' => [
                    'type' => 'and',
                    'conditions' => [
                        ['type' => 'not', 'condition' => ['type' => 'context_has', 'champs' => ['vmc_existante']]],
                        ['type' => 'context_has', 'champs' => ['piece_aveugle']],
                    ],
                ],
                'sous_travaux' => [
                    'installation_extracteur',
                    'installation_temporisation',
                ],
                'origine' => 'norme',
            ],
        ],

        'domaines_obligatoires_a_couvrir' => [
            'volumes_securite' => [
                'label' => 'Respect des volumes de sécurité',
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'volumes_sdb',
                ],
                'action_si_absent' => 'signaler_audit',
                'origine' => 'norme',
            ],
            'liaison_equipotentielle' => [
                'label' => 'Liaison équipotentielle locale',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'realisation_liaison_equipotentielle_locale',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'norme',
            ],
            'ip_materiel' => [
                'label' => 'IP adapté aux volumes',
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'ip_volumes_sdb',
                ],
                'action_si_absent' => 'signaler_audit',
                'origine' => 'norme',
            ],
            'protection_30ma' => [
                'label' => 'Protection différentielle 30mA',
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'protection_differentielle_sdb',
                ],
                'action_si_absent' => 'ajouter_besoin',
                'origine' => 'norme',
            ],
        ],

        'oublis_frequents' => [
            'prise_hors_volume' => [
                'label' => 'Prise hors volume',
                'besoin_technique' => 'prise_sdb',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'commercial',
            ],
            'extracteur' => [
                'label' => 'Extracteur si pièce aveugle',
                'besoin_technique' => 'extracteur',
                'calcul' => [
                    'type' => 'conditional_fixed',
                    'condition' => [
                        'type' => 'and',
                        'conditions' => [
                            ['type' => 'context_has', 'champs' => ['piece_aveugle']],
                            ['type' => 'not', 'condition' => ['type' => 'context_has', 'champs' => ['vmc_existante']]],
                        ],
                    ],
                    'si_vrai' => 1,
                    'si_faux' => 0,
                ],
                'origine' => 'norme',
            ],
            'boites_etanches' => [
                'label' => 'Boîtes de dérivation étanches',
                'besoin_technique' => 'boite_derivation_etanche',
                'calcul' => [
                    'type' => 'field',
                    'champ' => 'nb_derivations_sdb',
                ],
                'origine' => 'norme',
            ],
        ],

        'controles_finals' => [
            'volumes_respectes' => [
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'volumes_sdb',
                ],
                'message' => 'Volumes de sécurité non respectés',
                'origine' => 'norme',
            ],
            'liaison_equipotentielle' => [
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'realisation_liaison_equipotentielle_locale',
                ],
                'message' => 'Liaison équipotentielle absente',
                'origine' => 'norme',
            ],
        ],

        'questions_qualification' => [
            'surface' => [
                'question' => 'Surface de la salle de bain ?',
                'type' => 'number',
                'unite' => 'm²',
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['volumes', 'nb_points_lumineux'],
                'origine' => 'chantier',
            ],
            'type_douche_bain' => [
                'question' => 'Douche ou baignoire ?',
                'type' => 'select',
                'options' => ['douche', 'baignoire', 'les_deux', 'douche_italienne'],
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['volumes'],
                'origine' => 'chantier',
            ],
            'seche_serviettes' => [
                'question' => 'Sèche-serviettes souhaité ?',
                'type' => 'boolean',
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => false,
                'impact' => ['circuit_chauffage'],
                'origine' => 'commercial',
            ],
            'lave_linge' => [
                'question' => 'Lave-linge dans la salle de bain ?',
                'type' => 'boolean',
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => false,
                'impact' => ['circuit_lave_linge'],
                'origine' => 'chantier',
            ],
            'piece_aveugle' => [
                'question' => 'Pièce sans fenêtre ?',
                'type' => 'boolean',
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => false,
                'impact' => ['extracteur'],
                'origine' => 'chantier',
            ],
        ],
    ],

    'vmc' => [
        'id' => 'vmc',
        'label' => 'Installation VMC',
        'description' => 'Installation ou remplacement d\'une VMC',
        'contexte_tva_defaut' => 'renovation_plus_2_ans',

        'travaux_base' => [
            'installation_groupe' => [
                'sous_travaux' => [
                    'pose_groupe_vmc',
                    'raccordement_electrique_vmc',
                ],
                'origine' => 'chantier',
            ],
            'reseau_extraction' => [
                'sous_travaux' => [
                    'pose_bouches_extraction',
                    'tirage_gaines',
                ],
                'origine' => 'chantier',
            ],
            'evacuation' => [
                'sous_travaux' => [
                    'installation_sortie_exterieure',
                ],
                'origine' => 'chantier',
            ],
        ],

        'travaux_conditionnels' => [
            'double_flux' => [
                'condition' => [
                    'type' => 'field_eq',
                    'champ' => 'type_vmc',
                    'valeur' => 'double_flux',
                ],
                'sous_travaux' => [
                    'installation_echangeur',
                    'tirage_gaines_insufflation',
                    'pose_bouches_insufflation',
                ],
                'origine' => 'chantier',
            ],
            'bouches_hygroreglables' => [
                'condition' => [
                    'type' => 'field_eq',
                    'champ' => 'type_vmc',
                    'valeur' => 'hygro_b',
                ],
                'sous_travaux' => [
                    'pose_bouches_hygroreglables',
                ],
                'origine' => 'chantier',
            ],
            'entrees_air' => [
                'condition' => [
                    'type' => 'field_neq',
                    'champ' => 'type_vmc',
                    'valeur' => 'double_flux',
                ],
                'sous_travaux' => [
                    'installation_entrees_air',
                ],
                'origine' => 'norme',
            ],
            'isolation_gaines' => [
                'condition' => [
                    'type' => 'context_has',
                    'champs' => ['gaines_combles_non_isoles'],
                ],
                'sous_travaux' => [
                    'isolation_gaines_combles',
                ],
                'origine' => 'chantier',
            ],
        ],

        'domaines_obligatoires_a_couvrir' => [
            'groupe' => [
                'label' => 'Groupe VMC',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'pose_groupe_vmc',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'chantier',
            ],
            'bouches_pieces_humides' => [
                'label' => 'Bouches dans pièces humides',
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'bouches_vmc_pieces_humides',
                ],
                'action_si_absent' => 'completer_quantites',
                'origine' => 'norme',
            ],
            'entrees_air' => [
                'label' => 'Entrées d\'air pièces sèches',
                'verification' => [
                    'type' => 'conditional_delegate',
                    'condition' => ['type' => 'field_neq', 'champ' => 'type_vmc', 'valeur' => 'double_flux'],
                    'delegate' => ['type' => 'delegate_normes_engine', 'regle' => 'entrees_air_pieces_seches'],
                ],
                'action_si_absent' => 'completer_quantites',
                'origine' => 'norme',
            ],
            'sortie_exterieure' => [
                'label' => 'Sortie vers extérieur',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'installation_sortie_exterieure',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'norme',
            ],
            'alimentation_electrique' => [
                'label' => 'Alimentation électrique dédiée',
                'verification' => [
                    'type' => 'has_sous_travail',
                    'sous_travail' => 'raccordement_electrique_vmc',
                ],
                'action_si_absent' => 'ajouter_sous_travail',
                'origine' => 'norme',
            ],
        ],

        'oublis_frequents' => [
            'bouche_cuisine_debit' => [
                'label' => 'Bouche cuisine haut débit',
                'besoin_technique' => 'bouche_cuisine_debit_variable',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'norme',
            ],
            'manchons' => [
                'label' => 'Manchons de raccordement',
                'besoin_technique' => 'manchons_vmc',
                'calcul' => [
                    'type' => 'formula',
                    'expression' => 'nb_bouches + 1',
                ],
                'origine' => 'chantier',
            ],
            'colliers' => [
                'label' => 'Colliers de serrage',
                'besoin_technique' => 'colliers_serrage',
                'calcul' => [
                    'type' => 'formula',
                    'expression' => 'ceil(metrage_gaines * 2)',
                ],
                'origine' => 'chantier',
            ],
            'piquages' => [
                'label' => 'Piquages / réductions',
                'besoin_technique' => 'piquages_vmc',
                'calcul' => [
                    'type' => 'field',
                    'champ' => 'nb_bouches',
                ],
                'origine' => 'chantier',
            ],
            'protection_electrique' => [
                'label' => 'Disjoncteur 2A dédié',
                'besoin_technique' => 'disjoncteur_2a',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'norme',
            ],
            'mise_en_service' => [
                'label' => 'Réglage des débits',
                'besoin_technique' => 'mise_en_service_vmc',
                'calcul' => [
                    'type' => 'fixed',
                    'valeur' => 1,
                ],
                'origine' => 'commercial',
            ],
        ],

        'controles_finals' => [
            'debit_extraction' => [
                'verification' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'debit_extraction_vmc',
                ],
                'message' => 'Débit d\'extraction insuffisant',
                'origine' => 'norme',
            ],
            'equilibrage' => [
                'verification' => [
                    'type' => 'conditional_delegate',
                    'condition' => ['type' => 'field_eq', 'champ' => 'type_vmc', 'valeur' => 'double_flux'],
                    'delegate' => ['type' => 'delegate_normes_engine', 'regle' => 'equilibrage_double_flux'],
                ],
                'message' => 'Déséquilibre insufflation/extraction',
                'origine' => 'norme',
            ],
        ],

        'questions_qualification' => [
            'type_vmc' => [
                'question' => 'Type de VMC souhaitée ?',
                'type' => 'select',
                'options' => ['simple_flux_auto', 'hygro_a', 'hygro_b', 'double_flux'],
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['groupe', 'gaines', 'bouches'],
                'origine' => 'chantier',
            ],
            'nb_pieces_humides' => [
                'question' => 'Nombre de pièces humides ?',
                'type' => 'number',
                'priorite' => 1,
                'obligatoire_pour_chiffrage' => true,
                'impact' => ['nb_bouches', 'debit'],
                'origine' => 'chantier',
            ],
            'acces_combles' => [
                'question' => 'Accès aux combles possible ?',
                'type' => 'boolean',
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => true,
                'impact' => ['emplacement_groupe', 'type_gaines'],
                'origine' => 'chantier',
            ],
            'sortie_existante' => [
                'question' => 'Sortie toiture existante ?',
                'type' => 'boolean',
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => false,
                'impact' => ['travaux_toiture'],
                'origine' => 'chantier',
            ],
            'type_logement' => [
                'question' => 'Type de logement ?',
                'type' => 'select',
                'options' => ['appartement', 'maison', 'studio'],
                'priorite' => 2,
                'obligatoire_pour_chiffrage' => false,
                'valeur_defaut' => 'maison',
                'impact' => ['debit_requis', 'type_sortie'],
                'origine' => 'chantier',
            ],
        ],
    ],
];
