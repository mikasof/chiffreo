# Chiffreo - Documentation Technique et Fonctionnelle

## Vue d'ensemble

Chiffreo est une application PWA permettant aux électriciens de générer des devis professionnels par la voix grâce à l'IA.

---

## 1. Architecture Données

### Structure Company → Users

```
┌─────────────────────────────────────────────────────────────┐
│                         COMPANY                              │
│  - Plan (découverte, pro, équipe)                           │
│  - Période d'essai (trial_ends_at)                          │
│  - Quota mensuel (quotes_this_month)                        │
│  - Infos entreprise (SIRET, adresse, logo...)              │
├─────────────────────────────────────────────────────────────┤
│                          USERS                               │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐                     │
│  │  Owner  │  │  Admin  │  │ Member  │  (plan Équipe)      │
│  └─────────┘  └─────────┘  └─────────┘                     │
└─────────────────────────────────────────────────────────────┘
```

**Principe clé** : Le plan, l'essai gratuit et les quotas appartiennent à la **COMPANY**, pas aux utilisateurs individuels.

### Tables principales

| Table | Description |
|-------|-------------|
| `companies` | Entreprises avec leur plan et quotas |
| `users` | Utilisateurs liés à une company |
| `user_sessions` | Sessions d'authentification (token) |
| `quotes` | Devis générés |
| `quote_items` | Lignes de devis |
| `invitations` | Invitations pour rejoindre une équipe |

---

## 2. Les Plans

### Plan Découverte (Gratuit)

| Caractéristique | Valeur |
|-----------------|--------|
| **Prix** | 0 € / mois |
| **Devis par mois** | 10 |
| **Utilisateurs** | 1 |
| **Fonctionnalités** | Transcription vocale, génération IA, PDF basique |

**Limitations** :
- Compteur remis à zéro chaque 1er du mois
- Pas de personnalisation du PDF (logo, mentions)
- Pas d'historique au-delà de 30 jours

### Plan Pro

| Caractéristique | Valeur |
|-----------------|--------|
| **Prix** | 29 € / mois |
| **Devis par mois** | Illimité |
| **Utilisateurs** | 1 |
| **Fonctionnalités** | Tout Découverte + logo, historique complet, export |

**Avantages** :
- PDF personnalisé avec logo entreprise
- Historique illimité
- Export CSV/Excel des devis
- Support prioritaire

### Plan Équipe

| Caractéristique | Valeur |
|-----------------|--------|
| **Prix** | 49 € / mois |
| **Devis par mois** | Illimité |
| **Utilisateurs** | Jusqu'à 5 |
| **Fonctionnalités** | Tout Pro + multi-utilisateurs |

**Avantages supplémentaires** :
- Inviter des collaborateurs
- Rôles (owner, admin, member)
- Tous les devis partagés dans la company
- Gestion des permissions

---

## 3. Période d'Essai (Trial)

### Fonctionnement

```
Inscription → Plan "pro" automatique → 14 jours d'essai gratuit
```

| Champ | Valeur à l'inscription |
|-------|------------------------|
| `companies.plan` | `'pro'` |
| `companies.trial_ends_at` | `NOW() + 14 jours` |

### Calculs côté serveur

```php
// CompanyRepository::formatWithTrialInfo()
$trialEndsAt = strtotime($company['trial_ends_at']);
$now = time();

$company['trial_active'] = $trialEndsAt > $now;
$company['days_remaining'] = max(0, ceil(($trialEndsAt - $now) / 86400));
```

### Fin de l'essai

Quand `trial_ends_at < NOW()` :
- Si pas d'abonnement payant → bascule automatique vers plan `découverte`
- Le quota de 10 devis/mois s'applique

---

## 4. Système de Quotas

### Vérification (QuotaService)

```php
public function canCreateQuote(array $user): bool
{
    $company = $user['company'];

    // En période d'essai → illimité
    if ($company['trial_active']) {
        return true;
    }

    // Plan pro ou équipe → illimité
    if (in_array($company['plan'], ['pro', 'equipe'])) {
        return true;
    }

    // Plan découverte → max 10/mois
    if ($company['plan'] === 'decouverte') {
        return $company['quotes_this_month'] < 10;
    }

    return false;
}
```

### Compteur mensuel

| Champ | Description |
|-------|-------------|
| `quotes_this_month` | Nombre de devis créés ce mois |
| `quotes_month_reset` | Mois du dernier reset (format `YYYY-MM`) |

**Reset automatique** : Si `quotes_month_reset !== mois actuel`, le compteur repasse à 0.

---

## 5. Inscription et Authentification

### Flow d'inscription

```
1. Utilisateur remplit email + mot de passe
                    ↓
2. Création COMPANY (plan=pro, trial=14j)
                    ↓
3. Création USER (role=owner, lié à company)
                    ↓
4. Création SESSION (token 64 caractères)
                    ↓
5. Redirection → Onboarding
```

### Données créées à l'inscription

**Company** :
```sql
INSERT INTO companies (name, plan, trial_ends_at)
VALUES (NULL, 'pro', DATE_ADD(NOW(), INTERVAL 14 DAY));
```

**User** :
```sql
INSERT INTO users (company_id, email, password_hash, role, onboarding_step)
VALUES (:company_id, :email, :hash, 'owner', 0);
```

### Authentification par token

```
Authorization: Bearer <token_64_caractères>
```

Le token est :
- Stocké dans `localStorage` (clé `chiffreo_token`)
- Validé via `SessionRepository::validateToken()`
- Expire après 30 jours d'inactivité

### Structure de la réponse `/api/auth/me`

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "jean@example.com",
      "first_name": "Jean",
      "last_name": "Dupont",
      "role": "owner",
      "onboarding_completed": true,
      "company": {
        "id": 1,
        "name": "Dupont Électricité",
        "plan": "pro",
        "trial_active": true,
        "days_remaining": 12,
        "quotes_this_month": 3,
        "profile_completed": true
      }
    }
  }
}
```

---

## 6. Onboarding (4 étapes)

### Étape 1 : Bienvenue
- Sélection du métier (électricien, plombier, etc.)
- Volume de devis mensuel estimé

### Étape 2 : Infos entreprise
- Nom de l'entreprise (obligatoire)
- SIRET (optionnel)
- Téléphone (optionnel)

### Étape 3 : Premier devis démo
- Test de la transcription vocale
- Génération d'un devis exemple
- Découverte de l'interface

### Étape 4 : Installation PWA
- Proposition d'installer l'app
- Activation des notifications push

### Progression sauvegardée

```php
// Champ users.onboarding_step
0 = pas commencé
1 = étape 1 complétée
2 = étape 2 complétée
3 = étape 3 complétée
4 = terminé (onboarding_completed = true)
```

---

## 7. Génération de Devis

### Flow complet

```
┌──────────────────┐
│  Enregistrement  │ ← Microphone navigateur
│      vocal       │
└────────┬─────────┘
         ↓
┌──────────────────┐
│   Transcription  │ ← OpenAI Whisper API
│      (STT)       │
└────────┬─────────┘
         ↓
┌──────────────────┐
│   Analyse IA     │ ← GPT-4 avec prompt métier
│   + Chiffrage    │
└────────┬─────────┘
         ↓
┌──────────────────┐
│  Sauvegarde BDD  │ ← quotes + quote_items
└────────┬─────────┘
         ↓
┌──────────────────┐
│  Génération PDF  │ ← TCPDF/FPDF
└──────────────────┘
```

### Données stockées par devis

| Champ | Description |
|-------|-------------|
| `reference` | Numéro unique (ex: DEV-2024-001234) |
| `company_id` | Entreprise propriétaire |
| `user_id` | Utilisateur créateur |
| `client_name` | Nom du client |
| `client_email` | Email client |
| `client_phone` | Téléphone client |
| `description_originale` | Texte saisi/transcrit |
| `ai_response` | Réponse brute de l'IA (JSON) |
| `quote_data` | Devis calculé complet (JSON) |
| `total_ht` | Total HT en euros |
| `total_ttc` | Total TTC en euros |
| `status` | draft, sent, accepted, rejected |

---

## 8. Rôles Utilisateurs (Plan Équipe)

### Permissions par rôle

| Action | Owner | Admin | Member |
|--------|:-----:|:-----:|:------:|
| Créer des devis | ✅ | ✅ | ✅ |
| Voir tous les devis | ✅ | ✅ | ✅ |
| Modifier infos entreprise | ✅ | ✅ | ❌ |
| Inviter des membres | ✅ | ✅ | ❌ |
| Changer le plan | ✅ | ❌ | ❌ |
| Supprimer la company | ✅ | ❌ | ❌ |

### Invitation de membres

```
1. Owner/Admin crée une invitation (email)
                    ↓
2. Email envoyé avec lien unique
                    ↓
3. Invité clique → page d'inscription spéciale
                    ↓
4. Création user avec role=member, même company_id
```

Table `invitations` :
```sql
id, company_id, email, token, role, invited_by, expires_at, accepted_at
```

---

## 9. Stockage et Sécurité

### Données sensibles

| Donnée | Stockage | Chiffrement |
|--------|----------|-------------|
| Mot de passe | BDD | bcrypt (cost 12) |
| Token session | BDD | Hash SHA-256 |
| Données client | BDD | Non (RGPD: droit suppression) |
| Audio | Temporaire | Supprimé après transcription |

### RGPD

- Les données client sont stockées **directement sur le devis**, pas dans une table clients séparée
- Chaque devis est autonome et peut être supprimé individuellement
- Export des données personnelles possible
- Suppression compte = suppression company + tous users + tous devis

---

## 10. API Endpoints

### Authentification

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/auth/register` | Inscription |
| POST | `/api/auth/login` | Connexion |
| POST | `/api/auth/logout` | Déconnexion |
| GET | `/api/auth/me` | Infos utilisateur + company |
| POST | `/api/auth/onboarding` | Sauvegarder progression |

### Devis

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/transcribe` | Transcrire audio → texte |
| POST | `/api/generate` | Générer un devis |
| GET | `/api/quotes` | Lister les devis |
| GET | `/api/quotes/:id` | Détail d'un devis |
| GET | `/pdf/quote/:id` | Télécharger PDF |

### Company (Plan Équipe)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| PUT | `/api/company` | Modifier infos entreprise |
| POST | `/api/company/invite` | Inviter un membre |
| GET | `/api/company/members` | Lister les membres |
| DELETE | `/api/company/members/:id` | Retirer un membre |

---

## 11. Frontend - États UI

### Badge Plan (header)

```javascript
if (company.trial_active) {
    badge = "PRO - 12j"  // Jours restants
    class = "trial"
}
else if (company.plan === 'pro') {
    badge = "PRO"
    class = "pro"
}
else if (company.plan === 'equipe') {
    badge = "ÉQUIPE"
    class = "pro"
}
else {
    badge = "DÉCOUVERTE"
    class = "free"
}
```

### Barre de quota (plan découverte uniquement)

```javascript
if (company.plan === 'decouverte' && !company.trial_active) {
    // Afficher: "3/10 devis ce mois"
    width = (quotes_this_month / 10) * 100 + "%"

    if (quotes_this_month >= 8) {
        class = "warning"  // Orange
    }
}
```

---

## 12. Fichiers Clés

### Backend (PHP)

```
src/
├── Controllers/
│   ├── AuthController.php      # Inscription, connexion, onboarding
│   └── ApiController.php       # Transcription, génération devis
├── Models/
│   ├── CompanyRepository.php   # CRUD companies + quotas
│   ├── UserRepository.php      # CRUD users (JOIN company)
│   ├── SessionRepository.php   # Tokens + validation
│   └── QuoteRepository.php     # CRUD devis
├── Services/
│   ├── OpenAIClient.php        # Whisper + GPT-4
│   ├── QuotaService.php        # Vérification quotas
│   └── QuoteCalculator.php     # Calcul prix + TVA
└── Middleware/
    └── AuthMiddleware.php      # Validation token + permissions
```

### Frontend (JS)

```
public/js/
├── app.js          # Application principale
├── auth.js         # Login/Register
└── onboarding.js   # Parcours onboarding
```

---

## 13. Variables d'environnement

```env
# Base de données
DB_HOST=localhost
DB_NAME=chiffreo
DB_USER=root
DB_PASS=

# OpenAI
OPENAI_API_KEY=sk-...

# Application
APP_ENV=development
APP_URL=http://localhost/chiffreo/public

# Push notifications (optionnel)
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
```

---

## Résumé des règles métier

1. **Inscription** → Crée company (plan pro, trial 14j) + user (owner)
2. **Trial** → 14 jours, accès illimité, calculé via `trial_ends_at`
3. **Quota** → Géré au niveau company, pas user
4. **Multi-users** → Plan Équipe uniquement, via invitations
5. **Devis** → Appartient à une company, créé par un user
6. **Données client** → Stockées sur le devis, pas de table clients
