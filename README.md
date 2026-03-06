# Chiffreo MVP

Générateur de devis électrique intelligent propulsé par l'IA OpenAI.

## Fonctionnalités

- **Saisie vocale** : Dictez votre description de chantier, l'IA transcrit automatiquement
- **Génération IA** : Analyse intelligente des besoins et création du devis structuré
- **Calcul automatique** : Application de la grille tarifaire et calcul HT/TVA/TTC
- **Export PDF** : Génération de devis PDF professionnels
- **PWA** : Application installable sur mobile et desktop

## Stack technique

- PHP 8.2+
- MySQL 8
- Composer (vlucas/phpdotenv, guzzlehttp/guzzle, dompdf/dompdf)
- OpenAI API (Whisper + GPT-4)
- PWA (Service Worker, Web App Manifest)

---

## Installation

### 1. Prérequis

- PHP 8.2 ou supérieur
- MySQL 8.0 ou supérieur
- Composer
- Clé API OpenAI

### 2. Cloner ou copier le projet

```bash
# macOS / Linux
cd /chemin/vers/votre/dossier
git clone <repo> chiffreo
cd chiffreo

# Ou créer manuellement
mkdir -p chiffreo && cd chiffreo
```

### 3. Installer les dépendances

```bash
composer install
```

### 4. Configuration

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Éditer le fichier .env avec vos paramètres
nano .env  # ou code .env, vim .env, etc.
```

Contenu du `.env` :
```env
# Base de données MySQL
DB_HOST=localhost
DB_PORT=3306
DB_NAME=chiffreo
DB_USER=root
DB_PASS=votre_mot_de_passe

# OpenAI API (OBLIGATOIRE)
OPENAI_API_KEY=sk-votre-cle-api-openai

# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Limites
RATE_LIMIT_PER_MINUTE=30
MAX_UPLOAD_SIZE_MB=10
```

### 5. Créer la base de données

```bash
# Se connecter à MySQL
mysql -u root -p

# Dans MySQL, exécuter :
source database/migrations/001_create_tables.sql
```

Ou via ligne de commande :
```bash
mysql -u root -p < database/migrations/001_create_tables.sql
```

### 6. Créer les dossiers de stockage

```bash
mkdir -p storage/uploads storage/audio storage/logs
chmod -R 755 storage
```

### 7. Lancer le serveur

```bash
# Depuis la racine du projet
php -S localhost:8000 -t public
```

L'application est accessible sur : http://localhost:8000

---

## Utilisation

### Interface Web

1. Ouvrir http://localhost:8000
2. Saisir la description du chantier ou utiliser l'enregistrement vocal
3. (Optionnel) Ajouter des photos du chantier
4. Cliquer sur "Générer le devis"
5. Consulter le devis généré et télécharger le PDF

### Installation PWA

Sur Chrome/Edge :
1. Cliquer sur le bouton "Installer" dans l'en-tête
2. Ou utiliser le menu navigateur > "Installer l'application"

Sur Safari iOS :
1. Partager > "Sur l'écran d'accueil"

---

## API Endpoints

### POST /api/transcribe

Transcrit un fichier audio en texte.

**Request :**
```bash
curl -X POST http://localhost:8000/api/transcribe \
  -F "audio=@enregistrement.webm"
```

**Response :**
```json
{
  "success": true,
  "data": {
    "text": "Je voudrais installer un digicode à l'entrée de l'immeuble...",
    "language": "fr",
    "duration": 15.5
  }
}
```

**Formats audio supportés :** webm, mp3, m4a, wav, ogg

---

### POST /api/generate

Génère un devis à partir d'une description.

**Request (JSON) :**
```bash
curl -X POST http://localhost:8000/api/generate \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Installer un digicode à l entrée de l immeuble. La gâche électrique existe déjà. Le tableau est au sous-sol à environ 15 mètres."
  }'
```

**Request (avec transcription) :**
```bash
curl -X POST http://localhost:8000/api/generate \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Installation digicode",
    "transcription": "Je voudrais un digicode pour mon immeuble, la porte a déjà une gâche électrique"
  }'
```

**Request (multipart avec images) :**
```bash
curl -X POST http://localhost:8000/api/generate \
  -F "description=Installer un digicode" \
  -F "images[]=@photo1.jpg" \
  -F "images[]=@photo2.jpg"
```

**Response :**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "reference": "DEV-2024-0001",
    "quote": {
      "chantier": {
        "titre": "Installation digicode entrée immeuble",
        "localisation": null,
        "perimetre": "Fourniture et pose d'un digicode filaire...",
        "hypotheses": ["Gâche existante en 12V", "..."]
      },
      "taches": [...],
      "lignes": [...],
      "totaux": {
        "total_ht": 350.00,
        "taux_tva": 20,
        "montant_tva": 70.00,
        "total_ttc": 420.00
      },
      "questions_a_poser": [...],
      "exclusions": [...]
    },
    "pdf_url": "/pdf/quote/1"
  }
}
```

---

### GET /api/quote/{id}

Récupère un devis par son ID ou sa référence.

```bash
# Par ID
curl http://localhost:8000/api/quote/1

# Par référence
curl http://localhost:8000/api/quote/DEV-2024-0001
```

**Response :**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "reference": "DEV-2024-0001",
    "status": "draft",
    "created_at": "2024-01-15 10:30:00",
    "expires_at": "2024-02-14",
    "quote": {...},
    "attachments": [],
    "pdf_url": "/pdf/quote/1"
  }
}
```

---

### GET /api/quotes

Liste les devis avec pagination.

```bash
curl "http://localhost:8000/api/quotes?limit=10&offset=0"
```

**Response :**
```json
{
  "success": true,
  "data": {
    "quotes": [
      {
        "id": 1,
        "reference": "DEV-2024-0001",
        "titre": "Installation digicode",
        "total_ttc": 420.00,
        "status": "draft",
        "created_at": "2024-01-15 10:30:00"
      }
    ],
    "limit": 10,
    "offset": 0
  }
}
```

---

### GET /pdf/quote/{id}

Génère et renvoie le PDF du devis.

```bash
# Télécharger le PDF
curl -o devis.pdf http://localhost:8000/pdf/quote/1

# Ouvrir dans le navigateur
open http://localhost:8000/pdf/quote/1
```

---

### GET /api/prices

Retourne la grille de prix.

```bash
curl http://localhost:8000/api/prices
```

---

## Structure du projet

```
chiffreo/
├── public/                    # Point d'entrée web
│   ├── index.php              # Router principal
│   ├── index.html             # SPA frontend
│   ├── manifest.json          # PWA manifest
│   ├── service-worker.js      # Service Worker
│   ├── css/
│   │   └── app.css            # Styles
│   ├── js/
│   │   └── app.js             # JavaScript
│   └── icons/                 # Icônes PWA
├── src/
│   ├── Controllers/
│   │   └── ApiController.php  # Controllers API
│   ├── Services/
│   │   ├── OpenAIClient.php   # Client OpenAI
│   │   ├── QuoteCalculator.php# Calcul des montants
│   │   └── QuotePdfRenderer.php# Génération PDF
│   ├── Models/
│   │   └── QuoteRepository.php# Accès données
│   ├── Database/
│   │   └── Connection.php     # Connexion MySQL
│   └── Middleware/
│       └── RateLimiter.php    # Rate limiting
├── config/
│   ├── prices.php             # Grille tarifaire
│   └── quote_schema.php       # JSON Schema + prompts
├── database/
│   └── migrations/
│       └── 001_create_tables.sql
├── storage/
│   ├── uploads/               # Images uploadées
│   ├── audio/                 # Fichiers audio temporaires
│   └── logs/                  # Logs applicatifs
├── templates/
│   └── pdf/                   # Templates PDF (optionnel)
├── vendor/                    # Dépendances Composer
├── .env                       # Configuration (à créer)
├── .env.example               # Exemple de configuration
├── composer.json
└── README.md
```

---

## Codes prix disponibles

| Code | Description | Unité | Prix HT |
|------|-------------|-------|---------|
| **Main d'oeuvre** |||
| MO_H | Main d'oeuvre horaire | h | 45.00 € |
| MO_DEPLACEMENT | Forfait déplacement | forfait | 35.00 € |
| MO_MISE_EN_SERVICE | Mise en service | forfait | 80.00 € |
| **Câblage** |||
| CABLE_3G25 | Câble R2V 3G2.5mm² | m | 1.80 € |
| CABLE_PTT | Câble téléphone | m | 0.45 € |
| FOURREAU_TPC | Fourreau TPC | m | 2.50 € |
| **Contrôle d'accès** |||
| DIGICODE_FIL | Digicode filaire | u | 120.00 € |
| VISIOPHONE | Visiophone 2 fils | u | 180.00 € |
| GACHE_ELEC | Gâche électrique | u | 65.00 € |
| **Et plus...** |||

Voir `config/prices.php` pour la liste complète.

---

## Personnalisation

### Modifier la grille de prix

Éditer `config/prices.php` :

```php
'MON_ARTICLE' => [
    'label' => 'Mon article personnalisé',
    'unit' => 'u',
    'price_ht' => 99.00,
    'category' => 'materiel'
],
```

### Modifier le template PDF

Éditer `src/Services/QuotePdfRenderer.php`, méthode `generateHtml()`.

### Modifier les prompts IA

Éditer `config/quote_schema.php` :
- `system_prompt` : Instructions pour l'IA
- `json_schema` : Structure de la réponse

---

## Sécurité

- Clé API OpenAI uniquement côté serveur (jamais exposée au client)
- Rate limiting par IP (30 req/min par défaut)
- Validation des fichiers uploadés (type MIME, taille)
- Requêtes préparées PDO (protection SQL injection)
- Échappement HTML dans les templates

---

## Troubleshooting

### Erreur "OPENAI_API_KEY non configurée"
Vérifier que le fichier `.env` existe et contient la clé.

### Erreur de connexion MySQL
```bash
# Vérifier que MySQL est démarré
mysql.server start  # macOS
sudo systemctl start mysql  # Linux
```

### Erreur "Permission denied" sur storage
```bash
chmod -R 755 storage
```

### PDF vide ou caractères manquants
Installer les polices DejaVu (incluses avec Dompdf).

---

## Licence

MIT License - Usage libre pour tout projet.
