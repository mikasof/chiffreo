<?php

/**
 * PROMPT V2 - Système multi-agents délégué à OpenAI
 *
 * Approche : On donne les instructions de processus à OpenAI,
 * il gère tout en interne avec ses connaissances métier.
 * On injecte uniquement les paramètres business de l'utilisateur.
 */

return [
    /**
     * Prompt système - Instructions de processus
     */
    'system_prompt' => <<<'PROMPT'
Tu es un CHEF DE PROJET ÉLECTRICIEN avec 20 ans d'expérience en France.

## TA MISSION
Générer un devis complet, précis et professionnel à partir de la demande client.

## TON ÉQUIPE D'AGENTS (consulte mentalement chaque agent dans l'ordre)

### 1. AGENT ANALYSE
Tu dois comprendre parfaitement la demande :
- Quel est le TYPE de travaux ? (installation neuve, rénovation, dépannage, mise aux normes, extension)
- Quel est le DOMAINE ? (tableau électrique, prises/éclairage, borne IRVE, VMC, chauffage, domotique...)
- Quelles informations le client a-t-il données ? (surface, nb pièces, marques souhaitées, contraintes)
- Quelles informations manquent et doivent être estimées ?

### 2. AGENT CONFORMITÉ
⚠️ TRÈS IMPORTANT : Tu dois respecter scrupuleusement les normes électriques françaises en vigueur.
- Applique la NF C 15-100 et ses dernières évolutions
- Intègre TOUS les équipements de protection OBLIGATOIRES (différentiels, disjoncteurs adaptés)
- Respecte les sections de câbles réglementaires selon les circuits
- Identifie les certifications nécessaires (qualification IRVE si borne > 3.7kW, RGE si aides, Consuel si neuf)
- Ne fais AUCUN compromis sur la sécurité

### 3. AGENT MAÎTRE D'ŒUVRE
Liste ABSOLUMENT TOUT le matériel nécessaire. Un devis incomplet = client mécontent = mauvaise réputation.
- Équipements principaux (tableau, appareillage, luminaires...)
- Câblage COMPLET : calcule les métrages réalistes (distance + 20% de marge)
- Protections tableau : différentiels, disjoncteurs adaptés à chaque circuit
- Gaines et chemins de câbles
- Consommables : boîtes de dérivation, Wago, dominos, chevilles, attaches, borniers
- Petit matériel souvent oublié

### 4. AGENT TEMPS DE TRAVAIL
Pour CHAQUE type de tâche, estime le temps de main d'œuvre réaliste :
- Préparation et repérage
- Pose d'appareillage (prises, interrupteurs, luminaires)
- Tirage de câbles et passage de gaines
- Saignées si nécessaire
- Raccordements au tableau
- Tests, vérifications et mise en service
- Nettoyage du chantier

Base-toi sur un électricien professionnel expérimenté, pas un débutant.

### 5. AGENT CHIFFRAGE
Pour CHAQUE fourniture, tu DOIS spécifier précisément :
- **Marque** : le fabricant exact (Schneider Electric, Legrand, Hager, ABB, Gewiss, Siemens...)
- **Gamme** : la ligne de produit (Acti9, Odace, DX³, Céliane, Kallysta...)
- **Référence** : le code produit EXACT du fabricant (ex: A9F74616, 406774, MFN716)
- **Désignation** : description technique complète

⚠️ STRICTEMENT INTERDIT :
- "Disjoncteur 16A" sans marque ni référence
- "Câble électrique" sans section ni type
- "Prise de courant" sans gamme

✅ EXEMPLES CORRECTS :
- "Disjoncteur 16A courbe C Ph+N - Schneider Acti9 iC60N - A9F77616"
- "Câble R2V 3G2.5mm² - Nexans - 01019024"
- "Prise 2P+T 16A - Legrand Céliane Blanc - 067111"
- "Interrupteur différentiel 40A 30mA Type A - Hager - CDA743F"

**CALCUL DES PRIX :**
1. Estime le prix PUBLIC HT du produit (tarif catalogue France 2024)
2. Applique -30% (remise fournisseur professionnelle standard)
3. Ajoute la marge de l'entreprise (fournie dans les paramètres)
= Prix de vente HT final

### 6. AGENT TVA
Tu DOIS appliquer la réglementation française en vigueur sur la TVA travaux.

**Utilise tes connaissances sur la réglementation TVA française :**
- Recherche dans tes connaissances les règles exactes du Code Général des Impôts (CGI art. 279-0 bis)
- Les conditions d'application de la TVA à taux réduit (10% et 5.5%)
- Les critères : ancienneté du logement, nature des travaux, usage du local

**En cas de doute sur l'éligibilité à un taux réduit :**
- Applique le taux normal (20%)
- Mentionne dans "remarques_tva" les conditions qui permettraient un taux réduit
- Ajoute une question à poser au client pour vérifier l'éligibilité

**Dans "raison_tva", explique précisément :**
- Le taux choisi et pourquoi
- Les conditions réglementaires qui s'appliquent
- Si des justificatifs seront nécessaires (attestation simplifiée, etc.)

### 7. AGENT CALCUL DES TOTAUX
⚠️ TU DOIS CALCULER PRÉCISÉMENT les totaux :

1. **total_fournitures_ht** = somme de tous les total_ligne_ht des fournitures
2. **total_main_oeuvre_ht** = somme de tous les total_ligne_ht de la main d'œuvre
3. **total_deplacement_ht** = montant du déplacement
4. **total_ht** = total_fournitures_ht + total_main_oeuvre_ht + total_deplacement_ht
5. **tva_montant** = total_ht × (tva_taux / 100)
6. **total_ttc** = total_ht + tva_montant

**VÉRIFIE TES CALCULS** : les totaux doivent être corrects et cohérents.
Si total_ht = 1500€ et tva_taux = 10%, alors tva_montant = 150€ et total_ttc = 1650€.

### 8. AGENT VÉRIFICATION (TOI, LE CHEF)
Avant de finaliser :
- Les totaux sont-ils bien calculés ? (refais le calcul mentalement)
- Le total est-il cohérent pour ce type de travaux ? (vérifie les ordres de grandeur)
- Rien n'a été oublié ?
- Les quantités sont-elles réalistes ?
- Les prix sont-ils conformes au marché ?
- Le devis est-il complet et professionnel ?

---

## RÈGLES IMPORTANTES

1. **Sois EXHAUSTIF** : mieux vaut trop que pas assez. Le client préfère un devis complet.

2. **Sois PRÉCIS** : chaque ligne doit avoir marque + référence + prix détaillé.

3. **Sois RÉALISTE** :
   - Une prise complète (pose + câblage) = environ 1h de MO
   - Un point lumineux complet = environ 1h à 1h30
   - Compte 4-5m de câble par point en rénovation

4. **N'invente pas** : si une info manque, fais une hypothèse raisonnable ET ajoute une question à poser.

5. **Déplacement** : inclus TOUJOURS le déplacement selon les paramètres fournis.

6. **Main d'œuvre** : DÉTAILLE chaque type de travail séparément :
   - Tirage de câbles (Xh)
   - Pose appareillage (Xh)
   - Raccordements tableau (Xh)
   - Tests et mise en service (Xh)
   - etc.

⚠️ **RÈGLE ABSOLUE - INTERDICTION DES FORFAITS GLOBAUX** ⚠️

❌ STRICTEMENT INTERDIT :
- "Forfait rénovation complète"
- "Forfait installation électrique"
- "Forfait travaux au m²"
- Tout forfait global qui masque le détail

✅ TU DOIS OBLIGATOIREMENT :
- Lister CHAQUE produit individuellement (câbles, disjoncteurs, prises, interrupteurs...)
- Détailler CHAQUE type de main d'œuvre séparément
- Si travaux de gros œuvre nécessaires, ajouter des lignes spécifiques :
  - "Saignées murales" avec quantité en mètres linéaires
  - "Rebouchage saignées" avec quantité en mètres linéaires
  - "Tranchées extérieures" si passage enterré
  - "Percements béton/parpaing" si nécessaire
- Chaque ligne = 1 produit ou 1 type de travail identifiable

Le client DOIT pouvoir vérifier chaque élément du devis. Un forfait global = REFUSÉ.
PROMPT,

    /**
     * Template du prompt utilisateur
     * Variables : {parametres}, {transcription}, {images_context}
     */
    'user_prompt_template' => <<<'PROMPT'
## PARAMÈTRES DE L'ENTREPRISE

{parametres}

---

## DEMANDE CLIENT

{transcription}

{images_context}

---

## TA RÉPONSE

Exécute ton processus d'analyse avec ton équipe d'agents.
Puis retourne UNIQUEMENT le JSON du devis, sans aucun texte avant ou après.
PROMPT,

    /**
     * JSON Schema pour la réponse structurée
     */
    'json_schema' => [
        'name' => 'devis_electrique_v2',
        'strict' => true,
        'schema' => [
            'type' => 'object',
            'properties' => [
                'chantier' => [
                    'type' => 'object',
                    'properties' => [
                        'titre' => [
                            'type' => 'string',
                            'description' => 'Titre court du chantier'
                        ],
                        'type_travaux' => [
                            'type' => 'string',
                            'enum' => ['installation', 'renovation', 'depannage', 'mise_aux_normes', 'extension'],
                            'description' => 'Type de travaux identifié'
                        ],
                        'perimetre' => [
                            'type' => 'string',
                            'description' => 'Description du périmètre des travaux'
                        ],
                        'hypotheses' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Hypothèses prises pour établir le devis'
                        ],
                        'normes_appliquees' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Normes NF C 15-100 et autres appliquées'
                        ],
                        'certifications_requises' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Certifications nécessaires (IRVE, RGE, Consuel...)'
                        ]
                    ],
                    'required' => ['titre', 'type_travaux', 'perimetre', 'hypotheses', 'normes_appliquees', 'certifications_requises'],
                    'additionalProperties' => false
                ],
                'fournitures' => [
                    'type' => 'array',
                    'description' => 'Liste des fournitures avec marque et référence',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'designation' => [
                                'type' => 'string',
                                'description' => 'Description technique complète'
                            ],
                            'marque' => [
                                'type' => 'string',
                                'description' => 'Fabricant (Schneider, Legrand, Hager...)'
                            ],
                            'gamme' => [
                                'type' => ['string', 'null'],
                                'description' => 'Ligne de produit (Acti9, Céliane, Odace...)'
                            ],
                            'reference' => [
                                'type' => 'string',
                                'description' => 'Code produit fabricant'
                            ],
                            'quantite' => [
                                'type' => 'number',
                                'description' => 'Quantité'
                            ],
                            'unite' => [
                                'type' => 'string',
                                'description' => 'Unité (u, m, ml, lot)'
                            ],
                            'prix_public_ht' => [
                                'type' => 'number',
                                'description' => 'Prix catalogue public HT'
                            ],
                            'prix_achat_ht' => [
                                'type' => 'number',
                                'description' => 'Prix achat pro (-30%)'
                            ],
                            'prix_vente_unitaire_ht' => [
                                'type' => 'number',
                                'description' => 'Prix de vente unitaire HT (avec marge)'
                            ],
                            'total_ligne_ht' => [
                                'type' => 'number',
                                'description' => 'Total ligne HT'
                            ]
                        ],
                        'required' => ['designation', 'marque', 'gamme', 'reference', 'quantite', 'unite', 'prix_public_ht', 'prix_achat_ht', 'prix_vente_unitaire_ht', 'total_ligne_ht'],
                        'additionalProperties' => false
                    ]
                ],
                'main_oeuvre' => [
                    'type' => 'array',
                    'description' => 'Détail de la main d\'oeuvre',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'designation' => [
                                'type' => 'string',
                                'description' => 'Description du travail'
                            ],
                            'heures' => [
                                'type' => 'number',
                                'description' => 'Nombre d\'heures'
                            ],
                            'taux_horaire' => [
                                'type' => 'number',
                                'description' => 'Taux horaire HT'
                            ],
                            'total_ligne_ht' => [
                                'type' => 'number',
                                'description' => 'Total ligne HT'
                            ]
                        ],
                        'required' => ['designation', 'heures', 'taux_horaire', 'total_ligne_ht'],
                        'additionalProperties' => false
                    ]
                ],
                'deplacement' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => ['gratuit', 'forfait', 'km'],
                            'description' => 'Type de facturation déplacement'
                        ],
                        'montant_ht' => [
                            'type' => 'number',
                            'description' => 'Montant HT du déplacement'
                        ],
                        'detail' => [
                            'type' => ['string', 'null'],
                            'description' => 'Détail (ex: 25km x 0.50€)'
                        ]
                    ],
                    'required' => ['type', 'montant_ht', 'detail'],
                    'additionalProperties' => false
                ],
                'totaux' => [
                    'type' => 'object',
                    'properties' => [
                        'total_fournitures_ht' => ['type' => 'number'],
                        'total_main_oeuvre_ht' => ['type' => 'number'],
                        'total_deplacement_ht' => ['type' => 'number'],
                        'total_ht' => ['type' => 'number'],
                        'tva_taux' => ['type' => 'number'],
                        'tva_montant' => ['type' => 'number'],
                        'total_ttc' => ['type' => 'number']
                    ],
                    'required' => ['total_fournitures_ht', 'total_main_oeuvre_ht', 'total_deplacement_ht', 'total_ht', 'tva_taux', 'tva_montant', 'total_ttc'],
                    'additionalProperties' => false
                ],
                'parametres_appliques' => [
                    'type' => 'object',
                    'description' => 'Transparence sur les paramètres utilisés pour ce devis',
                    'properties' => [
                        'taux_horaire_utilise' => [
                            'type' => 'number',
                            'description' => 'Taux horaire MO appliqué'
                        ],
                        'marge_fournitures_pourcent' => [
                            'type' => 'number',
                            'description' => 'Marge appliquée sur les fournitures en %'
                        ],
                        'raison_tva' => [
                            'type' => 'string',
                            'description' => 'Explication du taux de TVA choisi'
                        ]
                    ],
                    'required' => ['taux_horaire_utilise', 'marge_fournitures_pourcent', 'raison_tva'],
                    'additionalProperties' => false
                ],
                'questions_a_poser' => [
                    'type' => 'array',
                    'description' => 'Questions pour préciser le devis',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'question' => ['type' => 'string'],
                            'impact' => ['type' => 'string'],
                            'priorite' => [
                                'type' => 'string',
                                'enum' => ['haute', 'moyenne', 'basse']
                            ]
                        ],
                        'required' => ['question', 'impact', 'priorite'],
                        'additionalProperties' => false
                    ]
                ],
                'exclusions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Ce qui n\'est PAS inclus dans le devis'
                ],
                'remarques_tva' => [
                    'type' => ['string', 'null'],
                    'description' => 'Remarques sur la TVA applicable'
                ],
                'duree_estimee' => [
                    'type' => 'string',
                    'description' => 'Durée estimée du chantier (ex: 2 jours, 1 semaine)'
                ],
                'notes_internes' => [
                    'type' => ['string', 'null'],
                    'description' => 'Notes pour l\'électricien (non visibles client)'
                ]
            ],
            'required' => [
                'chantier',
                'fournitures',
                'main_oeuvre',
                'deplacement',
                'totaux',
                'parametres_appliques',
                'questions_a_poser',
                'exclusions',
                'remarques_tva',
                'duree_estimee',
                'notes_internes'
            ],
            'additionalProperties' => false
        ]
    ],

    /**
     * Fonction pour formater les paramètres utilisateur
     */
    'format_parametres' => function(array $user): string {
        // Taux horaire
        $tauxHoraire = $user['hourly_rate'] ?? 45;

        // Marge sur fournitures
        $marge = $user['product_margin'] ?? 20;

        // Déplacement
        $deplacementType = $user['travel_type'] ?? 'fixed';
        $deplacement = match($deplacementType) {
            'free' => "Déplacement GRATUIT dans un rayon de " . ($user['travel_free_radius'] ?? 20) . " km",
            'fixed' => "Déplacement forfaitaire : " . ($user['travel_fixed_amount'] ?? 30) . "€ HT",
            'per_km' => "Déplacement au km : " . ($user['travel_per_km'] ?? 0.50) . "€/km",
            default => "Déplacement forfaitaire : 30€ HT"
        };

        // Marque préférée
        $marque = $user['preferred_brand'] ?? 'Schneider Electric';

        return <<<PARAMS
- **Taux horaire main d'œuvre** : {$tauxHoraire}€ HT/heure
- **Marge sur fournitures** : {$marge}% (à appliquer sur le prix d'achat)
- **{$deplacement}**
- **Marque préférée** (si pas de demande client spécifique) : {$marque}

**Rappel calcul prix de vente fourniture :**
Prix vente = Prix public × 0.70 × (1 + {$marge}/100)
PARAMS;
    }
];
