# Spécification Fonctionnelle : TechnicalNeedsGenerator

## 1. Rôle dans le Pipeline

```
WorkTreeBuilder → NormesEngine → [TechnicalNeedsGenerator] → CatalogResolver → DraftQuoteBuilder
                                         ↓
                                  Transforme les travaux
                                  en besoins techniques
                                  quantifiés et typés
```

Le **TechnicalNeedsGenerator** est le pont entre :
- La logique **métier/travaux** (ce qu'on fait)
- La logique **matériel/catalogue** (ce qu'on utilise)

Son rôle : **décomposer chaque sous-travail en besoins techniques concrets, quantifiés et prêts à être résolus par le catalogue**.

---

## 2. Entrées

### 2.1 Input principal

```php
final readonly class TechnicalNeedsInput
{
    public function __construct(
        public NormesEngineOutput $normesOutput,  // Contient le WorkTree enrichi
        public array $contexte,                    // Contexte complet du chantier
        public string $typeChantier,
        public array $options = [],                // gamme_materiel, preferences, etc.
    ) {}
}
```

### 2.2 Données exploitées

| Source | Utilisation |
|--------|-------------|
| `WorkTree.travaux[]` | Liste des travaux à traiter |
| `WorkTreeSousTravail.quantiteFinale` | Quantité de base pour calculer les besoins |
| `WorkTreeSousTravail.parametres` | Paramètres spécifiques (section câble, type prise, etc.) |
| `travaux_definitions.php` | Templates de besoins techniques par sous-travail |
| `contexte` | Variables pour calculs (surface, distances, etc.) |

---

## 3. Sorties

### 3.1 Output principal

```php
final readonly class TechnicalNeedsOutput
{
    public const STATUT_COMPLET = 'complet';
    public const STATUT_PARTIEL = 'partiel';      // Certains besoins non résolus
    public const STATUT_IMPOSSIBLE = 'impossible'; // Données critiques manquantes

    public function __construct(
        public string $statut,
        public array $besoins,                     // BesoinTechnique[]
        public array $besoinsParTravail,           // Groupés par travail
        public array $besoinsParCategorie,         // Groupés par catégorie
        public array $totaux,                      // Totaux par type
        public array $elementsNonResolus,          // Ce qui manque
        public array $statistiques,
    ) {}
}
```

### 3.2 Structure d'un BesoinTechnique

```php
final readonly class BesoinTechnique
{
    public const TYPE_MATERIEL = 'materiel';           // Appareillage, équipement
    public const TYPE_CONSOMMABLE = 'consommable';     // Câble, gaine, visserie
    public const TYPE_MAIN_OEUVRE = 'main_oeuvre';     // Temps de travail
    public const TYPE_FOURNITURE = 'fourniture';       // Petit matériel inclus
    public const TYPE_PRESTATION = 'prestation';       // Service externe (Consuel, etc.)

    public function __construct(
        public string $id,                    // Identifiant unique généré
        public string $type,                  // TYPE_* ci-dessus
        public string $categorie,             // electricite, plomberie, etc.
        public string $code,                  // Code technique (ex: "prise_2p_t")
        public string $label,                 // Libellé humain
        public float $quantite,               // Quantité calculée
        public string $unite,                 // pce, ml, h, m², etc.

        // Traçabilité
        public string $travailId,             // Travail source
        public string $sousTravailId,         // Sous-travail source
        public string $sousTravailInstanceId, // Instance unique

        // Spécifications techniques (pour CatalogResolver)
        public array $specifications,         // Critères de sélection produit

        // Calcul
        public ?string $formuleQuantite,      // Formule utilisée
        public array $contexteCalcul,         // Variables utilisées
    ) {}
}
```

---

## 4. Types de Besoins Techniques

### 4.1 MATERIEL (appareillage principal)

Ce qu'on pose/installe, visible et fonctionnel.

| Exemple sous-travail | Besoin matériel | Spécifications |
|---------------------|-----------------|----------------|
| `pose_prise_standard` | Prise 2P+T | `{type: "2P+T", encastre: true, couleur: "blanc"}` |
| `pose_interrupteur` | Interrupteur simple | `{type: "simple", encastre: true}` |
| `installation_differentiel` | Interrupteur diff 30mA | `{calibre: "40A", type: "A", sensibilite: "30mA"}` |
| `pose_disjoncteur` | Disjoncteur | `{calibre: "16A", courbe: "C"}` |
| `pose_borne_irve` | Borne de recharge | `{puissance: "7.4kW", type: "mono"}` |

### 4.2 CONSOMMABLE (ce qui se "consomme" à l'installation)

Matériaux utilisés pour l'installation, généralement au mètre ou en quantité.

| Exemple sous-travail | Besoin consommable | Calcul quantité |
|---------------------|-------------------|-----------------|
| `tirage_cable_prises` | Câble R2V 3G2.5 | `distance * 1.15` (marge 15%) |
| `pose_gaine_icta` | Gaine ICTA 20mm | `distance * 1.10` |
| `pose_boite_encastrement` | Boîte d'encastrement | `= nb_points` |
| `raccordement_tableau` | Fil H07VK | `nb_circuits * 0.5m` |

### 4.3 MAIN_OEUVRE (temps de travail)

Temps facturé pour réaliser l'installation.

| Exemple sous-travail | Temps unitaire | Formule |
|---------------------|----------------|---------|
| `pose_prise_standard` | 0.5h/prise | `quantite * 0.5` |
| `tirage_cable` | 0.1h/ml | `metres * 0.1` |
| `raccordement_tableau` | 0.25h/circuit | `nb_circuits * 0.25` |
| `mise_en_service` | 1h forfait | `1` |

### 4.4 FOURNITURE (petit matériel inclus)

Matériel inclus dans le prix, non détaillé au client.

| Exemple | Contenu typique |
|---------|-----------------|
| Kit de fixation | Chevilles, vis, colliers |
| Connectique | Wagos, cosses, dominos |
| Consommables divers | Scotch, gaine thermo |

### 4.5 PRESTATION (services externes)

| Exemple | Description |
|---------|-------------|
| Consuel | Attestation de conformité |
| Mise en service IRVE | Paramétrage borne |
| Location nacelle | Si hauteur > 3m |

---

## 5. Exploitation de travaux_definitions.php

### 5.1 Structure enrichie proposée

```php
'pose_prise_standard' => [
    'id' => 'pose_prise_standard',
    'label' => 'Pose prise 2P+T standard',
    'unite' => 'pce',

    // NOUVEAU : Templates de besoins techniques
    'besoins_techniques' => [
        [
            'type' => 'materiel',
            'code' => 'prise_2p_t',
            'label' => 'Prise 2P+T 16A',
            'quantite' => 1,  // Par unité de sous-travail
            'unite' => 'pce',
            'specifications' => [
                'type' => '2P+T',
                'intensite' => '16A',
                'encastrement' => '{encastre:true}',  // Variable contexte
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'cable_2g2_5',
            'label' => 'Câble R2V 3G2.5mm²',
            'quantite' => '{distance_moyenne_point:8}',  // Formule ou défaut
            'unite' => 'ml',
            'specifications' => [
                'section' => '2.5',
                'nb_conducteurs' => 3,
            ],
        ],
        [
            'type' => 'consommable',
            'code' => 'boite_encastrement',
            'label' => 'Boîte d\'encastrement',
            'quantite' => 1,
            'unite' => 'pce',
        ],
        [
            'type' => 'main_oeuvre',
            'code' => 'mo_pose_prise',
            'label' => 'Pose et raccordement',
            'quantite' => 0.5,
            'unite' => 'h',
        ],
    ],
],
```

### 5.2 Variables de contexte utilisables

| Variable | Source | Exemple |
|----------|--------|---------|
| `{quantite}` | Sous-travail | 10 (prises) |
| `{section_cable}` | Paramètres ou NormesEngine | 2.5 |
| `{distance_tableau}` | Contexte | 15m |
| `{distance_moyenne_point}` | Calcul ou défaut | 8m |
| `{nb_circuits}` | Calcul | 12 |
| `{surface}` | Contexte | 100m² |
| `{gamme}` | Options | "standard" / "premium" |

---

## 6. Frontières avec les autres composants

### 6.1 Ce que fait le TechnicalNeedsGenerator

| Responsabilité | Exemple |
|----------------|---------|
| Parcourir le WorkTree | Itérer sur travaux/sous-travaux actifs |
| Appliquer les templates | Lire `besoins_techniques` de chaque sous-travail |
| Calculer les quantités | `quantite_st * template.quantite * multiplicateurs` |
| Résoudre les variables | Remplacer `{section_cable}` par `2.5` |
| Agréger par catégorie | Total câble 2.5mm² = 120ml |
| Identifier les manques | Section non déterminée → ElementNonResolu |

### 6.2 Ce que NE fait PAS le TechnicalNeedsGenerator

| Hors scope | Responsable |
|------------|-------------|
| Choisir un produit spécifique | CatalogResolver |
| Fixer un prix | CatalogResolver / DraftQuoteBuilder |
| Appliquer une marge | DraftQuoteBuilder |
| Valider les normes | NormesEngine (déjà fait) |
| Créer les travaux | WorkTreeBuilder (déjà fait) |

### 6.3 Interface avec CatalogResolver

Le TechnicalNeedsGenerator produit des `BesoinTechnique` avec des **specifications** que le CatalogResolver utilisera pour trouver le produit :

```php
// Besoin généré par TechnicalNeedsGenerator
BesoinTechnique {
    code: "disjoncteur",
    quantite: 8,
    specifications: [
        'calibre' => '16A',
        'courbe' => 'C',
        'nb_poles' => 1,
    ]
}

// CatalogResolver trouve :
ProduitCatalogue {
    reference: "LEG-DNX3-16A",
    marque: "Legrand",
    designation: "Disjoncteur DNX3 16A courbe C",
    prix_unitaire: 12.50,
}
```

### 6.4 Interface avec DraftQuoteBuilder

Le CatalogResolver passe au DraftQuoteBuilder :
- Les produits résolus avec prix
- Les besoins non résolus (à traiter manuellement)
- Les totaux par catégorie

Le DraftQuoteBuilder :
- Structure le devis (sections, sous-sections)
- Applique les marges
- Calcule TVA
- Génère le document final

---

## 7. Algorithme de traitement

```
POUR chaque travail DANS workTree.travaux
    SI travail.actif
        POUR chaque sousTravail DANS travail.sousTravaux
            SI sousTravail.actif ET sousTravail.quantiteFinale > 0

                // 1. Récupérer le template
                template = travauxDefinitions[sousTravail.id].besoins_techniques

                // 2. Pour chaque besoin du template
                POUR chaque besoinTemplate DANS template

                    // 3. Calculer la quantité
                    quantite = evaluerQuantite(
                        besoinTemplate.quantite,
                        sousTravail.quantiteFinale,
                        contexte
                    )

                    // 4. Résoudre les spécifications
                    specs = resoudreSpecifications(
                        besoinTemplate.specifications,
                        sousTravail.parametres,
                        contexte
                    )

                    // 5. Créer le besoin technique
                    besoin = new BesoinTechnique(
                        type: besoinTemplate.type,
                        code: besoinTemplate.code,
                        quantite: quantite,
                        specifications: specs,
                        travailId: travail.id,
                        sousTravailId: sousTravail.id,
                    )

                    // 6. Ajouter aux résultats
                    besoins.add(besoin)

        FIN POUR
    FIN SI
FIN POUR

// 7. Agréger les besoins identiques
besoinsAgreges = agregerBesoins(besoins)

// 8. Calculer les totaux
totaux = calculerTotaux(besoinsAgreges)
```

---

## 8. Gestion des cas particuliers

### 8.1 Besoin non déterminable

```php
// Si section câble non définie
ElementNonResolu {
    code: 'section_cable_prises',
    type: 'specification_manquante',
    besoinCode: 'cable_r2v',
    champsManquants: ['section'],
    impact: 'Quantité câble non calculable',
    suggestion: 'Utiliser section par défaut 2.5mm²',
}
```

### 8.2 Agrégation des besoins identiques

Deux besoins de câble 2.5mm² de sous-travaux différents → un seul besoin agrégé :

```php
// Avant agrégation
[
    {code: 'cable_2g2_5', quantite: 40, source: 'prises_salon'},
    {code: 'cable_2g2_5', quantite: 25, source: 'prises_chambre'},
]

// Après agrégation
{
    code: 'cable_2g2_5',
    quantite: 65,
    sources: ['prises_salon', 'prises_chambre'],
    detail: [{source: 'prises_salon', quantite: 40}, ...],
}
```

### 8.3 Besoins conditionnels

```php
'besoins_techniques' => [
    [
        'type' => 'materiel',
        'code' => 'boite_etanche',
        'condition' => ['type' => 'context_has', 'champ' => 'exterieur'],
        // Seulement si installation extérieure
    ],
]
```

---

## 9. Structure des fichiers proposée

```
src/PipelineV2/TechnicalNeedsGenerator/
├── TechnicalNeedsGeneratorInterface.php
├── TechnicalNeedsGenerator.php
├── QuantityCalculator.php          # Calcul des quantités
├── SpecificationResolver.php       # Résolution des specs
├── NeedsAggregator.php             # Agrégation des besoins
├── DTO/
│   ├── TechnicalNeedsInput.php
│   ├── TechnicalNeedsOutput.php
│   ├── BesoinTechnique.php
│   ├── BesoinAgrege.php
│   └── ElementNonResolu.php
└── Exception/
    ├── TechnicalNeedsException.php
    └── QuantityCalculationException.php
```

---

## 10. Questions ouvertes

1. **Granularité des besoins** : Un besoin par ligne de sous-travail ou agrégation systématique ?

2. **Gestion des gammes** : Comment différencier standard/premium dans les templates ?

3. **Main d'œuvre** : Temps unitaire par sous-travail ou calcul global ?

4. **Forfaits** : Certains travaux sont forfaitaires (mise en service) - comment les traiter ?

5. **Consommables inclus** : Certains petits matériels sont inclus dans le prix MO - les lister quand même ?
