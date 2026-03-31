<?php
/**
 * Règles TVA pour travaux électriques en France
 *
 * Basé sur :
 * - Article 279-0 bis du CGI (TVA 10%)
 * - Article 278-0 bis A du CGI (TVA 5.5%)
 * - Bulletin Officiel des Finances Publiques BOI-TVA-LIQ-30-20-90
 *
 * @see https://www.legifrance.gouv.fr
 */

return [
    /**
     * Taux de TVA disponibles
     */
    'rates' => [
        'standard' => 20.0,      // Taux normal
        'intermediaire' => 10.0, // Travaux de rénovation
        'reduit' => 5.5,         // Travaux d'économie d'énergie
    ],

    /**
     * Conditions pour TVA 10% (intermédiaire)
     * Travaux d'amélioration, transformation, aménagement, entretien
     * sur locaux à usage d'habitation achevés depuis plus de 2 ans
     */
    'conditions_tva_10' => [
        'anciennete_minimum' => 2, // années
        'usage' => ['habitation', 'residentiel', 'logement', 'maison', 'appartement'],
        'travaux_eligibles' => [
            'renovation',
            'remplacement',
            'mise aux normes',
            'extension tableau',
            'ajout prises',
            'ajout points lumineux',
            'remplacement interrupteurs',
            'tirage cables',
            'pose goulotte',
            'controle acces', // Digicode, interphone sur logement existant
        ],
    ],

    /**
     * Conditions pour TVA 5.5% (réduit)
     * Travaux d'amélioration de la qualité énergétique
     * Article 278-0 bis A du CGI
     */
    'conditions_tva_5_5' => [
        'anciennete_minimum' => 2, // années
        'usage' => ['habitation', 'residentiel', 'logement', 'maison', 'appartement'],
        'travaux_eligibles' => [
            // Chauffage performant
            // Chauffage performant
            'pompe a chaleur',
            'pac',
            'chauffe-eau thermodynamique',
            'ballon thermodynamique',
            'chaudiere condensation',
            'radiateur performant',
            'radiateur a inertie',
            'radiateur inertie',
            'plancher chauffant',
            'chauffage au sol',

            // Régulation / Domotique énergétique
            'thermostat programmable',
            'thermostat connecte',
            'thermostat intelligent',
            'regulation chauffage',
            'robinets thermostatiques',
            'gestionnaire energie',
            'delesteur',
            'programmateur chauffage',
            'fil pilote',

            // Production énergie renouvelable
            'panneaux solaires',
            'panneau solaire',
            'photovoltaique',
            'eolienne',
            'autoconsommation',

            // Mobilité électrique (éligible TVA 5.5% depuis 2021)
            'borne de recharge',
            'borne recharge',
            'borne electrique',
            'recharge vehicule',
            'recharge voiture',
            'wallbox',
            'irve',
            'prise renforcee',
            'green up',
            'greenup',
            'recharge ve',
            'vehicule electrique',
            'voiture electrique',

            // VMC performante
            'vmc double flux',
            'vmc hygroreglable',
            'vmc hygro',
            'ventilation performante',
            'ventilation double flux',

            // Isolation (partie électrique uniquement)
            'spot isolation',
            'spot encastre isolant',

            // Éclairage performant (cas spécifiques)
            'detecteur presence',
            'eclairage led basse consommation',
        ],

        // Équipements éligibles avec codes prix (détection automatique)
        'equipements_eligibles' => [
            // Eau chaude thermodynamique
            'BALLON_THERMO_200L',

            // Ventilation performante
            'VMC_HYGRO',

            // Bornes de recharge VE (éligibles TVA 5.5% depuis 2021)
            'BORNE_RECHARGE_7KW',
            'BORNE_RECHARGE_11KW',
            'BORNE_RECHARGE_22KW',
            'WALLBOX',
            'PRISE_RENFORCEE',
            'FORFAIT_POSE_BORNE',
            'PROTECTION_BORNE',
        ],
    ],

    /**
     * Exclusions TVA réduite (toujours 20%)
     */
    'exclusions_tva_reduite' => [
        'construction neuve',
        'batiment neuf',
        'maison neuve',
        'appartement neuf',
        'logement neuf',
        'immeuble neuf',
        'programme neuf',
        'moins de 2 ans',
        'local commercial',
        'bureau',
        'entrepot',
        'industriel',
    ],

    /**
     * Mots-clés pour détection automatique du type de bâtiment
     */
    'detection_batiment' => [
        'habitation' => [
            'maison', 'appartement', 'logement', 'habitation', 'residence',
            'pavillon', 'villa', 'studio', 'duplex', 'triplex', 'loft',
            'chambre', 'salon', 'sejour', 'cuisine', 'salle de bain', 'sdb',
            'wc', 'toilettes', 'garage attenant', 'cave', 'grenier'
        ],
        'professionnel' => [
            'bureau', 'commerce', 'boutique', 'magasin', 'restaurant',
            'hotel', 'entrepot', 'usine', 'atelier', 'local commercial',
            'local professionnel', 'erp', 'etablissement'
        ],
    ],

    /**
     * Questions à poser si l'information manque
     */
    'questions_tva' => [
        'anciennete' => [
            'question' => 'Le bâtiment a-t-il été achevé depuis plus de 2 ans ?',
            'impact' => 'Détermine si TVA réduite (10% ou 5.5%) applicable au lieu de 20%',
            'priorite' => 'haute',
        ],
        'usage' => [
            'question' => 'Le local est-il à usage d\'habitation (résidence principale ou secondaire) ?',
            'impact' => 'TVA réduite uniquement pour les locaux d\'habitation',
            'priorite' => 'haute',
        ],
    ],

    /**
     * Attestations requises
     */
    'attestations' => [
        'tva_10' => [
            'nom' => 'Attestation simplifiée TVA 10%',
            'cerfa' => '1301-SD',
            'obligatoire' => true,
            'seuil' => 300, // euros HT
        ],
        'tva_5_5' => [
            'nom' => 'Attestation normale TVA 5.5%',
            'cerfa' => '1300-SD',
            'obligatoire' => true,
            'seuil' => 0, // toujours obligatoire
        ],
    ],

    /**
     * Messages explicatifs pour le devis
     */
    'messages' => [
        '20' => null, // Pas de message pour TVA standard
        '10' => 'TVA 10% applicable conformément à l\'article 279-0 bis du CGI (travaux de rénovation sur logement > 2 ans). Attestation simplifiée à fournir.',
        '5.5' => 'TVA 5.5% applicable conformément à l\'article 278-0 bis A du CGI (travaux d\'amélioration énergétique sur logement > 2 ans). Attestation à fournir.',
    ],
];
