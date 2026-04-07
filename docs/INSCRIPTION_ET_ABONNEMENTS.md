# Processus d'inscription et gestion des abonnements

## Vue d'ensemble

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   INSCRIPTION   │────▶│  PÉRIODE D'ESSAI │────▶│  FIN D'ESSAI    │
│   (Register)    │     │   14 jours PRO   │     │  (Downgrade?)   │
└─────────────────┘     └──────────────────┘     └─────────────────┘
        │                        │                       │
        ▼                        ▼                       ▼
   Création de:            Accès complet:          2 options:
   - Company (plan=pro)    - Devis illimités       - Passer en payant
   - User (role=owner)     - 10 images/devis       - Plan découverte
   - Session (30 jours)    - 5 min audio             (10 devis/mois)
```

---

## 1. Inscription (Register)

### Endpoint
```
POST /api/auth/register
```

### Données requises
| Champ | Type | Validation |
|-------|------|------------|
| `firstName` | string | Requis, max 100 caractères |
| `email` | string | Requis, format email valide, unique |
| `password` | string | Requis, min 8 caractères |

### Ce qui est créé

#### A) Table `companies`
```sql
INSERT INTO companies (
    plan,                -- 'pro' (toujours Pro au départ)
    trial_ends_at,       -- NOW() + 14 jours
    quotes_this_month,   -- 0
    quotes_month_reset,  -- 1er du mois courant
    default_tva_rate,    -- 20.00
    quote_validity_days, -- 30
    profile_completed    -- FALSE
)
```

#### B) Table `users`
```sql
INSERT INTO users (
    company_id,          -- ID de la company créée
    first_name,          -- Prénom fourni
    email,               -- Email fourni
    password_hash,       -- Bcrypt cost 12
    role,                -- 'owner'
    onboarding_step,     -- 0
    onboarding_completed,-- FALSE
    is_active            -- TRUE
)
```

#### C) Table `user_sessions`
```sql
INSERT INTO user_sessions (
    user_id,             -- ID du user créé
    token,               -- 64 caractères hex (random_bytes)
    expires_at,          -- NOW() + 30 jours
    device_type,         -- 'desktop', 'mobile', 'tablet'
    ip_address,          -- IP du client
    user_agent           -- User-Agent du navigateur
)
```

### Réponse
```json
{
    "success": true,
    "redirect": "/onboarding",
    "token": "abc123...xyz789"
}
```

---

## 2. Période d'essai (Trial)

### Configuration
```php
// AuthController.php
private const TRIAL_DAYS = 14;  // Durée de l'essai
```

### Stockage
- **Niveau** : Company (tous les users d'une company partagent l'essai)
- **Champ** : `companies.trial_ends_at` (DATETIME)

### Calcul du statut
```php
// CompanyRepository.php
$trialActive = $trialEndsAt && strtotime($trialEndsAt) > time();
$daysRemaining = ceil((strtotime($trialEndsAt) - time()) / 86400);
```

### Fonctionnalités pendant l'essai
| Fonctionnalité | Valeur |
|----------------|--------|
| Devis par mois | **Illimité** |
| Images par devis | 10 |
| Durée audio | 5 minutes |
| Filigrane PDF | Non |
| Export | PDF, XLSX, CSV |

---

## 3. Plans d'abonnement

### Les 3 plans disponibles

| Plan | Code | Devis/mois | Images | Audio | Filigrane | Export |
|------|------|------------|--------|-------|-----------|--------|
| **Découverte** | `decouverte` | 10 | 2 | 1 min | Oui | PDF |
| **Pro** | `pro` | Illimité | 10 | 5 min | Non | PDF, XLSX, CSV |
| **Équipe** | `equipe` | Illimité | 20 | 10 min | Non | PDF, XLSX, CSV, JSON |

### Plan effectif
```php
// QuotaService.php
public function getEffectivePlan(array $company): string
{
    // Pendant l'essai : fonctionnalités Pro
    if ($company['trial_ends_at'] && strtotime($company['trial_ends_at']) > time()) {
        return 'pro';
    }
    // Sinon : le plan enregistré
    return $company['plan'];
}
```

### Quotas définis
```php
// QuotaService.php
private array $planLimits = [
    'decouverte' => [
        'quotes_per_month' => 10,
        'images_per_quote' => 2,
        'audio_duration' => 60,      // secondes
        'pdf_watermark' => true,
        'export_formats' => ['pdf']
    ],
    'pro' => [
        'quotes_per_month' => null,  // illimité
        'images_per_quote' => 10,
        'audio_duration' => 300,     // 5 min
        'pdf_watermark' => false,
        'export_formats' => ['pdf', 'xlsx', 'csv']
    ],
    'equipe' => [
        'quotes_per_month' => null,
        'images_per_quote' => 20,
        'audio_duration' => 600,     // 10 min
        'pdf_watermark' => false,
        'export_formats' => ['pdf', 'xlsx', 'csv', 'json']
    ]
];
```

---

## 4. Fin de l'essai

### Ce qui se passe
```
Essai actif (14 jours)
         │
         ▼
    Essai expiré
         │
         ├──▶ Utilisateur a payé? ──▶ Reste sur Pro/Équipe
         │
         └──▶ Pas de paiement ──▶ Plan = 'decouverte'
                                  (10 devis/mois max)
```

### Vérification des quotas
```php
// CompanyRepository.php
public function getQuotesRemaining(int $id): int|string
{
    // Essai actif = illimité
    if ($company['trial_ends_at'] && strtotime($company['trial_ends_at']) > time()) {
        return 'unlimited';
    }

    // Plans payants = illimité
    if (in_array($company['plan'], ['pro', 'equipe'])) {
        return 'unlimited';
    }

    // Plan découverte : 10 - utilisés
    return max(0, 10 - $quotesThisMonth);
}
```

### Remise à zéro mensuelle
```php
// CompanyRepository.php - incrementQuoteCount()
$currentMonth = date('Y-m-01');  // Premier du mois

if ($company['quotes_month_reset'] !== $currentMonth) {
    // Nouveau mois : reset du compteur
    $stmt = $this->db->prepare("
        UPDATE companies
        SET quotes_this_month = 1,
            quotes_month_reset = ?
        WHERE id = ?
    ");
    $stmt->execute([$currentMonth, $id]);
}
```

---

## 5. Upgrade vers plan payant

### État actuel
> **ATTENTION** : Pas d'intégration paiement pour le moment (MVP)

### Endpoint prévu
```
GET /pricing  →  Page de tarification (à créer)
```

### Logique future
```php
// À implémenter avec Stripe ou autre
1. Utilisateur choisit un plan
2. Redirection vers Stripe Checkout
3. Webhook Stripe confirme le paiement
4. UPDATE companies SET plan = 'pro' WHERE id = ?
5. trial_ends_at peut rester ou être mis à NULL
```

### Message quota dépassé
```php
// AuthMiddleware.php
if ($quotasRemaining === 0) {
    return [
        'error' => 'Quota de devis atteint',
        'upgrade_url' => '/pricing'  // ← Redirige ici
    ];
}
```

---

## 6. API Quota

### Endpoint
```
GET /api/auth/quota
```

### Réponse
```json
{
    "success": true,
    "quota": {
        "plan": "pro",
        "trial_active": true,
        "days_remaining": 10,
        "quotes_this_month": 2,
        "quotes_remaining": "unlimited",
        "limits": {
            "quotes_per_month": null,
            "images_per_quote": 10,
            "audio_duration": 300,
            "pdf_watermark": false,
            "export_formats": ["pdf", "xlsx", "csv"]
        }
    }
}
```

---

## 7. Schéma de la base de données

### Table `companies`
```sql
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    siret VARCHAR(14),
    phone VARCHAR(20),
    address TEXT,
    logo_url VARCHAR(500),

    -- Abonnement
    plan ENUM('decouverte', 'pro', 'equipe') DEFAULT 'pro',
    trial_ends_at DATETIME,

    -- Quotas
    quotes_this_month INT DEFAULT 0,
    quotes_month_reset DATE,

    -- Paramètres
    default_tva_rate DECIMAL(5,2) DEFAULT 20.00,
    quote_validity_days INT DEFAULT 30,
    profile_completed BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Table `users`
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role ENUM('owner', 'admin', 'member') DEFAULT 'member',

    -- Onboarding
    onboarding_step INT DEFAULT 0,
    onboarding_completed BOOLEAN DEFAULT FALSE,

    -- Tarification (configurée à l'onboarding)
    hourly_rate DECIMAL(10,2) DEFAULT 70.00,
    product_margin DECIMAL(5,2) DEFAULT 20.00,
    supplier_discount DECIMAL(5,2) DEFAULT 0,
    travel_type ENUM('free', 'fixed', 'per_km') DEFAULT 'fixed',
    travel_fixed_amount DECIMAL(10,2) DEFAULT 30.00,
    travel_per_km DECIMAL(5,2) DEFAULT 0.50,
    travel_free_radius INT DEFAULT 20,

    -- État
    is_active BOOLEAN DEFAULT TRUE,
    pwa_installed BOOLEAN DEFAULT FALSE,
    notifications_enabled BOOLEAN DEFAULT FALSE,
    first_quote_done BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (company_id) REFERENCES companies(id)
);
```

### Table `user_sessions`
```sql
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    device_type VARCHAR(20),
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at DATETIME NOT NULL,
    last_activity_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 8. Diagramme de flux complet

```
┌─────────────────────────────────────────────────────────────────────┐
│                        PARCOURS UTILISATEUR                         │
└─────────────────────────────────────────────────────────────────────┘

[Page /auth.html]
      │
      ├── Onglet "Créer un compte"
      │         │
      │         ▼
      │   ┌─────────────┐
      │   │  Formulaire │
      │   │  - Prénom   │
      │   │  - Email    │
      │   │  - Password │
      │   └──────┬──────┘
      │          │
      │          ▼
      │   POST /api/auth/register
      │          │
      │          ├── Validation OK?
      │          │         │
      │          │    Non ─┴─▶ Erreur affichée
      │          │
      │          ▼
      │   ┌─────────────────────┐
      │   │  CRÉATION EN BDD    │
      │   │  - Company (pro)    │
      │   │  - User (owner)     │
      │   │  - Session (30j)    │
      │   └──────────┬──────────┘
      │              │
      │              ▼
      │   Redirect → /onboarding
      │              │
      │              ▼
      │   ┌─────────────────────────────────────┐
      │   │         ONBOARDING (4 étapes)       │
      │   ├─────────────────────────────────────┤
      │   │ 1. Métier (électricien, plombier)   │
      │   │ 2. Entreprise + tarification        │
      │   │ 3. Premier devis test               │
      │   │ 4. Installation PWA                 │
      │   └──────────────────┬──────────────────┘
      │                      │
      │                      ▼
      │            Redirect → /app
      │                      │
      │    ┌─────────────────┴─────────────────┐
      │    │                                   │
      │    ▼                                   ▼
      │  ESSAI ACTIF (14j)              ESSAI EXPIRÉ
      │    │                                   │
      │    │ Plan effectif = 'pro'             │
      │    │ Devis illimités                   │
      │    │                                   │
      │    │                            ┌──────┴──────┐
      │    │                            │             │
      │    │                            ▼             ▼
      │    │                      A PAYÉ?         PAS PAYÉ
      │    │                         │                │
      │    │                    Plan 'pro'      Plan 'decouverte'
      │    │                    ou 'equipe'     10 devis/mois
      │    │                                         │
      │    │                                         ▼
      │    │                                   Popup "Upgrade"
      │    │                                         │
      │    │                                         ▼
      │    │                                   /pricing (TODO)
      │    │
      │    └─────────────────────────────────────────┘
      │
      └── Onglet "Se connecter"
                │
                ▼
          POST /api/auth/login
                │
                ▼
          Session créée (30j)
                │
                ▼
          Redirect → /app ou /onboarding
```

---

## 9. Points d'attention pour le développement

### À implémenter

1. **Intégration paiement** (Stripe recommandé)
   - Checkout pour upgrade
   - Webhooks pour confirmation
   - Gestion des abonnements récurrents

2. **Page /pricing**
   - Comparatif des plans
   - Boutons d'upgrade
   - FAQ

3. **Gestion annulation**
   - Downgrade vers découverte
   - Conservation des données

4. **Emails transactionnels**
   - Confirmation inscription
   - Rappel fin d'essai (J-3, J-1)
   - Confirmation upgrade

### Sécurité en place

- Mots de passe : Bcrypt cost 12
- Rate limiting : 5 tentatives inscription, 10 tentatives login
- Sessions : Token 64 caractères, expiration 30 jours
- Validation : Tous les champs sanitisés

---

## 10. Fichiers clés

| Fichier | Rôle |
|---------|------|
| `src/Controllers/AuthController.php` | Inscription, login, onboarding |
| `src/Models/CompanyRepository.php` | Gestion company et quotas |
| `src/Services/QuotaService.php` | Définition des limites par plan |
| `src/Middleware/AuthMiddleware.php` | Vérification auth et quotas |
| `database/migrations/001_create_companies.sql` | Structure table companies |
| `database/migrations/002_create_users.sql` | Structure table users |
| `public/auth.html` | Formulaire inscription/connexion |
| `public/onboarding.html` | Parcours onboarding |
