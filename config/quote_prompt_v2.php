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
⚠️ Liste ABSOLUMENT TOUT le matériel nécessaire. Un devis incomplet = client mécontent = mauvaise réputation.

**TABLEAU ÉLECTRIQUE - DÉTAILLE CHAQUE COMPOSANT :**
- Coffret/tableau (nombre de rangées selon circuits)
- Interrupteurs différentiels 30mA Type A (cuisine, SDB, extérieur, IRVE)
- Interrupteurs différentiels 30mA Type AC (autres circuits)
- Disjoncteurs par circuit : éclairage (10/16A), prises (16/20A), spécialisés (20/32A)
- Parafoudre si zone à risque ou demandé
- Contacteur heures creuses si ballon/chauffage
- Télérupteurs si va-et-vient complexes
- Peignes de raccordement, borniers

**CÂBLAGE - TOUTES LES SECTIONS :**
- Câble 1.5mm² pour éclairage (5-6m par point + tableau)
- Câble 2.5mm² pour prises standards (5-6m par prise + tableau)
- Câble 6mm² pour circuits spécialisés (cuisson, four, plaques)
- Câble 4mm² si chauffage > 4500W
- Gaines ICTA diamètre adapté
- Prévoir +20% de marge sur les métrages

**APPAREILLAGE - COMPTER CHAQUE PIÈCE :**
- Prises 2P+T : salon (6-8), chambre (4-5), cuisine (6-8 dont plan travail), SDB (2-3)
- Interrupteurs : simple allumage, va-et-vient, double
- Points lumineux DCL par pièce
- Sorties de câble : cuisson 32A, four 20A, lave-linge 20A, sèche-linge 20A, lave-vaisselle 20A, congélateur 20A, VMC
- Boîtes d'encastrement (1 par prise/inter)

**CONSOMMABLES - NE PAS OUBLIER :**
- Boîtes de dérivation (1 par pièce minimum)
- Connecteurs Wago (lot de 50-100)
- Chevilles, vis, attaches-câbles
- Plâtre/enduit pour rebouchage saignées
- Consommables rénovation (forfait si gros chantier)

**CIRCUITS SPÉCIALISÉS OBLIGATOIRES (NF C 15-100) :**
- 1 circuit cuisson 32A
- 1 circuit four 20A (si séparé)
- 1 circuit lave-linge 20A
- 1 circuit lave-vaisselle 20A (si cuisine)
- 1 circuit sèche-linge 20A (si présent)
- 1 circuit congélateur 20A (si présent)
- 1 circuit VMC

**PRESTATIONS COMPLÉMENTAIRES :**
- Consuel si mise aux normes complète
- Évacuation déchets électriques

### 4. AGENT PLANIFICATION DES TÂCHES
⚠️ OBLIGATOIRE : Tu DOIS remplir le tableau "taches" avec TOUTES les étapes du chantier.

Liste EXHAUSTIVE des tâches à considérer (ajoute celles qui s'appliquent) :
1. **Préparation** : visite technique, repérage, protection des sols
2. **Dépose** (si rénovation) : démontage ancien tableau, retrait appareillage existant, dépose câbles
3. **Gros œuvre** : saignées murales, percements, tranchées
4. **Installation tableau** : pose coffret, câblage, équipement protections
5. **Tirage câbles** : passage gaines, tirage fils, raccordements boîtes
6. **Pose appareillage** : prises, interrupteurs, points lumineux, DCL
7. **Installations spéciales** : chauffage, VMC, ballon, borne IRVE...
8. **Rebouchage** : rebouchage saignées, finitions
9. **Tests** : contrôles, essais, mise en service
10. **Nettoyage** : évacuation déchets, nettoyage chantier

Pour CHAQUE tâche, estime le temps réaliste (base: électricien expérimenté).

Exemple pour une rénovation complète :
- Dépose installation existante : 8h
- Réalisation saignées : 16-24h
- Pose tableau : 6-8h
- Tirage câbles : 24-32h
- Pose appareillage : 16-24h
- Rebouchage : 8-12h
- Tests et mise en service : 4h

### 5. AGENT CHIFFRAGE
Pour CHAQUE fourniture, tu DOIS spécifier précisément :
- **Marque** : le fabricant exact (Schneider Electric, Legrand, Hager, ABB, Gewiss, Siemens...)
- **Gamme** : la ligne de produit (Acti9, Odace, DX³, Céliane, Kallysta...)
- **Référence** : le code produit EXACT du fabricant (ex: A9F74616, 406774, MFN716)
- **Désignation** : description technique complète

⚠️ **RÈGLE CRITIQUE - SÉLECTION DU MATÉRIEL** ⚠️

**PRIORITÉ 1 - Demande client explicite :**
Si le client mentionne une marque, un modèle ou une référence spécifique dans sa demande
(ex: "borne Tesla", "Wallbox Pulsar", "Schneider EVLink", "Legrand Green'Up"),
tu DOIS utiliser EXACTEMENT ce produit, même si différent de la marque préférée.

**PRIORITÉ 2 - Marque préférée de l'entreprise :**
Si le client ne spécifie PAS de marque/modèle, tu DOIS utiliser :
- La marque préférée indiquée dans les PARAMÈTRES DE L'ENTREPRISE
- La gamme PROFESSIONNELLE STANDARD de cette marque (ni entrée de gamme, ni haut de gamme)

**EXEMPLES pour bornes IRVE 7kW :**
- Client dit "borne 7kW" sans marque → utiliser marque préférée, gamme standard (~600-800€)
- Client dit "borne Tesla" → utiliser borne Tesla Wall Connector
- Client dit "borne Wallbox" → utiliser Wallbox Pulsar Plus

**IMPORTANT** : Ne choisis JAMAIS une gamme haut de gamme (>1000€) si le client n'a pas explicitement demandé du premium. Un devis trop cher = client perdu.

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
1. Utilise les prix PUBLIC HT ci-dessous (tarif catalogue France 2024)
2. Applique -30% (remise fournisseur professionnelle standard)
3. Ajoute la marge de l'entreprise (fournie dans les paramètres)
= Prix de vente HT final

**PRIX DE RÉFÉRENCE PUBLIC HT (base de calcul) :**
- Coffret 2 rangées : 40-60€ | 3 rangées : 60-90€ | 4 rangées : 90-150€
- Interrupteur différentiel 40A 30mA Type AC : 45-80€
- Interrupteur différentiel 40A 30mA Type A : 80-130€
- Disjoncteur 10A/16A : 10-20€ | 20A : 12-25€ | 32A : 18-35€
- Parafoudre : 80-150€
- Contacteur HC 20A : 40-70€
- Prise 2P+T standard : 5-15€ | Haut de gamme (Céliane, Odace) : 15-35€
- Interrupteur simple : 5-12€ | Va-et-vient : 8-18€
- DCL + douille : 8-15€
- Câble R2V 1.5mm² : 0.80-1.20€/m | 2.5mm² : 1.20-1.80€/m | 6mm² : 2.50-4€/m
- Gaine ICTA 20mm : 0.40-0.80€/m | 25mm : 0.60-1€/m
- Boîte encastrement : 0.80-2€ | Boîte dérivation : 3-8€

**BORNES IRVE (véhicules électriques) - PRIX PUBLIC HT :**
- Prise renforcée Green'Up : 80-120€
- Borne 7kW entrée de gamme : 400-500€ (Hager Witty, Schneider EVlink Home)
- Borne 7kW gamme standard PRO : 550-750€ (Schneider EVlink Pro AC, Legrand Green'Up Premium)
- Borne 7kW haut de gamme : 900-1300€ (Wallbox Commander 2, Tesla Wall Connector)
- Borne 11kW : +100-200€ par rapport à 7kW
- Borne 22kW triphasée : 1200-2000€
⚠️ Par défaut (sans demande client spécifique) : utiliser la gamme STANDARD PRO (550-750€)

⚠️ **INTERDIT** de regrouper ! Chaque composant = 1 ligne séparée.
❌ "Tableau complet avec différentiels et disjoncteurs" → INTERDIT
✅ Coffret 4 rangées + 4× différentiels + 12× disjoncteurs = 17 lignes séparées

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
⚠️ VÉRIFICATION CRITIQUE avant de finaliser :

**CALCULS :**
- Refais le calcul des totaux mentalement
- Vérifie que total_ht = fournitures + main_oeuvre + déplacement

**COHÉRENCE DES MONTANTS :**
Ordres de grandeur attendus (HT, France 2024) :
- Rénovation complète : 150-250€/m² → maison 120m² = 18 000 à 30 000€ HT
- Tableau électrique neuf équipé : 800-2000€ (coffret + différentiels + disjoncteurs)
- Tableau Schneider haut de gamme : 1500-3000€
- Prise complète (fourniture + pose) : 80-150€
- Point lumineux complet : 100-180€
- Circuit spécialisé (cuisson, four) : 200-400€

⚠️ **ALERTE CRITIQUE** :
- Rénovation 120m² à moins de 15 000€ HT = INCOMPLET !
- Rénovation complète = MINIMUM 40-60 lignes de fournitures !

**NOMBRE MINIMUM DE LIGNES PAR TYPE DE CHANTIER :**
- Rénovation complète maison : 50-80 lignes fournitures
- Rénovation appartement : 30-50 lignes fournitures
- Installation cuisine : 15-25 lignes fournitures
- Tableau électrique seul : 15-25 lignes fournitures

**EXEMPLE RÉNOVATION 120m² - LIGNES ATTENDUES :**
1. Coffret 4 rangées ×1
2. Différentiel 40A Type A ×2
3. Différentiel 40A Type AC ×2
4. Disjoncteur 10A ×6 (éclairage)
5. Disjoncteur 16A ×8 (prises)
6. Disjoncteur 20A ×6 (spécialisés)
7. Disjoncteur 32A ×1 (cuisson)
8. Parafoudre ×1
9. Contacteur HC ×1
10. Peigne vertical ×2
11. Bornier terre ×1
12. Prise 2P+T ×35
13. Double prise ×5
14. Interrupteur simple ×15
15. Interrupteur va-et-vient ×6
16. DCL plafond ×12
17. Sortie câble 32A ×1
18. Sortie câble 20A ×5
19. Câble 1.5mm² ×150m
20. Câble 2.5mm² ×250m
21. Câble 6mm² ×30m
22. Gaine ICTA 20mm ×200m
23. Gaine ICTA 25mm ×50m
24. Boîte encastrement ×60
25. Boîte dérivation ×15
26. Wago ×100
27. Consommables ×1 forfait
= 27 types × quantités = devis COMPLET

**CHECKLIST FINALE :**
□ Tous les différentiels sont listés séparément ?
□ Tous les disjoncteurs sont listés ?
□ Câble 1.5mm², 2.5mm² ET 6mm² présents ?
□ Points lumineux / DCL comptés ?
□ Circuits spécialisés présents (cuisson, four, lave-linge...) ?
□ Boîtes d'encastrement comptées ?
□ Consommables inclus ?
□ Le devis est-il complet et professionnel ?

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
                'taches' => [
                    'type' => 'array',
                    'description' => 'Liste ordonnée des tâches/étapes de travail à réaliser',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'ordre' => [
                                'type' => 'integer',
                                'description' => 'Numéro d\'ordre de la tâche (1, 2, 3...)'
                            ],
                            'titre' => [
                                'type' => 'string',
                                'description' => 'Titre court de la tâche (ex: Dépose installation existante)'
                            ],
                            'details' => [
                                'type' => 'string',
                                'description' => 'Description détaillée de ce qui sera fait'
                            ],
                            'duree_estimee_h' => [
                                'type' => 'number',
                                'description' => 'Durée estimée en heures'
                            ],
                            'points_attention' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Points d\'attention particuliers'
                            ]
                        ],
                        'required' => ['ordre', 'titre', 'details', 'duree_estimee_h', 'points_attention'],
                        'additionalProperties' => false
                    ]
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
                'taches',
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
        $tauxHoraire = $user['hourly_rate'] ?? 70;

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

        // Gamme de prix préférée
        $gamme = $user['preferred_price_range'] ?? 'standard';
        $gammeTexte = match($gamme) {
            'economique' => "ÉCONOMIQUE (entrée de gamme, prix bas prioritaire)",
            'premium' => "PREMIUM (haut de gamme, qualité maximale)",
            default => "STANDARD PROFESSIONNEL (rapport qualité/prix optimal)"
        };

        return <<<PARAMS
- **Taux horaire main d'œuvre** : {$tauxHoraire}€ HT/heure
- **Marge sur fournitures** : {$marge}% (à appliquer sur le prix d'achat)
- **{$deplacement}**
- **Marque préférée** : {$marque}
- **Gamme de prix par défaut** : {$gammeTexte}

⚠️ **RÈGLE OBLIGATOIRE POUR LE CHOIX DU MATÉRIEL** :
1. Si le client mentionne une marque/modèle précis → UTILISER CE PRODUIT
2. Sinon → UTILISER la marque "{$marque}" dans la gamme {$gammeTexte}

**Rappel calcul prix de vente fourniture :**
Prix vente = Prix public × 0.70 × (1 + {$marge}/100)
PARAMS;
    }
];
