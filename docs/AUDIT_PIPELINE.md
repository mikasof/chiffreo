# Audit Pipeline de Génération de Devis

**Date :** 2026-04-06
**Objectif :** Identifier et supprimer le code legacy (V1) pour ne conserver que le pipeline V2
**Statut :** ✅ NETTOYAGE EFFECTUÉ

---

## Résumé

| Pipeline | Statut | Utilisé par le frontend |
|----------|--------|-------------------------|
| V1 (`/api/generate`) | ✅ **SUPPRIMÉ** | Non |
| V2 (`/api/generate-v2`) | **ACTIF** | Oui |

---

## Pipeline V1 (SUPPRIMÉ ✅)

### Endpoint
- **Route :** `POST /api/generate`
- **Fichier :** `index.php` lignes 414-417
- **Controller :** `ApiController::generate()` ligne 213

### Fichiers utilisés uniquement par V1

| Fichier | Description | Action |
|---------|-------------|--------|
| `config/quote_schema.php` | Prompt système + schéma JSON pour V1 | **SUPPRIMER** |
| `OpenAIClient::generateQuoteJson()` | Méthode de génération V1 (ligne 153) | **SUPPRIMER** |
| `ApiController::generate()` | Endpoint V1 (ligne 213) | **SUPPRIMER** |

### Code à supprimer dans `OpenAIClient.php`

```php
// Lignes 145-350 environ - méthode generateQuoteJson()
public function generateQuoteJson(
    string $description,
    ?string $transcription = null,
    array $imageUrls = []
): array {
    // ... tout ce bloc
}
```

### Code à supprimer dans `ApiController.php`

```php
// Lignes 213-408 environ - méthode generate()
public function generate(): void {
    // ... tout ce bloc
}
```

### Code à supprimer dans `index.php`

```php
// Lignes 414-417
if ($uri === '/api/generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new ApiController();
    $controller->generate();
    exit;
}
```

---

## Pipeline V2 (ACTIF - À CONSERVER)

### Endpoint
- **Route :** `POST /api/generate-v2`
- **Fichier :** `index.php` lignes 421-424
- **Controller :** `ApiController::generateV2()` ligne 410

### Fichiers du Pipeline V2

| Fichier | Description |
|---------|-------------|
| `config/quote_prompt_v2.php` | Prompt système V2 avec schéma JSON intégré |
| `config/pipeline_v2/*.php` | Configuration des règles métier |
| `src/PipelineV2/**/*.php` | Moteur de règles et DTOs |
| `src/Services/MaterialEstimator.php` | Estimateur de matériaux |
| `src/Services/QuoteCalculator.php` | Calculateur de prix (utilisé par les deux, mais essentiel) |
| `OpenAIClient::generateQuoteV2()` | Méthode de génération V2 (ligne 550) |

### Configuration Pipeline V2

```
config/pipeline_v2/
├── chantier_types.php          # Types de chantiers
├── normes_rules.php            # Règles NFC 15-100
├── technical_needs_details.php # Détails besoins techniques
├── technical_needs_rules.php   # Règles besoins techniques
└── travaux_definitions.php     # Définitions des travaux
```

### Services V2

```
src/PipelineV2/
├── NormesEngine/               # Moteur d'application des normes
│   ├── DTO/                    # Data Transfer Objects
│   ├── Exception/              # Exceptions spécifiques
│   ├── NormesEngine.php        # Moteur principal
│   ├── NormesRuleApplier.php   # Application des règles
│   └── NormesRuleEvaluator.php # Évaluation des règles
├── TechnicalNeedsGenerator/    # Générateur de besoins techniques
│   └── DTO/
└── WorkTreeBuilder/            # Construction de l'arbre de travaux
    ├── DTO/
    ├── Exception/
    ├── ConditionEvaluator.php
    └── WorkTreeBuilder.php
```

---

## Services partagés (À CONSERVER)

Ces services sont utilisés par V2 et d'autres parties de l'application :

| Service | Utilisé par |
|---------|-------------|
| `QuoteCalculator.php` | V1, V2, édition devis |
| `TvaService.php` | V1, V2, vérification TVA |
| `NormsService.php` | V1, V2, vérification normes |
| `MaterialEstimator.php` | V2 uniquement |
| `QuestionGeneratorService.php` | V2 (questions contextuelles) |
| `ProductSearchService.php` | V2 (recherche prix) |

---

## Frontend

Le frontend utilise **uniquement V2** :

```javascript
// js/app.js ligne 801
const response = await fetch(BASE_PATH + '/api/generate-v2', {
```

---

## Plan de migration

### Étape 1 : Vérification (avant suppression)

1. Confirmer qu'aucune autre partie du code n'appelle `/api/generate`
2. Vérifier les tests qui utilisent V1

### Étape 2 : Suppression du code V1

1. **`index.php`** : Supprimer la route `/api/generate`
2. **`ApiController.php`** : Supprimer la méthode `generate()`
3. **`OpenAIClient.php`** : Supprimer la méthode `generateQuoteJson()`
4. **`config/quote_schema.php`** : Supprimer le fichier

### Étape 3 : Nettoyage documentation

1. Mettre à jour `README.md` (références à quote_schema.php)
2. Mettre à jour `docs/GENERATION_DEVIS.md`
3. Mettre à jour `docs/PROCESSUS_GENERATION_DEVIS.md`

### Étape 4 : Tests

1. Mettre à jour `tests/smoke_test.php` (test du schema V1)
2. Exécuter tous les tests pour confirmer

---

## Dépendances à vérifier avant suppression

### Fichiers qui référencent `quote_schema.php`

| Fichier | Ligne | Action |
|---------|-------|--------|
| `OpenAIClient.php` | 165 | Supprimer avec generateQuoteJson |
| `README.md` | 342, 404 | Mettre à jour doc |
| `docs/GENERATION_DEVIS.md` | 140, 156, 527, 584 | Mettre à jour doc |
| `docs/PROCESSUS_GENERATION_DEVIS.md` | 57, 231, 340 | Mettre à jour doc |
| `tests/smoke_test.php` | 90, 92 | Adapter ou supprimer test |

---

## Commandes pour vérifier avant suppression

```bash
# Vérifier les appels à /api/generate (sans -v2)
grep -r "api/generate[^-]" --include="*.js" --include="*.php" .

# Vérifier les références à generateQuoteJson
grep -r "generateQuoteJson" --include="*.php" .

# Vérifier les références à quote_schema
grep -r "quote_schema" --include="*.php" --include="*.md" .
```

---

## Risques

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Tests cassés | Moyenne | Faible | Adapter les tests |
| Documentation obsolète | Haute | Faible | Mettre à jour la doc |
| Appels API externes | Faible | Moyen | Vérifier les intégrations |

---

## Conclusion

Le pipeline V1 peut être supprimé en toute sécurité car :
- Le frontend utilise exclusivement V2
- V1 et V2 sont indépendants
- Les services partagés restent fonctionnels
