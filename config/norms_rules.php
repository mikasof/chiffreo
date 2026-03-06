<?php

/**
 * Règles de normes et équipements obligatoires par type de travaux
 *
 * Ce fichier définit, pour chaque type de travaux électriques :
 * - Les normes applicables (NF C 15-100, etc.)
 * - Les équipements obligatoires
 * - Les certifications requises
 * - Les points de contrôle
 *
 * Utilisé pour :
 * 1. Suggérer automatiquement le matériel obligatoire dans les devis
 * 2. Alerter sur les certifications nécessaires
 * 3. Générer des notes de conformité
 */

return [
    /**
     * =====================================================
     * VENTILATION - VMC
     * =====================================================
     */
    'vmc' => [
        'nom' => 'Installation VMC',
        'mots_cles' => [
            'vmc', 'ventilation', 'extraction', 'aeration',
            'hygro', 'double flux', 'insufflation'
        ],
        'normes' => [
            'NF C 15-100 §10.1.4.1' => 'Circuit spécialisé VMC obligatoire',
            'NF DTU 68.3' => 'Norme de mise en œuvre VMC',
            'Arrêté 24/03/1982' => 'Aération des logements (débits réglementaires)',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'DJ_2A',
                'designation' => 'Disjoncteur 2A dédié VMC',
                'raison' => 'Protection circuit VMC obligatoire NF C 15-100',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'INTER_VMC',
                'designation' => 'Interrupteur 3 positions (arrêt/V1/V2)',
                'raison' => 'Commande des vitesses VMC',
                'quantite' => 1
            ],
            [
                'code' => 'BOUCHE_EXTRACTION',
                'designation' => 'Bouches d\'extraction (cuisine, SDB, WC)',
                'raison' => 'Extraction air vicié pièces humides',
                'quantite_par_piece_humide' => 1
            ],
        ],
        'certifications' => [],
        'points_controle' => [
            'Vérifier débit extraction conforme (arrêté 24/03/82)',
            'Entrées d\'air dans pièces sèches obligatoires',
            'Gaine VMC Ø125 minimum pour cuisine',
            'Pas de VMC dans garage ou local contenant chaudière gaz',
        ],
        'tva_applicable' => [
            'taux' => 5.5,
            'condition' => 'VMC hygroréglable ou double flux sur logement > 2 ans',
        ],
    ],

    /**
     * =====================================================
     * BORNE DE RECHARGE VÉHICULE ÉLECTRIQUE (IRVE)
     * =====================================================
     */
    'irve' => [
        'nom' => 'Installation borne de recharge véhicule électrique',
        'mots_cles' => [
            'borne', 'recharge', 'voiture electrique', 'vehicule electrique',
            've', 'wallbox', 'green up', 'greenup', 'irve', 'prise renforcee',
            'tesla', 'charging', '7kw', '11kw', '22kw'
        ],
        'normes' => [
            'NF C 15-100 §722' => 'Alimentation des véhicules électriques',
            'Décret 2017-26' => 'Infrastructures de recharge VE',
            'IEC 61851' => 'Système de charge conductive pour VE',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'PROTECTION_BORNE',
                'designation' => 'Différentiel Type A ou Type F dédié',
                'raison' => 'Protection obligatoire contre courants de fuite DC (NF C 15-100 §722)',
                'quantite' => 1
            ],
            [
                'code' => 'DJ_BORNE',
                'designation' => 'Disjoncteur courbe C adapté (20A à 40A selon puissance)',
                'raison' => 'Protection circuit borne',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'PARAFOUDRE',
                'designation' => 'Parafoudre Type 2',
                'raison' => 'Protection surtensions (fortement recommandé)',
                'quantite' => 1
            ],
        ],
        'certifications' => [
            [
                'nom' => 'Qualification IRVE',
                'obligatoire_si' => 'Puissance > 3.7 kW',
                'delivree_par' => 'Qualifelec ou AFNOR',
                'description' => 'Obligatoire pour bornes > 3.7 kW depuis décret 2017-26',
            ],
        ],
        'points_controle' => [
            'Vérifier puissance disponible au compteur (abonnement suffisant)',
            'Distance tableau-borne (section câble adaptée)',
            'Borne avec câble pilote obligatoire (mode 3)',
            'Marquage CE et conformité IEC 61851',
            'Déclaration Enedis si puissance > 3.7 kW',
        ],
        'tva_applicable' => [
            'taux' => 5.5,
            'condition' => 'Logement achevé > 2 ans (amélioration énergétique)',
        ],
        'aides_disponibles' => [
            'ADVENIR' => 'Prime jusqu\'à 960€ pour particuliers (copropriété)',
            'Crédit impôt' => '300€ max par point de charge (résidence principale)',
        ],
    ],

    /**
     * =====================================================
     * SALLE DE BAIN / PIÈCES D'EAU
     * =====================================================
     */
    'salle_bain' => [
        'nom' => 'Installation électrique salle de bain',
        'mots_cles' => [
            'salle de bain', 'sdb', 'douche', 'baignoire',
            'salle d\'eau', 'wc', 'toilettes', 'piece humide'
        ],
        'normes' => [
            'NF C 15-100 §701' => 'Locaux contenant une baignoire ou une douche',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'ID_30MA',
                'designation' => 'Différentiel 30mA haute sensibilité',
                'raison' => 'Protection obligatoire circuits salle de bain',
                'quantite' => 1
            ],
            [
                'code' => 'LIAISON_EQUIPOT',
                'designation' => 'Liaison équipotentielle locale',
                'raison' => 'Raccordement des masses métalliques (NF C 15-100 §701)',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'SPOT_IP44',
                'designation' => 'Luminaires IP44 minimum (volume 2)',
                'raison' => 'Étanchéité requise en volume 2',
                'quantite_estimee' => 2
            ],
            [
                'code' => 'PRISE_RASOIR',
                'designation' => 'Prise rasoir avec transfo isolement',
                'raison' => 'Seule prise autorisée en volume 2',
                'quantite' => 1
            ],
        ],
        'certifications' => [],
        'points_controle' => [
            'Respect strict des volumes (0, 1, 2, hors volume)',
            'Pas de prise ni interrupteur en volumes 0, 1, 2 (sauf TBTS)',
            'Chauffe-eau électrique en volume 1 si horizontal et impossible ailleurs',
            'Liaison équipotentielle : baignoire, canalisations, huisseries métalliques',
            'Classe II obligatoire pour appareils en volume 1',
        ],
        'volumes' => [
            'Volume 0' => 'Intérieur baignoire/receveur - Aucun équipement sauf TBTS 12V',
            'Volume 1' => 'Au-dessus baignoire/douche jusqu\'à 2.25m - IPX5 mini, Classe II',
            'Volume 2' => '60cm autour volume 1 - IPX4 mini, prises interdites sauf rasoir',
            'Hors volume' => 'Au-delà 60cm - Installations standard avec DDR 30mA',
        ],
    ],

    /**
     * =====================================================
     * CUISINE
     * =====================================================
     */
    'cuisine' => [
        'nom' => 'Installation électrique cuisine',
        'mots_cles' => [
            'cuisine', 'plaque', 'cuisson', 'four', 'hotte',
            'lave-vaisselle', 'frigo', 'congelateur', 'electromenager'
        ],
        'normes' => [
            'NF C 15-100 §10.1.3.2' => 'Circuits spécialisés obligatoires',
            'NF C 15-100 §11.3' => 'Nombre minimum de prises par pièce',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'CIRCUIT_PLAQUE',
                'designation' => 'Circuit spécialisé 32A plaque cuisson',
                'raison' => 'Circuit dédié obligatoire NF C 15-100',
                'quantite' => 1
            ],
            [
                'code' => 'CIRCUIT_FOUR',
                'designation' => 'Circuit spécialisé 20A four',
                'raison' => 'Circuit dédié obligatoire si four indépendant',
                'quantite' => 1
            ],
            [
                'code' => 'CIRCUIT_LAVE_VAISSELLE',
                'designation' => 'Circuit spécialisé 20A lave-vaisselle',
                'raison' => 'Circuit dédié obligatoire NF C 15-100',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'PRISE_PLAN_TRAVAIL',
                'designation' => 'Minimum 6 prises au-dessus plan de travail',
                'raison' => 'NF C 15-100 pour cuisine > 4m²',
                'quantite' => 6
            ],
            [
                'code' => 'CIRCUIT_HOTTE',
                'designation' => 'Alimentation hotte',
                'raison' => 'Point lumineux dédié ou prise pour hotte',
                'quantite' => 1
            ],
        ],
        'certifications' => [],
        'points_controle' => [
            'Circuit 32A obligatoire pour plaque (câble 6mm²)',
            'Minimum 6 prises plan de travail (cuisine > 4m²)',
            'Pas de prise au-dessus évier ou plaque',
            'Hotte : distance min 65cm de la plaque',
            'Congélateur : circuit dédié recommandé (coupure indépendante)',
        ],
    ],

    /**
     * =====================================================
     * PISCINE
     * =====================================================
     */
    'piscine' => [
        'nom' => 'Installation électrique piscine',
        'mots_cles' => [
            'piscine', 'bassin', 'local technique', 'pompe piscine',
            'filtration', 'electrolyseur', 'projecteur piscine', 'spa', 'jacuzzi'
        ],
        'normes' => [
            'NF C 15-100 §702' => 'Piscines et bassins',
            'NF C 15-100 Annexe Q' => 'Schémas piscines',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'ID_30MA',
                'designation' => 'Différentiel 30mA haute sensibilité',
                'raison' => 'Protection obligatoire tous circuits piscine',
                'quantite' => 1
            ],
            [
                'code' => 'LIAISON_EQUIPOT',
                'designation' => 'Liaison équipotentielle bassin',
                'raison' => 'Raccordement éléments métalliques bassin',
                'quantite' => 1
            ],
            [
                'code' => 'TRANSFO_TBTS',
                'designation' => 'Transformateur TBTS 12V pour projecteurs',
                'raison' => 'Éclairage bassin obligatoirement en TBTS',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'COFFRET_PISCINE',
                'designation' => 'Coffret électrique dédié local technique',
                'raison' => 'Centralisation des protections piscine',
                'quantite' => 1
            ],
            [
                'code' => 'HORLOGE_FILTRATION',
                'designation' => 'Horloge programmable filtration',
                'raison' => 'Programmation cycles de filtration',
                'quantite' => 1
            ],
        ],
        'certifications' => [],
        'points_controle' => [
            'Volumes 0, 1, 2 stricts autour du bassin',
            'Aucun équipement en volume 0 (dans l\'eau) sauf TBTS 12V',
            'Coffret électrique à minimum 1.25m du bord du bassin',
            'Local technique : IP24 minimum pour appareillage',
            'Liaison équipotentielle : échelle, plongeoir, éléments métalliques',
        ],
        'volumes' => [
            'Volume 0' => 'Intérieur bassin - Uniquement TBTS 12V',
            'Volume 1' => '2m du bord + 2.5m au-dessus - IPX5, Classe II uniquement',
            'Volume 2' => '1.5m autour volume 1 - IPX4 mini, prise interdite',
        ],
    ],

    /**
     * =====================================================
     * EXTÉRIEUR
     * =====================================================
     */
    'exterieur' => [
        'nom' => 'Installation électrique extérieure',
        'mots_cles' => [
            'exterieur', 'jardin', 'terrasse', 'eclairage exterieur',
            'portail', 'cloture', 'abri jardin', 'pergola', 'carport'
        ],
        'normes' => [
            'NF C 15-100 §702.55' => 'Installations extérieures',
            'NF C 17-200' => 'Éclairage extérieur',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'ID_30MA',
                'designation' => 'Différentiel 30mA haute sensibilité',
                'raison' => 'Protection obligatoire circuits extérieurs',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'PRISE_EXTERIEURE',
                'designation' => 'Prises étanches IP55 minimum',
                'raison' => 'Étanchéité obligatoire en extérieur',
                'quantite_estimee' => 2
            ],
            [
                'code' => 'CABLE_ENTERRE',
                'designation' => 'Câble U1000R2V ou équivalent',
                'raison' => 'Câble adapté enterrement sous fourreau',
                'quantite' => 'selon distance'
            ],
            [
                'code' => 'FOURREAU_TPC',
                'designation' => 'Fourreau TPC rouge Ø63 ou Ø90',
                'raison' => 'Protection câbles enterrés',
                'quantite' => 'selon distance'
            ],
        ],
        'certifications' => [],
        'points_controle' => [
            'Tout appareillage extérieur minimum IP44 (IP55 recommandé)',
            'Câbles enterrés sous fourreau TPC rouge à 50cm minimum',
            'Grillage avertisseur rouge obligatoire à 20cm au-dessus câbles',
            'Mise à la terre de tous les éléments métalliques',
            'Pas de câbles aériens sans autorisation (servitude)',
        ],
    ],

    /**
     * =====================================================
     * PHOTOVOLTAÏQUE
     * =====================================================
     */
    'photovoltaique' => [
        'nom' => 'Installation photovoltaïque',
        'mots_cles' => [
            'photovoltaique', 'panneau solaire', 'panneaux solaires',
            'pv', 'autoconsommation', 'onduleur', 'micro-onduleur',
            'injection', 'revente', 'solaire'
        ],
        'normes' => [
            'NF C 15-100 §712' => 'Installations photovoltaïques',
            'Guide UTE C 15-712-1' => 'Installations PV raccordées au réseau',
            'NF EN 62446' => 'Documentation et essais installations PV',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'COFFRET_DC',
                'designation' => 'Coffret DC avec sectionneurs et parafoudre',
                'raison' => 'Protection côté courant continu obligatoire',
                'quantite' => 1
            ],
            [
                'code' => 'COFFRET_AC',
                'designation' => 'Coffret AC avec disjoncteur et parafoudre',
                'raison' => 'Protection côté courant alternatif obligatoire',
                'quantite' => 1
            ],
            [
                'code' => 'PARAFOUDRE_DC',
                'designation' => 'Parafoudre Type 2 DC',
                'raison' => 'Protection surtensions côté panneaux',
                'quantite' => 1
            ],
            [
                'code' => 'PARAFOUDRE_AC',
                'designation' => 'Parafoudre Type 2 AC',
                'raison' => 'Protection surtensions côté réseau',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'COMPTEUR_PRODUCTION',
                'designation' => 'Compteur de production',
                'raison' => 'Suivi production (obligatoire si revente)',
                'quantite' => 1
            ],
        ],
        'certifications' => [
            [
                'nom' => 'QualiPV',
                'obligatoire_si' => 'Vente surplus ou totalité + demande aides',
                'delivree_par' => 'Qualit\'EnR',
                'description' => 'Qualification installateur photovoltaïque',
            ],
            [
                'nom' => 'Attestation Consuel',
                'obligatoire_si' => 'Toute installation raccordée réseau',
                'delivree_par' => 'Consuel',
                'description' => 'Obligatoire avant mise en service Enedis',
            ],
        ],
        'points_controle' => [
            'Étude de faisabilité (orientation, ombrage, structure toiture)',
            'Déclaration préalable de travaux en mairie',
            'Contrat de raccordement Enedis',
            'Câblage DC en série/parallèle selon onduleur',
            'Étiquetage obligatoire "Attention installation PV"',
            'Mise à la terre des châssis métalliques',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Installation ≤ 3 kWc sur logement > 2 ans',
        ],
    ],

    /**
     * =====================================================
     * TABLEAU ÉLECTRIQUE / MISE AUX NORMES
     * =====================================================
     */
    'tableau' => [
        'nom' => 'Rénovation / Mise aux normes tableau électrique',
        'mots_cles' => [
            'tableau', 'coffret', 'mise aux normes', 'renovation tableau',
            'differentiel', 'disjoncteur', 'gtl', 'etel', 'gaine technique'
        ],
        'normes' => [
            'NF C 15-100 §10.1' => 'Tableau de répartition',
            'NF C 15-100 §11' => 'Nombre et répartition des circuits',
            'NF C 15-100 §558' => 'GTL - Gaine Technique Logement',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'ID_30MA',
                'designation' => 'Différentiels 30mA (minimum 2 pour logement)',
                'raison' => 'Protection personnes obligatoire NF C 15-100',
                'quantite' => 2
            ],
            [
                'code' => 'BORNIER_TERRE',
                'designation' => 'Bornier de terre',
                'raison' => 'Distribution terre obligatoire',
                'quantite' => 1
            ],
            [
                'code' => 'PEIGNE',
                'designation' => 'Peignes de raccordement',
                'raison' => 'Raccordement conforme des modules',
                'quantite' => 'selon tableau'
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'PARAFOUDRE',
                'designation' => 'Parafoudre Type 2',
                'raison' => 'Obligatoire en zone AQ2 (foudre), recommandé ailleurs',
                'quantite' => 1
            ],
            [
                'code' => 'CONTACTEUR_HC',
                'designation' => 'Contacteur heures creuses',
                'raison' => 'Si chauffe-eau avec tarif HC',
                'quantite' => 1
            ],
            [
                'code' => 'TELERUPTEUR',
                'designation' => 'Télérupteur pour éclairage va-et-vient',
                'raison' => 'Alternative aux va-et-vient multiples',
                'quantite' => 'selon besoins'
            ],
        ],
        'certifications' => [
            [
                'nom' => 'Attestation Consuel',
                'obligatoire_si' => 'Rénovation lourde ou mise en conformité totale',
                'delivree_par' => 'Consuel',
                'description' => 'Attestation de conformité des installations électriques',
            ],
        ],
        'points_controle' => [
            'Minimum 2 différentiels 30mA pour logement',
            'Répartition équilibrée des circuits par différentiel',
            '20% de réserve minimum dans le tableau',
            'Repérage obligatoire des circuits',
            'Accessibilité du tableau (hauteur entre 0.90m et 1.80m)',
            'GTL obligatoire si rénovation lourde',
        ],
    ],

    /**
     * =====================================================
     * CHAUFFAGE ÉLECTRIQUE
     * =====================================================
     */
    'chauffage' => [
        'nom' => 'Installation chauffage électrique',
        'mots_cles' => [
            'chauffage', 'radiateur', 'convecteur', 'inertie',
            'plancher chauffant', 'seche-serviette', 'thermostat',
            'fil pilote', 'programmation'
        ],
        'normes' => [
            'NF C 15-100 §10.1.3.6' => 'Circuits chauffage',
            'NF EN 60335' => 'Sécurité appareils de chauffage',
            'RT 2012/RE 2020' => 'Réglementation thermique',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'DJ_CHAUFFAGE',
                'designation' => 'Disjoncteur dédié par circuit chauffage',
                'raison' => 'Un disjoncteur par tranche de 4500W max',
                'quantite' => 'selon puissance'
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'FIL_PILOTE',
                'designation' => 'Câblage fil pilote 2 fils',
                'raison' => 'Gestion centralisée des radiateurs',
                'quantite' => 1
            ],
            [
                'code' => 'GESTIONNAIRE_ENERGIE',
                'designation' => 'Gestionnaire d\'énergie / Programmateur',
                'raison' => 'Économies d\'énergie significatives',
                'quantite' => 1
            ],
            [
                'code' => 'DELESTEUR',
                'designation' => 'Délesteur',
                'raison' => 'Éviter dépassement puissance souscrite',
                'quantite' => 1
            ],
        ],
        'certifications' => [],
        'points_controle' => [
            'Section câble adaptée à la puissance (2.5mm² jusqu\'à 4500W)',
            'Hauteur pose radiateur : 15cm du sol minimum',
            'Distance radiateur/prise : 25cm minimum',
            'Pas de radiateur sous tableau électrique',
            'Fil pilote prévu pour chaque radiateur (programmation)',
        ],
        'tva_applicable' => [
            'taux' => 5.5,
            'condition' => 'Radiateurs à inertie/connectés sur logement > 2 ans (amélioration énergétique)',
        ],
    ],

    /**
     * =====================================================
     * ALARME & SÉCURITÉ
     * =====================================================
     */
    'alarme' => [
        'nom' => 'Installation alarme et sécurité',
        'mots_cles' => [
            'alarme', 'securite', 'intrusion', 'detecteur',
            'sirene', 'centrale', 'videosurveillance', 'camera'
        ],
        'normes' => [
            'NF C 15-100 §10.1.3.10' => 'Circuits sécurité',
            'NF A2P' => 'Certification matériel anti-intrusion',
            'NF & A2P' => 'Certification installateur (optionnel)',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'ALIM_SECOURS',
                'designation' => 'Alimentation secourue (batterie)',
                'raison' => 'Continuité de service en cas de coupure',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'DETECTEUR_FUMEE',
                'designation' => 'Détecteurs de fumée (DAAF)',
                'raison' => 'Obligatoire depuis 2015 (1 par niveau)',
                'quantite' => 'selon logement'
            ],
            [
                'code' => 'SIRENE_EXTERIEURE',
                'designation' => 'Sirène extérieure avec flash',
                'raison' => 'Effet dissuasif',
                'quantite' => 1
            ],
        ],
        'certifications' => [
            [
                'nom' => 'APSAD',
                'obligatoire_si' => 'Réduction prime assurance souhaitée',
                'delivree_par' => 'CNPP',
                'description' => 'Certification pour réduction assurance vol/incendie',
            ],
        ],
        'points_controle' => [
            'Détecteur fumée obligatoire (loi ALUR 2015)',
            'Autonomie batterie centrale : 72h minimum',
            'Transmission alarme : GSM ou IP',
            'Sirène extérieure : temporisation 3 min max (arrêté)',
        ],
    ],

    /**
     * =====================================================
     * CONTRÔLE D'ACCÈS
     * =====================================================
     */
    'controle_acces' => [
        'nom' => 'Installation contrôle d\'accès',
        'mots_cles' => [
            'digicode', 'interphone', 'visiophone', 'portier',
            'badge', 'gache', 'ventouse', 'controle acces'
        ],
        'normes' => [
            'NF C 15-100 §10.1.3.10' => 'Circuits commande',
            'NF S 61-934' => 'Systèmes de sécurité incendie (SSI) si ERP',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'ALIM_12V',
                'designation' => 'Alimentation 12V stabilisée',
                'raison' => 'Alimentation gâche/ventouse',
                'quantite' => 1
            ],
            [
                'code' => 'BP_SORTIE',
                'designation' => 'Bouton poussoir de sortie',
                'raison' => 'Sortie sans badge/code (sécurité incendie)',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'GACHE_ELEC',
                'designation' => 'Gâche électrique à émission ou à rupture',
                'raison' => 'Déverrouillage porte',
                'quantite' => 1
            ],
        ],
        'certifications' => [],
        'points_controle' => [
            'Gâche à rupture obligatoire sur issues de secours',
            'Bouton poussoir de sortie côté intérieur',
            'Alimentation secourue si accès sécurisé',
            'Passage câble en gaine séparée du 230V',
        ],
    ],

    /**
     * =====================================================
     * DOMOTIQUE
     * =====================================================
     */
    'domotique' => [
        'nom' => 'Installation domotique',
        'mots_cles' => [
            'domotique', 'connecte', 'smart home', 'maison intelligente',
            'knx', 'zigbee', 'zwave', 'wifi', 'box domotique'
        ],
        'normes' => [
            'NF C 15-100' => 'Installation basse tension standard',
            'NF EN 50090 (KNX)' => 'Si système KNX filaire',
        ],
        'equipements_obligatoires' => [],
        'equipements_recommandes' => [
            [
                'code' => 'BOX_DOMOTIQUE',
                'designation' => 'Box ou contrôleur domotique',
                'raison' => 'Centralisation et scénarios',
                'quantite' => 1
            ],
            [
                'code' => 'RESEAU_DEDIE',
                'designation' => 'Réseau informatique dédié ou WiFi robuste',
                'raison' => 'Communication fiable des équipements',
                'quantite' => 1
            ],
        ],
        'certifications' => [
            [
                'nom' => 'KNX Partner',
                'obligatoire_si' => 'Installation KNX filaire',
                'delivree_par' => 'KNX Association',
                'description' => 'Certification installateur KNX',
            ],
        ],
        'points_controle' => [
            'Prévoir câblage en étoile depuis tableau (anticipation)',
            'Alimentation secourue pour box domotique',
            'Interrupteurs filaires de secours (backup)',
            'Réseau WiFi stable (répéteurs si nécessaire)',
        ],
    ],

    /**
     * =====================================================
     * TERTIAIRE / ERP
     * =====================================================
     */
    'erp' => [
        'nom' => 'Installation ERP / Tertiaire',
        'mots_cles' => [
            'erp', 'commerce', 'magasin', 'bureau', 'tertiaire',
            'local commercial', 'restaurant', 'hotel', 'etablissement'
        ],
        'normes' => [
            'NF C 15-100' => 'Installation générale',
            'NF C 14-100' => 'Installations de branchement',
            'Règlement de sécurité ERP' => 'Arrêté du 25 juin 1980',
            'NF S 61-931 à 940' => 'Systèmes de Sécurité Incendie',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'ECLAIRAGE_SECURITE',
                'designation' => 'Éclairage de sécurité (BAES)',
                'raison' => 'Obligatoire en ERP',
                'quantite' => 'selon surface et configuration'
            ],
            [
                'code' => 'COUPURE_URGENCE',
                'designation' => 'Dispositif de coupure d\'urgence',
                'raison' => 'Arrêt urgence accessible pompiers',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'SSI',
                'designation' => 'Système de Sécurité Incendie',
                'raison' => 'Obligatoire selon catégorie ERP',
                'quantite' => 1
            ],
        ],
        'certifications' => [
            [
                'nom' => 'Vérification initiale',
                'obligatoire_si' => 'Tout ERP',
                'delivree_par' => 'Bureau de contrôle agréé',
                'description' => 'Avant ouverture au public',
            ],
            [
                'nom' => 'Vérifications périodiques',
                'obligatoire_si' => 'Tout ERP',
                'delivree_par' => 'Organisme agréé',
                'description' => 'Annuelle ou selon catégorie',
            ],
        ],
        'points_controle' => [
            'BAES : 1 par porte, escalier, changement direction',
            'Autonomie BAES : 1h minimum',
            'Coupure urgence accessible depuis extérieur',
            'Conformité accessibilité PMR',
            'Rapport de vérification électrique annuel',
        ],
    ],
];
