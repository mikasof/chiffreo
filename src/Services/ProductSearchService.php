<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PDO;

/**
 * Service de recherche de produits et de prix
 *
 * Stratégies de recherche:
 * 1. Cache local en base de données
 * 2. Grille de prix statique
 * 3. Estimation GPT-4o basée sur les prix des distributeurs français
 */
class ProductSearchService
{
    private Client $httpClient;
    private string $apiKey;
    private ?PDO $db;
    private array $priceGrid;

    // Distributeurs français de référence pour l'électricité/domotique
    private array $distributors = [
        'rexel' => 'Rexel',
        'sonepar' => 'Sonepar',
        'cged' => 'CGED',
        'yesss' => 'Yesss Electrique',
        'leroymerlin' => 'Leroy Merlin',
        'castorama' => 'Castorama',
        'bricodepot' => 'Brico Dépôt',
        'electricite_discount' => 'electricite-discount.com',
        '123elec' => '123elec.com',
        'domomat' => 'Domomat',
        'bis_electric' => 'bis-electric.com',
    ];

    // Marques connues par catégorie
    private array $brandCategories = [
        'electricite' => [
            'high' => ['Legrand', 'Schneider Electric', 'Hager', 'ABB', 'Siemens', 'Gewiss'],
            'mid' => ['Eur\'ohm', 'Debflex', 'Arnould', 'Niko', 'General Electric'],
            'low' => ['Debflex', 'Zenitech', 'Profile', 'Voltman']
        ],
        'domotique' => [
            'high' => ['Legrand', 'Schneider Wiser', 'Hager', 'Somfy', 'Yokis', 'Delta Dore', 'Tahoma'],
            'mid' => ['Sonoff', 'Shelly', 'Tuya', 'Zigbee', 'Meross'],
            'low' => ['Generic WiFi', 'Nous', 'Blitzwolf']
        ],
        'interphonie' => [
            'high' => ['Aiphone', 'Urmet', 'Comelit', 'Bticino', 'Intratone'],
            'mid' => ['Extel', 'SCS Sentinel', 'Avidsen', 'Thomson'],
            'low' => ['Elro', 'Smartwares', 'Byron']
        ],
        'chauffage' => [
            'high' => ['Atlantic', 'Thermor', 'Noirot', 'Campa', 'Acova'],
            'mid' => ['Sauter', 'Airelec', 'Applimo', 'Cayenne'],
            'low' => ['Carrera', 'Drexon', 'Voltman']
        ],
        'plomberie' => [
            'high' => ['Grohe', 'Hansgrohe', 'Jacob Delafon', 'Villeroy & Boch', 'Geberit'],
            'mid' => ['Ideal Standard', 'Porcher', 'Alterna', 'Wirquin'],
            'low' => ['Equation', 'Cooke & Lewis', 'Goodhome']
        ],
        'irve' => [
            'high' => ['Legrand Green\'up', 'Schneider EVlink', 'Hager Witty', 'ABB Terra'],
            'mid' => ['Wallbox', 'Evbox', 'SMA', 'Morec'],
            'low' => ['Juice Booster', 'NRGkick', 'Type2store']
        ]
    ];

    public function __construct(?PDO $db = null)
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
        $this->db = $db;
        $this->priceGrid = require __DIR__ . '/../../config/prices.php';

        $this->httpClient = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 30.0,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    /**
     * Recherche le prix d'un produit avec plusieurs stratégies
     *
     * @param string $marque Marque du produit
     * @param string $reference Référence produit
     * @param string $designation Description du produit (optionnel)
     * @param string $gamme Gamme de prix souhaitée (low, mid, high)
     * @return array{prix_ht: float, source: string, fiabilite: int, designation: string|null}
     */
    public function searchProductPrice(
        string $marque,
        string $reference,
        string $designation = '',
        string $gamme = 'mid'
    ): array {
        $this->log('INFO', 'Recherche prix produit', [
            'marque' => $marque,
            'reference' => $reference,
            'gamme' => $gamme
        ]);

        // 1. Vérifier le cache en base de données
        $cached = $this->getCachedPrice($marque, $reference, $gamme);
        if ($cached !== null) {
            $this->incrementSearchCount($marque, $reference, $gamme);
            return $cached;
        }

        // 2. Vérifier si la recherche a déjà échoué récemment
        if ($this->hasRecentFailure($marque, $reference)) {
            return $this->estimateFromGrid($designation, $gamme);
        }

        // 3. Estimation via GPT-4o basée sur les prix du marché français
        $estimated = $this->estimateWithGPT($marque, $reference, $designation, $gamme);

        if ($estimated !== null) {
            // Sauvegarder en cache
            $this->cachePrice($marque, $reference, $estimated, $gamme);
            return $estimated;
        }

        // 4. Fallback: estimation depuis la grille statique
        $fallback = $this->estimateFromGrid($designation, $gamme);
        $this->recordFailure($marque, $reference, 'No price found');

        return $fallback;
    }

    /**
     * Recherche multiple produits en batch
     */
    public function searchMultipleProducts(array $products): array
    {
        $results = [];

        foreach ($products as $product) {
            $marque = $product['marque'] ?? '';
            $reference = $product['reference'] ?? '';
            $designation = $product['designation'] ?? '';
            $gamme = $product['gamme'] ?? 'mid';

            if (empty($marque) && empty($reference)) {
                $results[] = $this->estimateFromGrid($designation, $gamme);
                continue;
            }

            $results[] = $this->searchProductPrice($marque, $reference, $designation, $gamme);
        }

        return $results;
    }

    /**
     * Estimation via GPT-4o basée sur la connaissance des prix du marché français
     */
    private function estimateWithGPT(
        string $marque,
        string $reference,
        string $designation,
        string $gamme
    ): ?array {
        $category = $this->detectCategory($marque, $designation);
        $brandTier = $this->getBrandTier($marque, $category);

        $systemPrompt = <<<PROMPT
Tu es un expert en estimation de prix pour le matériel électrique, domotique, chauffage et plomberie en France.

Tu connais les prix pratiqués par les distributeurs français comme:
- Professionnels: Rexel, Sonepar, CGED, Yesss Electrique
- Grand public: Leroy Merlin, Castorama, Brico Dépôt
- En ligne: 123elec.com, domomat.com, bis-electric.com, electricite-discount.com

RÈGLES D'ESTIMATION:
1. Base-toi sur les prix catalogue TTC que tu connais, puis calcule le HT (÷1.20)
2. Applique une marge revendeur de 15-25% pour les prix professionnels
3. Prends en compte la gamme demandée (low/mid/high)
4. Si tu ne connais pas le produit exact, estime basé sur des produits similaires
5. Ne jamais retourner 0 - fais une estimation raisonnable

GAMMES DE PRIX:
- low: Prix d'entrée de gamme, -20% par rapport au mid
- mid: Prix standard du marché (DÉFAUT)
- high: Prix premium, +20-30% par rapport au mid

CATÉGORIES ET FOURCHETTES TYPIQUES:
- Disjoncteur modulaire: 8-50€ HT selon calibre et marque
- Interrupteur différentiel: 30-150€ HT
- Prise de courant complète: 5-40€ HT
- Interrupteur complet: 5-35€ HT
- Tableau électrique pré-équipé: 150-800€ HT
- Câble R2V au mètre: 1-10€ HT selon section
- Thermostat connecté: 80-300€ HT
- Borne IRVE 7kW: 400-1200€ HT
- Visiophone: 150-800€ HT
- Radiateur électrique: 200-1500€ HT

Retourne UNIQUEMENT un JSON valide avec cette structure exacte.
PROMPT;

        $userPrompt = <<<PROMPT
Estime le prix HT de ce produit pour le marché français:

Marque: {$marque}
Référence: {$reference}
Description: {$designation}
Gamme demandée: {$gamme}
Catégorie détectée: {$category}
Positionnement marque: {$brandTier}

Retourne un JSON avec:
- prix_ht: nombre décimal (prix HT en euros)
- fiabilite: nombre 0-100 (confiance dans l'estimation)
- designation_complete: string (description normalisée du produit)
- categorie: string (electricite, domotique, chauffage, plomberie, irve)
- sous_categorie: string (type de produit plus précis)
PROMPT;

        try {
            $response = $this->httpClient->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt]
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'price_estimate',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'prix_ht' => ['type' => 'number'],
                                    'fiabilite' => ['type' => 'integer'],
                                    'designation_complete' => ['type' => 'string'],
                                    'categorie' => ['type' => 'string'],
                                    'sous_categorie' => ['type' => 'string']
                                ],
                                'required' => ['prix_ht', 'fiabilite', 'designation_complete', 'categorie', 'sous_categorie'],
                                'additionalProperties' => false
                            ]
                        ]
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 300
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $content = $result['choices'][0]['message']['content'] ?? '';
            $data = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($data['prix_ht'])) {
                $this->log('INFO', 'Prix estimé via GPT', [
                    'marque' => $marque,
                    'reference' => $reference,
                    'prix_ht' => $data['prix_ht'],
                    'fiabilite' => $data['fiabilite']
                ]);

                return [
                    'prix_ht' => round((float) $data['prix_ht'], 2),
                    'source' => 'gpt_estimate',
                    'fiabilite' => (int) $data['fiabilite'],
                    'designation' => $data['designation_complete'] ?? $designation,
                    'categorie' => $data['categorie'] ?? $category,
                    'sous_categorie' => $data['sous_categorie'] ?? null
                ];
            }

        } catch (GuzzleException $e) {
            $this->log('WARNING', 'Erreur estimation GPT', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Estimation depuis la grille de prix statique
     */
    private function estimateFromGrid(string $designation, string $gamme): array
    {
        $designationLower = strtolower($designation);
        $priceField = 'price_' . $gamme;

        // Recherche par mots-clés dans la grille
        $bestMatch = null;
        $bestScore = 0;

        foreach ($this->priceGrid as $code => $item) {
            $label = strtolower($item['label'] ?? '');
            $score = similar_text($designationLower, $label);

            // Bonus pour correspondance exacte de mots-clés
            $keywords = explode(' ', $label);
            foreach ($keywords as $keyword) {
                if (strlen($keyword) > 3 && strpos($designationLower, $keyword) !== false) {
                    $score += 10;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $item;
            }
        }

        if ($bestMatch !== null && $bestScore > 15) {
            return [
                'prix_ht' => (float) ($bestMatch[$priceField] ?? $bestMatch['price_mid']),
                'source' => 'price_grid',
                'fiabilite' => 60,
                'designation' => $bestMatch['label'] ?? $designation
            ];
        }

        // Fallback générique
        $genericPrices = [
            'low' => 15.00,
            'mid' => 25.00,
            'high' => 40.00
        ];

        return [
            'prix_ht' => $genericPrices[$gamme] ?? 25.00,
            'source' => 'fallback',
            'fiabilite' => 20,
            'designation' => $designation
        ];
    }

    /**
     * Détecte la catégorie du produit
     */
    private function detectCategory(string $marque, string $designation): string
    {
        $text = strtolower($marque . ' ' . $designation);

        $categoryKeywords = [
            'domotique' => ['domotique', 'connecté', 'wifi', 'zigbee', 'smart', 'tahoma', 'wiser', 'yokis', 'somfy'],
            'chauffage' => ['radiateur', 'chauffage', 'thermostat', 'convecteur', 'chaudière', 'pompe à chaleur'],
            'plomberie' => ['robinet', 'mitigeur', 'wc', 'lavabo', 'douche', 'baignoire', 'siphon', 'tuyau pvc'],
            'irve' => ['borne', 'wallbox', 'recharge', 'ev', 'green\'up', 'evlink', 'witty', 'type 2'],
            'interphonie' => ['interphone', 'visiophone', 'platine', 'moniteur', 'aiphone', 'urmet', 'comelit'],
            'electricite' => ['disjoncteur', 'différentiel', 'tableau', 'câble', 'prise', 'interrupteur', 'gaine']
        ];

        foreach ($categoryKeywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'electricite';
    }

    /**
     * Détermine le niveau de gamme d'une marque
     */
    private function getBrandTier(string $marque, string $category): string
    {
        $marqueLower = strtolower($marque);
        $categories = $this->brandCategories[$category] ?? $this->brandCategories['electricite'];

        foreach (['high', 'mid', 'low'] as $tier) {
            foreach ($categories[$tier] ?? [] as $brand) {
                if (strpos(strtolower($brand), $marqueLower) !== false ||
                    strpos($marqueLower, strtolower($brand)) !== false) {
                    return $tier;
                }
            }
        }

        return 'mid';
    }

    /**
     * Récupère le prix depuis le cache
     */
    private function getCachedPrice(string $marque, string $reference, string $gamme): ?array
    {
        if (!$this->db) return null;

        try {
            $stmt = $this->db->prepare("
                SELECT prix_vente_ht, source, fiabilite, designation, categorie, sous_categorie
                FROM product_prices
                WHERE marque = ? AND reference = ? AND gamme = ?
                AND (last_verified_at IS NULL OR last_verified_at > DATE_SUB(NOW(), INTERVAL 30 DAY))
            ");
            $stmt->execute([$marque, $reference, $gamme]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $this->log('INFO', 'Prix trouvé en cache', [
                    'marque' => $marque,
                    'reference' => $reference,
                    'prix' => $row['prix_vente_ht']
                ]);

                return [
                    'prix_ht' => (float) $row['prix_vente_ht'],
                    'source' => 'cache_' . $row['source'],
                    'fiabilite' => (int) $row['fiabilite'],
                    'designation' => $row['designation'],
                    'categorie' => $row['categorie'],
                    'sous_categorie' => $row['sous_categorie']
                ];
            }
        } catch (\PDOException $e) {
            $this->log('WARNING', 'Erreur lecture cache', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Sauvegarde le prix en cache
     */
    private function cachePrice(string $marque, string $reference, array $priceData, string $gamme): void
    {
        if (!$this->db) return;

        try {
            $stmt = $this->db->prepare("
                INSERT INTO product_prices
                (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                prix_vente_ht = VALUES(prix_vente_ht),
                designation = VALUES(designation),
                source = VALUES(source),
                fiabilite = VALUES(fiabilite),
                search_count = search_count + 1,
                updated_at = NOW()
            ");

            $stmt->execute([
                $marque,
                $reference,
                $priceData['designation'] ?? null,
                $priceData['prix_ht'],
                str_replace('cache_', '', $priceData['source']),
                $priceData['categorie'] ?? 'materiel',
                $priceData['sous_categorie'] ?? null,
                $gamme,
                $priceData['fiabilite'] ?? 50
            ]);

            $this->log('INFO', 'Prix mis en cache', [
                'marque' => $marque,
                'reference' => $reference,
                'prix' => $priceData['prix_ht']
            ]);

        } catch (\PDOException $e) {
            $this->log('WARNING', 'Erreur écriture cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Incrémente le compteur de recherche
     */
    private function incrementSearchCount(string $marque, string $reference, string $gamme): void
    {
        if (!$this->db) return;

        try {
            $stmt = $this->db->prepare("
                UPDATE product_prices
                SET search_count = search_count + 1, updated_at = NOW()
                WHERE marque = ? AND reference = ? AND gamme = ?
            ");
            $stmt->execute([$marque, $reference, $gamme]);
        } catch (\PDOException $e) {
            // Ignorer l'erreur
        }
    }

    /**
     * Vérifie si une recherche a échoué récemment
     */
    private function hasRecentFailure(string $marque, string $reference): bool
    {
        if (!$this->db) return false;

        try {
            $query = "{$marque} {$reference}";
            $stmt = $this->db->prepare("
                SELECT 1 FROM product_search_failures
                WHERE search_query = ? AND (retry_after IS NULL OR retry_after > NOW())
            ");
            $stmt->execute([$query]);
            return $stmt->fetch() !== false;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Enregistre un échec de recherche
     */
    private function recordFailure(string $marque, string $reference, string $reason): void
    {
        if (!$this->db) return;

        try {
            $query = "{$marque} {$reference}";
            $stmt = $this->db->prepare("
                INSERT INTO product_search_failures
                (search_query, marque, reference, failure_reason, retry_after, attempts)
                VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), 1)
                ON DUPLICATE KEY UPDATE
                attempts = attempts + 1,
                failure_reason = VALUES(failure_reason),
                retry_after = DATE_ADD(NOW(), INTERVAL 7 DAY),
                updated_at = NOW()
            ");
            $stmt->execute([$query, $marque, $reference, $reason]);
        } catch (\PDOException $e) {
            // Ignorer l'erreur
        }
    }

    /**
     * Obtient les statistiques du cache
     */
    public function getCacheStats(): array
    {
        if (!$this->db) return ['enabled' => false];

        try {
            $stats = [];

            // Total de produits en cache
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM product_prices");
            $stats['total_products'] = (int) $stmt->fetch()['total'];

            // Par catégorie
            $stmt = $this->db->query("
                SELECT categorie, COUNT(*) as count
                FROM product_prices
                GROUP BY categorie
            ");
            $stats['by_category'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Par source
            $stmt = $this->db->query("
                SELECT source, COUNT(*) as count
                FROM product_prices
                GROUP BY source
            ");
            $stats['by_source'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Produits les plus recherchés
            $stmt = $this->db->query("
                SELECT marque, reference, designation, search_count
                FROM product_prices
                ORDER BY search_count DESC
                LIMIT 10
            ");
            $stats['top_searched'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $stats;

        } catch (\PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Enregistre une correction de prix par un utilisateur
     * Permet d'apprendre des modifications manuelles
     */
    public function recordPriceCorrection(
        int $userId,
        string $marque,
        ?string $reference,
        string $designation,
        float $prixInitial,
        float $prixCorrige,
        string $gamme = 'mid',
        ?int $quoteId = null,
        ?string $sourceInitiale = null,
        ?string $commentaire = null
    ): bool {
        if (!$this->db) return false;

        try {
            $stmt = $this->db->prepare("
                INSERT INTO price_corrections
                (user_id, quote_id, marque, reference, designation, prix_initial_ht, prix_corrige_ht, gamme, source_initiale, commentaire)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $quoteId,
                $marque,
                $reference,
                $designation,
                $prixInitial,
                $prixCorrige,
                $gamme,
                $sourceInitiale,
                $commentaire
            ]);

            $this->log('INFO', 'Correction de prix enregistrée', [
                'user_id' => $userId,
                'marque' => $marque,
                'reference' => $reference,
                'prix_initial' => $prixInitial,
                'prix_corrige' => $prixCorrige
            ]);

            // Si la correction a une référence, mettre à jour le cache avec le prix corrigé (fiabilité 90)
            if ($reference) {
                $this->updateCacheFromCorrection($marque, $reference, $designation, $prixCorrige, $gamme);
            }

            return true;

        } catch (\PDOException $e) {
            $this->log('ERROR', 'Erreur enregistrement correction', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Met à jour le cache avec un prix corrigé par l'utilisateur (haute fiabilité)
     */
    private function updateCacheFromCorrection(
        string $marque,
        string $reference,
        string $designation,
        float $prixCorrige,
        string $gamme
    ): void {
        if (!$this->db) return;

        try {
            $stmt = $this->db->prepare("
                INSERT INTO product_prices
                (marque, reference, designation, prix_vente_ht, source, gamme, fiabilite)
                VALUES (?, ?, ?, ?, 'user_correction', ?, 90)
                ON DUPLICATE KEY UPDATE
                prix_vente_ht = VALUES(prix_vente_ht),
                designation = VALUES(designation),
                source = 'user_correction',
                fiabilite = 90,
                last_verified_at = NOW(),
                updated_at = NOW()
            ");

            $stmt->execute([$marque, $reference, $designation, $prixCorrige, $gamme]);

            $this->log('INFO', 'Cache mis à jour depuis correction utilisateur', [
                'marque' => $marque,
                'reference' => $reference,
                'prix' => $prixCorrige
            ]);

        } catch (\PDOException $e) {
            $this->log('WARNING', 'Erreur mise à jour cache depuis correction', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sauvegarde une nouvelle référence découverte (apprentissage automatique)
     */
    public function saveDiscoveredReference(
        string $marque,
        string $reference,
        string $designation,
        float $prixHt,
        string $gamme = 'mid',
        string $categorie = 'materiel',
        ?string $sousCategorie = null,
        string $source = 'quote_generation'
    ): bool {
        if (!$this->db || empty($reference)) return false;

        try {
            // Vérifier si la référence existe déjà
            $stmt = $this->db->prepare("
                SELECT id, fiabilite FROM product_prices
                WHERE marque = ? AND reference = ? AND gamme = ?
            ");
            $stmt->execute([$marque, $reference, $gamme]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Ne pas écraser si la fiabilité existante est supérieure
                if ($existing['fiabilite'] >= 70) {
                    return false;
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO product_prices
                (marque, reference, designation, prix_vente_ht, source, categorie, sous_categorie, gamme, fiabilite)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 50)
                ON DUPLICATE KEY UPDATE
                designation = COALESCE(VALUES(designation), designation),
                prix_vente_ht = IF(fiabilite < 70, VALUES(prix_vente_ht), prix_vente_ht),
                search_count = search_count + 1,
                updated_at = NOW()
            ");

            $stmt->execute([
                $marque,
                $reference,
                $designation,
                $prixHt,
                $source,
                $categorie,
                $sousCategorie,
                $gamme
            ]);

            $this->log('INFO', 'Nouvelle référence sauvegardée', [
                'marque' => $marque,
                'reference' => $reference,
                'prix' => $prixHt,
                'source' => $source
            ]);

            return true;

        } catch (\PDOException $e) {
            $this->log('WARNING', 'Erreur sauvegarde référence', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtient les corrections de prix en attente de validation
     */
    public function getPendingCorrections(int $limit = 50): array
    {
        if (!$this->db) return [];

        try {
            $stmt = $this->db->prepare("
                SELECT pc.*, u.email as user_email
                FROM price_corrections pc
                LEFT JOIN users u ON pc.user_id = u.id
                WHERE pc.validated = FALSE
                ORDER BY pc.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Valide une correction de prix (admin)
     */
    public function validateCorrection(int $correctionId, int $validatedBy): bool
    {
        if (!$this->db) return false;

        try {
            $stmt = $this->db->prepare("
                UPDATE price_corrections
                SET validated = TRUE, validated_by = ?, validated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$validatedBy, $correctionId]);

            return $stmt->rowCount() > 0;

        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Log structuré
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'service' => 'ProductSearch',
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];

        $logFile = __DIR__ . '/../../storage/logs/product_search.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents(
            $logFile,
            json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
