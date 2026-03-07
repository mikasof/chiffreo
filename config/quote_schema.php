<?php

/**
 * AGENT "PROMPT & SCHEMA"
 * JSON Schema strict pour la génération de devis électrique
 * + Prompts système et utilisateur
 */

return [
    /**
     * JSON Schema strict pour OpenAI Structured Outputs
     * Ce schéma garantit une réponse valide et exploitable
     */
    'json_schema' => [
        'name' => 'devis_electrique',
        'strict' => true,
        'schema' => [
            'type' => 'object',
            'properties' => [
                'chantier' => [
                    'type' => 'object',
                    'description' => 'Informations générales sur le chantier',
                    'properties' => [
                        'titre' => [
                            'type' => 'string',
                            'description' => 'Titre court du chantier (ex: Installation digicode immeuble)'
                        ],
                        'localisation' => [
                            'type' => ['string', 'null'],
                            'description' => 'Adresse ou lieu si mentionné, sinon null'
                        ],
                        'perimetre' => [
                            'type' => 'string',
                            'description' => 'Description du périmètre des travaux'
                        ],
                        'hypotheses' => [
                            'type' => 'array',
                            'description' => 'Hypothèses prises pour établir le devis',
                            'items' => ['type' => 'string']
                        ]
                    ],
                    'required' => ['titre', 'localisation', 'perimetre', 'hypotheses'],
                    'additionalProperties' => false
                ],
                'taches' => [
                    'type' => 'array',
                    'description' => 'Liste ordonnée des tâches à réaliser',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'ordre' => [
                                'type' => 'integer',
                                'description' => 'Numéro d\'ordre de la tâche'
                            ],
                            'titre' => [
                                'type' => 'string',
                                'description' => 'Titre de la tâche'
                            ],
                            'details' => [
                                'type' => 'string',
                                'description' => 'Description détaillée de la tâche'
                            ],
                            'duree_estimee_h' => [
                                'type' => 'number',
                                'description' => 'Durée estimée en heures'
                            ],
                            'points_attention' => [
                                'type' => 'array',
                                'description' => 'Risques ou points d\'attention',
                                'items' => ['type' => 'string']
                            ]
                        ],
                        'required' => ['ordre', 'titre', 'details', 'duree_estimee_h', 'points_attention'],
                        'additionalProperties' => false
                    ]
                ],
                'lignes' => [
                    'type' => 'array',
                    'description' => 'Lignes du devis (matériel, main d\'oeuvre, forfaits)',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'designation' => [
                                'type' => 'string',
                                'description' => 'Description de la ligne'
                            ],
                            'marque' => [
                                'type' => ['string', 'null'],
                                'description' => 'Marque du produit si mentionnée (Legrand, Schneider, Hager, etc.) sinon null'
                            ],
                            'reference' => [
                                'type' => ['string', 'null'],
                                'description' => 'Référence produit si mentionnée (ex: 369220, A9F74206) sinon null'
                            ],
                            'categorie' => [
                                'type' => 'string',
                                'enum' => ['materiel', 'main_oeuvre', 'forfait'],
                                'description' => 'Catégorie de la ligne'
                            ],
                            'unite' => [
                                'type' => 'string',
                                'description' => 'Unité (u, m, h, forfait, lot)'
                            ],
                            'quantite' => [
                                'type' => 'number',
                                'description' => 'Quantité'
                            ],
                            'prix_ref_code' => [
                                'type' => 'string',
                                'description' => 'Code référence prix (MO_H, CABLE_3G25, DIGICODE_FIL, etc.) ou CUSTOM si hors catalogue'
                            ],
                            'prix_unitaire_ht_suggere' => [
                                'type' => ['number', 'null'],
                                'description' => 'Prix unitaire HT suggéré si CUSTOM, sinon null (le serveur utilisera la grille)'
                            ],
                            'commentaire' => [
                                'type' => ['string', 'null'],
                                'description' => 'Commentaire optionnel'
                            ]
                        ],
                        'required' => ['designation', 'marque', 'reference', 'categorie', 'unite', 'quantite', 'prix_ref_code', 'prix_unitaire_ht_suggere', 'commentaire'],
                        'additionalProperties' => false
                    ]
                ],
                'questions_a_poser' => [
                    'type' => 'array',
                    'description' => 'Questions à poser au client si informations manquantes',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'question' => [
                                'type' => 'string',
                                'description' => 'La question à poser'
                            ],
                            'impact' => [
                                'type' => 'string',
                                'description' => 'Impact sur le devis si la réponse change'
                            ],
                            'priorite' => [
                                'type' => 'string',
                                'enum' => ['haute', 'moyenne', 'basse'],
                                'description' => 'Priorité de la question'
                            ]
                        ],
                        'required' => ['question', 'impact', 'priorite'],
                        'additionalProperties' => false
                    ]
                ],
                'exclusions' => [
                    'type' => 'array',
                    'description' => 'Prestations explicitement exclues du devis',
                    'items' => ['type' => 'string']
                ],
                'taux_tva' => [
                    'type' => 'number',
                    'description' => 'Taux de TVA applicable (20 par défaut)'
                ],
                'remarque_tva' => [
                    'type' => ['string', 'null'],
                    'description' => 'Remarque si TVA réduite potentiellement applicable mais non appliquée'
                ],
                'notes_internes' => [
                    'type' => ['string', 'null'],
                    'description' => 'Notes pour l\'électricien (non visibles sur le devis client)'
                ]
            ],
            'required' => [
                'chantier',
                'taches',
                'lignes',
                'questions_a_poser',
                'exclusions',
                'taux_tva',
                'remarque_tva',
                'notes_internes'
            ],
            'additionalProperties' => false
        ]
    ],

    /**
     * Prompt système pour la génération de devis
     */
    'system_prompt' => <<<'PROMPT'
Tu es un assistant expert en électricité bâtiment, spécialisé dans l'établissement de devis RÉALISTES.

## Ton rôle
Analyser la demande client et produire un devis structuré au format JSON strict avec des PRIX DE MARCHÉ RÉALISTES.

## Règles absolues
1. **Ne jamais inventer d'information** : si une donnée manque (adresse, type de bâtiment, distance de câblage...), ajoute une question dans `questions_a_poser` et utilise une hypothèse raisonnable.
2. **Utiliser les codes prix référencés** : utilise uniquement les `prix_ref_code` de la grille fournie. Si un article n'existe pas, utilise `CUSTOM` avec un `prix_unitaire_ht_suggere`.
3. **Être TRÈS réaliste sur les quantités** : les quantités doivent refléter une installation RÉELLE complète.
4. **Inclure systématiquement** : déplacement, consommables, mise en service si pertinent.
5. **TVA** : 20% par défaut. Mentionner en remarque si TVA réduite (10%) potentiellement applicable (rénovation logement > 2 ans).

## Extraction des marques et références
TRÈS IMPORTANT : Si le client mentionne une marque ou une référence produit, tu DOIS les capturer :
- **marque** : Legrand, Schneider, Hager, Somfy, Aiphone, Atlantic, Grohe, etc.
- **reference** : Numéro de référence fabricant (ex: 369220, A9F74206, 1870133)

Exemples d'extraction :
- "un disjoncteur Legrand 406774" → marque: "Legrand", reference: "406774"
- "un interrupteur Schneider Odace" → marque: "Schneider", reference: null (pas de ref précise)
- "un disjoncteur 16A" → marque: null, reference: null

Si une marque est mentionnée, inclure la marque dans la désignation ET dans le champ marque.

## ESTIMATION RÉNOVATION COMPLÈTE - GUIDE ESSENTIEL

Pour une RÉNOVATION ÉLECTRIQUE COMPLÈTE d'une maison, voici les quantités MINIMALES réalistes :

### Par pièce (moyenne) :
- Chambre : 4-5 prises, 1-2 points lumineux, 1-2 interrupteurs, 1 radiateur
- Séjour : 8-10 prises, 2-3 points lumineux, 2-3 interrupteurs, 1-2 radiateurs
- Cuisine : 6-8 prises (dont spécialisées), 2-3 points lumineux, 2 interrupteurs
- Salle de bain : 2-3 prises, 2 points lumineux, 1-2 interrupteurs, 1 sèche-serviettes
- Couloir/entrée : 2 prises, 1-2 points lumineux, 2-3 interrupteurs (va-et-vient)
- WC : 1 prise, 1 point lumineux, 1 interrupteur

### Circuits spécialisés obligatoires :
- Plaque de cuisson : 1 circuit 32A (câble 6mm²)
- Four : 1 circuit 20A
- Lave-linge : 1 circuit 20A
- Lave-vaisselle : 1 circuit 20A
- Sèche-linge : 1 circuit 20A (si présent)
- Chauffe-eau : 1 circuit 20A avec contacteur HC
- Chaque radiateur > 2000W : circuit dédié
- VMC : 1 circuit

### Câblage pour rénovation complète :
- Compter 4-5 mètres de câble PAR POINT (prise ou lumière)
- Maison 100m² ≈ 400-600m de câble au total
- Gaine ICTA : ~80% du linéaire câble

### Main d'œuvre rénovation complète :
- Compter 1h à 1h30 par point électrique (pose + câblage)
- Maison 100m² ≈ 80-120 heures de travail
- OU utiliser le forfait FORFAIT_RENOV_M2 (90-200€/m² selon gamme)

### Prix de référence rénovation complète :
- Maison 80m² : 12 000€ - 20 000€ HT
- Maison 100m² : 15 000€ - 25 000€ HT
- Maison 120m² : 18 000€ - 30 000€ HT
- Maison 150m² : 22 000€ - 38 000€ HT

⚠️ Si le devis calculé est TRÈS INFÉRIEUR à ces fourchettes pour une rénovation complète, c'est que des éléments manquent !

## Codes prix disponibles (grille serveur)
### Main d'oeuvre
- MO_H : Main d'oeuvre horaire (45€/h en moyenne)
- MO_DEPLACEMENT : Forfait déplacement
- MO_MISE_EN_SERVICE : Mise en service et tests
- SAIGNEE_BRIQUE : Saignée mur brique/parpaing (12€/m)
- SAIGNEE_BETON : Saignée béton (25€/m)
- REBOUCHAGE_SAIGNEE : Rebouchage au plâtre (8€/m)

### Câblage
- CABLE_3G15, CABLE_3G25, CABLE_5G25, CABLE_3G6 : Câbles R2V standard
- CABLE_3G10 : Câble 10mm² pour chauffe-eau (7.50€/m)
- CABLE_5G6 : Câble 5G6mm² pour plaque cuisson (9€/m)
- CABLE_PTT : Câble téléphone
- CABLE_SYT : Câble alarme
- FOURREAU_TPC : Fourreau enterré
- GAINE_ICTA_20 : Gaine ICTA
- GOULOTTE_40x25 : Goulotte apparente (6€/m)
- MOULURE_32x12 : Moulure électrique (2.80€/m)

### Appareillage
- PRISE_2PT, PRISE_DOUBLE : Prises
- INTER_SA, INTER_VV, INTER_VR : Interrupteurs
- BOITE_DERIV, BOITE_ENCAST : Boîtes

### Éclairage
- SPOT_LED, PLAFONNIER_LED, REGLETTE_LED, HUBLOT_LED
- DETECTEUR_MVT : Détecteur mouvement

### Tableau
- TABLEAU_13M, TABLEAU_26M, TABLEAU_39M, TABLEAU_52M : Coffrets
- DJ_10A, DJ_16A, DJ_20A, DJ_32A, DJ_40A : Disjoncteurs
- ID_40A_30MA, ID_63A_30MA : Différentiels
- PEIGNE, BORNIER_TERRE, PARAFOUDRE
- CONTACTEUR_HC : Contacteur heures creuses
- TELERUPTEUR : Télérupteur modulaire

### Gros équipements eau chaude
- BALLON_ECS_100L, BALLON_ECS_150L, BALLON_ECS_200L : Ballons eau chaude électrique
- BALLON_THERMO_200L : Chauffe-eau thermodynamique (1800€)

### Chauffage électrique (tarifs 2026)
- RADIATEUR_1000W, RADIATEUR_1500W, RADIATEUR_2000W : Radiateurs convecteurs
- RADIATEUR_INERTIE_1000W, RADIATEUR_INERTIE_1500W, RADIATEUR_INERTIE_2000W : Radiateurs à inertie (450-700€)
- RADIATEUR_CONNECTE : Radiateur connecté intelligent (850€)
- SECHE_SERVIETTE : Sèche-serviettes électrique
- PLANCHER_CHAUFFANT_ELEC : Plancher chauffant électrique (95€/m²)
- THERMOSTAT_PROGRAMMABLE : Thermostat programmable (85€)
- THERMOSTAT_CONNECTE : Thermostat connecté intelligent (200€)
- GESTIONNAIRE_ENERGIE : Gestionnaire d'énergie centralisé (320€)
- DELESTEUR : Délesteur modulaire (150€)

### Ventilation (tarifs 2026)
- VMC_SIMPLE : VMC simple flux (150€)
- VMC_HYGRO : VMC hygroréglable (320€)
- VMC_DOUBLE_FLUX : VMC double flux haut rendement (2800€)
- FORFAIT_POSE_VMC : Pose VMC simple flux (400€)
- FORFAIT_POSE_VMC_DF : Pose VMC double flux (2200€)
- BOUCHE_EXTRACTION, BOUCHE_INSUFFLATION : Bouches VMC
- GAINE_VMC : Gaine souple VMC Ø125 (6€/m)

### Bornes de recharge véhicule électrique (IRVE - tarifs 2026)
- BORNE_RECHARGE_7KW : Borne 7kW monophasé (650€)
- BORNE_RECHARGE_11KW : Borne 11kW triphasé (900€)
- BORNE_RECHARGE_22KW : Borne 22kW triphasé (1400€)
- WALLBOX : Wallbox murale avec câble (800€)
- PRISE_RENFORCEE : Prise renforcée Green'Up (150€)
- PROTECTION_BORNE : Différentiel dédié Type A/F (150€)
- FORFAIT_POSE_BORNE : Pose et raccordement borne (500€)

### Volets roulants & motorisation (tarifs 2026)
- VOLET_ROULANT_ELEC : Volet roulant électrique PVC (550€)
- VOLET_ROULANT_ALU : Volet roulant électrique alu (700€)
- MOTEUR_VOLET : Moteur tubulaire volet (200€)
- MOTORISATION_VOLET : Motorisation volet manuel existant (400€)
- INTER_VR, INTER_VOLET_RADIO : Interrupteurs volet
- STORE_BANNE_ELEC : Store banne électrique 4m (1400€)
- FORFAIT_POSE_STORE : Pose store banne (350€)

### Portail électrique (tarifs 2026)
- MOTEUR_PORTAIL_BATTANT : Kit motorisation battant (700€)
- MOTEUR_PORTAIL_COULISSANT : Kit motorisation coulissant (850€)
- FORFAIT_POSE_MOTEUR_PORTAIL : Pose motorisation portail (550€)
- PHOTOCELLULE : Photocellules sécurité (65€)
- GYROPHARE : Clignotant portail (40€)
- TELECOMMANDE_PORTAIL : Télécommande 4 canaux (45€)

### Éclairage extérieur (tarifs 2026)
- PROJECTEUR_LED_20W, PROJECTEUR_LED_50W : Projecteurs LED extérieur
- PROJECTEUR_DETECTEUR : Projecteur LED avec détecteur (65€)
- APPLIQUE_EXTERIEURE : Applique murale LED extérieure (55€)
- BORNE_JARDIN : Borne lumineuse jardin (70€)
- LAMPADAIRE_JARDIN : Lampadaire de jardin LED (150€)
- SPOT_ENCASTRE_SOL : Spot LED encastré sol IP67 (50€)
- HUBLOT_LED : Hublot LED extérieur (38€)
- FORFAIT_ECLAIRAGE_EXT : Installation point lumineux extérieur (180€)

### Antenne TV & Parabole
- ANTENNE_TNT_EXT : Antenne TNT extérieure (100€)
- PARABOLE_FIXE : Parabole satellite fixe (150€)
- PARABOLE_MOTORISEE : Parabole motorisée (320€)
- REPARTITEUR_TV : Répartiteur TV 4 sorties (18€)
- FORFAIT_POSE_ANTENNE : Pose antenne/parabole complète (200€)
- PRISE_TV_COAX : Prise TV coaxiale (15€)
- CABLE_COAX : Câble coaxial TV (1.50€/m)

### Réseau informatique RJ45
- PRISE_RJ45 : Prise RJ45 Cat6 (22€)
- CABLE_RJ45_CAT6 : Câble réseau Cat6 FTP (1.50€/m)
- COFFRET_COMM : Coffret de communication Grade 2 (220€)
- SWITCH_8P : Switch réseau 8 ports Gigabit (50€)
- FORFAIT_PRISE_RJ45 : Création prise RJ45 complète (130€)

### Contrôle d'accès (tarifs 2026)
- DIGICODE_FIL : Digicode filaire (80€)
- DIGICODE_RADIO : Digicode radio (95€)
- VISIOPHONE : Visiophone 2 fils (140€)
- INTERPHONE : Interphone audio (75€)
- GACHE_ELEC : Gâche électrique 12V (55€)
- VENTOUSE : Ventouse électromagnétique (75€)
- ALIM_12V, BP_SORTIE, BADGE_RFID : Accessoires contrôle d'accès

### Alarme & Sécurité (tarifs 2026)
- CENTRALE_ALARME : Centrale alarme sans fil (400€)
- DETECTEUR_INTRUSION : Détecteur mouvement IR (50€)
- DETECTEUR_OUVERTURE : Détecteur ouverture porte/fenêtre (30€)
- SIRENE_INTERIEURE : Sirène intérieure (50€)
- SIRENE_EXTERIEURE : Sirène extérieure avec flash (120€)
- CLAVIER_ALARME : Clavier de commande (90€)
- DETECTEUR_FUMEE : Détecteur de fumée connecté (45€)
- FORFAIT_ALARME_MAISON : Installation alarme complète (1000€)

### Domotique (tarifs 2026)
- BOX_DOMOTIQUE : Box domotique centrale (300€)
- MODULE_ECLAIRAGE : Module domotique éclairage (65€)
- MODULE_VOLET : Module domotique volet roulant (85€)
- PRISE_CONNECTEE : Prise connectée intelligente (40€)
- INTER_CONNECTE : Interrupteur connecté (75€)
- FORFAIT_DOMOTIQUE_BASE : Installation domotique de base (900€)

### Prises spéciales
- PRISE_USB : Prise 2P+T avec USB intégré (32€)
- PRISE_32A : Prise 32A plaque cuisson (28€)
- PRISE_20A : Prise 20A spécialisée (18€)
- PRISE_RASOIR : Prise rasoir salle de bain (65€)
- PRISE_EXTERIEURE : Prise étanche extérieure IP55 (35€)

### Éclairage intérieur complémentaire
- VARIATEUR : Variateur d'intensité lumineuse (50€)
- MINUTERIE : Minuterie escalier modulaire (40€)
- BANDEAU_LED : Bandeau LED 5m avec alimentation (70€)
- SPOT_ORIENTABLE : Spot LED orientable sur rail (50€)
- RAIL_SPOTS : Rail pour spots 1m (60€)
- SUSPENSION_LED : Suspension LED design (120€)

### Circuits spécialisés (création complète)
- CIRCUIT_PLAQUE : Circuit plaque cuisson 32A (280€)
- CIRCUIT_FOUR : Circuit four 20A (200€)
- CIRCUIT_LAVE_LINGE, CIRCUIT_SECHE_LINGE, CIRCUIT_LAVE_VAISSELLE : Circuits électroménager 20A (200€)
- CIRCUIT_CONGELATEUR : Circuit congélateur dédié (160€)
- CIRCUIT_CLIM : Circuit climatisation (300€)

### Dépannage & Urgences (tarifs 2026)
- MO_DEPANNAGE : Dépannage électrique heure (70€/h)
- MO_URGENCE_SOIR : Intervention urgente soir/WE (120€/h)
- MO_URGENCE_NUIT : Intervention urgente nuit/férié (180€/h)
- FORFAIT_RECHERCHE_PANNE : Recherche de panne électrique (130€)
- FORFAIT_DEPANNAGE_MIN : Forfait dépannage minimum (90€)

### Diagnostic & Conformité
- DIAGNOSTIC_ELEC : Diagnostic électrique complet (160€)
- MISE_SECURITE : Mise en sécurité installation (75€/m²)
- ATTESTATION_CONSUEL : Frais attestation Consuel (155€)

### Consommables
- CONSOMMABLES : Petit matériel (petits travaux)
- CONSOMMABLES_RENOV : Consommables rénovation complète (300€)
- WAGO : Bornes de connexion

### Forfaits installation principaux
- FORFAIT_POINT_LUM : Point lumineux complet (150€)
- FORFAIT_PRISE : Prise complète avec câblage (120€)
- FORFAIT_CONSUEL : Préparation Consuel (250€)
- FORFAIT_RENOV_M2 : Rénovation complète au m² (130€/m²) ⭐ UTILISER POUR GROSSES RÉNOVATIONS
- FORFAIT_POSE_BALLON : Pose ballon eau chaude (280€)
- FORFAIT_POSE_RADIATEUR : Pose radiateur (120€/u)

## RÈGLE TABLEAU ÉLECTRIQUE - TRÈS IMPORTANT
Pour un tableau électrique, tu dois TOUJOURS DÉCOMPOSER les éléments :
1. Coffret : TABLEAU_13M, TABLEAU_26M, TABLEAU_39M ou TABLEAU_52M selon le nombre de modules nécessaires
2. Différentiels : ID_30MA_40A (1 par groupe de 8 circuits max)
3. Disjoncteurs : DJ_10A, DJ_16A, DJ_20A, DJ_32A selon les circuits
4. Accessoires : PEIGNE_H, PEIGNE_V, BORNIER_TERRE, PARAFOUDRE si nécessaire
5. Main d'œuvre : MO_H pour la pose

NE JAMAIS utiliser de forfait global "tableau complet" car cela crée des doublons avec les composants.

### Autre
- CUSTOM : Article hors catalogue (préciser prix_unitaire_ht_suggere)

## Structure de réponse
Tu dois TOUJOURS répondre avec un JSON valide respectant le schéma fourni. Chaque champ est obligatoire.
PROMPT,

    /**
     * Template du prompt utilisateur
     * Variables : {description}, {transcription}, {contexte_images}
     */
    'user_prompt_template' => <<<'PROMPT'
## Demande client

{description}

{transcription}

{contexte_images}

---

Génère le devis structuré en JSON. Si des informations manquent, ajoute les questions pertinentes et formule des hypothèses raisonnables pour les quantités.
PROMPT,

    /**
     * Exemples entrée/sortie pour référence et tests
     */
    'examples' => [
        // === EXEMPLE 1 : Installation digicode ===
        [
            'input' => [
                'description' => "Je voudrais installer un digicode à l'entrée de mon immeuble. Il y a déjà une gâche électrique sur la porte. Le tableau électrique est au sous-sol, environ 15 mètres de la porte d'entrée.",
                'transcription' => null,
                'images' => []
            ],
            'output' => [
                'chantier' => [
                    'titre' => 'Installation digicode entrée immeuble',
                    'localisation' => null,
                    'perimetre' => 'Fourniture et pose d\'un digicode filaire avec raccordement sur gâche existante',
                    'hypotheses' => [
                        'Gâche électrique existante fonctionnelle en 12V',
                        'Passage de câble possible en apparent ou sous goulotte',
                        'Alimentation disponible au tableau',
                        'Distance tableau-porte estimée à 15m'
                    ]
                ],
                'taches' => [
                    [
                        'ordre' => 1,
                        'titre' => 'Repérage et préparation',
                        'details' => 'Vérification de la gâche existante, repérage du passage de câble, identification du point d\'alimentation au tableau',
                        'duree_estimee_h' => 0.5,
                        'points_attention' => ['Vérifier la tension de la gâche (12V ou 24V)', 'S\'assurer de l\'accessibilité du tableau']
                    ],
                    [
                        'ordre' => 2,
                        'titre' => 'Tirage de câble',
                        'details' => 'Passage du câble PTT 1 paire du tableau au digicode (environ 15m) en apparent ou sous goulotte',
                        'duree_estimee_h' => 1.5,
                        'points_attention' => ['Fixation soignée', 'Protection mécanique si nécessaire']
                    ],
                    [
                        'ordre' => 3,
                        'titre' => 'Installation digicode et alimentation',
                        'details' => 'Fixation du digicode, installation de l\'alimentation 12V au tableau, câblage',
                        'duree_estimee_h' => 1.0,
                        'points_attention' => ['Étanchéité du digicode si extérieur']
                    ],
                    [
                        'ordre' => 4,
                        'titre' => 'Raccordement et programmation',
                        'details' => 'Raccordement sur la gâche, programmation du code, tests de fonctionnement',
                        'duree_estimee_h' => 0.5,
                        'points_attention' => ['Tester plusieurs cycles d\'ouverture', 'Former l\'utilisateur à la programmation']
                    ]
                ],
                'lignes' => [
                    [
                        'designation' => 'Digicode filaire à clavier rétroéclairé',
                        'categorie' => 'materiel',
                        'unite' => 'u',
                        'quantite' => 1,
                        'prix_ref_code' => 'DIGICODE_FIL',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Alimentation 12V 2A rail DIN',
                        'categorie' => 'materiel',
                        'unite' => 'u',
                        'quantite' => 1,
                        'prix_ref_code' => 'ALIM_12V',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Câble PTT 1 paire',
                        'categorie' => 'materiel',
                        'unite' => 'm',
                        'quantite' => 18,
                        'prix_ref_code' => 'CABLE_PTT',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => '15m + 20% de marge'
                    ],
                    [
                        'designation' => 'Consommables (attaches, chevilles, connecteurs)',
                        'categorie' => 'materiel',
                        'unite' => 'forfait',
                        'quantite' => 1,
                        'prix_ref_code' => 'CONSOMMABLES',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Main d\'oeuvre installation',
                        'categorie' => 'main_oeuvre',
                        'unite' => 'h',
                        'quantite' => 3.5,
                        'prix_ref_code' => 'MO_H',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Déplacement',
                        'categorie' => 'main_oeuvre',
                        'unite' => 'forfait',
                        'quantite' => 1,
                        'prix_ref_code' => 'MO_DEPLACEMENT',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ]
                ],
                'questions_a_poser' => [
                    [
                        'question' => 'La gâche électrique fonctionne-t-elle en 12V ou 24V ?',
                        'impact' => 'Peut nécessiter une alimentation différente',
                        'priorite' => 'haute'
                    ],
                    [
                        'question' => 'Le passage de câble peut-il se faire en encastré ou doit-il rester apparent ?',
                        'impact' => 'Ajout de goulotte si apparent souhaité pour l\'esthétique',
                        'priorite' => 'moyenne'
                    ],
                    [
                        'question' => 'Combien de codes différents souhaitez-vous programmer ?',
                        'impact' => 'Aucun impact prix, juste pour la programmation',
                        'priorite' => 'basse'
                    ]
                ],
                'exclusions' => [
                    'Remplacement de la gâche électrique existante',
                    'Travaux de maçonnerie',
                    'Réfection des peintures'
                ],
                'taux_tva' => 20,
                'remarque_tva' => 'TVA à 10% potentiellement applicable si immeuble à usage d\'habitation achevé depuis plus de 2 ans',
                'notes_internes' => 'Vérifier sur place le type de gâche avant intervention'
            ]
        ],

        // === EXEMPLE 2 : Ajout prises cuisine ===
        [
            'input' => [
                'description' => "J'aimerais ajouter 3 prises dans ma cuisine pour brancher mes électroménagers. Le tableau est dans l'entrée à 8 mètres environ.",
                'transcription' => null,
                'images' => []
            ],
            'output' => [
                'chantier' => [
                    'titre' => 'Ajout de 3 prises électriques cuisine',
                    'localisation' => null,
                    'perimetre' => 'Création de 3 prises 16A pour électroménagers cuisine avec protection dédiée',
                    'hypotheses' => [
                        'Tableau avec emplacement disponible pour disjoncteur supplémentaire',
                        'Passage de câble possible (apparent, plinthe ou encastré)',
                        'Prises destinées à petit électroménager (non spécialisées 32A)',
                        'Distance tableau-cuisine estimée à 8m'
                    ]
                ],
                'taches' => [
                    [
                        'ordre' => 1,
                        'titre' => 'Repérage et traçage',
                        'details' => 'Définition de l\'emplacement des 3 prises, repérage du cheminement du câble depuis le tableau',
                        'duree_estimee_h' => 0.5,
                        'points_attention' => ['Éviter les zones de chaleur (four, plaques)', 'Hauteur réglementaire à 1.05m ou sous plan de travail']
                    ],
                    [
                        'ordre' => 2,
                        'titre' => 'Perçage et saignées',
                        'details' => 'Réalisation des perçages pour boîtes d\'encastrement et saignées si encastré',
                        'duree_estimee_h' => 1.0,
                        'points_attention' => ['Attention aux canalisations existantes', 'Aspirer la poussière']
                    ],
                    [
                        'ordre' => 3,
                        'titre' => 'Tirage de câble et pose appareillage',
                        'details' => 'Passage du câble 3G2.5, pose des boîtes et des prises',
                        'duree_estimee_h' => 1.5,
                        'points_attention' => ['Respecter les rayons de courbure', 'Repérage des fils']
                    ],
                    [
                        'ordre' => 4,
                        'titre' => 'Raccordement tableau et tests',
                        'details' => 'Installation disjoncteur 20A, raccordement, vérification et tests',
                        'duree_estimee_h' => 0.5,
                        'points_attention' => ['Couper l\'alimentation générale', 'Vérifier le serrage des bornes']
                    ]
                ],
                'lignes' => [
                    [
                        'designation' => 'Prise 2P+T 16A',
                        'categorie' => 'materiel',
                        'unite' => 'u',
                        'quantite' => 3,
                        'prix_ref_code' => 'PRISE_2PT',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Boîte d\'encastrement',
                        'categorie' => 'materiel',
                        'unite' => 'u',
                        'quantite' => 3,
                        'prix_ref_code' => 'BOITE_ENCAST',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Câble R2V 3G2.5mm²',
                        'categorie' => 'materiel',
                        'unite' => 'm',
                        'quantite' => 15,
                        'prix_ref_code' => 'CABLE_3G25',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => '8m + distribution + marge'
                    ],
                    [
                        'designation' => 'Gaine ICTA Ø20',
                        'categorie' => 'materiel',
                        'unite' => 'm',
                        'quantite' => 10,
                        'prix_ref_code' => 'GAINE_ICTA_20',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Si passage en cloison'
                    ],
                    [
                        'designation' => 'Disjoncteur 20A',
                        'categorie' => 'materiel',
                        'unite' => 'u',
                        'quantite' => 1,
                        'prix_ref_code' => 'DJ_20A',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Circuit dédié prises cuisine'
                    ],
                    [
                        'designation' => 'Consommables',
                        'categorie' => 'materiel',
                        'unite' => 'forfait',
                        'quantite' => 1,
                        'prix_ref_code' => 'CONSOMMABLES',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Main d\'oeuvre',
                        'categorie' => 'main_oeuvre',
                        'unite' => 'h',
                        'quantite' => 3.5,
                        'prix_ref_code' => 'MO_H',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Déplacement',
                        'categorie' => 'main_oeuvre',
                        'unite' => 'forfait',
                        'quantite' => 1,
                        'prix_ref_code' => 'MO_DEPLACEMENT',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ]
                ],
                'questions_a_poser' => [
                    [
                        'question' => 'Les prises doivent-elles être encastrées ou en saillie est acceptable ?',
                        'impact' => 'Saillie = moins de travaux mais esthétique différente',
                        'priorite' => 'haute'
                    ],
                    [
                        'question' => 'Y a-t-il un emplacement libre dans le tableau pour un disjoncteur ?',
                        'impact' => 'Si non, prévoir remplacement ou extension du tableau',
                        'priorite' => 'haute'
                    ],
                    [
                        'question' => 'Souhaitez-vous une finition identique aux prises existantes (marque/couleur) ?',
                        'impact' => 'Peut modifier le prix de l\'appareillage',
                        'priorite' => 'basse'
                    ]
                ],
                'exclusions' => [
                    'Réfection des peintures et enduits',
                    'Circuit spécialisé 32A pour plaque de cuisson',
                    'Mise aux normes du tableau existant'
                ],
                'taux_tva' => 10,
                'remarque_tva' => null,
                'notes_internes' => 'Confirmer avec client si logement > 2 ans pour TVA 10%'
            ]
        ],

        // === EXEMPLE 3 : Demande floue (déclenche questions) ===
        [
            'input' => [
                'description' => "Je veux refaire l'électricité de mon appartement",
                'transcription' => null,
                'images' => []
            ],
            'output' => [
                'chantier' => [
                    'titre' => 'Rénovation électrique appartement',
                    'localisation' => null,
                    'perimetre' => 'À définir après précisions du client',
                    'hypotheses' => [
                        'Appartement de taille standard (environ 50-70m²)',
                        'Installation vétuste nécessitant mise aux normes',
                        'Compteur et disjoncteur abonné conservés'
                    ]
                ],
                'taches' => [
                    [
                        'ordre' => 1,
                        'titre' => 'Visite technique préalable',
                        'details' => 'Diagnostic de l\'installation existante, comptage des points, relevé des besoins',
                        'duree_estimee_h' => 1.5,
                        'points_attention' => ['Prévoir accès à toutes les pièces', 'Identifier les contraintes (copropriété, amiante...)']
                    ]
                ],
                'lignes' => [
                    [
                        'designation' => 'Visite technique et établissement devis détaillé',
                        'categorie' => 'main_oeuvre',
                        'unite' => 'forfait',
                        'quantite' => 1,
                        'prix_ref_code' => 'MO_DEPLACEMENT',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Offert si travaux acceptés'
                    ]
                ],
                'questions_a_poser' => [
                    [
                        'question' => 'Quelle est la surface de l\'appartement (nombre de pièces) ?',
                        'impact' => 'Détermine le nombre de circuits et le dimensionnement du tableau',
                        'priorite' => 'haute'
                    ],
                    [
                        'question' => 'S\'agit-il d\'une rénovation complète (fils à nu) ou partielle (conservation de certains circuits) ?',
                        'impact' => 'Impact majeur sur le volume de travaux',
                        'priorite' => 'haute'
                    ],
                    [
                        'question' => 'Le tableau électrique actuel sera-t-il remplacé ?',
                        'impact' => 'Coût tableau + repose de tous les circuits',
                        'priorite' => 'haute'
                    ],
                    [
                        'question' => 'Y a-t-il des besoins spécifiques (plaque induction, borne véhicule, VMC, chauffage électrique) ?',
                        'impact' => 'Circuits spécialisés à prévoir',
                        'priorite' => 'haute'
                    ],
                    [
                        'question' => 'Les travaux seront-ils réalisés logement vide ou occupé ?',
                        'impact' => 'Contraintes d\'organisation du chantier',
                        'priorite' => 'moyenne'
                    ],
                    [
                        'question' => 'Un passage du Consuel est-il nécessaire ?',
                        'impact' => 'Forfait préparation Consuel à ajouter',
                        'priorite' => 'moyenne'
                    ]
                ],
                'exclusions' => [
                    'Travaux de plomberie',
                    'Travaux de maçonnerie lourde',
                    'Peinture et finitions',
                    'Domotique (sauf demande spécifique)'
                ],
                'taux_tva' => 10,
                'remarque_tva' => null,
                'notes_internes' => 'Demande trop vague pour chiffrer - proposer visite technique gratuite pour établir devis complet'
            ]
        ],

        // === EXEMPLE 4 : Rénovation complète maison (DÉTAILLÉ) ===
        [
            'input' => [
                'description' => "Rénovation électrique complète d'une maison de 120 mètres carrés avec un rez-de-chaussée, une cuisine, trois chambres. Il faut prévoir la rénovation complète avec le tableau, le passage des câbles et le chauffage qui est à l'électricité. Je rajoute qu'on va devoir rajouter un ballon d'eau chaude. Il faut aussi changer toutes les prises. Il faut prévoir des saignées pour faire passer les câbles.",
                'transcription' => null,
                'images' => []
            ],
            'output' => [
                'chantier' => [
                    'titre' => 'Rénovation électrique complète maison 120m²',
                    'localisation' => null,
                    'perimetre' => 'Rénovation complète de l\'installation électrique : tableau neuf, câblage complet avec saignées, chauffage électrique, ballon eau chaude, remplacement de tous les points',
                    'hypotheses' => [
                        'Maison de plain-pied avec rez-de-chaussée uniquement',
                        'Configuration : séjour/salon, cuisine, 3 chambres, 1 SDB, 1 WC, couloir',
                        'Chauffage électrique avec radiateurs standards (non connectés)',
                        'Ballon eau chaude 200L pour 4-5 personnes',
                        'Compteur monophasé suffisant',
                        'Saignées nécessaires dans murs brique/parpaing'
                    ]
                ],
                'taches' => [
                    [
                        'ordre' => 1,
                        'titre' => 'Dépose installation existante',
                        'details' => 'Dépose de l\'ancien tableau, démontage des prises/interrupteurs existants, retrait des câbles accessibles',
                        'duree_estimee_h' => 8,
                        'points_attention' => ['Coupure générale obligatoire', 'Évacuation déchets électriques']
                    ],
                    [
                        'ordre' => 2,
                        'titre' => 'Réalisation des saignées',
                        'details' => 'Traçage et réalisation des saignées murales pour passage des gaines dans toutes les pièces',
                        'duree_estimee_h' => 24,
                        'points_attention' => ['Protection des sols', 'Aspiration poussière', 'Attention aux canalisations']
                    ],
                    [
                        'ordre' => 3,
                        'titre' => 'Pose du tableau électrique',
                        'details' => 'Installation coffret 4 rangées, équipement complet avec différentiels, disjoncteurs, contacteur HC, parafoudre',
                        'duree_estimee_h' => 8,
                        'points_attention' => ['Respect schéma NF C 15-100', 'Repérage des circuits']
                    ],
                    [
                        'ordre' => 4,
                        'titre' => 'Tirage de câbles',
                        'details' => 'Passage des gaines ICTA et tirage de tous les câbles depuis le tableau vers chaque point',
                        'duree_estimee_h' => 32,
                        'points_attention' => ['Respect des sections', 'Repérage couleur des fils']
                    ],
                    [
                        'ordre' => 5,
                        'titre' => 'Pose appareillage',
                        'details' => 'Installation de toutes les prises, interrupteurs, points lumineux, boîtes de dérivation',
                        'duree_estimee_h' => 24,
                        'points_attention' => ['Horizontalité des poses', 'Serrage des connexions']
                    ],
                    [
                        'ordre' => 6,
                        'titre' => 'Installation chauffage',
                        'details' => 'Pose et raccordement des radiateurs électriques dans toutes les pièces',
                        'duree_estimee_h' => 8,
                        'points_attention' => ['Hauteur de pose réglementaire', 'Programmation fils pilotes']
                    ],
                    [
                        'ordre' => 7,
                        'titre' => 'Installation ballon eau chaude',
                        'details' => 'Pose du ballon 200L, raccordement électrique avec contacteur HC',
                        'duree_estimee_h' => 4,
                        'points_attention' => ['Fixation murale solide', 'Groupe de sécurité']
                    ],
                    [
                        'ordre' => 8,
                        'titre' => 'Rebouchage et finitions',
                        'details' => 'Rebouchage des saignées au plâtre, nettoyage du chantier',
                        'duree_estimee_h' => 12,
                        'points_attention' => ['Temps de séchage', 'Ponçage avant peinture']
                    ],
                    [
                        'ordre' => 9,
                        'titre' => 'Tests et mise en service',
                        'details' => 'Tests de continuité, isolement, vérification différentiels, mise sous tension progressive',
                        'duree_estimee_h' => 4,
                        'points_attention' => ['Contrôle de tous les circuits', 'Formation utilisateur']
                    ]
                ],
                'lignes' => [
                    [
                        'designation' => 'Rénovation électrique complète 120m²',
                        'categorie' => 'forfait',
                        'unite' => 'm²',
                        'quantite' => 120,
                        'prix_ref_code' => 'FORFAIT_RENOV_M2',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Forfait tout inclus : câblage, appareillage, main d\'oeuvre'
                    ],
                    [
                        'designation' => 'Tableau électrique neuf 4 rangées équipé',
                        'categorie' => 'forfait',
                        'unite' => 'forfait',
                        'quantite' => 1,
                        'prix_ref_code' => 'FORFAIT_TABLEAU_NEUF',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Coffret + différentiels + disjoncteurs + accessoires'
                    ],
                    [
                        'designation' => 'Ballon eau chaude électrique 200L',
                        'categorie' => 'materiel',
                        'unite' => 'u',
                        'quantite' => 1,
                        'prix_ref_code' => 'BALLON_ECS_200L',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Pose et raccordement ballon eau chaude',
                        'categorie' => 'forfait',
                        'unite' => 'forfait',
                        'quantite' => 1,
                        'prix_ref_code' => 'FORFAIT_POSE_BALLON',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Inclut contacteur heures creuses'
                    ],
                    [
                        'designation' => 'Radiateur électrique 1500W (chambres)',
                        'categorie' => 'materiel',
                        'unite' => 'u',
                        'quantite' => 3,
                        'prix_ref_code' => 'RADIATEUR_1500W',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Pour les 3 chambres'
                    ],
                    [
                        'designation' => 'Radiateur électrique 2000W (séjour)',
                        'categorie' => 'materiel',
                        'unite' => 'u',
                        'quantite' => 2,
                        'prix_ref_code' => 'RADIATEUR_2000W',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Séjour/salon'
                    ],
                    [
                        'designation' => 'Radiateur électrique 1000W (cuisine)',
                        'categorie' => 'materiel',
                        'unite' => 'u',
                        'quantite' => 1,
                        'prix_ref_code' => 'RADIATEUR_1000W',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Sèche-serviettes électrique (SDB)',
                        'categorie' => 'materiel',
                        'unite' => 'u',
                        'quantite' => 1,
                        'prix_ref_code' => 'SECHE_SERVIETTE',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Pose et raccordement radiateurs',
                        'categorie' => 'forfait',
                        'unite' => 'u',
                        'quantite' => 8,
                        'prix_ref_code' => 'FORFAIT_POSE_RADIATEUR',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => '7 radiateurs + 1 sèche-serviettes'
                    ],
                    [
                        'designation' => 'Saignées murales (brique/parpaing)',
                        'categorie' => 'main_oeuvre',
                        'unite' => 'm',
                        'quantite' => 150,
                        'prix_ref_code' => 'SAIGNEE_BRIQUE',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Estimation passages encastrés'
                    ],
                    [
                        'designation' => 'Rebouchage saignées au plâtre',
                        'categorie' => 'main_oeuvre',
                        'unite' => 'm',
                        'quantite' => 150,
                        'prix_ref_code' => 'REBOUCHAGE_SAIGNEE',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => null
                    ],
                    [
                        'designation' => 'Consommables rénovation complète',
                        'categorie' => 'materiel',
                        'unite' => 'forfait',
                        'quantite' => 1,
                        'prix_ref_code' => 'CONSOMMABLES_RENOV',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Wago, chevilles, attaches, plâtre, etc.'
                    ],
                    [
                        'designation' => 'Préparation passage Consuel',
                        'categorie' => 'forfait',
                        'unite' => 'forfait',
                        'quantite' => 1,
                        'prix_ref_code' => 'FORFAIT_CONSUEL',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Dossier + attestation de conformité'
                    ],
                    [
                        'designation' => 'Déplacement (x5 interventions)',
                        'categorie' => 'main_oeuvre',
                        'unite' => 'forfait',
                        'quantite' => 5,
                        'prix_ref_code' => 'MO_DEPLACEMENT',
                        'prix_unitaire_ht_suggere' => null,
                        'commentaire' => 'Chantier sur plusieurs semaines'
                    ]
                ],
                'questions_a_poser' => [
                    [
                        'question' => 'Le ballon eau chaude sera-t-il posé à la place de l\'ancien ou à un nouvel emplacement ?',
                        'impact' => 'Peut nécessiter des travaux de plomberie supplémentaires',
                        'priorite' => 'haute'
                    ],
                    [
                        'question' => 'Quel type de radiateurs souhaitez-vous (convecteurs basiques, à inertie, connectés) ?',
                        'impact' => 'Prix très variable selon technologie (x2 à x4)',
                        'priorite' => 'haute'
                    ],
                    [
                        'question' => 'Y a-t-il une VMC existante à conserver ou faut-il en installer une ?',
                        'impact' => 'Ajout VMC si nécessaire',
                        'priorite' => 'moyenne'
                    ],
                    [
                        'question' => 'Souhaitez-vous des prises USB intégrées dans certaines pièces ?',
                        'impact' => 'Supplément par prise USB',
                        'priorite' => 'basse'
                    ]
                ],
                'exclusions' => [
                    'Travaux de plomberie (raccordement eau ballon)',
                    'Peinture des murs après rebouchage',
                    'Remplacement du compteur ou disjoncteur abonné (Enedis)',
                    'Domotique et objets connectés',
                    'Installation VMC (non mentionnée dans la demande)'
                ],
                'taux_tva' => 10,
                'remarque_tva' => 'TVA 10% applicable si maison achevée depuis plus de 2 ans',
                'notes_internes' => 'IMPORTANT : Devis de l\'ordre de 20 000€ à 25 000€ HT pour ce type de rénovation complète. Vérifier l\'état des murs avant saignées. Prévoir 3-4 semaines de travaux.'
            ]
        ]
    ]
];
