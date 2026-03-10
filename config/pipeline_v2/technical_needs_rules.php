<?php

/**
 * Règles de génération des besoins techniques
 *
 * Ce fichier définit les templates de besoins techniques pour chaque sous-travail.
 * Il est utilisé par le TechnicalNeedsGenerator pour transformer les sous-travaux
 * du WorkTree en besoins techniques concrets (matériel, consommables, main d'œuvre).
 *
 * STRUCTURE :
 * - Clé = ID du sous-travail (doit correspondre à travaux_definitions.php)
 * - Valeur = tableau de besoins techniques
 *
 * TYPES DE BESOINS :
 * - materiel : Appareillage principal (prises, disjoncteurs, etc.)
 * - consommable : Matériaux (câbles, gaines, boîtes, etc.)
 * - main_oeuvre : Temps de travail unitaire
 * - main_oeuvre_forfait : Temps forfaitaire (mise en service, etc.)
 * - fourniture : Petit matériel inclus
 * - prestation : Services externes (Consuel, etc.)
 *
 * VARIABLES DISPONIBLES :
 * - {quantite} : Quantité du sous-travail
 * - {section_cable} : Section câble (depuis NormesEngine ou défaut)
 * - {distance_tableau} : Distance au tableau (contexte)
 * - {distance_moyenne_point} : Distance moyenne par point (défaut 8m)
 * - {surface} : Surface du logement (contexte)
 * - {gamme} : Gamme sélectionnée (standard/premium/chantier)
 *
 * ATTRIBUTS SPÉCIAUX :
 * - visible_devis : true/false - Affiché sur le devis client
 * - mode_facturation : 'ligne' / 'inclus' - Facturé séparément ou inclus
 * - condition : Condition d'application du besoin
 */

return [
    // =========================================================================
    // MÉTADONNÉES
    // =========================================================================
    '_meta' => [
        'version' => '1.0.0',
        'description' => 'Règles de besoins techniques V1',
        'defaults' => [
            'distance_moyenne_point' => 8,      // mètres
            'marge_cable' => 1.15,              // 15% de marge sur câbles
            'marge_gaine' => 1.10,              // 10% de marge sur gaines
            'gamme_defaut' => 'standard',
        ],
        'gammes' => ['chantier', 'standard', 'premium'],
        'unites' => [
            'pce' => 'pièce',
            'ml' => 'mètre linéaire',
            'h' => 'heure',
            'fft' => 'forfait',
            'm2' => 'mètre carré',
            'lot' => 'lot',
        ],
    ],

    // =========================================================================
    // DISTRIBUTION PRISES
    // =========================================================================

    'pose_prise_standard' => [
        [
            'type' => 'materiel',
            'code' => 'prise_2p_t_16a',
            'label' => 'Prise 2P+T 16A',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'type' => '2P+T',
                'intensite' => '16A',
                'encastrement' => true,
                'gamme' => '{gamme:standard}',
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'cable_r2v_3g2_5',
            'label' => 'Câble R2V 3G2.5mm²',
            'quantite' => '{distance_moyenne_point:8}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'type' => 'R2V',
                'section' => 2.5,
                'nb_conducteurs' => 3,
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'gaine_icta_20',
            'label' => 'Gaine ICTA 20mm',
            'quantite' => '{distance_moyenne_point:8}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'type' => 'ICTA',
                'diametre' => 20,
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'boite_encastrement_simple',
            'label' => 'Boîte d\'encastrement simple',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'type' => 'encastrement',
                'profondeur' => 40,
            ],
        ],
        [
            'type' => 'fourniture',
            'code' => 'kit_fixation_prise',
            'label' => 'Kit fixation (vis, chevilles)',
            'quantite' => 1,
            'unite' => 'lot',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_prise',
            'label' => 'Pose et raccordement prise',
            'quantite' => 0.5,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'pose_prise_cuisine' => [
        [
            'type' => 'materiel',
            'code' => 'prise_2p_t_16a',
            'label' => 'Prise 2P+T 16A plan de travail',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'type' => '2P+T',
                'intensite' => '16A',
                'encastrement' => true,
                'usage' => 'plan_travail',
                'gamme' => '{gamme:standard}',
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'cable_r2v_3g2_5',
            'label' => 'Câble R2V 3G2.5mm²',
            'quantite' => '{distance_moyenne_point:10}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'section' => 2.5,
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'boite_encastrement_simple',
            'label' => 'Boîte d\'encastrement',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_prise_cuisine',
            'label' => 'Pose prise cuisine',
            'quantite' => 0.6,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    // =========================================================================
    // DISTRIBUTION ÉCLAIRAGE
    // =========================================================================

    'pose_point_lumineux' => [
        [
            'type' => 'materiel',
            'code' => 'douille_dcl',
            'label' => 'Douille DCL + cache',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'type' => 'DCL',
                'gamme' => '{gamme:standard}',
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'cable_r2v_3g1_5',
            'label' => 'Câble R2V 3G1.5mm²',
            'quantite' => '{distance_moyenne_point:6}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'section' => 1.5,
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'gaine_icta_16',
            'label' => 'Gaine ICTA 16mm',
            'quantite' => '{distance_moyenne_point:6}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
        [
            'type' => 'consommable',
            'code' => 'boite_dcl',
            'label' => 'Boîte DCL',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_point_lumineux',
            'label' => 'Pose point lumineux',
            'quantite' => 0.4,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'pose_interrupteur_simple' => [
        [
            'type' => 'materiel',
            'code' => 'interrupteur_simple',
            'label' => 'Interrupteur simple allumage',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'type' => 'simple',
                'gamme' => '{gamme:standard}',
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'boite_encastrement_simple',
            'label' => 'Boîte d\'encastrement',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_interrupteur',
            'label' => 'Pose interrupteur',
            'quantite' => 0.3,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'pose_interrupteur_va_et_vient' => [
        [
            'type' => 'materiel',
            'code' => 'interrupteur_va_et_vient',
            'label' => 'Interrupteur va-et-vient',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'type' => 'va_et_vient',
                'gamme' => '{gamme:standard}',
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'cable_navette',
            'label' => 'Câble navette',
            'quantite' => '{distance_navette:5}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
        [
            'type' => 'consommable',
            'code' => 'boite_encastrement_simple',
            'label' => 'Boîte d\'encastrement',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_va_et_vient',
            'label' => 'Pose va-et-vient',
            'quantite' => 0.5,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    // =========================================================================
    // TABLEAU ÉLECTRIQUE
    // =========================================================================

    'fourniture_tableau' => [
        [
            'type' => 'materiel',
            'code' => 'coffret_electrique',
            'label' => 'Coffret électrique',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'nb_modules' => '{nb_modules_tableau:26}',
                'nb_rangees' => '{nb_rangees:2}',
                'encastre' => '{tableau_encastre:true}',
                'gamme' => '{gamme:standard}',
            ],
        ],
    ],

    'pose_tableau' => [
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_tableau',
            'label' => 'Pose et fixation tableau',
            'quantite' => 1.5,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
        [
            'type' => 'fourniture',
            'code' => 'kit_fixation_tableau',
            'label' => 'Fixations tableau',
            'quantite' => 1,
            'unite' => 'lot',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
    ],

    'installation_differentiel' => [
        [
            'type' => 'materiel',
            'code' => 'interrupteur_differentiel',
            'label' => 'Interrupteur différentiel 30mA',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'calibre' => '{calibre_differentiel:40}',
                'sensibilite' => '30mA',
                'type' => '{type_differentiel:A}',
                'nb_poles' => 2,
                'gamme' => '{gamme:standard}',
            ],
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_differentiel',
            'label' => 'Pose et câblage différentiel',
            'quantite' => 0.25,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'installation_disjoncteur' => [
        [
            'type' => 'materiel',
            'code' => 'disjoncteur_divisionnaire',
            'label' => 'Disjoncteur divisionnaire',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'calibre' => '{calibre_disjoncteur:16}',
                'courbe' => '{courbe_disjoncteur:C}',
                'nb_poles' => 1,
                'gamme' => '{gamme:standard}',
            ],
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_disjoncteur',
            'label' => 'Pose et raccordement disjoncteur',
            'quantite' => 0.15,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'raccordement_circuits' => [
        [
            'type' => 'consommable',
            'code' => 'fil_h07vk_phase',
            'label' => 'Fil H07V-K phase',
            'quantite' => 0.5,
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'section' => '{section_circuit:2.5}',
                'couleur' => 'rouge',
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'fil_h07vk_neutre',
            'label' => 'Fil H07V-K neutre',
            'quantite' => 0.5,
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'section' => '{section_circuit:2.5}',
                'couleur' => 'bleu',
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'peigne_repartition',
            'label' => 'Peigne de répartition',
            'quantite' => 0.1, // Fraction par circuit
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_raccordement_circuit',
            'label' => 'Raccordement circuit au tableau',
            'quantite' => 0.2,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    // =========================================================================
    // MISE À LA TERRE
    // =========================================================================

    'verification_terre' => [
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_verification_terre',
            'label' => 'Vérification prise de terre',
            'quantite' => 0.5,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'pose_piquet_terre' => [
        [
            'type' => 'materiel',
            'code' => 'piquet_terre',
            'label' => 'Piquet de terre cuivre 1.5m',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'longueur' => 1.5,
                'materiau' => 'cuivre',
            ],
        ],
        [
            'type' => 'materiel',
            'code' => 'regard_terre',
            'label' => 'Regard de visite terre',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
        [
            'type' => 'consommable',
            'code' => 'cable_terre_25',
            'label' => 'Câble de terre 25mm²',
            'quantite' => '{distance_tableau:10}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'section' => 25,
                'couleur' => 'vert_jaune',
            ],
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_terre',
            'label' => 'Pose prise de terre complète',
            'quantite' => 2,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'liaison_equipotentielle' => [
        [
            'type' => 'materiel',
            'code' => 'barrette_coupure',
            'label' => 'Barrette de coupure',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
        [
            'type' => 'consommable',
            'code' => 'cable_equipotentielle',
            'label' => 'Câble liaison équipotentielle',
            'quantite' => '{longueur_lep:5}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'section' => '{section_lep:4}',
            ],
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_liaison_equipotentielle',
            'label' => 'Réalisation liaison équipotentielle',
            'quantite' => 1,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    // =========================================================================
    // VMC
    // =========================================================================

    'fourniture_vmc' => [
        [
            'type' => 'materiel',
            'code' => 'groupe_vmc',
            'label' => 'Groupe VMC',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'type' => '{type_vmc:simple_flux}',
                'nb_bouches' => '{nb_bouches_extraction:4}',
                'gamme' => '{gamme:standard}',
            ],
        ],
    ],

    'pose_groupe_vmc' => [
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_vmc',
            'label' => 'Pose groupe VMC',
            'quantite' => 2,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
        [
            'type' => 'fourniture',
            'code' => 'kit_fixation_vmc',
            'label' => 'Kit suspension VMC',
            'quantite' => 1,
            'unite' => 'lot',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
    ],

    'pose_bouche_extraction' => [
        [
            'type' => 'materiel',
            'code' => 'bouche_extraction',
            'label' => 'Bouche d\'extraction',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'debit' => '{debit_bouche:30}',
                'type_piece' => '{type_piece:sdb}',
                'gamme' => '{gamme:standard}',
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'gaine_vmc_80',
            'label' => 'Gaine VMC Ø80',
            'quantite' => '{distance_groupe:6}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'diametre' => 80,
            ],
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_bouche',
            'label' => 'Pose bouche extraction',
            'quantite' => 0.5,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'alimentation_vmc' => [
        [
            'type' => 'consommable',
            'code' => 'cable_r2v_3g1_5',
            'label' => 'Câble alimentation VMC',
            'quantite' => '{distance_tableau:15}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'section' => 1.5,
            ],
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_alimentation_vmc',
            'label' => 'Tirage câble VMC',
            'quantite' => 1,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    // =========================================================================
    // BORNE IRVE
    // =========================================================================

    'fourniture_borne' => [
        [
            'type' => 'materiel',
            'code' => 'borne_recharge',
            'label' => 'Borne de recharge',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'puissance' => '{puissance_borne:7.4kW}',
                'type_prise' => '{type_prise_borne:T2}',
                'connectivite' => '{connectivite_borne:wifi}',
                'gamme' => '{gamme:standard}',
            ],
        ],
    ],

    'pose_borne' => [
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_borne',
            'label' => 'Pose et fixation borne',
            'quantite' => 1.5,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
        [
            'type' => 'fourniture',
            'code' => 'kit_fixation_borne',
            'label' => 'Kit fixation borne',
            'quantite' => 1,
            'unite' => 'lot',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
    ],

    'tirage_cable_alimentation' => [
        [
            'type' => 'consommable',
            'code' => 'cable_alimentation_borne',
            'label' => 'Câble alimentation borne',
            'quantite' => '{distance_tableau:15}',
            'unite' => 'ml',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'section' => '{section_cable_borne:10}',
                'type' => '{type_cable_borne:R2V}',
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'gaine_tpc_40',
            'label' => 'Gaine TPC Ø40',
            'quantite' => '{distance_tableau:15}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'condition' => [
                'type' => 'context_has',
                'champ' => 'passage_exterieur',
            ],
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_tirage_cable_borne',
            'label' => 'Tirage câble alimentation',
            'quantite' => 'ceil({distance_tableau:15} * 0.15)',
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'installation_protection_differentielle_dediee' => [
        [
            'type' => 'materiel',
            'code' => 'differentiel_borne',
            'label' => 'Différentiel dédié borne',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'calibre' => '{calibre_diff_borne:40}',
                'sensibilite' => '30mA',
                'type' => '{type_diff_borne:A}',
            ],
        ],
        [
            'type' => 'materiel',
            'code' => 'disjoncteur_borne',
            'label' => 'Disjoncteur dédié borne',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'calibre' => '{calibre_disj_borne:32}',
                'courbe' => 'C',
            ],
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_protection_borne',
            'label' => 'Installation protections borne',
            'quantite' => 0.5,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'mise_en_service_borne' => [
        [
            'type' => 'main_oeuvre_forfait',
            'code' => 'mo_mise_service_borne',
            'label' => 'Mise en service et paramétrage',
            'quantite' => 1,
            'unite' => 'fft',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    // =========================================================================
    // CIRCUITS SPÉCIALISÉS CUISINE
    // =========================================================================

    'circuit_cuisson' => [
        [
            'type' => 'materiel',
            'code' => 'sortie_cable_32a',
            'label' => 'Sortie de câble 32A',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'intensite' => '32A',
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'cable_r2v_3g6',
            'label' => 'Câble R2V 3G6mm²',
            'quantite' => '{distance_tableau:12}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
            'specifications' => [
                'section' => 6,
            ],
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_circuit_cuisson',
            'label' => 'Tirage circuit cuisson',
            'quantite' => 1.5,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'circuit_four' => [
        [
            'type' => 'materiel',
            'code' => 'prise_20a',
            'label' => 'Prise 20A four',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
            'specifications' => [
                'intensite' => '20A',
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'cable_r2v_3g2_5',
            'label' => 'Câble R2V 3G2.5mm²',
            'quantite' => '{distance_tableau:12}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_circuit_four',
            'label' => 'Circuit dédié four',
            'quantite' => 1,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'circuit_lave_vaisselle' => [
        [
            'type' => 'materiel',
            'code' => 'prise_20a',
            'label' => 'Prise 20A lave-vaisselle',
            'quantite' => 1,
            'unite' => 'pce',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
        [
            'type' => 'consommable',
            'code' => 'cable_r2v_3g2_5',
            'label' => 'Câble R2V 3G2.5mm²',
            'quantite' => '{distance_tableau:12}',
            'unite' => 'ml',
            'visible_devis' => false,
            'mode_facturation' => 'inclus',
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_circuit_lv',
            'label' => 'Circuit dédié lave-vaisselle',
            'quantite' => 1,
            'unite' => 'h',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    // =========================================================================
    // PRESTATIONS ET FORFAITS
    // =========================================================================

    'consuel' => [
        [
            'type' => 'prestation',
            'code' => 'attestation_consuel',
            'label' => 'Attestation de conformité Consuel',
            'quantite' => 1,
            'unite' => 'fft',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'mise_en_service_installation' => [
        [
            'type' => 'main_oeuvre_forfait',
            'code' => 'mo_mise_service',
            'label' => 'Mise en service et vérifications',
            'quantite' => 1,
            'unite' => 'fft',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],

    'reprise_existant' => [
        [
            'type' => 'main_oeuvre_forfait',
            'code' => 'mo_reprise_existant',
            'label' => 'Reprise et raccordement existant',
            'quantite' => 1,
            'unite' => 'fft',
            'visible_devis' => true,
            'mode_facturation' => 'ligne',
        ],
    ],
];
