# Processus de génération de devis

## Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        FLUX DE GÉNÉRATION DE DEVIS                          │
└─────────────────────────────────────────────────────────────────────────────┘

[Utilisateur]
     │
     ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ 1. SAISIE   │───▶│ 2. ANALYSE  │───▶│ 3. ENRICHI- │
│ Texte/Audio │    │ Détection   │    │ SSEMENT     │
│ + Images    │    │ type travaux│    │ Normes+Prix │
└─────────────┘    └─────────────┘    └─────────────┘
                                             │
                                             ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ 6. AFFICHAGE│◀───│ 5. CALCUL   │◀───│ 4. GÉNÉRATION│
│ + Édition   │    │ Prix réels  │    │ IA (Claude) │
└─────────────┘    └─────────────┘    └─────────────┘
```

---

## PROCESSUS ACTUEL (ce qui existe)

### Étape 1 : Saisie utilisateur
**Fichier** : `public/js/app.js` → `handleSubmit()`

L'utilisateur fournit :
- **Description texte** : champ textarea
- **Transcription audio** : enregistrement vocal transcrit par Whisper
- **Images** : photos du chantier (optionnel)
- **Infos client** : nom, téléphone, email, adresse
- **Infos chantier** : adresse, code postal, ville

### Étape 2 : Envoi au serveur
**Fichier** : `src/Controllers/ApiController.php` → `generate()`

```
POST /api/generate
{
    description: "Je veux installer un tableau électrique...",
    transcription: "...",
    images: [...],
    client: {...},
    chantier: {...}
}
```

### Étape 3 : Génération IA (ACTUEL)
**Fichiers** :
- `src/Services/OpenAIService.php` → `generateQuoteJson()`
- `config/quote_schema.php` → Prompt système + grille de prix

**Problème actuel** : L'IA reçoit une grille de prix STATIQUE (fichier `prices.php`) et doit deviner :
- Quels équipements sont nécessaires
- Quels prix utiliser
- Sans consulter les normes ni la base de prix réels

### Étape 4 : Calcul des prix
**Fichier** : `src/Services/QuoteCalculator.php`

- Prend le JSON de l'IA
- Remplace les codes prix (DJ_16A, CABLE_3G15...) par les vrais prix
- Calcule les totaux HT, TVA, TTC

### Étape 5 : Sauvegarde et affichage
- Sauvegarde en BDD
- Retourne le devis au frontend
- Affichage avec possibilité d'édition

---

## PROCESSUS AMÉLIORÉ (ce qu'on veut)

### Nouveau flux avec enrichissement

```
┌────────────────────────────────────────────────────────────────────────────┐
│                           NOUVEAU PROCESSUS                                 │
└────────────────────────────────────────────────────────────────────────────┘

ENTRÉE UTILISATEUR
       │
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ ÉTAPE 1 : ANALYSE DE LA DEMANDE                                          │
│ ─────────────────────────────────                                        │
│ Fichier : src/Services/QuoteContextService.php (À CRÉER)                 │
│                                                                          │
│ • Reçoit : description + transcription                                   │
│ • Détecte le(s) type(s) de travaux via mots-clés :                      │
│   - "tableau" → type: tableau                                            │
│   - "VMC" → type: vmc                                                    │
│   - "borne recharge" → type: irve                                        │
│   - "salle de bain" → type: salle_bain                                   │
│   - etc.                                                                 │
│                                                                          │
│ • Utilise : config/norms_rules.php → champ "mots_cles" de chaque type   │
└──────────────────────────────────────────────────────────────────────────┘
       │
       │ Types détectés : ["tableau", "chauffage"]
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ ÉTAPE 2 : RÉCUPÉRATION DES ÉQUIPEMENTS OBLIGATOIRES                      │
│ ────────────────────────────────────────────────────                     │
│ Fichier : config/norms_rules.php                                         │
│                                                                          │
│ Pour chaque type détecté, récupérer :                                    │
│ • equipements_obligatoires[] → DOIT être dans le devis                  │
│ • equipements_recommandes[] → PEUT être suggéré                         │
│ • normes[] → À mentionner dans les notes                                │
│ • points_controle[] → Pour les questions à poser                        │
│ • temps_estime → Pour calculer la main d'œuvre                          │
│ • ordre_travaux[] → Pour structurer les tâches                          │
│                                                                          │
│ Exemple pour type "tableau" :                                            │
│ {                                                                        │
│   equipements_obligatoires: [                                            │
│     { code: "ID_30MA", designation: "Différentiel 30mA", quantite: 2 }, │
│     { code: "BORNIER_TERRE", designation: "Bornier de terre", qte: 1 }  │
│   ],                                                                     │
│   temps_estime: { standard: "1 journée" },                              │
│   normes: ["NF C 15-100 §10.1"]                                         │
│ }                                                                        │
└──────────────────────────────────────────────────────────────────────────┘
       │
       │ Liste d'équipements à inclure
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ ÉTAPE 3 : RECHERCHE DES PRIX RÉELS                                       │
│ ──────────────────────────────────                                       │
│ Fichier : src/Services/ProductSearchService.php (EXISTE DÉJÀ)           │
│ Table : product_prices (BDD)                                             │
│                                                                          │
│ Pour chaque équipement obligatoire/recommandé :                          │
│ • Rechercher dans product_prices par désignation/marque                 │
│ • Si trouvé → utiliser le prix de la base                               │
│ • Si non trouvé → utiliser le prix de prices.php (fallback)            │
│                                                                          │
│ Exemple :                                                                │
│ "Différentiel 30mA 40A Type A" → Recherche dans BDD                     │
│   → Trouvé : Legrand 411617 à 45.50€                                    │
│   → Ou : Schneider A9R61240 à 42.80€                                    │
│                                                                          │
│ Résultat : Liste d'équipements AVEC prix réels                          │
└──────────────────────────────────────────────────────────────────────────┘
       │
       │ Équipements + Prix
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ ÉTAPE 4 : CONSTRUCTION DU CONTEXTE ENRICHI                               │
│ ──────────────────────────────────────────                               │
│ Fichier : src/Services/QuoteContextService.php                           │
│                                                                          │
│ Créer un contexte complet pour l'IA :                                    │
│                                                                          │
│ {                                                                        │
│   "types_detectes": ["tableau"],                                        │
│   "equipements_a_inclure": [                                            │
│     {                                                                    │
│       "designation": "Différentiel 30mA 40A Type A",                    │
│       "marque_suggeree": "Legrand",                                     │
│       "reference_suggeree": "411617",                                   │
│       "prix_unitaire": 45.50,                                           │
│       "quantite_min": 2,                                                │
│       "obligatoire": true,                                              │
│       "raison": "Protection obligatoire NF C 15-100"                    │
│     },                                                                   │
│     ...                                                                  │
│   ],                                                                     │
│   "normes_applicables": ["NF C 15-100 §10.1"],                          │
│   "temps_estime_total": "1 journée",                                    │
│   "points_attention": ["Vérifier puissance abonnement", ...]            │
│ }                                                                        │
└──────────────────────────────────────────────────────────────────────────┘
       │
       │ Contexte enrichi
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ ÉTAPE 5 : GÉNÉRATION IA AVEC CONTEXTE                                    │
│ ─────────────────────────────────────                                    │
│ Fichier : src/Services/OpenAIService.php                                 │
│                                                                          │
│ Le prompt inclut maintenant :                                            │
│ • La demande client (description + transcription)                       │
│ • La LISTE EXACTE des équipements à inclure avec leurs prix             │
│ • Les quantités minimales                                               │
│ • Les normes à respecter                                                │
│                                                                          │
│ L'IA doit :                                                              │
│ • Utiliser les équipements fournis (ne pas en inventer)                 │
│ • Ajuster les quantités selon la demande spécifique                     │
│ • Ajouter la main d'œuvre et les consommables                          │
│ • Structurer le devis (tâches, questions, etc.)                         │
│                                                                          │
│ Ce que l'IA NE FAIT PLUS :                                               │
│ • Deviner les équipements nécessaires                                   │
│ • Inventer des prix                                                     │
│ • Utiliser des forfaits qui doublent les composants                     │
└──────────────────────────────────────────────────────────────────────────┘
       │
       │ JSON du devis
       ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ ÉTAPE 6 : CALCUL FINAL ET SAUVEGARDE                                     │
│ ────────────────────────────────────                                     │
│ Fichier : src/Services/QuoteCalculator.php                               │
│                                                                          │
│ • Vérifie que les prix correspondent à ceux fournis                     │
│ • Calcule les totaux                                                    │
│ • Applique la TVA appropriée                                            │
│ • Sauvegarde en BDD                                                     │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## FICHIERS IMPLIQUÉS

### Configuration (ce qu'on a)

| Fichier | Rôle | Contenu |
|---------|------|---------|
| `config/norms_rules.php` | Normes & équipements par type | 18 types de travaux avec équipements obligatoires, recommandés, normes, temps estimé |
| `config/prices.php` | Grille de prix statique (fallback) | Codes prix avec 3 niveaux (low/mid/high) |
| `config/quote_schema.php` | Prompt IA + schéma JSON | Instructions pour l'IA, exemples |

### Base de données

| Table | Rôle |
|-------|------|
| `product_prices` | Prix réels des produits (Legrand, Schneider...) |
| `price_corrections` | Corrections de prix utilisateur |
| `quotes` | Devis sauvegardés |

### Services (ce qu'on a)

| Fichier | Rôle |
|---------|------|
| `src/Services/OpenAIService.php` | Appel Claude pour génération |
| `src/Services/QuoteCalculator.php` | Calcul des prix et totaux |
| `src/Services/ProductSearchService.php` | Recherche prix dans BDD |

### Service à créer

| Fichier | Rôle |
|---------|------|
| `src/Services/QuoteContextService.php` | **Orchestre le tout** : détection type → récup normes → recherche prix → contexte enrichi |

---

## EXEMPLE CONCRET

### Entrée utilisateur
```
"Je voudrais faire installer un nouveau tableau électrique dans ma maison.
Il y a 3 chambres, un salon, une cuisine et 2 salles de bain."
```

### Étape 1 : Détection
```php
$types = ['tableau']; // Mot-clé "tableau électrique" détecté
```

### Étape 2 : Équipements depuis norms_rules.php
```php
$equipements = [
    ['code' => 'TABLEAU_39M', 'designation' => 'Coffret 3 rangées', 'quantite' => 1],
    ['code' => 'ID_30MA_40A', 'designation' => 'Différentiel 30mA 40A Type A', 'quantite' => 3],
    ['code' => 'DJ_16A', 'designation' => 'Disjoncteur 16A', 'quantite' => 15],
    ['code' => 'DJ_20A', 'designation' => 'Disjoncteur 20A', 'quantite' => 5],
    ['code' => 'DJ_32A', 'designation' => 'Disjoncteur 32A', 'quantite' => 1],
    ['code' => 'PEIGNE_H', 'designation' => 'Peigne horizontal', 'quantite' => 3],
    ['code' => 'BORNIER_TERRE', 'designation' => 'Bornier de terre', 'quantite' => 1],
];
```

### Étape 3 : Recherche prix dans BDD
```php
$prixRecherches = [
    ['designation' => 'Coffret 3 rangées 39 modules', 'marque' => 'Legrand', 'prix' => 89.50],
    ['designation' => 'Différentiel 30mA 40A Type A', 'marque' => 'Schneider', 'prix' => 45.80],
    ['designation' => 'Disjoncteur 16A courbe C', 'marque' => 'Legrand', 'prix' => 8.20],
    // ...
];
```

### Étape 4 : Contexte pour l'IA
```json
{
    "demande_client": "Je voudrais faire installer un nouveau tableau électrique...",
    "types_travaux": ["tableau"],
    "equipements_predefinis": [
        {
            "designation": "Coffret 3 rangées 39 modules Legrand",
            "prix_unitaire": 89.50,
            "quantite_suggeree": 1,
            "obligatoire": true
        },
        {
            "designation": "Différentiel 30mA 40A Type A Schneider",
            "prix_unitaire": 45.80,
            "quantite_suggeree": 3,
            "obligatoire": true,
            "note": "1 par groupe de 8 circuits"
        }
    ],
    "main_oeuvre_estimee": "8 heures",
    "normes": ["NF C 15-100 §10.1"],
    "instructions": "Utilise UNIQUEMENT les équipements listés ci-dessus. Ajuste les quantités selon les pièces mentionnées (3 chambres, 1 salon, 1 cuisine, 2 SDB)."
}
```

### Étape 5 : L'IA génère le devis
L'IA a toutes les infos, elle n'a plus qu'à :
- Structurer le devis
- Ajuster les quantités (ex: 15 disjoncteurs → peut-être 18 vu le nombre de pièces)
- Ajouter consommables et déplacement
- Créer les tâches et questions

---

## RÉSUMÉ DES ACTIONS À FAIRE

1. **Créer `QuoteContextService.php`**
   - Méthode `detectTypes(string $description)` : détection via mots-clés
   - Méthode `getRequiredEquipments(array $types)` : récup depuis norms_rules
   - Méthode `enrichWithPrices(array $equipements)` : recherche prix BDD
   - Méthode `buildContext(...)` : construction du contexte final

2. **Modifier `ApiController::generate()`**
   - Appeler QuoteContextService avant OpenAI
   - Passer le contexte enrichi à l'IA

3. **Adapter `quote_schema.php`**
   - Ajouter placeholder `{contexte_equipements}` dans le prompt
   - Simplifier les instructions (l'IA n'a plus à deviner)

4. **Supprimer les forfaits dangereux**
   - Plus de FORFAIT_TABLEAU, FORFAIT_POINT_LUM, etc.
   - Toujours décomposer avec les vrais équipements

---

## AVANTAGES DU NOUVEAU SYSTÈME

| Avant | Après |
|-------|-------|
| L'IA devine les équipements | L'IA reçoit la liste exacte |
| Prix statiques de prices.php | Prix réels de la BDD |
| Risque de doublons (forfait + composants) | Toujours décomposé |
| Normes ignorées | Normes intégrées au contexte |
| Quantités approximatives | Quantités basées sur les règles métier |
