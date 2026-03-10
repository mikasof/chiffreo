<?php

/**
 * Définition des travaux et sous-travaux de référence
 *
 * PRINCIPES :
 * - Les sous-travaux sont des ACTIONS MÉTIER (verbes d'action)
 * - Chaque sous-travail génère des BESOINS TECHNIQUES
 * - Les besoins techniques sont résolus en PRODUITS par le CatalogResolver
 * - Les règles de quantité utilisent des structures, pas des chaînes
 *
 * COHÉRENCE avec chantier_types.php :
 * - Les IDs de sous-travaux correspondent à ceux référencés dans chantier_types
 */

return [
    /**
     * ═══════════════════════════════════════════════════════════════════
     * TABLEAU ÉLECTRIQUE
     * ═══════════════════════════════════════════════════════════════════
     */
    'tableau_electrique' => [
        'id' => 'tableau_electrique',
        'label' => 'Tableau électrique',
        'description' => 'Installation, remplacement ou mise aux normes du tableau électrique',
        'categorie' => 'distribution',

        'sous_travaux' => [
            'depose_ancien_tableau' => [
                'label' => 'Dépose ancien tableau',
                'description' => 'Démontage et évacuation de l\'ancien tableau',
                'besoins_techniques' => [
                    ['type' => 'main_oeuvre', 'code' => 'mo_depose_tableau'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_coffret' => [
                'label' => 'Installation du coffret',
                'description' => 'Pose et fixation du coffret/tableau',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'coffret_tableau',
                        'selection' => [
                            'type' => 'selon_contexte',
                            'champ' => 'nb_modules_requis',
                            'regles' => [
                                ['condition' => ['type' => 'field_lte', 'valeur' => 13], 'produit' => 'coffret_1_rangee'],
                                ['condition' => ['type' => 'field_lte', 'valeur' => 26], 'produit' => 'coffret_2_rangees'],
                                ['condition' => ['type' => 'field_lte', 'valeur' => 39], 'produit' => 'coffret_3_rangees'],
                                ['condition' => ['type' => 'default'], 'produit' => 'coffret_4_rangees'],
                            ],
                        ],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_coffret'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_protection_generale' => [
                'label' => 'Installation protection générale',
                'description' => 'Pose du disjoncteur de branchement',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'disjoncteur_branchement',
                        'selection' => [
                            'type' => 'selon_contexte',
                            'champ' => 'puissance_abonnement',
                            'regles' => [
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'monophase_15_45'], 'produit' => 'db_mono_15_45a'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'monophase_30_60'], 'produit' => 'db_mono_30_60a'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'triphase_10_30'], 'produit' => 'db_tri_10_30a'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'triphase_30_60'], 'produit' => 'db_tri_30_60a'],
                            ],
                        ],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_db'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_protections_differentielles' => [
                'label' => 'Installation protections différentielles',
                'description' => 'Pose des interrupteurs différentiels 30mA',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'interrupteur_differentiel',
                        'variantes' => ['id_type_ac_40a', 'id_type_ac_63a', 'id_type_a_40a', 'id_type_a_63a', 'id_type_f_40a'],
                        'selection' => ['type' => 'delegate_normes_engine', 'regle' => 'type_differentiel_requis'],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_id'],
                ],
                'quantite' => [
                    'type' => 'formula',
                    'expression' => 'ceil(nb_circuits / 8)',
                ],
            ],
            'installation_protections_circuits' => [
                'label' => 'Installation protections circuits',
                'description' => 'Pose des disjoncteurs divisionnaires',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'disjoncteur_divisionnaire',
                        'variantes' => ['dj_2a', 'dj_10a', 'dj_16a', 'dj_20a', 'dj_25a', 'dj_32a'],
                        'selection' => ['type' => 'selon_circuit'],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_disjoncteur'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_circuits',
                ],
            ],
            'raccordement_alimentation' => [
                'label' => 'Raccordement alimentation générale',
                'description' => 'Raccordement depuis le compteur',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_alimentation_tableau'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_raccordement_alimentation'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'raccordement_circuits_existants' => [
                'label' => 'Raccordement circuits existants',
                'description' => 'Reconnexion des circuits existants au nouveau tableau',
                'besoins_techniques' => [
                    ['type' => 'consommable', 'code' => 'bornes_connexion'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_raccordement_circuit'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_circuits_existants',
                ],
            ],
            'reperage_etiquetage' => [
                'label' => 'Repérage et étiquetage',
                'description' => 'Étiquetage des circuits et schéma',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'lot_etiquettes'],
                    ['type' => 'materiel', 'code' => 'schema_unifilaire'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_reperage'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_protection_surtension' => [
                'label' => 'Installation protection surtension',
                'description' => 'Pose d\'un parafoudre',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'parafoudre',
                        'selection' => [
                            'type' => 'selon_contexte',
                            'champ' => 'type_installation',
                            'regles' => [
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'monophase'], 'produit' => 'parafoudre_type2_mono'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'triphase'], 'produit' => 'parafoudre_type2_tri'],
                            ],
                        ],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_parafoudre'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_contacteur_hphc' => [
                'label' => 'Installation contacteur HP/HC',
                'description' => 'Pose du contacteur jour/nuit pour chauffe-eau',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'contacteur_hphc'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_contacteur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_telerupteurs' => [
                'label' => 'Installation télérupteurs',
                'description' => 'Pose de télérupteurs pour commandes multiples',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'telerupteur_16a'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_telerupteur'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_telerupteurs',
                ],
            ],
            'installation_delesteur' => [
                'label' => 'Installation délesteur',
                'description' => 'Pose d\'un délesteur pour gestion de puissance',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'delesteur'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_delesteur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'reserve_20_pourcent',
            'max_8_circuits_par_differentiel',
            'type_differentiel_selon_circuit',
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * MISE À LA TERRE
     * ═══════════════════════════════════════════════════════════════════
     */
    'mise_a_la_terre' => [
        'id' => 'mise_a_la_terre',
        'label' => 'Mise à la terre',
        'description' => 'Vérification ou création de la prise de terre',
        'categorie' => 'securite',

        'sous_travaux' => [
            'verification_terre_existante' => [
                'label' => 'Vérification terre existante',
                'description' => 'Mesure de la résistance de terre',
                'besoins_techniques' => [
                    ['type' => 'main_oeuvre', 'code' => 'mo_mesure_terre'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'creation_prise_terre' => [
                'label' => 'Création prise de terre',
                'description' => 'Installation d\'un piquet de terre',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'piquet_terre_1m50'],
                    ['type' => 'materiel', 'code' => 'regard_terre'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_creation_terre'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_barrette_coupure' => [
                'label' => 'Installation barrette de coupure',
                'description' => 'Pose de la barrette de mesure',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'barrette_coupure'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_barrette'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'verification_barrette_coupure' => [
                'label' => 'Vérification barrette de coupure',
                'description' => 'Contrôle de la barrette existante',
                'besoins_techniques' => [
                    ['type' => 'main_oeuvre', 'code' => 'mo_verification_barrette'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'tirage_conducteur_principal' => [
                'label' => 'Tirage conducteur principal de terre',
                'description' => 'Pose du câble de terre vers le tableau',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_terre_16mm2'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_tirage_terre'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'distance_terre_tableau',
                    'unite' => 'ml',
                ],
            ],
            'realisation_liaison_equipotentielle_locale' => [
                'label' => 'Liaison équipotentielle locale',
                'description' => 'Raccordement des éléments métalliques (SDB)',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_terre_4mm2'],
                    ['type' => 'materiel', 'code' => 'borne_equipotentielle'],
                    ['type' => 'materiel', 'code' => 'colliers_terre'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_liaison_equipotentielle'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_elements_metalliques',
                ],
            ],
        ],

        'contraintes_normes' => [
            'resistance_terre_max_100_ohms',
            'section_conducteur_selon_installation',
        ],
    ],

    /**
     * Vérification terre (remplacement tableau)
     */
    'verification_terre' => [
        'id' => 'verification_terre',
        'label' => 'Vérification terre',
        'description' => 'Contrôle de la prise de terre existante',
        'categorie' => 'securite',

        'sous_travaux' => [
            'verification_terre_existante' => [
                'label' => 'Vérification terre existante',
                'description' => 'Mesure de la résistance de terre',
                'besoins_techniques' => [
                    ['type' => 'main_oeuvre', 'code' => 'mo_mesure_terre'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'verification_barrette_coupure' => [
                'label' => 'Vérification barrette de coupure',
                'description' => 'Contrôle de la barrette existante',
                'besoins_techniques' => [
                    ['type' => 'main_oeuvre', 'code' => 'mo_verification_barrette'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'resistance_terre_max_100_ohms',
        ],
    ],

    /**
     * Gestion chauffe-eau (remplacement tableau)
     */
    'gestion_chauffe_eau' => [
        'id' => 'gestion_chauffe_eau',
        'label' => 'Gestion chauffe-eau',
        'description' => 'Contacteur HP/HC pour chauffe-eau électrique',
        'categorie' => 'distribution',

        'sous_travaux' => [
            'installation_contacteur_hphc' => [
                'label' => 'Installation contacteur HP/HC',
                'description' => 'Pose du contacteur jour/nuit pour chauffe-eau',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'contacteur_hphc'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_contacteur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DISTRIBUTION PRISES
     * ═══════════════════════════════════════════════════════════════════
     */
    'distribution_prises' => [
        'id' => 'distribution_prises',
        'label' => 'Distribution prises de courant',
        'description' => 'Installation des circuits et prises',
        'categorie' => 'distribution',

        'sous_travaux' => [
            'tirage_lignes_prises' => [
                'label' => 'Tirage lignes prises',
                'description' => 'Passage des câbles pour circuits prises',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'materiel', 'code' => 'gaine_icta_20'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_tirage_cable'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'metrage_cables_prises',
                    'unite' => 'ml',
                ],
            ],
            'pose_appareillage_prises' => [
                'label' => 'Pose prises de courant',
                'description' => 'Installation des prises 2P+T',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_prise'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_prises',
                ],
            ],
            'installation_prises_plan_travail' => [
                'label' => 'Installation prises plan de travail',
                'description' => 'Pose des prises au-dessus du plan de travail cuisine',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_prise'],
                ],
                'quantite' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'nb_prises_plan_travail',
                    'minimum' => 4,
                ],
            ],
            'installation_prise_refrigerateur' => [
                'label' => 'Installation prise réfrigérateur',
                'description' => 'Pose prise dédiée réfrigérateur',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_prise'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_prises_ilot' => [
                'label' => 'Installation prises îlot central',
                'description' => 'Pose prises dans îlot cuisine avec passage sol',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'boite_sol'],
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_prise_sol'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 2],
            ],
            'installation_prise_hors_volume' => [
                'label' => 'Installation prise hors volume SDB',
                'description' => 'Pose prise respectant les volumes',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_prise'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'max_8_prises_par_circuit',
            'section_2_5mm2_protection_20a',
            'hauteur_minimum_5cm',
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DISTRIBUTION ÉCLAIRAGE
     * ═══════════════════════════════════════════════════════════════════
     */
    'distribution_eclairage' => [
        'id' => 'distribution_eclairage',
        'label' => 'Distribution éclairage',
        'description' => 'Installation des circuits d\'éclairage',
        'categorie' => 'eclairage',

        'sous_travaux' => [
            'tirage_lignes_eclairage' => [
                'label' => 'Tirage lignes éclairage',
                'description' => 'Passage des câbles pour circuits éclairage',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_1_5mm2'],
                    ['type' => 'materiel', 'code' => 'gaine_icta_16'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_tirage_cable'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'metrage_cables_eclairage',
                    'unite' => 'ml',
                ],
            ],
            'pose_commandes' => [
                'label' => 'Pose commandes d\'éclairage',
                'description' => 'Installation interrupteurs et boutons poussoirs',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'interrupteur_ou_poussoir'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_interrupteur'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_commandes',
                ],
            ],
            'pose_points_lumineux' => [
                'label' => 'Pose points lumineux',
                'description' => 'Installation DCL et sorties de câbles',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'dcl_plafond'],
                    ['type' => 'materiel', 'code' => 'fiche_dcl'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_dcl'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_points_lumineux',
                ],
            ],
            'installation_eclairage_general' => [
                'label' => 'Installation éclairage général',
                'description' => 'Point lumineux central de la pièce',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'dcl_plafond'],
                    ['type' => 'materiel', 'code' => 'fiche_dcl'],
                    ['type' => 'materiel', 'code' => 'cable_1_5mm2'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_dcl'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_eclairage_plan_travail' => [
                'label' => 'Installation éclairage plan de travail',
                'description' => 'Réglettes LED sous meubles hauts',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'reglette_led_60cm'],
                    ['type' => 'materiel', 'code' => 'cable_1_5mm2'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_reglette'],
                ],
                'quantite' => [
                    'type' => 'formula',
                    'expression' => 'ceil(longueur_plan_travail / 0.6)',
                ],
            ],
            'installation_commande_eclairage' => [
                'label' => 'Installation commande éclairage',
                'description' => 'Interrupteur pour éclairage cuisine',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'interrupteur_simple'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_interrupteur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_point_lumineux_hors_volume' => [
                'label' => 'Installation point lumineux hors volume',
                'description' => 'Éclairage SDB respectant les volumes',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'dcl_plafond'],
                    ['type' => 'materiel', 'code' => 'fiche_dcl'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_dcl'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_commande_hors_volume' => [
                'label' => 'Installation commande hors volume',
                'description' => 'Interrupteur SDB hors volume',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'interrupteur_simple'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_interrupteur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'max_8_points_par_circuit',
            'section_1_5mm2_protection_10a',
            'dcl_obligatoire',
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CIRCUITS SPÉCIALISÉS
     * ═══════════════════════════════════════════════════════════════════
     */
    'circuits_specialises' => [
        'id' => 'circuits_specialises',
        'label' => 'Circuits spécialisés',
        'description' => 'Circuits dédiés pour appareils de forte puissance',
        'categorie' => 'distribution',

        'sous_travaux' => [
            'installation_circuit_cuisson' => [
                'label' => 'Installation circuit cuisson 32A',
                'description' => 'Circuit dédié plaque de cuisson',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_6mm2'],
                    ['type' => 'materiel', 'code' => 'sortie_cable_32a'],
                    ['type' => 'materiel', 'code' => 'disjoncteur_32a'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_circuit_specialise'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_circuit_four' => [
                'label' => 'Installation circuit four 20A',
                'description' => 'Circuit dédié four électrique',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'disjoncteur_20a'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_circuit_specialise'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_circuit_lave_vaisselle' => [
                'label' => 'Installation circuit lave-vaisselle',
                'description' => 'Circuit dédié lave-vaisselle',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'disjoncteur_20a'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_circuit_specialise'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_circuit_lave_linge' => [
                'label' => 'Installation circuit lave-linge',
                'description' => 'Circuit dédié lave-linge',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'disjoncteur_20a'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_circuit_specialise'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_circuit_seche_linge' => [
                'label' => 'Installation circuit sèche-linge',
                'description' => 'Circuit dédié sèche-linge',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'disjoncteur_20a'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_circuit_specialise'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_circuit_congelateur' => [
                'label' => 'Installation circuit congélateur',
                'description' => 'Circuit dédié congélateur',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'disjoncteur_20a'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_circuit_specialise'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_circuits_electromenager' => [
                'label' => 'Installation circuits électroménager',
                'description' => 'Ensemble des circuits électroménager cuisine',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'disjoncteur_20a'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_circuit_specialise'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_appareils_electromenager',
                ],
            ],
            'installation_alimentation_hotte' => [
                'label' => 'Installation alimentation hotte',
                'description' => 'Sortie de câble pour hotte aspirante',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_1_5mm2'],
                    ['type' => 'materiel', 'code' => 'sortie_cable'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_sortie_cable'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_commande_hotte' => [
                'label' => 'Installation commande hotte',
                'description' => 'Interrupteur dédié hotte',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'interrupteur_simple'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_interrupteur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_alimentation_seche_serviettes' => [
                'label' => 'Installation alimentation sèche-serviettes',
                'description' => 'Sortie de câble classe 2 pour sèche-serviettes',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'materiel', 'code' => 'sortie_cable_classe2'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_sortie_cable'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_alimentation_miroir' => [
                'label' => 'Installation alimentation miroir',
                'description' => 'Sortie de câble pour miroir éclairant',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_1_5mm2'],
                    ['type' => 'materiel', 'code' => 'sortie_cable'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_sortie_cable'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_alimentation_balneo' => [
                'label' => 'Installation alimentation balnéo',
                'description' => 'Circuit dédié baignoire balnéo',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'materiel', 'code' => 'boite_derivation_etanche'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_circuit_specialise'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_protection_dediee_balneo' => [
                'label' => 'Installation protection dédiée balnéo',
                'description' => 'Différentiel 30mA dédié balnéo',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'id_type_ac_40a'],
                    ['type' => 'materiel', 'code' => 'disjoncteur_20a'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_protection'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'un_appareil_par_circuit',
            'differentiel_type_a_selon_appareil',
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SALLE DE BAIN - Travaux spécifiques
     * ═══════════════════════════════════════════════════════════════════
     */

    /**
     * SDB - Éclairage
     */
    'eclairage_sdb' => [
        'id' => 'eclairage_sdb',
        'label' => 'Éclairage salle de bain',
        'description' => 'Installation éclairage respectant les volumes',
        'categorie' => 'eclairage',

        'sous_travaux' => [
            'installation_point_lumineux_hors_volume' => [
                'label' => 'Installation point lumineux hors volume',
                'description' => 'Éclairage SDB respectant les volumes',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'dcl_plafond'],
                    ['type' => 'materiel', 'code' => 'fiche_dcl'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_dcl'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_commande_hors_volume' => [
                'label' => 'Installation commande hors volume',
                'description' => 'Interrupteur SDB hors volume',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'interrupteur_simple'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_interrupteur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'respect_volumes_sdb',
            'ip_adapte_volume',
        ],
    ],

    /**
     * SDB - Liaison équipotentielle
     */
    'liaison_equipotentielle' => [
        'id' => 'liaison_equipotentielle',
        'label' => 'Liaison équipotentielle',
        'description' => 'Liaison équipotentielle locale SDB',
        'categorie' => 'securite',

        'sous_travaux' => [
            'realisation_liaison_equipotentielle_locale' => [
                'label' => 'Liaison équipotentielle locale',
                'description' => 'Raccordement des éléments métalliques (SDB)',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_terre_4mm2'],
                    ['type' => 'materiel', 'code' => 'borne_equipotentielle'],
                    ['type' => 'materiel', 'code' => 'colliers_terre'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_liaison_equipotentielle'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_elements_metalliques',
                ],
            ],
        ],

        'contraintes_normes' => [
            'liaison_equipotentielle_obligatoire_sdb',
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CUISINE - Travaux spécifiques
     * ═══════════════════════════════════════════════════════════════════
     */

    /**
     * Cuisine - Distribution prises
     */
    'distribution_prises_cuisine' => [
        'id' => 'distribution_prises_cuisine',
        'label' => 'Distribution prises cuisine',
        'description' => 'Prises plan de travail et réfrigérateur',
        'categorie' => 'distribution',

        'sous_travaux' => [
            'installation_prises_plan_travail' => [
                'label' => 'Installation prises plan de travail',
                'description' => 'Pose des prises au-dessus du plan de travail cuisine',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_prise'],
                ],
                'quantite' => [
                    'type' => 'delegate_normes_engine',
                    'regle' => 'nb_prises_plan_travail',
                    'minimum' => 4,
                ],
            ],
            'installation_prise_refrigerateur' => [
                'label' => 'Installation prise réfrigérateur',
                'description' => 'Pose prise dédiée réfrigérateur',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_prise'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'min_4_prises_plan_travail',
            'prise_dediee_refrigerateur',
        ],
    ],

    /**
     * Cuisine - Éclairage
     */
    'eclairage_cuisine' => [
        'id' => 'eclairage_cuisine',
        'label' => 'Éclairage cuisine',
        'description' => 'Éclairage général et plan de travail',
        'categorie' => 'eclairage',

        'sous_travaux' => [
            'installation_eclairage_general' => [
                'label' => 'Installation éclairage général',
                'description' => 'Point lumineux central de la pièce',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'dcl_plafond'],
                    ['type' => 'materiel', 'code' => 'fiche_dcl'],
                    ['type' => 'materiel', 'code' => 'cable_1_5mm2'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_dcl'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_eclairage_plan_travail' => [
                'label' => 'Installation éclairage plan de travail',
                'description' => 'Réglettes LED sous meubles hauts',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'reglette_led_60cm'],
                    ['type' => 'materiel', 'code' => 'cable_1_5mm2'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_reglette'],
                ],
                'quantite' => [
                    'type' => 'formula',
                    'expression' => 'ceil(longueur_plan_travail / 0.6)',
                ],
            ],
            'installation_commande_eclairage' => [
                'label' => 'Installation commande éclairage',
                'description' => 'Interrupteur pour éclairage cuisine',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'interrupteur_simple'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_interrupteur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'point_lumineux_par_piece',
        ],
    ],

    /**
     * Cuisine - Alimentation hotte
     */
    'alimentation_hotte' => [
        'id' => 'alimentation_hotte',
        'label' => 'Alimentation hotte',
        'description' => 'Alimentation électrique de la hotte aspirante',
        'categorie' => 'distribution',

        'sous_travaux' => [
            'installation_alimentation_hotte' => [
                'label' => 'Installation alimentation hotte',
                'description' => 'Sortie de câble pour hotte aspirante',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_1_5mm2'],
                    ['type' => 'materiel', 'code' => 'sortie_cable'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_sortie_cable'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_commande_hotte' => [
                'label' => 'Installation commande hotte',
                'description' => 'Interrupteur dédié hotte',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'interrupteur_simple'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_interrupteur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [],
    ],

    /**
     * Cuisine - Équipement îlot
     */
    'equipement_ilot' => [
        'id' => 'equipement_ilot',
        'label' => 'Équipement îlot central',
        'description' => 'Alimentation électrique de l\'îlot central',
        'categorie' => 'distribution',

        'sous_travaux' => [
            'installation_prises_ilot' => [
                'label' => 'Installation prises îlot central',
                'description' => 'Pose prises dans îlot cuisine avec passage sol',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'prise_2pt_complete'],
                    ['type' => 'materiel', 'code' => 'boite_sol'],
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_prise_sol'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 2],
            ],
            'passage_sol' => [
                'label' => 'Passage sol',
                'description' => 'Cheminement câble dans la dalle',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'gaine_icta_20'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_passage_sol'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'distance_ilot',
                    'unite' => 'ml',
                ],
            ],
        ],

        'contraintes_normes' => [],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VMC
     * ═══════════════════════════════════════════════════════════════════
     */
    'ventilation_mecanique' => [
        'id' => 'ventilation_mecanique',
        'label' => 'Ventilation mécanique contrôlée',
        'description' => 'Installation VMC',
        'categorie' => 'ventilation',

        'sous_travaux' => [
            'pose_groupe_vmc' => [
                'label' => 'Pose groupe VMC',
                'description' => 'Installation du caisson VMC',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'groupe_vmc',
                        'selection' => [
                            'type' => 'selon_contexte',
                            'champ' => 'type_vmc',
                            'regles' => [
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'simple_flux_auto'], 'produit' => 'vmc_simple_flux_auto'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'hygro_a'], 'produit' => 'vmc_hygro_a'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'hygro_b'], 'produit' => 'vmc_hygro_b'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'double_flux'], 'produit' => 'vmc_double_flux'],
                            ],
                        ],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_groupe_vmc'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'raccordement_electrique_vmc' => [
                'label' => 'Raccordement électrique VMC',
                'description' => 'Alimentation électrique du groupe',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_1_5mm2'],
                    ['type' => 'materiel', 'code' => 'disjoncteur_2a'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_raccordement_vmc'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'pose_bouches_extraction' => [
                'label' => 'Pose bouches d\'extraction',
                'description' => 'Installation des bouches dans pièces humides',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'bouche_extraction'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_bouche'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_pieces_humides',
                ],
            ],
            'tirage_gaines' => [
                'label' => 'Tirage des gaines',
                'description' => 'Passage des gaines souples',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'gaine_souple_80'],
                    ['type' => 'materiel', 'code' => 'colliers_serrage'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_tirage_gaine'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'metrage_gaines',
                    'unite' => 'ml',
                ],
            ],
            'installation_entrees_air' => [
                'label' => 'Installation entrées d\'air',
                'description' => 'Pose des entrées d\'air dans pièces sèches',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'entree_air'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_entree_air'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_pieces_seches',
                ],
            ],
            'installation_sortie_exterieure' => [
                'label' => 'Installation sortie extérieure',
                'description' => 'Pose sortie toiture ou murale',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'sortie_vmc',
                        'variantes' => ['sortie_toiture', 'sortie_murale'],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_sortie_vmc'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'pose_bouches_hygroreglables' => [
                'label' => 'Pose bouches hygroréglables',
                'description' => 'Bouches à débit variable selon humidité',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'bouche_hygro'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_bouche'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_pieces_humides',
                ],
            ],
            'installation_groupe_vmc' => [
                'label' => 'Installation groupe VMC',
                'description' => 'Installation complète du groupe',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'groupe_vmc'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_groupe_vmc'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_reseau_extraction' => [
                'label' => 'Installation réseau extraction',
                'description' => 'Gaines et bouches d\'extraction',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'gaine_souple_80'],
                    ['type' => 'materiel', 'code' => 'bouche_extraction'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_reseau_vmc'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_pieces_humides',
                ],
            ],
        ],

        'contraintes_normes' => [
            'debit_minimum_selon_logement',
            'bouche_par_piece_humide',
            'entree_air_pieces_seches',
        ],
    ],

    /**
     * VMC - Installation groupe (chantier VMC standalone)
     */
    'installation_groupe' => [
        'id' => 'installation_groupe',
        'label' => 'Installation groupe VMC',
        'description' => 'Pose et raccordement du groupe VMC',
        'categorie' => 'ventilation',

        'sous_travaux' => [
            'pose_groupe_vmc' => [
                'label' => 'Pose groupe VMC',
                'description' => 'Installation du caisson VMC',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'groupe_vmc',
                        'selection' => [
                            'type' => 'selon_contexte',
                            'champ' => 'type_vmc',
                            'regles' => [
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'simple_flux_auto'], 'produit' => 'vmc_simple_flux_auto'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'hygro_a'], 'produit' => 'vmc_hygro_a'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'hygro_b'], 'produit' => 'vmc_hygro_b'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => 'double_flux'], 'produit' => 'vmc_double_flux'],
                            ],
                        ],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_groupe_vmc'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'raccordement_electrique_vmc' => [
                'label' => 'Raccordement électrique VMC',
                'description' => 'Alimentation électrique du groupe',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_1_5mm2'],
                    ['type' => 'materiel', 'code' => 'disjoncteur_2a'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_raccordement_vmc'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'protection_2a_vmc',
        ],
    ],

    /**
     * VMC - Réseau extraction
     */
    'reseau_extraction' => [
        'id' => 'reseau_extraction',
        'label' => 'Réseau d\'extraction VMC',
        'description' => 'Gaines et bouches d\'extraction',
        'categorie' => 'ventilation',

        'sous_travaux' => [
            'pose_bouches_extraction' => [
                'label' => 'Pose bouches d\'extraction',
                'description' => 'Installation des bouches dans pièces humides',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'bouche_extraction'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_bouche'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_pieces_humides',
                ],
            ],
            'tirage_gaines' => [
                'label' => 'Tirage des gaines',
                'description' => 'Passage des gaines souples',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'gaine_souple_80'],
                    ['type' => 'materiel', 'code' => 'colliers_serrage'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_tirage_gaine'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'metrage_gaines',
                    'unite' => 'ml',
                ],
            ],
        ],

        'contraintes_normes' => [
            'bouche_par_piece_humide',
        ],
    ],

    /**
     * VMC - Évacuation
     */
    'evacuation' => [
        'id' => 'evacuation',
        'label' => 'Évacuation VMC',
        'description' => 'Sortie extérieure VMC',
        'categorie' => 'ventilation',

        'sous_travaux' => [
            'installation_sortie_exterieure' => [
                'label' => 'Installation sortie extérieure',
                'description' => 'Pose sortie toiture ou murale',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'sortie_vmc',
                        'variantes' => ['sortie_toiture', 'sortie_murale'],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_sortie_vmc'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [],
    ],

    /**
     * VMC - Entrées d'air
     */
    'entrees_air' => [
        'id' => 'entrees_air',
        'label' => 'Entrées d\'air',
        'description' => 'Installation des entrées d\'air dans pièces sèches',
        'categorie' => 'ventilation',

        'sous_travaux' => [
            'installation_entrees_air' => [
                'label' => 'Installation entrées d\'air',
                'description' => 'Pose des entrées d\'air dans pièces sèches',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'entree_air'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_entree_air'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_pieces_seches',
                ],
            ],
        ],

        'contraintes_normes' => [
            'entree_air_pieces_seches',
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * BORNE IRVE
     * ═══════════════════════════════════════════════════════════════════
     */
    'installation_borne' => [
        'id' => 'installation_borne',
        'label' => 'Installation borne IRVE',
        'description' => 'Installation borne de recharge véhicule électrique',
        'categorie' => 'irve',

        'sous_travaux' => [
            'pose_borne_murale' => [
                'label' => 'Pose borne murale',
                'description' => 'Fixation de la borne',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'borne_recharge',
                        'selection' => [
                            'type' => 'selon_contexte',
                            'champ' => 'puissance_borne',
                            'regles' => [
                                ['condition' => ['type' => 'field_eq', 'valeur' => '7kw'], 'produit' => 'borne_7kw_mono'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => '11kw'], 'produit' => 'borne_11kw_tri'],
                                ['condition' => ['type' => 'field_eq', 'valeur' => '22kw'], 'produit' => 'borne_22kw_tri'],
                            ],
                        ],
                    ],
                    ['type' => 'materiel', 'code' => 'support_borne'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_borne'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'tirage_cable_alimentation' => [
                'label' => 'Tirage câble alimentation',
                'description' => 'Passage du câble depuis le tableau',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'cable_irve',
                        'selection' => ['type' => 'delegate_normes_engine', 'regle' => 'section_cable_irve'],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_tirage_cable_irve'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'distance_tableau',
                    'unite' => 'ml',
                ],
            ],
            'raccordement_tableau' => [
                'label' => 'Raccordement au tableau',
                'description' => 'Connexion au tableau électrique',
                'besoins_techniques' => [
                    ['type' => 'main_oeuvre', 'code' => 'mo_raccordement_irve'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_protection_circuit' => [
                'label' => 'Installation protection circuit',
                'description' => 'Disjoncteur courbe C',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'disjoncteur_irve',
                        'selection' => ['type' => 'selon_puissance_borne'],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_protection'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_protection_differentielle_dediee' => [
                'label' => 'Installation protection différentielle',
                'description' => 'Différentiel type A ou F dédié',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'differentiel_irve',
                        'variantes' => ['id_type_a_40a', 'id_type_f_40a'],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_protection'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'parametrage_borne' => [
                'label' => 'Paramétrage borne',
                'description' => 'Configuration et tests',
                'besoins_techniques' => [
                    ['type' => 'main_oeuvre', 'code' => 'mo_parametrage_borne'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'mise_en_service_borne' => [
                'label' => 'Mise en service',
                'description' => 'Délivrance certificat IRVE',
                'besoins_techniques' => [
                    ['type' => 'service', 'code' => 'certificat_irve'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_mise_en_service_irve'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_cheminement_etanche' => [
                'label' => 'Installation cheminement étanche',
                'description' => 'Goulotte ou tube pour passage extérieur',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'goulotte_etanche'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_goulotte'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'metrage_exterieur',
                    'unite' => 'ml',
                ],
            ],
            'protection_mecanique_cable' => [
                'label' => 'Protection mécanique câble',
                'description' => 'Protection du câble en passage exposé',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'protection_cable'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_protection_cable'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'metrage_protection',
                    'unite' => 'ml',
                ],
            ],
        ],

        'contraintes_normes' => [
            'certification_irve_obligatoire',
            'section_cable_selon_puissance_distance',
            'differentiel_type_a_minimum',
        ],
    ],

    /**
     * IRVE - Alimentation borne
     */
    'alimentation_borne' => [
        'id' => 'alimentation_borne',
        'label' => 'Alimentation borne IRVE',
        'description' => 'Câblage et raccordement de la borne au tableau',
        'categorie' => 'irve',

        'sous_travaux' => [
            'tirage_cable_alimentation' => [
                'label' => 'Tirage câble alimentation',
                'description' => 'Passage du câble depuis le tableau',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'cable_irve',
                        'selection' => ['type' => 'delegate_normes_engine', 'regle' => 'section_cable_irve'],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_tirage_cable_irve'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'distance_tableau',
                    'unite' => 'ml',
                ],
            ],
            'raccordement_tableau' => [
                'label' => 'Raccordement au tableau',
                'description' => 'Connexion au tableau électrique',
                'besoins_techniques' => [
                    ['type' => 'main_oeuvre', 'code' => 'mo_raccordement_irve'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'section_cable_selon_puissance_distance',
        ],
    ],

    /**
     * IRVE - Protection borne
     */
    'protection_borne' => [
        'id' => 'protection_borne',
        'label' => 'Protection borne IRVE',
        'description' => 'Protections électriques dédiées à la borne',
        'categorie' => 'irve',

        'sous_travaux' => [
            'installation_protection_circuit' => [
                'label' => 'Installation protection circuit',
                'description' => 'Disjoncteur courbe C',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'disjoncteur_irve',
                        'selection' => ['type' => 'selon_puissance_borne'],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_protection'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_protection_differentielle_dediee' => [
                'label' => 'Installation protection différentielle',
                'description' => 'Différentiel type A ou F dédié',
                'besoins_techniques' => [
                    [
                        'type' => 'materiel',
                        'code' => 'differentiel_irve',
                        'variantes' => ['id_type_a_40a', 'id_type_f_40a'],
                    ],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_protection'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'differentiel_type_a_minimum',
        ],
    ],

    /**
     * IRVE - Coffret secondaire
     */
    'coffret_secondaire' => [
        'id' => 'coffret_secondaire',
        'label' => 'Coffret secondaire IRVE',
        'description' => 'Installation d\'un coffret secondaire si distance > 25m',
        'categorie' => 'irve',

        'sous_travaux' => [
            'installation_coffret_secondaire' => [
                'label' => 'Installation coffret secondaire',
                'description' => 'Pose d\'un coffret intermédiaire',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'coffret_secondaire_irve'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_coffret'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [],
    ],

    /**
     * IRVE - Cheminement extérieur
     */
    'cheminement_exterieur' => [
        'id' => 'cheminement_exterieur',
        'label' => 'Cheminement extérieur',
        'description' => 'Protection du câble en passage extérieur',
        'categorie' => 'irve',

        'sous_travaux' => [
            'installation_cheminement_etanche' => [
                'label' => 'Installation cheminement étanche',
                'description' => 'Goulotte ou tube pour passage extérieur',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'goulotte_etanche'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_goulotte'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'metrage_exterieur',
                    'unite' => 'ml',
                ],
            ],
            'protection_mecanique_cable' => [
                'label' => 'Protection mécanique câble',
                'description' => 'Protection du câble en passage exposé',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'protection_cable'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_protection_cable'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'metrage_protection',
                    'unite' => 'ml',
                ],
            ],
        ],

        'contraintes_normes' => [
            'protection_ip_exterieur',
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VENTILATION PONCTUELLE (EXTRACTEUR)
     * ═══════════════════════════════════════════════════════════════════
     */
    'ventilation_ponctuelle' => [
        'id' => 'ventilation_ponctuelle',
        'label' => 'Ventilation ponctuelle',
        'description' => 'Extracteur d\'air pour pièces aveugles',
        'categorie' => 'ventilation',

        'sous_travaux' => [
            'installation_extracteur' => [
                'label' => 'Installation extracteur',
                'description' => 'Pose d\'un extracteur d\'air',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'extracteur_air'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_extracteur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'installation_temporisation' => [
                'label' => 'Installation temporisation',
                'description' => 'Temporisateur pour fonctionnement différé',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'temporisateur'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_temporisateur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'debit_minimum_sdb',
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * COMMUNICATION
     * ═══════════════════════════════════════════════════════════════════
     */
    'reseau_communication' => [
        'id' => 'reseau_communication',
        'label' => 'Réseau de communication',
        'description' => 'Installation réseau informatique et téléphonique',
        'categorie' => 'courants_faibles',

        'sous_travaux' => [
            'installation_coffret_communication' => [
                'label' => 'Installation coffret communication',
                'description' => 'Pose du coffret Grade 2/3',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'coffret_communication'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_coffret_com'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
            'tirage_cables_rj45' => [
                'label' => 'Tirage câbles RJ45',
                'description' => 'Passage des câbles Grade',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_rj45'],
                    ['type' => 'materiel', 'code' => 'gaine_icta_20'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_tirage_cable_rj45'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'metrage_cables_rj45',
                    'unite' => 'ml',
                ],
            ],
            'pose_prises_rj45' => [
                'label' => 'Pose prises RJ45',
                'description' => 'Installation des prises informatiques',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'prise_rj45'],
                    ['type' => 'materiel', 'code' => 'boite_encastrement'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_prise_rj45'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_prises_rj45',
                ],
            ],
        ],

        'contraintes_normes' => [
            'grade_2_minimum_neuf',
            'longueur_max_50m',
        ],
    ],

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CHAUFFAGE ÉLECTRIQUE
     * ═══════════════════════════════════════════════════════════════════
     */
    'chauffage_electrique' => [
        'id' => 'chauffage_electrique',
        'label' => 'Chauffage électrique',
        'description' => 'Installation radiateurs et régulation',
        'categorie' => 'chauffage',

        'sous_travaux' => [
            'installation_circuits_chauffage' => [
                'label' => 'Installation circuits chauffage',
                'description' => 'Tirage des lignes pour radiateurs',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_2_5mm2'],
                    ['type' => 'materiel', 'code' => 'gaine_icta_20'],
                    ['type' => 'materiel', 'code' => 'sortie_cable'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_circuit_chauffage'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'nb_radiateurs',
                ],
            ],
            'installation_fil_pilote' => [
                'label' => 'Installation fil pilote',
                'description' => 'Câblage fil pilote pour régulation',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'cable_fil_pilote'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_fil_pilote'],
                ],
                'quantite' => [
                    'type' => 'field',
                    'champ' => 'metrage_fil_pilote',
                    'unite' => 'ml',
                ],
            ],
            'installation_programmation' => [
                'label' => 'Installation programmation',
                'description' => 'Pose programmateur/gestionnaire',
                'besoins_techniques' => [
                    ['type' => 'materiel', 'code' => 'programmateur_chauffage'],
                    ['type' => 'main_oeuvre', 'code' => 'mo_pose_programmateur'],
                ],
                'quantite' => ['type' => 'fixed', 'valeur' => 1],
            ],
        ],

        'contraintes_normes' => [
            'section_selon_puissance',
            'protection_adaptee',
        ],
    ],
];
