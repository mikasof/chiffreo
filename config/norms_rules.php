<?php

/**
 * Règles de normes et équipements obligatoires par type de travaux
 *
 * Ce fichier définit, pour chaque type de travaux :
 * - Les normes applicables
 * - Les équipements obligatoires et recommandés
 * - Les certifications requises
 * - Les points de contrôle
 * - Le temps estimé par type d'intervention
 * - Les outils nécessaires
 * - L'ordre des travaux recommandé
 * - Les erreurs courantes à éviter
 *
 * Utilisé pour :
 * 1. Suggérer automatiquement le matériel obligatoire dans les devis
 * 2. Alerter sur les certifications nécessaires
 * 3. Générer des notes de conformité
 * 4. Estimer les durées d'intervention
 * 5. Guider l'ordre des travaux
 */

return [
    /**
     * =====================================================
     * VENTILATION - VMC
     * =====================================================
     */
    'vmc' => [
        'nom' => 'Installation VMC',
        'categorie' => 'electricite',
        'mots_cles' => [
            'vmc', 'ventilation', 'extraction', 'aeration',
            'hygro', 'double flux', 'insufflation', 'bouche extraction',
            'gaine vmc', 'caisson vmc', 'hygroreglable'
        ],
        'normes' => [
            'NF C 15-100 §10.1.4.1' => 'Circuit spécialisé VMC obligatoire',
            'NF DTU 68.3' => 'Norme de mise en œuvre VMC',
            'Arrêté 24/03/1982' => 'Aération des logements (débits réglementaires)',
            'RT 2012/RE 2020' => 'Performance énergétique VMC double flux',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'DJ_2A',
                'designation' => 'Disjoncteur 2A dédié VMC',
                'raison' => 'Protection circuit VMC obligatoire NF C 15-100',
                'quantite' => 1
            ],
            [
                'code' => 'CABLE_1.5',
                'designation' => 'Câble 3G1.5mm²',
                'raison' => 'Section adaptée au circuit VMC',
                'quantite' => 'selon distance'
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
            [
                'code' => 'ENTREE_AIR',
                'designation' => 'Entrées d\'air autoréglables ou hygroréglables',
                'raison' => 'Apport air neuf pièces sèches',
                'quantite_par_piece_seche' => 1
            ],
            [
                'code' => 'GAINE_ALU',
                'designation' => 'Gaines souples aluminium Ø80/125',
                'raison' => 'Raccordement bouches au caisson',
                'quantite' => 'selon configuration'
            ],
        ],
        'certifications' => [
            [
                'nom' => 'Qualification RGE',
                'obligatoire_si' => 'VMC double flux avec demande de prime',
                'delivree_par' => 'Qualibat, Qualifelec',
                'description' => 'Nécessaire pour MaPrimeRénov\'',
            ],
        ],
        'points_controle' => [
            'Vérifier débit extraction conforme (arrêté 24/03/82)',
            'Entrées d\'air dans pièces sèches obligatoires',
            'Gaine VMC Ø125 minimum pour cuisine',
            'Pas de VMC dans garage ou local contenant chaudière gaz',
            'Distance caisson/bouche la plus éloignée < 20m',
            'Pente des gaines vers le caisson (condensation)',
            'Calorifugeage gaines en combles non isolés',
        ],
        'temps_estime' => [
            'simple_flux_remplacement' => [
                'duree' => '2-3h',
                'description' => 'Remplacement VMC simple flux existante'
            ],
            'simple_flux_creation' => [
                'duree' => '1 journée',
                'description' => 'Installation complète avec percements et gaines'
            ],
            'double_flux' => [
                'duree' => '2-3 jours',
                'description' => 'Installation VMC double flux avec réseau de gaines'
            ],
            'facteurs_variation' => [
                'Accessibilité combles',
                'Nombre de bouches',
                'Longueur des gaines',
                'Type de VMC (simple/double flux)',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/visseuse',
            'Scie cloche Ø80-125',
            'Mètre laser',
            'Anémomètre (mesure débits)',
            'Colliers de serrage',
            'Adhésif aluminium',
            'Escabeau/échelle',
            'EPI (masque, gants)',
        ],
        'ordre_travaux' => [
            '1. Repérage et traçage du parcours des gaines',
            '2. Percement des entrées d\'air (pièces sèches)',
            '3. Percement des passages de gaines (cloisons/planchers)',
            '4. Pose du caisson VMC (combles ou local technique)',
            '5. Tirage des gaines depuis le caisson',
            '6. Pose des bouches d\'extraction',
            '7. Raccordement électrique (circuit dédié)',
            '8. Test de fonctionnement et mesure des débits',
        ],
        'erreurs_courantes' => [
            'Oublier les entrées d\'air dans les pièces sèches',
            'Gaines trop longues ou avec trop de coudes',
            'Caisson VMC posé à l\'horizontale au lieu de vertical',
            'Bouches cuisine et SDB inversées (débits différents)',
            'Gaines non calorifugées en combles froids',
            'Circuit électrique non dédié',
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
        'categorie' => 'electricite',
        'mots_cles' => [
            'borne', 'recharge', 'voiture electrique', 'vehicule electrique',
            've', 'wallbox', 'green up', 'greenup', 'irve', 'prise renforcee',
            'tesla', 'charging', '7kw', '11kw', '22kw', 'type 2'
        ],
        'normes' => [
            'NF C 15-100 §722' => 'Alimentation des véhicules électriques',
            'Décret 2017-26' => 'Infrastructures de recharge VE',
            'IEC 61851' => 'Système de charge conductive pour VE',
            'NF EN 62196' => 'Connecteurs et prises de charge',
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
            [
                'code' => 'CABLE_BORNE',
                'designation' => 'Câble adapté (6mm² à 10mm² selon puissance et distance)',
                'raison' => 'Section conforme NF C 15-100',
                'quantite' => 'selon distance'
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'PARAFOUDRE',
                'designation' => 'Parafoudre Type 2',
                'raison' => 'Protection surtensions (fortement recommandé)',
                'quantite' => 1
            ],
            [
                'code' => 'CONTACTEUR_HC',
                'designation' => 'Contacteur heures creuses',
                'raison' => 'Programmation recharge en heures creuses',
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
            'Vérifier équilibrage des phases (triphasé)',
            'Test de charge complète avant mise en service',
        ],
        'temps_estime' => [
            'prise_renforcee' => [
                'duree' => '2-3h',
                'description' => 'Installation prise Green\'Up ou similaire'
            ],
            'borne_monophase' => [
                'duree' => '3-4h',
                'description' => 'Borne 7 kW monophasée'
            ],
            'borne_triphase' => [
                'duree' => '4-5h',
                'description' => 'Borne 11 ou 22 kW triphasée'
            ],
            'facteurs_variation' => [
                'Distance tableau-borne',
                'Passage en apparent ou encastré',
                'Nécessité d\'augmenter l\'abonnement',
                'Copropriété (démarches supplémentaires)',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/perforateur',
            'Multimètre',
            'Pince ampèremétrique',
            'Niveau à bulle',
            'Tire-fil',
            'Testeur de prise',
            'Appareil de mesure de terre',
            'EPI (gants isolants)',
        ],
        'ordre_travaux' => [
            '1. Vérification puissance disponible au compteur',
            '2. Définition de l\'emplacement de la borne',
            '3. Tirage du câble depuis le tableau',
            '4. Installation des protections au tableau',
            '5. Fixation du support de la borne',
            '6. Raccordement électrique de la borne',
            '7. Paramétrage de la borne (puissance, heures creuses)',
            '8. Test de charge avec véhicule',
            '9. Déclaration Enedis (si > 3.7 kW)',
        ],
        'erreurs_courantes' => [
            'Sous-estimer la puissance disponible (disjoncteur saute)',
            'Différentiel Type AC au lieu de Type A/F',
            'Section de câble insuffisante pour la distance',
            'Oublier la déclaration Enedis',
            'Ne pas vérifier l\'équilibrage des phases',
            'Installer sans qualification IRVE (> 3.7 kW)',
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
        'categorie' => 'electricite',
        'mots_cles' => [
            'salle de bain', 'sdb', 'douche', 'baignoire',
            'salle d\'eau', 'wc', 'toilettes', 'piece humide',
            'spot salle de bain', 'seche serviette', 'miroir lumineux'
        ],
        'normes' => [
            'NF C 15-100 §701' => 'Locaux contenant une baignoire ou une douche',
            'NF C 15-100 §701.411.3.2' => 'Protection différentielle 30mA',
            'NF C 15-100 §701.512.2' => 'Liaison équipotentielle supplémentaire',
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
            [
                'code' => 'LUMINAIRE_IP',
                'designation' => 'Luminaires IP adaptés aux volumes',
                'raison' => 'IP X7 en volume 0, IPX5 en volume 1, IPX4 en volume 2',
                'quantite' => 'selon besoins'
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'SPOT_IP44',
                'designation' => 'Spots encastrés IP44 minimum (volume 2)',
                'raison' => 'Étanchéité requise en volume 2',
                'quantite_estimee' => 3
            ],
            [
                'code' => 'PRISE_RASOIR',
                'designation' => 'Prise rasoir avec transfo isolement',
                'raison' => 'Seule prise autorisée en volume 2',
                'quantite' => 1
            ],
            [
                'code' => 'SECHE_SERVIETTE',
                'designation' => 'Sèche-serviette électrique Classe II',
                'raison' => 'Confort et séchage du linge',
                'quantite' => 1
            ],
            [
                'code' => 'EXTRACTEUR',
                'designation' => 'Extracteur d\'air temporisé',
                'raison' => 'Évacuation humidité si pas de VMC',
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
            'Boîte de connexion IP55 minimum si en volume 2',
            'Interrupteur à commande à cordon ou hors volume',
        ],
        'volumes' => [
            'Volume 0' => [
                'description' => 'Intérieur baignoire/receveur',
                'ip_minimum' => 'IPX7',
                'tension_max' => 'TBTS 12V uniquement',
                'appareils_autorises' => ['Aucun sauf éclairage TBTS 12V encastré'],
            ],
            'Volume 1' => [
                'description' => 'Au-dessus baignoire/douche jusqu\'à 2.25m',
                'ip_minimum' => 'IPX5',
                'tension_max' => 'TBTS 12V ou appareils Classe II fixes',
                'appareils_autorises' => ['Éclairage Classe II', 'Chauffe-eau horizontal', 'Pompe hydromassage'],
            ],
            'Volume 2' => [
                'description' => '60cm autour du volume 1',
                'ip_minimum' => 'IPX4',
                'tension_max' => '230V avec protections',
                'appareils_autorises' => ['Luminaires Classe II', 'Prise rasoir isolée', 'Sèche-serviette Classe II'],
            ],
            'Hors volume' => [
                'description' => 'Au-delà de 60cm',
                'ip_minimum' => 'IP20 (standard)',
                'tension_max' => '230V standard',
                'appareils_autorises' => ['Tout équipement avec DDR 30mA'],
            ],
        ],
        'temps_estime' => [
            'renovation_complete' => [
                'duree' => '1-2 jours',
                'description' => 'Rénovation électrique complète SDB'
            ],
            'ajout_points' => [
                'duree' => '3-4h',
                'description' => 'Ajout de quelques points lumineux/prises'
            ],
            'liaison_equipotentielle' => [
                'duree' => '1-2h',
                'description' => 'Mise en place liaison équipotentielle seule'
            ],
            'facteurs_variation' => [
                'Accessibilité (sous baignoire, faux plafond)',
                'Type de cloisons (carrelage existant)',
                'Nombre de points à créer',
                'Passage en apparent ou encastré',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/perforateur',
            'Scie cloche carrelage',
            'Détecteur de canalisations',
            'Multimètre',
            'Pince à sertir',
            'Niveau à bulle',
            'Mastic silicone',
            'EPI (lunettes, gants)',
        ],
        'ordre_travaux' => [
            '1. Coupure et consignation du circuit',
            '2. Repérage des volumes et des passages de câbles',
            '3. Percement des boîtiers (encastré) ou pose goulottes (apparent)',
            '4. Tirage des câbles',
            '5. Pose de la liaison équipotentielle',
            '6. Installation des boîtiers étanches et DCL',
            '7. Raccordement des appareils',
            '8. Test d\'isolement et vérification DDR',
            '9. Pose des luminaires et finitions',
        ],
        'erreurs_courantes' => [
            'Prise standard installée en volume 2',
            'Oublier la liaison équipotentielle',
            'Luminaire non étanche en volume 1 ou 2',
            'Interrupteur classique en volume 2',
            'Boîte de connexion non étanche',
            'Câble passant dans le volume 0 ou 1 sans protection',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Rénovation logement > 2 ans',
        ],
    ],

    /**
     * =====================================================
     * CUISINE
     * =====================================================
     */
    'cuisine' => [
        'nom' => 'Installation électrique cuisine',
        'categorie' => 'electricite',
        'mots_cles' => [
            'cuisine', 'plaque', 'cuisson', 'four', 'hotte',
            'lave-vaisselle', 'frigo', 'congelateur', 'electromenager',
            'plan de travail', 'ilot', 'micro-onde', 'prise cuisine'
        ],
        'normes' => [
            'NF C 15-100 §10.1.3.2' => 'Circuits spécialisés obligatoires',
            'NF C 15-100 §11.3' => 'Nombre minimum de prises par pièce',
            'NF C 15-100 §10.1.3.3' => 'Protection différentielle 30mA',
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
            [
                'code' => 'CIRCUIT_LAVE_LINGE',
                'designation' => 'Circuit spécialisé 20A lave-linge',
                'raison' => 'Circuit dédié obligatoire si en cuisine',
                'quantite' => 1
            ],
            [
                'code' => 'ID_30MA',
                'designation' => 'Différentiel 30mA pour circuits cuisine',
                'raison' => 'Protection obligatoire',
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
                'designation' => 'Alimentation hotte dédiée',
                'raison' => 'Point lumineux dédié ou prise pour hotte',
                'quantite' => 1
            ],
            [
                'code' => 'CIRCUIT_FRIGO',
                'designation' => 'Circuit dédié frigo/congélateur',
                'raison' => 'Éviter coupure involontaire',
                'quantite' => 1
            ],
            [
                'code' => 'PRISE_ILOT',
                'designation' => 'Prise sur îlot central',
                'raison' => 'Pratique pour petits appareils',
                'quantite' => 2
            ],
        ],
        'certifications' => [],
        'points_controle' => [
            'Circuit 32A obligatoire pour plaque (câble 6mm²)',
            'Minimum 6 prises plan de travail (cuisine > 4m²)',
            'Pas de prise au-dessus évier ou plaque',
            'Hotte : distance min 65cm de la plaque',
            'Congélateur : circuit dédié recommandé (coupure indépendante)',
            'Prises plan de travail à 8cm min du plan de travail',
            'Sortie de câble plaque à 12cm max du sol',
        ],
        'temps_estime' => [
            'renovation_complete' => [
                'duree' => '2-3 jours',
                'description' => 'Installation électrique cuisine complète'
            ],
            'ajout_circuits' => [
                'duree' => '4-6h',
                'description' => 'Ajout de circuits spécialisés depuis tableau'
            ],
            'prises_plan_travail' => [
                'duree' => '2-3h',
                'description' => 'Installation prises plan de travail seules'
            ],
            'facteurs_variation' => [
                'État de l\'installation existante',
                'Distance au tableau électrique',
                'Type de murs (placo, béton)',
                'Cuisine équipée déjà en place ou pas',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/perforateur',
            'Rainureuse (si encastré)',
            'Scie cloche Ø67',
            'Détecteur de canalisations',
            'Multimètre',
            'Tire-fil',
            'Niveau à bulle',
            'EPI complets',
        ],
        'ordre_travaux' => [
            '1. Plan d\'implantation avec cuisiniste si besoin',
            '2. Repérage des emplacements appareils',
            '3. Tirage des circuits depuis le tableau',
            '4. Pose des circuits spécialisés (plaque, four, LV)',
            '5. Pose du circuit prises plan de travail',
            '6. Installation sortie de câble hotte',
            '7. Test de chaque circuit',
            '8. Coordination avec cuisiniste pour finitions',
        ],
        'erreurs_courantes' => [
            'Sortie de câble plaque mal positionnée',
            'Oublier l\'alimentation hotte',
            'Prises plan de travail derrière les appareils',
            'Circuit non dédié pour lave-vaisselle',
            'Prise au-dessus de l\'évier',
            'Section câble insuffisante pour plaque',
            'Trop peu de prises plan de travail',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Rénovation logement > 2 ans',
        ],
    ],

    /**
     * =====================================================
     * PISCINE
     * =====================================================
     */
    'piscine' => [
        'nom' => 'Installation électrique piscine',
        'categorie' => 'electricite',
        'mots_cles' => [
            'piscine', 'bassin', 'local technique', 'pompe piscine',
            'filtration', 'electrolyseur', 'projecteur piscine', 'spa', 'jacuzzi',
            'coffret piscine', 'nage contre courant', 'volet roulant piscine'
        ],
        'normes' => [
            'NF C 15-100 §702' => 'Piscines et bassins',
            'NF C 15-100 Annexe Q' => 'Schémas piscines',
            'NF C 15-100 §702.55' => 'Autres équipements fixes',
            'NF EN 60335-2-41' => 'Pompes pour piscines',
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
            [
                'code' => 'COFFRET_PISCINE',
                'designation' => 'Coffret électrique étanche IP55',
                'raison' => 'Protection des équipements en local technique',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'HORLOGE_FILTRATION',
                'designation' => 'Horloge programmable filtration',
                'raison' => 'Programmation cycles de filtration',
                'quantite' => 1
            ],
            [
                'code' => 'COFFRET_HORS_GEL',
                'designation' => 'Coffret hors gel',
                'raison' => 'Protection pompe contre le gel',
                'quantite' => 1
            ],
            [
                'code' => 'PROJECTEUR_LED',
                'designation' => 'Projecteurs LED 12V',
                'raison' => 'Éclairage basse consommation',
                'quantite' => 2
            ],
            [
                'code' => 'PRISE_ROBOT',
                'designation' => 'Prise dédiée robot de piscine',
                'raison' => 'Alimentation robot nettoyeur',
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
            'Câblage en TPC jusqu\'au local technique',
            'Transfo TBTS hors volume 0, 1 et 2',
        ],
        'volumes' => [
            'Volume 0' => [
                'description' => 'Intérieur du bassin',
                'ip_minimum' => 'IPX8',
                'tension_max' => 'TBTS 12V uniquement',
                'appareils_autorises' => ['Projecteurs 12V', 'Nage contre courant'],
            ],
            'Volume 1' => [
                'description' => '2m du bord + 2.5m au-dessus de la surface',
                'ip_minimum' => 'IPX5',
                'tension_max' => 'TBTS ou Classe II',
                'appareils_autorises' => ['Transfo TBTS', 'Pompes fixes'],
            ],
            'Volume 2' => [
                'description' => '1.5m autour du volume 1',
                'ip_minimum' => 'IPX4',
                'tension_max' => '230V avec DDR 30mA',
                'appareils_autorises' => ['Luminaires étanches', 'Prises protégées'],
            ],
        ],
        'temps_estime' => [
            'local_technique_complet' => [
                'duree' => '2-3 jours',
                'description' => 'Installation électrique local technique complet'
            ],
            'eclairage_bassin' => [
                'duree' => '1 journée',
                'description' => 'Installation projecteurs 12V avec transfo'
            ],
            'mise_aux_normes' => [
                'duree' => '1-2 jours',
                'description' => 'Mise en conformité installation existante'
            ],
            'facteurs_variation' => [
                'Distance tableau principal - local technique',
                'Nombre d\'équipements (filtration, PAC, électrolyseur)',
                'Nombre de projecteurs',
                'Automatismes (volet, nage contre courant)',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/perforateur',
            'Niveau à bulle',
            'Multimètre',
            'Mégohmmètre',
            'Tire-fil',
            'Pince à sertir',
            'Appareil de mesure de terre',
            'EPI (gants isolants, lunettes)',
        ],
        'ordre_travaux' => [
            '1. Tirage câble alimentation depuis tableau principal',
            '2. Installation coffret électrique local technique',
            '3. Câblage des équipements (pompe, filtration, PAC)',
            '4. Installation transformateur TBTS',
            '5. Câblage des projecteurs 12V',
            '6. Mise en place liaison équipotentielle',
            '7. Programmation horloge et automatismes',
            '8. Tests et vérifications (isolement, DDR)',
        ],
        'erreurs_courantes' => [
            'Projecteurs 230V au lieu de 12V TBTS',
            'Transfo TBTS installé en volume 1',
            'Oublier la liaison équipotentielle des éléments métalliques',
            'Coffret trop proche du bassin (< 1.25m)',
            'Câbles non protégés entre local et bassin',
            'Différentiel non 30mA',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Rénovation installation existante',
        ],
    ],

    /**
     * =====================================================
     * EXTÉRIEUR
     * =====================================================
     */
    'exterieur' => [
        'nom' => 'Installation électrique extérieure',
        'categorie' => 'electricite',
        'mots_cles' => [
            'exterieur', 'jardin', 'terrasse', 'eclairage exterieur',
            'portail', 'cloture', 'abri jardin', 'pergola', 'carport',
            'borne jardin', 'spot exterieur', 'prise jardin'
        ],
        'normes' => [
            'NF C 15-100 §702.55' => 'Installations extérieures',
            'NF C 17-200' => 'Éclairage extérieur',
            'NF C 15-100 §559.6' => 'Câbles enterrés',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'ID_30MA',
                'designation' => 'Différentiel 30mA haute sensibilité',
                'raison' => 'Protection obligatoire circuits extérieurs',
                'quantite' => 1
            ],
            [
                'code' => 'FOURREAU_TPC',
                'designation' => 'Fourreau TPC rouge pour câbles enterrés',
                'raison' => 'Protection mécanique obligatoire',
                'quantite' => 'selon distance'
            ],
            [
                'code' => 'GRILLAGE_AVERT',
                'designation' => 'Grillage avertisseur rouge',
                'raison' => 'Signalisation câbles enterrés (20cm au-dessus)',
                'quantite' => 'selon distance'
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
                'code' => 'CABLE_U1000R2V',
                'designation' => 'Câble U1000R2V ou équivalent',
                'raison' => 'Câble adapté enterrement sous fourreau',
                'quantite' => 'selon distance'
            ],
            [
                'code' => 'BORNE_LUMINEUSE',
                'designation' => 'Bornes lumineuses de jardin',
                'raison' => 'Balisage allées',
                'quantite_estimee' => 4
            ],
            [
                'code' => 'SPOT_ENCASTRE',
                'designation' => 'Spots encastrés de sol IP67',
                'raison' => 'Éclairage terrasse/allée',
                'quantite_estimee' => 6
            ],
            [
                'code' => 'PROJECTEUR_LED',
                'designation' => 'Projecteur LED avec détecteur',
                'raison' => 'Éclairage sécuritaire',
                'quantite_estimee' => 2
            ],
        ],
        'certifications' => [],
        'points_controle' => [
            'Tout appareillage extérieur minimum IP44 (IP55 recommandé)',
            'Câbles enterrés sous fourreau TPC rouge à 50cm minimum',
            'Grillage avertisseur rouge obligatoire à 20cm au-dessus câbles',
            'Mise à la terre de tous les éléments métalliques',
            'Pas de câbles aériens sans autorisation (servitude)',
            'Profondeur enterrement : 50cm zones non carrossables, 85cm si carrossable',
            'Crosses de remontée obligatoires',
        ],
        'temps_estime' => [
            'eclairage_terrasse' => [
                'duree' => '3-4h',
                'description' => 'Installation 4-6 points lumineux terrasse'
            ],
            'eclairage_jardin_complet' => [
                'duree' => '1-2 jours',
                'description' => 'Réseau extérieur complet avec tranchée'
            ],
            'prise_exterieure' => [
                'duree' => '2h',
                'description' => 'Installation d\'une prise étanche'
            ],
            'facteurs_variation' => [
                'Longueur des tranchées',
                'Nature du sol (terre, béton, graviers)',
                'Nombre de points d\'éclairage',
                'Complexité du terrain',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/perforateur',
            'Trancheuse ou pioche/pelle',
            'Niveau à bulle',
            'Multimètre',
            'Mégohmmètre',
            'Tire-fil',
            'Dameuse',
            'EPI (gants, lunettes, chaussures de sécurité)',
        ],
        'ordre_travaux' => [
            '1. Traçage du parcours des câbles',
            '2. Creusement des tranchées (50cm mini)',
            '3. Pose d\'un lit de sable (5-10cm)',
            '4. Déroulage des fourreaux TPC',
            '5. Tirage des câbles U1000R2V',
            '6. Pose du grillage avertisseur',
            '7. Remblaiement avec sable puis terre',
            '8. Installation des appareillages extérieurs',
            '9. Raccordement au tableau',
            '10. Tests d\'isolement et DDR',
        ],
        'erreurs_courantes' => [
            'Profondeur de tranchée insuffisante',
            'Oublier le grillage avertisseur',
            'Câbles non protégés par fourreau',
            'Luminaires non IP approprié',
            'Connexions non étanches',
            'Crosse de remontée mal protégée',
            'Pas de DDR 30mA dédié',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Travaux liés à logement > 2 ans',
        ],
    ],

    /**
     * =====================================================
     * PHOTOVOLTAÏQUE
     * =====================================================
     */
    'photovoltaique' => [
        'nom' => 'Installation photovoltaïque',
        'categorie' => 'electricite',
        'mots_cles' => [
            'photovoltaique', 'panneau solaire', 'panneaux solaires',
            'pv', 'autoconsommation', 'onduleur', 'micro-onduleur',
            'injection', 'revente', 'solaire', 'kwc', 'batterie solaire'
        ],
        'normes' => [
            'NF C 15-100 §712' => 'Installations photovoltaïques',
            'Guide UTE C 15-712-1' => 'Installations PV raccordées au réseau',
            'NF EN 62446' => 'Documentation et essais installations PV',
            'Guide UTE C 15-712-2' => 'Autoconsommation',
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
            [
                'code' => 'COUPURE_URGENCE',
                'designation' => 'Dispositif de coupure d\'urgence accessible',
                'raison' => 'Sécurité pompiers',
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
            [
                'code' => 'MONITORING',
                'designation' => 'Système de monitoring',
                'raison' => 'Suivi en temps réel de la production',
                'quantite' => 1
            ],
            [
                'code' => 'OPTIMISEUR',
                'designation' => 'Optimiseurs de puissance',
                'raison' => 'Maximisation rendement si ombrage',
                'quantite' => 'selon panneaux'
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
            'Vérification résistance toiture (charge panneaux)',
            'Conformité équerres et rails de fixation',
        ],
        'temps_estime' => [
            'installation_3kwc' => [
                'duree' => '2 jours',
                'description' => 'Installation 8-10 panneaux (3 kWc)'
            ],
            'installation_6kwc' => [
                'duree' => '2-3 jours',
                'description' => 'Installation 15-18 panneaux (6 kWc)'
            ],
            'installation_9kwc' => [
                'duree' => '3-4 jours',
                'description' => 'Installation 22-26 panneaux (9 kWc)'
            ],
            'facteurs_variation' => [
                'Type de toiture (tuiles, ardoises, bac acier)',
                'Hauteur et accessibilité',
                'Distance panneaux-onduleur',
                'Micro-onduleurs vs onduleur central',
                'Installation batteries',
            ],
        ],
        'outils_necessaires' => [
            'Échafaudage ou nacelle',
            'Perceuse/visseuse',
            'Clés adaptées (M8, M10)',
            'Pince à sertir MC4',
            'Multimètre DC',
            'Appareil de test I-V',
            'Mégohmmètre',
            'EPI (harnais, casque, gants isolants DC)',
        ],
        'ordre_travaux' => [
            '1. Étude préalable (ombrage, orientation, inclinaison)',
            '2. Déclaration préalable de travaux',
            '3. Pose de l\'échafaudage / sécurisation',
            '4. Installation des rails et fixations toiture',
            '5. Pose des panneaux photovoltaïques',
            '6. Câblage DC (connecteurs MC4)',
            '7. Installation coffret DC et onduleur',
            '8. Raccordement AC au tableau',
            '9. Installation parafoudres et protections',
            '10. Mise en service et test de production',
            '11. Demande de raccordement Enedis + Consuel',
        ],
        'erreurs_courantes' => [
            'Sous-estimation de l\'ombrage (cheminées, arbres)',
            'Rails mal ancrés (fuites toiture)',
            'Connecteurs MC4 mal sertis',
            'Câbles DC mal dimensionnés',
            'Ventilation insuffisante de l\'onduleur',
            'Oublier la déclaration de travaux',
            'Étiquetage absent',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Installation ≤ 3 kWc sur logement > 2 ans',
        ],
        'aides_disponibles' => [
            'Prime à l\'autoconsommation' => 'Prime dégressive selon puissance',
            'Obligation d\'achat EDF OA' => 'Revente surplus ou totalité',
            'MaPrimeRénov\'' => 'Si couplé à autres travaux énergétiques',
        ],
    ],

    /**
     * =====================================================
     * TABLEAU ÉLECTRIQUE / MISE AUX NORMES
     * =====================================================
     */
    'tableau' => [
        'nom' => 'Rénovation / Mise aux normes tableau électrique',
        'categorie' => 'electricite',
        'mots_cles' => [
            'tableau', 'coffret', 'mise aux normes', 'renovation tableau',
            'differentiel', 'disjoncteur', 'gtl', 'etel', 'gaine technique',
            'peigne', 'bornier', 'rangee'
        ],
        'normes' => [
            'NF C 15-100 §10.1' => 'Tableau de répartition',
            'NF C 15-100 §11' => 'Nombre et répartition des circuits',
            'NF C 15-100 §558' => 'GTL - Gaine Technique Logement',
            'NF C 14-100' => 'Installations de branchement',
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
            [
                'code' => 'OBTURATEUR',
                'designation' => 'Obturateurs pour emplacements vides',
                'raison' => 'Protection contre contacts directs',
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
            [
                'code' => 'MINUTERIE',
                'designation' => 'Minuterie pour parties communes',
                'raison' => 'Économie d\'énergie',
                'quantite' => 1
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
            'Calibre DB adapté à l\'abonnement',
            'Schéma électrique à fournir',
        ],
        'temps_estime' => [
            'remplacement_simple' => [
                'duree' => '1 journée',
                'description' => 'Remplacement tableau existant (mêmes circuits)'
            ],
            'mise_aux_normes' => [
                'duree' => '1-2 jours',
                'description' => 'Mise aux normes avec création de circuits'
            ],
            'installation_neuve' => [
                'duree' => '2-3 jours',
                'description' => 'Installation tableau complet + câblage'
            ],
            'facteurs_variation' => [
                'Nombre de circuits existants',
                'Circuits à créer ou reprendre',
                'Installation GTL/ETEL',
                'Accessibilité du tableau',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/perforateur',
            'Tournevis isolés',
            'Pince à dénuder',
            'Pince coupante isolée',
            'Multimètre',
            'Testeur de tension (VAT)',
            'Niveau à bulle',
            'Étiqueteuse',
            'EPI (gants isolants classe 0)',
        ],
        'ordre_travaux' => [
            '1. Relevé de l\'existant et schéma',
            '2. Coupure au DB et consignation',
            '3. Dépose de l\'ancien tableau si remplacement',
            '4. Pose du nouveau coffret',
            '5. Installation des borniers et peignes',
            '6. Pose des différentiels et disjoncteurs',
            '7. Raccordement des circuits existants',
            '8. Création des nouveaux circuits si besoin',
            '9. Test de chaque circuit et DDR',
            '10. Étiquetage et schéma final',
        ],
        'erreurs_courantes' => [
            'Mauvaise répartition des circuits par DDR',
            'Circuits éclairage et prises sur même DDR',
            'Calibre de disjoncteur inadapté',
            'Peignes mal coupés ou mal insérés',
            'Serrage insuffisant des connexions',
            'Pas de réserve dans le tableau',
            'Repérage manquant ou incorrect',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Rénovation logement > 2 ans',
        ],
    ],

    /**
     * =====================================================
     * CHAUFFAGE ÉLECTRIQUE
     * =====================================================
     */
    'chauffage' => [
        'nom' => 'Installation chauffage électrique',
        'categorie' => 'electricite',
        'mots_cles' => [
            'chauffage', 'radiateur', 'convecteur', 'inertie',
            'plancher chauffant', 'seche-serviette', 'thermostat',
            'fil pilote', 'programmation', 'panneau rayonnant'
        ],
        'normes' => [
            'NF C 15-100 §10.1.3.6' => 'Circuits chauffage',
            'NF EN 60335' => 'Sécurité appareils de chauffage',
            'RT 2012/RE 2020' => 'Réglementation thermique',
            'NF C 15-100 §559.2' => 'Planchers chauffants',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'DJ_CHAUFFAGE',
                'designation' => 'Disjoncteur dédié par circuit chauffage',
                'raison' => 'Un disjoncteur par tranche de 4500W max',
                'quantite' => 'selon puissance'
            ],
            [
                'code' => 'ID_30MA',
                'designation' => 'Différentiel 30mA',
                'raison' => 'Protection obligatoire circuits chauffage',
                'quantite' => 1
            ],
            [
                'code' => 'CABLE_ADAPTE',
                'designation' => 'Câble section adaptée',
                'raison' => '1.5mm² jusqu\'à 2250W, 2.5mm² jusqu\'à 4500W',
                'quantite' => 'selon circuits'
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
            [
                'code' => 'THERMOSTAT_CONNECTE',
                'designation' => 'Thermostat connecté',
                'raison' => 'Pilotage intelligent du chauffage',
                'quantite' => 1
            ],
        ],
        'certifications' => [
            [
                'nom' => 'RGE',
                'obligatoire_si' => 'Demande d\'aides (CEE, MaPrimeRénov\')',
                'delivree_par' => 'Qualibat, Qualifelec',
                'description' => 'Reconnu Garant de l\'Environnement',
            ],
        ],
        'points_controle' => [
            'Section câble adaptée à la puissance (2.5mm² jusqu\'à 4500W)',
            'Hauteur pose radiateur : 15cm du sol minimum',
            'Distance radiateur/prise : 25cm minimum',
            'Pas de radiateur sous tableau électrique',
            'Fil pilote prévu pour chaque radiateur (programmation)',
            'Aération suffisante autour des convecteurs',
            'Thermostat d\'ambiance hors courants d\'air',
        ],
        'temps_estime' => [
            'remplacement_radiateur' => [
                'duree' => '1-2h',
                'description' => 'Remplacement d\'un radiateur existant'
            ],
            'installation_piece' => [
                'duree' => '3-4h',
                'description' => 'Installation complète avec création circuit'
            ],
            'maison_complete' => [
                'duree' => '2-3 jours',
                'description' => 'Installation chauffage maison complète'
            ],
            'facteurs_variation' => [
                'Création de circuits ou remplacement',
                'Fil pilote à tirer ou existant',
                'Programmateur centralisé ou non',
                'Accessibilité des passages de câbles',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/perforateur',
            'Niveau à bulle',
            'Multimètre',
            'Tournevis isolés',
            'Pince à dénuder',
            'Mètre',
            'Détecteur de métaux/canalisations',
            'EPI',
        ],
        'ordre_travaux' => [
            '1. Dimensionnement thermique des pièces',
            '2. Choix des emplacements radiateurs',
            '3. Tirage des câbles depuis le tableau',
            '4. Pose des boîtiers de raccordement',
            '5. Installation des supports muraux',
            '6. Raccordement électrique des radiateurs',
            '7. Câblage fil pilote',
            '8. Installation programmateur/gestionnaire',
            '9. Paramétrage et mise en service',
        ],
        'erreurs_courantes' => [
            'Radiateur sous fenêtre sans rideau d\'air',
            'Section câble insuffisante pour la puissance',
            'Fil pilote oublié ou non raccordé',
            'Thermostat mal placé (courant d\'air)',
            'Circuit surchargé (plus de 4500W)',
            'Hauteur de pose inadaptée',
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
        'categorie' => 'electricite',
        'mots_cles' => [
            'alarme', 'securite', 'intrusion', 'detecteur',
            'sirene', 'centrale', 'videosurveillance', 'camera',
            'detecteur mouvement', 'contact ouverture', 'telecommande'
        ],
        'normes' => [
            'NF C 15-100 §10.1.3.10' => 'Circuits sécurité',
            'NF A2P' => 'Certification matériel anti-intrusion',
            'NF & A2P' => 'Certification installateur (optionnel)',
            'RGPD' => 'Protection données vidéosurveillance',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'ALIM_SECOURS',
                'designation' => 'Alimentation secourue (batterie)',
                'raison' => 'Continuité de service en cas de coupure',
                'quantite' => 1
            ],
            [
                'code' => 'DETECTEUR_FUMEE',
                'designation' => 'Détecteurs de fumée (DAAF)',
                'raison' => 'Obligatoire depuis 2015 (1 par niveau)',
                'quantite' => 'selon logement'
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'SIRENE_INTERIEURE',
                'designation' => 'Sirène intérieure',
                'raison' => 'Effet de surprise sur intrus',
                'quantite' => 1
            ],
            [
                'code' => 'SIRENE_EXTERIEURE',
                'designation' => 'Sirène extérieure avec flash',
                'raison' => 'Effet dissuasif + repérage',
                'quantite' => 1
            ],
            [
                'code' => 'DETECTEUR_MOUVEMENT',
                'designation' => 'Détecteurs de mouvement IR',
                'raison' => 'Détection volumétrique',
                'quantite' => 'selon configuration'
            ],
            [
                'code' => 'CONTACT_OUVERTURE',
                'designation' => 'Contacts d\'ouverture portes/fenêtres',
                'raison' => 'Détection périphérique',
                'quantite' => 'selon ouvertures'
            ],
            [
                'code' => 'CAMERA_IP',
                'designation' => 'Caméra IP avec enregistrement',
                'raison' => 'Levée de doute et preuve',
                'quantite_estimee' => 2
            ],
        ],
        'certifications' => [
            [
                'nom' => 'APSAD',
                'obligatoire_si' => 'Réduction prime assurance souhaitée',
                'delivree_par' => 'CNPP',
                'description' => 'Certification pour réduction assurance vol/incendie',
            ],
            [
                'nom' => 'NF A2P',
                'obligatoire_si' => 'Installation professionnelle assurance',
                'delivree_par' => 'CNPP/AFNOR',
                'description' => 'Certification matériel anti-intrusion',
            ],
        ],
        'points_controle' => [
            'Détecteur fumée obligatoire (loi ALUR 2015)',
            'Autonomie batterie centrale : 72h minimum',
            'Transmission alarme : GSM ou IP',
            'Sirène extérieure : temporisation 3 min max (arrêté)',
            'Câblage basse tension séparé du 230V',
            'Centrale dans zone protégée',
            'Déclaration CNIL/préfecture si caméras',
        ],
        'temps_estime' => [
            'alarme_sans_fil' => [
                'duree' => '3-4h',
                'description' => 'Installation alarme sans fil basique'
            ],
            'alarme_filaire' => [
                'duree' => '1-2 jours',
                'description' => 'Installation alarme filaire complète'
            ],
            'videosurveillance' => [
                'duree' => '1 journée',
                'description' => 'Installation 4-6 caméras IP'
            ],
            'facteurs_variation' => [
                'Filaire vs sans fil',
                'Nombre de détecteurs',
                'Vidéosurveillance incluse',
                'Télésurveillance à configurer',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/visseuse',
            'Niveau à bulle',
            'Multimètre',
            'Testeur de câbles RJ45',
            'Escabeau',
            'Tournevis',
            'Smartphone (paramétrage app)',
            'EPI',
        ],
        'ordre_travaux' => [
            '1. Étude des besoins et des risques',
            '2. Définition des zones à protéger',
            '3. Emplacement de la centrale',
            '4. Câblage (si filaire) ou pose des détecteurs',
            '5. Installation sirènes intérieure et extérieure',
            '6. Raccordement et programmation centrale',
            '7. Apprentissage des télécommandes/badges',
            '8. Configuration de la transmission (GSM/IP)',
            '9. Test de tous les détecteurs',
            '10. Formation de l\'utilisateur',
        ],
        'erreurs_courantes' => [
            'Détecteur mouvement face à fenêtre (faux positifs)',
            'Centrale accessible depuis l\'extérieur',
            'Batterie de secours non vérifiée',
            'Sirène sans autorisation (copropriété)',
            'Pas de détecteur sur accès principal',
            'Transmission GSM sans carte SIM valide',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Installation sur logement > 2 ans',
        ],
    ],

    /**
     * =====================================================
     * INTERPHONIE / VIDÉOPHONIE
     * =====================================================
     */
    'interphonie' => [
        'nom' => 'Installation interphone / visiophone',
        'categorie' => 'electricite',
        'mots_cles' => [
            'interphone', 'visiophone', 'portier', 'platine',
            'moniteur', 'combine', 'sonnette', 'carillon',
            'badge', 'digicode', 'ouverture portail'
        ],
        'normes' => [
            'NF C 15-100 §10.1.3.10' => 'Circuits commande',
            'NF C 15-100 §771.314.2' => 'Protection circuits commande',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'ALIM_PORTIER',
                'designation' => 'Alimentation adaptée au système',
                'raison' => 'Tension selon modèle (12V, 18V, 230V)',
                'quantite' => 1
            ],
            [
                'code' => 'GAINE_PORTIER',
                'designation' => 'Gaine de passage dédiée',
                'raison' => 'Séparation des courants forts',
                'quantite' => 'selon distance'
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'PLATINE_EXTERIEURE',
                'designation' => 'Platine de rue avec caméra',
                'raison' => 'Identification des visiteurs',
                'quantite' => 1
            ],
            [
                'code' => 'MONITEUR',
                'designation' => 'Moniteur intérieur couleur',
                'raison' => 'Visualisation et communication',
                'quantite' => 1
            ],
            [
                'code' => 'COMBINE_SUPPLEMENTAIRE',
                'designation' => 'Combiné supplémentaire',
                'raison' => 'Second point de réponse',
                'quantite' => 1
            ],
            [
                'code' => 'GACHE_ELECTRIQUE',
                'designation' => 'Gâche électrique portillon',
                'raison' => 'Ouverture à distance',
                'quantite' => 1
            ],
        ],
        'certifications' => [],
        'points_controle' => [
            'Câblage adapté au système (2 fils, 4 fils, IP)',
            'Alimentation correcte de la platine',
            'Étanchéité platine extérieure (IP44 min)',
            'Hauteur platine : 1.50m du sol',
            'Éclairage suffisant pour caméra',
            'Gâche compatible avec la platine',
            'Test de portée audio/vidéo',
        ],
        'temps_estime' => [
            'interphone_simple' => [
                'duree' => '2-3h',
                'description' => 'Installation interphone audio 2 fils'
            ],
            'visiophone' => [
                'duree' => '3-4h',
                'description' => 'Installation visiophone avec gâche'
            ],
            'systeme_collectif' => [
                'duree' => '1-2 jours',
                'description' => 'Installation immeuble plusieurs logements'
            ],
            'facteurs_variation' => [
                'Distance platine-moniteur',
                'Passage câbles (existant ou à créer)',
                'Gâche électrique à installer',
                'Nombre de postes intérieurs',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/perforateur',
            'Niveau à bulle',
            'Tournevis',
            'Pince à dénuder',
            'Multimètre',
            'Tire-fil',
            'Escabeau',
            'Caméra d\'inspection (pour gaines)',
        ],
        'ordre_travaux' => [
            '1. Repérage passage câbles platine-moniteur',
            '2. Pose de la gaine si nécessaire',
            '3. Tirage du câble',
            '4. Installation de la platine extérieure',
            '5. Installation du moniteur intérieur',
            '6. Installation de la gâche si prévue',
            '7. Raccordement de l\'alimentation',
            '8. Câblage et raccordement des postes',
            '9. Paramétrage et tests',
        ],
        'erreurs_courantes' => [
            'Section câble insuffisante (chute de tension)',
            'Platine exposée plein sud (contre-jour caméra)',
            'Gâche incompatible avec l\'alimentation',
            'Câblage mélangé avec courant fort',
            'Hauteur de platine inadaptée',
            'Oubli de l\'alimentation de la gâche',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Installation sur logement > 2 ans',
        ],
    ],

    /**
     * =====================================================
     * MOTORISATION PORTAIL / VOLET
     * =====================================================
     */
    'motorisation' => [
        'nom' => 'Motorisation portail / volet / store',
        'categorie' => 'electricite',
        'mots_cles' => [
            'motorisation', 'portail', 'volet', 'store', 'banne',
            'coulissant', 'battant', 'garage', 'porte garage',
            'telecommande', 'automatisme', 'moteur tubulaire'
        ],
        'normes' => [
            'NF EN 12453' => 'Sécurité des portes motorisées',
            'NF EN 12978' => 'Dispositifs de protection',
            'NF C 15-100 §462' => 'Protection des moteurs',
            'Directive Machines 2006/42/CE' => 'Conformité CE',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'PHOTOCELLULES',
                'designation' => 'Cellules photoélectriques',
                'raison' => 'Détection obstacles obligatoire portail',
                'quantite' => 1
            ],
            [
                'code' => 'GYROPHARE',
                'designation' => 'Feu clignotant',
                'raison' => 'Signalisation mouvement portail',
                'quantite' => 1
            ],
            [
                'code' => 'PROTECTION_MOTEUR',
                'designation' => 'Disjoncteur dédié moteur',
                'raison' => 'Protection du circuit',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'BATTERIE_SECOURS',
                'designation' => 'Batterie de secours',
                'raison' => 'Fonctionnement en cas de coupure',
                'quantite' => 1
            ],
            [
                'code' => 'DIGICODE',
                'designation' => 'Clavier à code',
                'raison' => 'Ouverture sans télécommande',
                'quantite' => 1
            ],
            [
                'code' => 'ANTENNE_DEPORTEE',
                'designation' => 'Antenne déportée',
                'raison' => 'Amélioration portée télécommandes',
                'quantite' => 1
            ],
            [
                'code' => 'BARRE_PALPEUSE',
                'designation' => 'Barre palpeuse',
                'raison' => 'Détection contact (portail coulissant)',
                'quantite' => 1
            ],
        ],
        'certifications' => [
            [
                'nom' => 'Marquage CE',
                'obligatoire_si' => 'Toute installation',
                'delivree_par' => 'Fabricant/Installateur',
                'description' => 'Conformité directive machines',
            ],
        ],
        'points_controle' => [
            'Photocellules obligatoires en fermeture',
            'Feu clignotant visible depuis voie publique',
            'Déverrouillage manuel accessible',
            'Force de poussée conforme (< 400N)',
            'Test des sécurités avant mise en service',
            'Documentation et marquage CE obligatoires',
            'Butées mécaniques présentes',
        ],
        'types_motorisation' => [
            'Portail battant' => [
                'types' => ['Bras articulés', 'Vérins', 'Enterrée', 'À roue'],
                'alimentation' => '230V ou solaire',
            ],
            'Portail coulissant' => [
                'types' => ['Rail au sol', 'Autoportant'],
                'alimentation' => '230V ou 24V batterie',
            ],
            'Porte de garage' => [
                'types' => ['Sectionnelle', 'Basculante', 'Enroulable'],
                'alimentation' => '230V',
            ],
            'Volet roulant' => [
                'types' => ['Moteur tubulaire', 'Radio ou filaire'],
                'alimentation' => '230V',
            ],
            'Store banne' => [
                'types' => ['Moteur tubulaire', 'Avec capteur vent/soleil'],
                'alimentation' => '230V',
            ],
        ],
        'temps_estime' => [
            'portail_battant' => [
                'duree' => '4-6h',
                'description' => 'Motorisation portail battant 2 vantaux'
            ],
            'portail_coulissant' => [
                'duree' => '4-5h',
                'description' => 'Motorisation portail coulissant'
            ],
            'volet_roulant' => [
                'duree' => '2-3h',
                'description' => 'Motorisation volet roulant existant'
            ],
            'porte_garage' => [
                'duree' => '3-4h',
                'description' => 'Motorisation porte de garage'
            ],
            'facteurs_variation' => [
                'Type et poids du portail',
                'Alimentation à amener ou existante',
                'Pose cellules et feu clignotant',
                'Interphone/visiophone à coupler',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/perforateur',
            'Niveau à bulle',
            'Clés (12, 13, 17)',
            'Multimètre',
            'Tournevis',
            'Pince coupante/dénuder',
            'Mètre',
            'EPI',
        ],
        'ordre_travaux' => [
            '1. Vérification du portail/volet (état, équilibrage)',
            '2. Pose de l\'alimentation si nécessaire',
            '3. Installation du moteur ou des vérins',
            '4. Pose du récepteur/armoire de commande',
            '5. Installation des sécurités (cellules, feu)',
            '6. Câblage de l\'ensemble',
            '7. Paramétrage des fins de course',
            '8. Apprentissage des télécommandes',
            '9. Tests de fonctionnement et sécurités',
            '10. Réglage de la force et de la vitesse',
        ],
        'erreurs_courantes' => [
            'Portail mal équilibré avant motorisation',
            'Photocellules mal alignées',
            'Feu clignotant non visible',
            'Force de fermeture trop élevée',
            'Pas de déverrouillage manuel',
            'Câblage alimentation sous-dimensionné',
            'Oubli de la documentation CE',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Installation sur logement > 2 ans',
        ],
    ],

    /**
     * =====================================================
     * DOMOTIQUE
     * =====================================================
     */
    'domotique' => [
        'nom' => 'Installation domotique',
        'categorie' => 'electricite',
        'mots_cles' => [
            'domotique', 'connecte', 'smart home', 'maison intelligente',
            'knx', 'zigbee', 'zwave', 'wifi', 'box domotique',
            'scenario', 'automatisation', 'assistant vocal'
        ],
        'normes' => [
            'NF C 15-100' => 'Installation basse tension standard',
            'NF EN 50090 (KNX)' => 'Si système KNX filaire',
            'IEEE 802.15.4' => 'Protocoles Zigbee/Z-Wave',
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
            [
                'code' => 'ONDULEUR',
                'designation' => 'Onduleur pour box et réseau',
                'raison' => 'Continuité de service',
                'quantite' => 1
            ],
            [
                'code' => 'INTER_CONNECTE',
                'designation' => 'Interrupteurs connectés',
                'raison' => 'Commande éclairage intelligente',
                'quantite' => 'selon besoins'
            ],
            [
                'code' => 'PRISE_CONNECTEE',
                'designation' => 'Prises connectées avec mesure',
                'raison' => 'Contrôle et suivi consommation',
                'quantite' => 'selon besoins'
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
            'Sécurité du réseau (mot de passe, VLAN)',
            'Compatibilité des équipements entre eux',
            'Documentation des scénarios programmés',
        ],
        'protocoles' => [
            'KNX' => [
                'type' => 'Filaire bus',
                'avantages' => ['Fiabilité', 'Pérennité', 'Interopérabilité'],
                'inconvenients' => ['Coût', 'Complexité installation'],
            ],
            'Zigbee' => [
                'type' => 'Sans fil maillé',
                'avantages' => ['Faible consommation', 'Réseau maillé', 'Prix'],
                'inconvenients' => ['Portée limitée', 'Compatibilité variable'],
            ],
            'Z-Wave' => [
                'type' => 'Sans fil maillé',
                'avantages' => ['Interopérabilité', 'Portée', 'Fiabilité'],
                'inconvenients' => ['Prix', 'Moins de choix'],
            ],
            'WiFi' => [
                'type' => 'Sans fil IP',
                'avantages' => ['Facilité', 'Pas de hub', 'Portée'],
                'inconvenients' => ['Consommation', 'Dépendance réseau'],
            ],
        ],
        'temps_estime' => [
            'systeme_basique' => [
                'duree' => '1 journée',
                'description' => 'Installation box + quelques équipements'
            ],
            'maison_complete' => [
                'duree' => '2-5 jours',
                'description' => 'Domotisation complète (éclairage, volets, chauffage)'
            ],
            'knx_neuf' => [
                'duree' => '5-10 jours',
                'description' => 'Installation KNX complète construction neuve'
            ],
            'facteurs_variation' => [
                'Protocole choisi (filaire/sans fil)',
                'Nombre d\'équipements',
                'Complexité des scénarios',
                'Intégration avec existant',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/visseuse',
            'Tournevis',
            'Multimètre',
            'Ordinateur portable',
            'Smartphone/tablette',
            'Testeur réseau',
            'Câbles RJ45',
            'Documentation constructeur',
        ],
        'ordre_travaux' => [
            '1. Définition des besoins et scénarios souhaités',
            '2. Choix du protocole et de la box',
            '3. Plan d\'implantation des équipements',
            '4. Installation du réseau (si filaire)',
            '5. Mise en place de la box domotique',
            '6. Installation des équipements (interrupteurs, modules)',
            '7. Appairage des équipements à la box',
            '8. Programmation des scénarios',
            '9. Tests de tous les automatismes',
            '10. Formation de l\'utilisateur',
        ],
        'erreurs_courantes' => [
            'WiFi instable (équipements déconnectés)',
            'Trop de protocoles différents',
            'Pas de commande manuelle de secours',
            'Scénarios trop complexes',
            'Sécurité réseau négligée',
            'Pas de documentation des programmations',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Installation sur logement > 2 ans',
        ],
    ],

    /**
     * =====================================================
     * TERTIAIRE / ERP
     * =====================================================
     */
    'erp' => [
        'nom' => 'Installation ERP / Tertiaire',
        'categorie' => 'electricite',
        'mots_cles' => [
            'erp', 'commerce', 'magasin', 'bureau', 'tertiaire',
            'local commercial', 'restaurant', 'hotel', 'etablissement',
            'baes', 'ssi', 'eclairage securite'
        ],
        'normes' => [
            'NF C 15-100' => 'Installation générale',
            'NF C 14-100' => 'Installations de branchement',
            'Règlement de sécurité ERP' => 'Arrêté du 25 juin 1980',
            'NF S 61-931 à 940' => 'Systèmes de Sécurité Incendie',
            'Code du travail' => 'Articles R4227-1 et suivants',
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
            [
                'code' => 'EXTINCTEURS',
                'designation' => 'Extincteurs adaptés',
                'raison' => 'Moyens de première intervention',
                'quantite' => '1 par 200m² minimum'
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'SSI',
                'designation' => 'Système de Sécurité Incendie',
                'raison' => 'Obligatoire selon catégorie ERP',
                'quantite' => 1
            ],
            [
                'code' => 'DESENFUMAGE',
                'designation' => 'Système de désenfumage',
                'raison' => 'Selon configuration et catégorie',
                'quantite' => 'selon besoins'
            ],
            [
                'code' => 'ALARME_TYPE4',
                'designation' => 'Alarme incendie Type 4',
                'raison' => 'Alerte évacuation',
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
            'Registre de sécurité à jour',
            'Plans d\'évacuation affichés',
        ],
        'categories_erp' => [
            '1ère catégorie' => '> 1500 personnes',
            '2ème catégorie' => '701 à 1500 personnes',
            '3ème catégorie' => '301 à 700 personnes',
            '4ème catégorie' => '≤ 300 personnes (selon type)',
            '5ème catégorie' => 'Petit établissement (seuils spécifiques)',
        ],
        'temps_estime' => [
            'local_commercial' => [
                'duree' => '2-5 jours',
                'description' => 'Installation électrique local commercial'
            ],
            'mise_conformite' => [
                'duree' => '1-3 jours',
                'description' => 'Mise en conformité ERP existant'
            ],
            'eclairage_securite' => [
                'duree' => '1 journée',
                'description' => 'Installation BAES complets'
            ],
            'facteurs_variation' => [
                'Surface et configuration',
                'Catégorie ERP',
                'État de l\'existant',
                'Type d\'activité',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/perforateur',
            'Nacelle ou échafaudage',
            'Multimètre',
            'Testeur BAES',
            'Mégohmmètre',
            'Appareil de mesure d\'éclairement',
            'EPI complets',
            'Documentation réglementaire',
        ],
        'ordre_travaux' => [
            '1. Étude réglementaire (catégorie, type ERP)',
            '2. Contact bureau de contrôle',
            '3. Plan d\'implantation électrique',
            '4. Installation tableau et circuits',
            '5. Installation éclairage et prises',
            '6. Installation éclairage de sécurité',
            '7. Installation SSI si requis',
            '8. Coupure d\'urgence pompiers',
            '9. Vérification initiale bureau de contrôle',
            '10. Commission de sécurité si nécessaire',
        ],
        'erreurs_courantes' => [
            'BAES manquants ou mal placés',
            'Pas de coupure d\'urgence accessible',
            'Câblage SSI non conforme',
            'Accessibilité PMR non respectée',
            'Registre de sécurité absent',
            'Vérification périodique oubliée',
        ],
        'tva_applicable' => [
            'taux' => 20,
            'condition' => 'Travaux dans local professionnel',
        ],
    ],

    /**
     * =====================================================
     * PLOMBERIE - SANITAIRES
     * =====================================================
     */
    'plomberie' => [
        'nom' => 'Installation plomberie sanitaire',
        'categorie' => 'plomberie',
        'mots_cles' => [
            'plomberie', 'sanitaire', 'robinet', 'mitigeur',
            'wc', 'toilette', 'lavabo', 'douche', 'baignoire',
            'evacuation', 'alimentation eau', 'per', 'cuivre', 'multicouche'
        ],
        'normes' => [
            'DTU 60.1' => 'Plomberie sanitaire',
            'DTU 60.11' => 'Règles de calcul des installations',
            'DTU 60.31' => 'Canalisations en chlorure de polyvinyle',
            'DTU 60.32' => 'Canalisations en polypropylène',
            'NF EN 1717' => 'Protection contre la pollution de l\'eau potable',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'DISCONNECTEUR',
                'designation' => 'Disconnecteur ou clapet anti-retour',
                'raison' => 'Protection réseau eau potable',
                'quantite' => 1
            ],
            [
                'code' => 'ROBINET_ARRET',
                'designation' => 'Robinet d\'arrêt général',
                'raison' => 'Coupure eau obligatoire',
                'quantite' => 1
            ],
            [
                'code' => 'REDUCTEUR_PRESSION',
                'designation' => 'Réducteur de pression',
                'raison' => 'Si pression réseau > 3 bars',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'FILTRE',
                'designation' => 'Filtre à tamis',
                'raison' => 'Protection des équipements',
                'quantite' => 1
            ],
            [
                'code' => 'MITIGEUR_THERMO',
                'designation' => 'Mitigeur thermostatique',
                'raison' => 'Confort et sécurité (anti-brûlure)',
                'quantite' => 1
            ],
            [
                'code' => 'SIPHON',
                'designation' => 'Siphon à garde d\'eau',
                'raison' => 'Anti-odeurs obligatoire',
                'quantite' => 'par appareil'
            ],
        ],
        'certifications' => [
            [
                'nom' => 'Attestation de conformité',
                'obligatoire_si' => 'Raccordement réseau public',
                'delivree_par' => 'Installateur qualifié',
                'description' => 'Conformité des installations',
            ],
        ],
        'points_controle' => [
            'Pression réseau entre 1.5 et 3 bars',
            'Pentes d\'évacuation 1 à 3%',
            'Diamètres évacuation conformes DTU',
            'Aération des chutes (ventilation primaire)',
            'Garde d\'eau siphons 50mm minimum',
            'Fixation des canalisations tous les 50-80cm',
            'Calorifugeage si passage zones froides',
        ],
        'temps_estime' => [
            'wc_complet' => [
                'duree' => '3-4h',
                'description' => 'Installation WC avec alimentation et évacuation'
            ],
            'lavabo' => [
                'duree' => '2-3h',
                'description' => 'Pose lavabo avec robinetterie'
            ],
            'douche_italienne' => [
                'duree' => '1-2 jours',
                'description' => 'Création douche à l\'italienne'
            ],
            'salle_bain_complete' => [
                'duree' => '3-5 jours',
                'description' => 'Plomberie complète salle de bain'
            ],
            'facteurs_variation' => [
                'Création ou remplacement',
                'Accessibilité des canalisations',
                'Distance au réseau existant',
                'Type de matériaux (PER, multicouche, cuivre)',
            ],
        ],
        'outils_necessaires' => [
            'Coupe-tube',
            'Pince à sertir (PER/multicouche)',
            'Chalumeau (cuivre)',
            'Clé à molette',
            'Niveau à bulle',
            'Perceuse/perforateur',
            'Ébavureur',
            'Détecteur de fuite',
        ],
        'ordre_travaux' => [
            '1. Repérage des réseaux existants',
            '2. Coupure et vidange des réseaux',
            '3. Percements et passages des canalisations',
            '4. Pose des alimentations eau',
            '5. Pose des évacuations',
            '6. Installation des appareils sanitaires',
            '7. Raccordement de la robinetterie',
            '8. Test d\'étanchéité sous pression',
            '9. Mise en eau et purge',
            '10. Vérification évacuations',
        ],
        'erreurs_courantes' => [
            'Pente d\'évacuation insuffisante',
            'Diamètre évacuation trop petit',
            'Pas de ventilation primaire',
            'Oubli du réducteur de pression',
            'Siphon avec garde d\'eau insuffisante',
            'Canalisations non calorifugées',
            'Raccords mal serrés (fuites)',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Rénovation logement > 2 ans',
        ],
    ],

    /**
     * =====================================================
     * CLIMATISATION / POMPE À CHALEUR
     * =====================================================
     */
    'climatisation' => [
        'nom' => 'Installation climatisation / PAC',
        'categorie' => 'chauffage',
        'mots_cles' => [
            'climatisation', 'clim', 'pompe a chaleur', 'pac',
            'split', 'monosplit', 'multisplit', 'reversible',
            'gainable', 'air air', 'air eau', 'froid'
        ],
        'normes' => [
            'NF C 15-100 §422' => 'Protection des circuits climatisation',
            'RT 2012/RE 2020' => 'Performance énergétique',
            'Règlement F-Gaz' => 'Manipulation fluides frigorigènes',
            'DTU 68.3' => 'Ventilation et conditionnement d\'air',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'DJ_CLIM',
                'designation' => 'Disjoncteur dédié climatisation',
                'raison' => 'Circuit spécialisé obligatoire',
                'quantite' => 1
            ],
            [
                'code' => 'CABLE_CLIM',
                'designation' => 'Câble adapté à la puissance',
                'raison' => 'Section selon puissance et distance',
                'quantite' => 'selon installation'
            ],
            [
                'code' => 'SUPPORT_UE',
                'designation' => 'Support unité extérieure',
                'raison' => 'Fixation sécurisée',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'BAC_CONDENSATS',
                'designation' => 'Pompe de relevage condensats',
                'raison' => 'Si évacuation gravitaire impossible',
                'quantite' => 1
            ],
            [
                'code' => 'GOULOTTES',
                'designation' => 'Goulottes de protection liaisons',
                'raison' => 'Protection esthétique',
                'quantite' => 'selon parcours'
            ],
            [
                'code' => 'THERMOSTAT',
                'designation' => 'Thermostat programmable',
                'raison' => 'Optimisation consommation',
                'quantite' => 1
            ],
        ],
        'certifications' => [
            [
                'nom' => 'Attestation de capacité',
                'obligatoire_si' => 'Toute manipulation de fluide frigorigène',
                'delivree_par' => 'Organisme certifié',
                'description' => 'Obligatoire pour manipuler les fluides',
            ],
            [
                'nom' => 'QualiPAC',
                'obligatoire_si' => 'Demande d\'aides (CEE, MaPrimeRénov\')',
                'delivree_par' => 'Qualit\'EnR',
                'description' => 'RGE pour pompes à chaleur',
            ],
        ],
        'points_controle' => [
            'Unité extérieure : distance murs et obstacles',
            'Évacuation condensats conforme',
            'Liaison frigorifique étanche (test azote)',
            'Tirage au vide avant mise en gaz',
            'Distance maximum unités intérieure/extérieure',
            'Niveau sonore unité extérieure (voisinage)',
            'Déclaration préalable si façade ou toit',
        ],
        'temps_estime' => [
            'monosplit' => [
                'duree' => '4-5h',
                'description' => 'Installation climatisation monosplit'
            ],
            'multisplit' => [
                'duree' => '1-2 jours',
                'description' => 'Installation multisplit 3-4 unités'
            ],
            'gainable' => [
                'duree' => '2-3 jours',
                'description' => 'Installation climatisation gainable'
            ],
            'pac_air_eau' => [
                'duree' => '2-4 jours',
                'description' => 'Installation PAC air/eau'
            ],
            'facteurs_variation' => [
                'Type de système (split, gainable)',
                'Nombre d\'unités intérieures',
                'Longueur des liaisons',
                'Accessibilité pour l\'unité extérieure',
            ],
        ],
        'outils_necessaires' => [
            'Pompe à vide',
            'Manomètre froid',
            'Détecteur de fuite',
            'Dudgeonnière',
            'Coupe-tube cuivre',
            'Clé dynamométrique',
            'Perceuse/carotteuse',
            'Niveau à bulle',
            'Multimètre',
        ],
        'ordre_travaux' => [
            '1. Repérage emplacements (intérieur/extérieur)',
            '2. Percement traversée de mur',
            '3. Pose du support unité extérieure',
            '4. Fixation de l\'unité intérieure',
            '5. Passage des liaisons frigorifiques',
            '6. Passage de l\'alimentation électrique',
            '7. Raccordement des liaisons',
            '8. Tirage au vide du circuit',
            '9. Mise en gaz et test d\'étanchéité',
            '10. Mise en service et paramétrage',
        ],
        'erreurs_courantes' => [
            'Mauvais tirage au vide (humidité dans circuit)',
            'Liaison frigorifique mal dudgeonnée',
            'Pente évacuation condensats insuffisante',
            'Unité extérieure mal ventilée',
            'Longueur liaison excessive',
            'Section câble électrique insuffisante',
            'Oubli déclaration travaux',
        ],
        'tva_applicable' => [
            'taux' => 5.5,
            'condition' => 'PAC en remplacement chauffage sur logement > 2 ans',
        ],
        'aides_disponibles' => [
            'MaPrimeRénov\'' => 'PAC air/eau ou géothermique',
            'CEE' => 'Certificats d\'économie d\'énergie',
            'Éco-PTZ' => 'Prêt à taux zéro',
        ],
    ],

    /**
     * =====================================================
     * CHAUFFE-EAU / BALLON
     * =====================================================
     */
    'chauffe_eau' => [
        'nom' => 'Installation chauffe-eau',
        'categorie' => 'plomberie',
        'mots_cles' => [
            'chauffe eau', 'ballon', 'cumulus', 'eau chaude',
            'thermodynamique', 'solaire', 'electrique', 'gaz',
            'instantane', 'groupe securite'
        ],
        'normes' => [
            'NF C 15-100 §10.1.3.5' => 'Circuit chauffe-eau électrique',
            'DTU 60.1' => 'Plomberie sanitaire',
            'NF EN 12897' => 'Exigences pour les chauffe-eau',
            'DTU 65.11' => 'Chauffe-eau solaires (CESI)',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'GROUPE_SECURITE',
                'designation' => 'Groupe de sécurité NF',
                'raison' => 'Protection surpression obligatoire',
                'quantite' => 1
            ],
            [
                'code' => 'DJ_20A',
                'designation' => 'Disjoncteur 20A dédié',
                'raison' => 'Protection circuit chauffe-eau',
                'quantite' => 1
            ],
            [
                'code' => 'SIPHON_GROUPE',
                'designation' => 'Siphon pour groupe de sécurité',
                'raison' => 'Évacuation eau expansion',
                'quantite' => 1
            ],
            [
                'code' => 'VASE_EXPANSION',
                'designation' => 'Vase d\'expansion sanitaire',
                'raison' => 'Réduction pertes eau (recommandé)',
                'quantite' => 1
            ],
        ],
        'equipements_recommandes' => [
            [
                'code' => 'CONTACTEUR_HC',
                'designation' => 'Contacteur heures creuses',
                'raison' => 'Économie sur tarif HC',
                'quantite' => 1
            ],
            [
                'code' => 'LIMITEUR_TEMP',
                'designation' => 'Mitigeur thermostatique',
                'raison' => 'Anti-brûlure ECS (obligatoire ERP)',
                'quantite' => 1
            ],
            [
                'code' => 'CALORIFUGE',
                'designation' => 'Calorifugeage canalisations',
                'raison' => 'Réduction pertes thermiques',
                'quantite' => 'selon longueur'
            ],
        ],
        'certifications' => [
            [
                'nom' => 'QualiSol',
                'obligatoire_si' => 'Chauffe-eau solaire avec aides',
                'delivree_par' => 'Qualit\'EnR',
                'description' => 'RGE solaire thermique',
            ],
        ],
        'points_controle' => [
            'Groupe de sécurité accessible et visible',
            'Évacuation groupe vers canalisation ou récupérateur',
            'Température de consigne 55-60°C (légionelles)',
            'Fixation murale adaptée au poids',
            'Circuit électrique dédié',
            'Accessibilité pour maintenance',
            'Mitigeur ECS si ERP ou logement collectif',
        ],
        'temps_estime' => [
            'remplacement_simple' => [
                'duree' => '2-3h',
                'description' => 'Remplacement chauffe-eau identique'
            ],
            'installation_neuve' => [
                'duree' => '4-5h',
                'description' => 'Installation complète avec création circuits'
            ],
            'thermodynamique' => [
                'duree' => '1 journée',
                'description' => 'Installation chauffe-eau thermodynamique'
            ],
            'solaire' => [
                'duree' => '2-3 jours',
                'description' => 'Installation chauffe-eau solaire individuel'
            ],
            'facteurs_variation' => [
                'Remplacement ou création',
                'Accessibilité de l\'emplacement',
                'Modification des réseaux',
                'Type de chauffe-eau',
            ],
        ],
        'outils_necessaires' => [
            'Clé à molette',
            'Clé à chaine (gros écrous)',
            'Niveau à bulle',
            'Perceuse/perforateur',
            'Multimètre',
            'Pompe de vidange',
            'Ruban téflon',
            'Filasse/pâte à joint',
        ],
        'ordre_travaux' => [
            '1. Coupure eau et électricité',
            '2. Vidange du chauffe-eau existant',
            '3. Dépose de l\'ancien appareil',
            '4. Fixation du nouveau support/appareil',
            '5. Raccordement eau froide avec groupe sécurité',
            '6. Raccordement eau chaude',
            '7. Installation siphon et évacuation',
            '8. Raccordement électrique',
            '9. Mise en eau et purge',
            '10. Test et réglage thermostat',
        ],
        'erreurs_courantes' => [
            'Groupe de sécurité mal orienté',
            'Évacuation groupe non conforme',
            'Pas de vase d\'expansion (pertes d\'eau)',
            'Thermostat trop bas (< 55°C, risque légionelles)',
            'Fixation murale insuffisante',
            'Circuit électrique non dédié',
            'Oubli du siphon d\'évacuation',
        ],
        'tva_applicable' => [
            'taux' => 5.5,
            'condition' => 'Chauffe-eau thermodynamique ou solaire > 2 ans',
        ],
        'aides_disponibles' => [
            'MaPrimeRénov\'' => 'Chauffe-eau solaire ou thermodynamique',
            'CEE' => 'Certificats d\'économie d\'énergie',
        ],
    ],

    /**
     * =====================================================
     * CONTRÔLE D'ACCÈS
     * =====================================================
     */
    'controle_acces' => [
        'nom' => 'Installation contrôle d\'accès',
        'categorie' => 'electricite',
        'mots_cles' => [
            'digicode', 'interphone', 'visiophone', 'portier',
            'badge', 'gache', 'ventouse', 'controle acces',
            'lecteur badge', 'biometrie', 'clavier code'
        ],
        'normes' => [
            'NF C 15-100 §10.1.3.10' => 'Circuits commande',
            'NF S 61-934' => 'Systèmes de sécurité incendie (SSI) si ERP',
            'Code du travail' => 'CNIL si biométrie',
        ],
        'equipements_obligatoires' => [
            [
                'code' => 'ALIM_12V',
                'designation' => 'Alimentation 12V ou 24V stabilisée',
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
            [
                'code' => 'VENTOUSE',
                'designation' => 'Ventouse électromagnétique',
                'raison' => 'Alternative à la gâche',
                'quantite' => 1
            ],
            [
                'code' => 'LECTEUR_BADGE',
                'designation' => 'Lecteur de badges RFID',
                'raison' => 'Accès par badge',
                'quantite' => 1
            ],
            [
                'code' => 'BATTERIE_SECOURS',
                'designation' => 'Batterie de secours',
                'raison' => 'Fonctionnement en cas de coupure',
                'quantite' => 1
            ],
        ],
        'certifications' => [
            [
                'nom' => 'Déclaration CNIL',
                'obligatoire_si' => 'Système biométrique ou vidéo',
                'delivree_par' => 'Auto-déclaration',
                'description' => 'Protection des données personnelles',
            ],
        ],
        'points_controle' => [
            'Gâche à rupture obligatoire sur issues de secours',
            'Bouton poussoir de sortie côté intérieur',
            'Alimentation secourue si accès sécurisé',
            'Passage câble en gaine séparée du 230V',
            'Hauteur lecteur : 1.10m du sol',
            'Conformité RGPD si données personnelles',
            'Test d\'ouverture en cas de coupure',
        ],
        'temps_estime' => [
            'digicode_simple' => [
                'duree' => '2-3h',
                'description' => 'Installation digicode avec gâche'
            ],
            'controle_acces' => [
                'duree' => '4-6h',
                'description' => 'Système complet avec lecteur badges'
            ],
            'systeme_multi_portes' => [
                'duree' => '1-2 jours',
                'description' => 'Installation multi-accès avec centrale'
            ],
            'facteurs_variation' => [
                'Nombre de points d\'accès',
                'Type de verrouillage (gâche, ventouse)',
                'Câblage à créer ou existant',
                'Intégration vidéophonie',
            ],
        ],
        'outils_necessaires' => [
            'Perceuse/perforateur',
            'Niveau à bulle',
            'Tournevis',
            'Multimètre',
            'Pince à dénuder',
            'Programmateur badges',
            'Smartphone (paramétrage)',
            'EPI',
        ],
        'ordre_travaux' => [
            '1. Étude des flux et des accès',
            '2. Choix du type de verrouillage',
            '3. Passage des câbles basse tension',
            '4. Installation de l\'alimentation',
            '5. Pose de la gâche ou ventouse',
            '6. Installation du lecteur/clavier',
            '7. Câblage et raccordement',
            '8. Programmation des codes/badges',
            '9. Test de fonctionnement',
            '10. Formation des utilisateurs',
        ],
        'erreurs_courantes' => [
            'Gâche à émission sur issue de secours',
            'Pas de bouton de sortie intérieur',
            'Alimentation non secourue',
            'Câblage mélangé avec courant fort',
            'Lecteur accessible depuis l\'extérieur',
            'Oubli déclaration CNIL',
        ],
        'tva_applicable' => [
            'taux' => 10,
            'condition' => 'Installation sur logement > 2 ans',
        ],
    ],
];
