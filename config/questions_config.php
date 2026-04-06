<?php

/**
 * Configuration des questions contextuelles par type de travaux
 *
 * CRITÈRES DE SÉLECTION :
 * - Chaque question doit influencer les TRAVAUX (lignes du devis) ou le COÛT (TVA, quantités)
 * - Les infos déjà dans la description ne sont pas redemandées
 * - L'électricien répond, pas le client final
 */

return [
    'questions_par_type' => [

        // === IRVE (Borne de recharge véhicule électrique) ===
        'irve' => [
            [
                'id' => 'anciennete_logement',
                'question' => 'Le logement a-t-il plus de 2 ans ?',
                'type' => 'oui_non',
                'impact' => 'TVA 10% au lieu de 20%',
                'priorite' => 1
            ],
            [
                'id' => 'parking_type',
                'question' => 'Où sera installée la borne ?',
                'type' => 'choix',
                'options' => ['Garage privatif', 'Sous auvent / carport', 'Parking extérieur', 'Parking souterrain copropriété', 'Place de parking extérieure copropriété'],
                'impact' => 'Type de câble (intérieur/extérieur), protection IP, complexité pose',
                'priorite' => 2,
                'sous_question_si' => [
                    'Parking souterrain copropriété' => [
                        'id' => 'tableau_accessible',
                        'question' => 'Le tableau électrique est-il accessible depuis le parking ?',
                        'type' => 'oui_non',
                        'impact' => 'Si non : tirage câble depuis appartement (longueur ++)',
                        'priorite' => 2
                    ]
                ]
            ],
            [
                'id' => 'distance_tableau',
                'question' => 'Distance approximative entre le tableau et l\'emplacement de la borne ?',
                'type' => 'choix',
                'options' => ['Moins de 10m', '10 à 25m', '25 à 50m', 'Plus de 50m'],
                'impact' => 'Quantité de câble, section selon longueur',
                'priorite' => 3
            ]
        ],

        // === Tableau électrique ===
        'tableau' => [
            [
                'id' => 'anciennete_logement',
                'question' => 'Le logement a-t-il plus de 2 ans ?',
                'type' => 'oui_non',
                'impact' => 'TVA 10% au lieu de 20%',
                'priorite' => 1
            ],
            [
                'id' => 'nb_circuits',
                'question' => 'Nombre de circuits à prévoir (approximativement) ?',
                'type' => 'choix',
                'options' => ['Moins de 10', '10 à 15', '15 à 25', 'Plus de 25'],
                'impact' => 'Taille du tableau (1, 2, 3 ou 4 rangées), nombre de modules',
                'priorite' => 2
            ],
            [
                'id' => 'equipements_dedies',
                'question' => 'Équipements nécessitant un circuit dédié ?',
                'type' => 'choix_multiple',
                'options' => ['Plaque induction (32A)', 'Four électrique (20A)', 'Borne véhicule', 'Pompe à chaleur', 'Chauffe-eau', 'VMC', 'Aucun'],
                'impact' => 'Circuits dédiés, différentiels type A ou F, disjoncteurs spécifiques',
                'priorite' => 3
            ]
        ],

        // === Rénovation électrique complète ===
        'renovation' => [
            [
                'id' => 'anciennete_logement',
                'question' => 'Le logement a-t-il plus de 2 ans ?',
                'type' => 'oui_non',
                'impact' => 'TVA 10% au lieu de 20%',
                'priorite' => 1
            ],
            [
                'id' => 'surface',
                'question' => 'Surface approximative à rénover ?',
                'type' => 'choix',
                'options' => ['Moins de 50m²', '50 à 80m²', '80 à 120m²', '120 à 150m²', 'Plus de 150m²'],
                'impact' => 'Quantité de câble, nombre de prises selon norme NF C 15-100',
                'priorite' => 2
            ],
            [
                'id' => 'nb_pieces',
                'question' => 'Nombre de pièces ?',
                'type' => 'choix',
                'options' => ['Studio/T1', 'T2 (2 pièces)', 'T3 (3 pièces)', 'T4 (4 pièces)', 'T5 ou plus'],
                'impact' => 'Nombre de circuits par pièce, points lumineux',
                'priorite' => 3
            ],
            [
                'id' => 'type_pose',
                'question' => 'Type de pose prévu ?',
                'type' => 'choix',
                'options' => ['Encastré (murs ouverts)', 'Apparent / moulures', 'Mixte'],
                'impact' => 'Main d\'oeuvre : apparent = 1.5x à 2x plus long',
                'priorite' => 4
            ]
        ],

        // === Travaux généraux / non catégorisés ===
        'general' => [
            [
                'id' => 'anciennete_logement',
                'question' => 'Le logement/local a-t-il plus de 2 ans ?',
                'type' => 'oui_non',
                'impact' => 'TVA 10% au lieu de 20% (habitation uniquement)',
                'priorite' => 1
            ],
            [
                'id' => 'type_local',
                'question' => 'Type de local ?',
                'type' => 'choix',
                'options' => ['Appartement', 'Maison individuelle', 'Local commercial', 'Bureau', 'ERP'],
                'impact' => 'Normes applicables (NF C 15-100 vs ERP), TVA 20% si pro',
                'priorite' => 2
            ]
        ]
    ],

    // Mots-clés pour détecter le type de travaux
    'detection_keywords' => [
        'irve' => ['borne', 'recharge', 'véhicule électrique', 'voiture électrique', 'wallbox', 'prise renforcée', 'green up', 'tesla', 'ev', 'voiture', 'électrique'],
        'tableau' => ['tableau', 'coffret', 'disjoncteur', 'différentiel', 'GTL', 'TGBT', 'rangée', 'module', 'interrupteur différentiel'],
        'renovation' => ['rénovation', 'refaire', 'complète', 'mise aux normes', 'appartement complet', 'maison complète', 'électricité complète', 'tout refaire', 'réfection']
    ]
];
