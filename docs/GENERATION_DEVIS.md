# Processus de Génération de Devis - Chiffreo

## Vue d'ensemble

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Client    │───▶│   Frontend  │───▶│   Backend   │───▶│   OpenAI    │
│  (Browser)  │    │  (app.js)   │    │    (PHP)    │    │   (GPT-4)   │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
                          │                  │                  │
                          ▼                  ▼                  ▼
                   Saisie/Dictée      API /generate      JSON structuré
                                            │
                                            ▼
                                   ┌─────────────────┐
                                   │ QuoteCalculator │
                                   │  (prix réels)   │
                                   └─────────────────┘
                                            │
                                            ▼
                                   ┌─────────────────┐
                                   │    Base MySQL   │
                                   │   + PDF généré  │
                                   └─────────────────┘
```

---

## Étape 1 : Saisie utilisateur (Frontend)

### Fichiers impliqués
- `public/index.html` - Formulaire HTML
- `public/js/app.js` - Logique JavaScript

### Données collectées

#### Étape 1 du formulaire : Informations client
```javascript
state.client = {
    civilite: 'M.',      // M., Mme, Société
    nom: '',
    prenom: '',
    societe: '',
    email: '',
    telephone: '',
    adresse: '',
    codePostal: '',
    ville: ''
};
```
> ⚠️ **RGPD** : Ces données ne sont JAMAIS envoyées à OpenAI. Elles restent en local et en BDD uniquement.

#### Étape 2 du formulaire : Description des travaux
- **Texte saisi** : `elements.description.value`
- **Transcription audio** : `state.transcription` (via Whisper)
- **Images** : `state.images[]` (max 4, encodées en base64)

### Processus d'enregistrement audio

```javascript
// 1. Clic sur bouton micro
toggleRecording() → startRecording()

// 2. Capture audio via MediaRecorder
navigator.mediaDevices.getUserMedia({ audio: true })
state.mediaRecorder = new MediaRecorder(stream)

// 3. Arrêt et envoi à Whisper
stopRecording() → transcribeAudio(audioBlob)

// 4. Appel API transcription
fetch('/api/transcribe', { body: formData })

// 5. Résultat affiché + copié dans description
state.transcription = result.data.text
elements.description.value = state.transcription
```

---

## Étape 2 : Appel API /api/generate (Frontend → Backend)

### Fichier : `public/js/app.js` → fonction `handleSubmit()`

```javascript
const formData = new FormData();
formData.append('description', description);
formData.append('transcription', transcription);
formData.append('client', JSON.stringify(state.client));  // Stocké, pas envoyé à IA
formData.append('chantier', JSON.stringify(state.chantier));
state.images.forEach(file => formData.append('images[]', file));

fetch('/api/generate', { method: 'POST', body: formData });
```

---

## Étape 3 : Traitement Backend (PHP)

### Fichier : `src/Controllers/ApiController.php` → méthode `generate()`

### 3.1 Réception des données

```php
$input = array_merge($_POST, $_FILES);
$description = trim($input['description'] ?? '');
$transcription = trim($input['transcription'] ?? '');

// Données client (JAMAIS envoyées à l'IA)
$clientData = json_decode($input['client'], true);
$chantierData = json_decode($input['chantier'], true);

// Texte combiné pour l'IA (SANS données personnelles)
$texteComplet = $description;
if (!empty($transcription)) {
    $texteComplet .= "\n\n[Transcription audio] " . $transcription;
}
```

### 3.2 Gestion des images (optionnel)

```php
// Images converties en base64 pour GPT-4 Vision
foreach ($_FILES['images']['tmp_name'] as $tmpPath) {
    $base64 = base64_encode(file_get_contents($tmpPath));
    $imageUrls[] = "data:image/jpeg;base64,{$base64}";
}
```

---

## Étape 4 : Appel OpenAI (GPT-4)

### Fichier : `src/Services/OpenAIClient.php` → méthode `generateQuoteJson()`

### 4.1 Construction du prompt

```php
// Charger la configuration
$schemaConfig = require 'config/quote_schema.php';

$messages = [
    [
        'role' => 'system',
        'content' => $schemaConfig['system_prompt']  // Instructions expert électricien
    ],
    [
        'role' => 'user',
        'content' => $userPrompt  // Description + transcription + images
    ]
];
```

### 4.2 Le System Prompt (résumé)

Le fichier `config/quote_schema.php` contient :

```
Tu es un assistant expert en électricité bâtiment...

## GUIDE RÉNOVATION COMPLÈTE
- Chambre : 4-5 prises, 1-2 points lumineux, 1 radiateur
- Séjour : 8-10 prises, 2-3 points lumineux, 1-2 radiateurs
- etc.

## Prix de référence
- Maison 80m² : 12 000€ - 20 000€ HT
- Maison 100m² : 15 000€ - 25 000€ HT
- Maison 120m² : 18 000€ - 30 000€ HT

## Codes prix disponibles
- FORFAIT_RENOV_M2 : 130€/m² (rénovation complète)
- FORFAIT_TABLEAU_NEUF : 1800€
- RADIATEUR_1500W : 220€
- etc.
```

### 4.3 Appel API avec Structured Outputs

```php
$response = $this->httpClient->post('chat/completions', [
    'json' => [
        'model' => 'gpt-4o',
        'messages' => $messages,
        'response_format' => [
            'type' => 'json_schema',
            'json_schema' => $schemaConfig['json_schema']  // Schéma strict
        ],
        'temperature' => 0.3,  // Faible = plus cohérent
        'max_tokens' => 4000
    ]
]);
```

### 4.4 Structure JSON retournée par GPT-4

```json
{
  "chantier": {
    "titre": "Rénovation électrique complète maison 120m²",
    "localisation": null,
    "perimetre": "Rénovation complète...",
    "hypotheses": ["Maison plain-pied", "Murs brique", ...]
  },
  "taches": [
    {
      "ordre": 1,
      "titre": "Dépose installation existante",
      "details": "...",
      "duree_estimee_h": 8,
      "points_attention": ["Coupure générale", ...]
    },
    ...
  ],
  "lignes": [
    {
      "designation": "Rénovation électrique complète 120m²",
      "categorie": "forfait",
      "unite": "m²",
      "quantite": 120,
      "prix_ref_code": "FORFAIT_RENOV_M2",  // ← Code référence
      "prix_unitaire_ht_suggere": null,
      "commentaire": "Forfait tout inclus"
    },
    {
      "designation": "Ballon eau chaude 200L",
      "categorie": "materiel",
      "unite": "u",
      "quantite": 1,
      "prix_ref_code": "BALLON_ECS_200L",
      "prix_unitaire_ht_suggere": null,
      "commentaire": null
    },
    ...
  ],
  "questions_a_poser": [...],
  "exclusions": [...],
  "taux_tva": 10,
  "remarque_tva": "TVA 10% si maison > 2 ans",
  "notes_internes": "Devis ~20-25k€ HT"
}
```

> **Important** : GPT-4 retourne des `prix_ref_code` (ex: `FORFAIT_RENOV_M2`) mais PAS les prix réels. C'est le QuoteCalculator qui applique les prix.

---

## Étape 5 : Calcul des prix (QuoteCalculator)

### Fichier : `src/Services/QuoteCalculator.php` → méthode `calculate()`

### 5.1 Chargement de la grille de prix

```php
// Fichier config/prices.php
$this->priceGrid = [
    'FORFAIT_RENOV_M2' => [
        'label' => 'Rénovation électrique complète au m²',
        'unit' => 'm²',
        'price_low' => 90.00,
        'price_mid' => 130.00,   // ← Prix utilisé par défaut
        'price_high' => 200.00,
        'category' => 'forfait'
    ],
    'BALLON_ECS_200L' => [
        'price_mid' => 420.00,
        ...
    ],
    ...
];
```

### 5.2 Application des prix ligne par ligne

```php
foreach ($quoteData['lignes'] as &$ligne) {
    $code = $ligne['prix_ref_code'];

    if ($code === 'CUSTOM') {
        // Prix suggéré par l'IA
        $prixUnitaire = $ligne['prix_unitaire_ht_suggere'];
    } else {
        // Prix de la grille (milieu de gamme par défaut)
        $prixUnitaire = $this->priceGrid[$code]['price_mid'];
    }

    $ligne['prix_unitaire_ht'] = $prixUnitaire;
    $ligne['total_ligne_ht'] = $prixUnitaire * $ligne['quantite'];
}
```

### 5.3 Calcul des totaux

```php
$totalHT = array_sum(array_column($lignes, 'total_ligne_ht'));
$tauxTVA = $quoteData['taux_tva'] ?? 20;
$montantTVA = $totalHT * ($tauxTVA / 100);
$totalTTC = $totalHT + $montantTVA;

$quoteData['totaux'] = [
    'total_ht' => round($totalHT, 2),
    'taux_tva' => $tauxTVA,
    'montant_tva' => round($montantTVA, 2),
    'total_ttc' => round($totalTTC, 2)
];
```

### Exemple de calcul (rénovation 120m²)

| Ligne | Code | Qté | Prix unit. | Total |
|-------|------|-----|------------|-------|
| Rénovation au m² | FORFAIT_RENOV_M2 | 120 | 130€ | 15 600€ |
| Tableau neuf | FORFAIT_TABLEAU_NEUF | 1 | 1 800€ | 1 800€ |
| Ballon 200L | BALLON_ECS_200L | 1 | 420€ | 420€ |
| Pose ballon | FORFAIT_POSE_BALLON | 1 | 280€ | 280€ |
| Radiateurs 1500W | RADIATEUR_1500W | 3 | 220€ | 660€ |
| Radiateurs 2000W | RADIATEUR_2000W | 2 | 280€ | 560€ |
| Sèche-serviettes | SECHE_SERVIETTE | 1 | 250€ | 250€ |
| Pose radiateurs | FORFAIT_POSE_RADIATEUR | 8 | 120€ | 960€ |
| Saignées | SAIGNEE_BRIQUE | 150 | 12€ | 1 800€ |
| Rebouchage | REBOUCHAGE_SAIGNEE | 150 | 8€ | 1 200€ |
| Consommables | CONSOMMABLES_RENOV | 1 | 300€ | 300€ |
| Consuel | FORFAIT_CONSUEL | 1 | 250€ | 250€ |
| Déplacements | MO_DEPLACEMENT | 5 | 35€ | 175€ |
| **TOTAL HT** | | | | **24 255€** |
| TVA 10% | | | | 2 425€ |
| **TOTAL TTC** | | | | **26 680€** |

---

## Étape 6 : Sauvegarde en base de données

### Fichier : `src/Models/QuoteRepository.php`

```php
// 1. Génération de la référence
$reference = $this->generateReference();  // Ex: "DEV-2026-0042"

// 2. Sauvegarde client (si fourni)
$clientId = $this->clientRepo->findOrCreate($clientData);

// 3. Insertion du devis
$quoteId = $this->quoteRepo->create([
    'reference' => $reference,
    'client_id' => $clientId,
    'titre' => $quoteData['chantier']['titre'],
    'description_originale' => $texteComplet,
    'ai_response' => json_encode($aiResponse),      // Réponse brute GPT
    'quote_data' => json_encode($quoteWithTotals),  // Avec prix calculés
    'total_ht' => $totaux['total_ht'],
    'total_tva' => $totaux['montant_tva'],
    'total_ttc' => $totaux['total_ttc'],
    'taux_tva' => $totaux['taux_tva']
]);
```

### Tables MySQL

```sql
-- Table quotes (devis)
quotes (
    id, reference, client_id, user_id,
    titre, localisation, perimetre,
    description_originale, transcription_audio,
    ai_response, quote_data,
    total_ht, total_tva, total_ttc, taux_tva,
    status, created_at, updated_at
)

-- Table clients (données personnelles - JAMAIS envoyées à l'IA)
clients (
    id, user_id,
    civilite, nom, prenom, societe,
    email, telephone,
    adresse_ligne1, code_postal, ville,
    created_at, updated_at
)
```

---

## Étape 7 : Réponse au Frontend

### Données retournées

```json
{
  "success": true,
  "data": {
    "id": 42,
    "reference": "DEV-2026-0042",
    "quote": {
      "chantier": {...},
      "taches": [...],
      "lignes": [
        {
          "designation": "Rénovation électrique complète 120m²",
          "categorie": "forfait",
          "quantite": 120,
          "prix_unitaire_ht": 130,      // ← Prix appliqué
          "total_ligne_ht": 15600       // ← Total calculé
        },
        ...
      ],
      "totaux": {
        "total_ht": 24255,
        "taux_tva": 10,
        "montant_tva": 2425.50,
        "total_ttc": 26680.50
      },
      "questions_a_poser": [...],
      "exclusions": [...]
    },
    "pdf_url": "/pdf/quote/42",
    "client_id": 15,
    "client_nom": "Jean Dupont"
  }
}
```

---

## Étape 8 : Affichage des résultats (Frontend)

### Fichier : `public/js/app.js` → fonction `renderQuoteResult()`

```javascript
// En-tête
elements.quoteTitle.textContent = quote.chantier.titre;
elements.quoteRef.textContent = `Réf: ${data.reference}`;

// Lignes groupées par catégorie
const categories = {
    materiel: { label: 'FOURNITURES', items: [] },
    main_oeuvre: { label: 'MAIN D\'ŒUVRE', items: [] },
    forfait: { label: 'FORFAITS', items: [] }
};

quote.lignes.forEach(ligne => {
    categories[ligne.categorie].items.push(ligne);
});

// Totaux
elements.totalHT.textContent = formatPrice(totaux.total_ht);
elements.totalTTC.textContent = formatPrice(totaux.total_ttc);
```

---

## Résumé du flux de données

```
┌────────────────────────────────────────────────────────────────────┐
│                         UTILISATEUR                                 │
│  Saisit : "Rénovation électrique 120m² avec chauffage et ballon"   │
└────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌────────────────────────────────────────────────────────────────────┐
│                         FRONTEND (app.js)                           │
│  • Capture la description                                           │
│  • Stocke les données client (localStorage)                         │
│  • Envoie POST /api/generate                                        │
└────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌────────────────────────────────────────────────────────────────────┐
│                    BACKEND (ApiController.php)                      │
│  • Reçoit description + client (séparés)                           │
│  • Envoie UNIQUEMENT la description à OpenAI                        │
└────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌────────────────────────────────────────────────────────────────────┐
│                      OPENAI (GPT-4o)                                │
│  • Reçoit : description travaux + system prompt expert              │
│  • Retourne : JSON structuré avec prix_ref_code                     │
│               (pas les prix réels !)                                │
└────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌────────────────────────────────────────────────────────────────────┐
│                   QUOTE CALCULATOR (PHP)                            │
│  • Charge la grille prices.php                                      │
│  • Remplace chaque prix_ref_code par le prix réel                   │
│  • Calcule totaux HT/TVA/TTC                                        │
└────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌────────────────────────────────────────────────────────────────────┐
│                       BASE DE DONNÉES                               │
│  • Sauvegarde client dans table `clients`                           │
│  • Sauvegarde devis dans table `quotes`                             │
│  • Génère référence unique (DEV-2026-XXXX)                          │
└────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌────────────────────────────────────────────────────────────────────┐
│                    RÉPONSE AU FRONTEND                              │
│  • JSON complet avec prix calculés                                  │
│  • URL du PDF généré                                                │
│  • Affichage dans l'interface                                       │
└────────────────────────────────────────────────────────────────────┘
```

---

## Points clés à retenir

### 1. Séparation des données (RGPD)
- **Données client** → Stockées en BDD, jamais envoyées à OpenAI
- **Description travaux** → Envoyée à OpenAI pour analyse

### 2. Système de prix à 3 niveaux
- `price_low` : Entrée de gamme
- `price_mid` : Milieu de gamme (défaut)
- `price_high` : Haut de gamme

### 3. L'IA ne décide pas des prix
- GPT-4 retourne des **codes référence** (`FORFAIT_RENOV_M2`)
- Le **QuoteCalculator** applique les prix de la grille
- Cela permet de modifier les prix sans toucher au prompt

### 4. Structured Outputs
- Le schéma JSON est **strict** (OpenAI garantit le format)
- Pas besoin de parser/valider la réponse
- Champs obligatoires définis dans `quote_schema.php`

### 5. Détermination automatique de la TVA

Le **TvaService** analyse automatiquement la description pour déterminer le taux applicable :

```php
// Appel automatique dans QuoteCalculator
$tvaInfo = $this->tvaService->determinerTva($description, $lignes, $context);
```

#### Taux applicables

| Taux | Condition | Exemple |
|------|-----------|---------|
| **20%** | Construction neuve, bâtiment < 2 ans, local pro | "Installation électrique bâtiment neuf" |
| **10%** | Rénovation logement > 2 ans | "Remplacement tableau appartement" |
| **5.5%** | Travaux amélioration énergétique | "Installation pompe à chaleur", "VMC hygroréglable" |

#### Détection automatique

Le service analyse :
1. **Type de bâtiment** : mots-clés "maison", "appartement" vs "bureau", "commerce"
2. **Ancienneté** : "rénovation", "ancien" vs "neuf", "construction"
3. **Type de travaux** : équipements éligibles TVA 5.5% (PAC, VMC hygro, etc.)

#### API de vérification

```bash
POST /api/check-tva
{
  "description": "Rénovation électrique maison ancienne avec VMC hygroréglable"
}

# Réponse
{
  "taux": 5.5,
  "raison": "Travaux d'amélioration énergétique : vmc hygroreglable",
  "attestation": {
    "nom": "Attestation normale TVA 5.5%",
    "cerfa": "1300-SD"
  }
}
```

#### Attestations obligatoires

- **TVA 10%** : Attestation simplifiée (Cerfa 1301-SD) si montant > 300€
- **TVA 5.5%** : Attestation normale (Cerfa 1300-SD) obligatoire

---

## Fichiers de configuration

| Fichier | Rôle |
|---------|------|
| `config/prices.php` | Grille des prix unitaires (3 gammes) |
| `config/quote_schema.php` | Prompt système + schéma JSON + exemples |
| `config/tva_rules.php` | Règles TVA selon législation française |
| `.env` | Clé API OpenAI |

## Fichiers principaux

| Fichier | Rôle |
|---------|------|
| `public/js/app.js` | Logique frontend, formulaire, appels API |
| `src/Controllers/ApiController.php` | Routes API, orchestration |
| `src/Services/OpenAIClient.php` | Appels OpenAI (Whisper + GPT-4) |
| `src/Services/QuoteCalculator.php` | Application des prix + TVA |
| `src/Services/TvaService.php` | Détermination automatique taux TVA |
| `src/Models/QuoteRepository.php` | Sauvegarde BDD |
