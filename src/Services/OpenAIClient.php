<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * AGENT "OPENAI CLIENT"
 * Service PHP pour les appels OpenAI (transcription + génération structurée)
 */
class OpenAIClient
{
    private Client $httpClient;
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1/';
    private NormsService $normsService;
    private ?ProductSearchService $productSearchService = null;
    private MaterialEstimator $materialEstimator;

    // Modèles recommandés
    private string $transcriptionModel = 'whisper-1';
    private string $chatModel = 'gpt-4o';

    public function __construct(?\PDO $db = null)
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';

        if (empty($this->apiKey)) {
            throw new \RuntimeException('OPENAI_API_KEY non configurée dans .env');
        }

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 120.0, // 2 min pour les requêtes longues
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ]
        ]);

        $this->normsService = new NormsService();
        $this->materialEstimator = new MaterialEstimator();

        // Initialiser le service de recherche de produits avec la BDD si disponible
        if ($db !== null) {
            $this->productSearchService = new ProductSearchService($db);
        }
    }

    /**
     * AGENT "SPEECH-TO-TEXT"
     * Transcrit un fichier audio en texte via l'API OpenAI
     *
     * @param string $filePath Chemin vers le fichier audio
     * @param string $mimeType Type MIME du fichier
     * @return array{text: string, language: string|null, duration: float|null}
     * @throws \RuntimeException
     */
    public function transcribe(string $filePath, string $mimeType): array
    {
        $this->log('INFO', 'Début transcription', ['file' => basename($filePath)]);

        if (!file_exists($filePath)) {
            throw new \RuntimeException('Fichier audio introuvable');
        }

        $fileSize = filesize($filePath);
        $maxSize = 25 * 1024 * 1024; // 25 MB limite OpenAI

        if ($fileSize > $maxSize) {
            throw new \RuntimeException('Fichier trop volumineux (max 25 MB)');
        }

        try {
            $response = $this->httpClient->post('audio/transcriptions', [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath)
                    ],
                    [
                        'name' => 'model',
                        'contents' => $this->transcriptionModel
                    ],
                    [
                        'name' => 'language',
                        'contents' => 'fr' // Français par défaut
                    ],
                    [
                        'name' => 'response_format',
                        'contents' => 'verbose_json' // Pour avoir duration
                    ]
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            $this->log('INFO', 'Transcription réussie', [
                'length' => strlen($result['text'] ?? ''),
                'duration' => $result['duration'] ?? null
            ]);

            // Nettoyer la transcription (enlever les numéros de liste automatiques)
            $cleanedText = $this->cleanTranscription($result['text'] ?? '');

            return [
                'text' => $cleanedText,
                'language' => $result['language'] ?? 'fr',
                'duration' => $result['duration'] ?? null
            ];

        } catch (GuzzleException $e) {
            $this->log('ERROR', 'Erreur transcription', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Erreur lors de la transcription : ' . $e->getMessage());
        }
    }

    /**
     * Nettoie la transcription des artefacts de Whisper
     * - Supprime les numéros de liste automatiques (1., 2., etc.)
     * - Remplace par des tirets pour plus de lisibilité
     */
    private function cleanTranscription(string $text): string
    {
        // Pattern : numéro suivi d'un point au début de ligne ou après un saut de ligne
        // Ex: "4. Fourniture" → "- Fourniture"
        $text = preg_replace('/(?:^|\n)\s*\d+\.\s*/m', "\n- ", $text);

        // Nettoyer les tirets en début de texte (si le premier élément a été converti)
        $text = ltrim($text, "\n- ");

        // Si le texte commence maintenant par un tiret, le garder propre
        if (preg_match('/^[A-ZÀ-Ÿa-zà-ÿ]/', $text)) {
            // Le texte commence par une lettre, c'est bon
        } else {
            // Remettre le tiret proprement si nécessaire
            $text = preg_replace('/^\s*-\s*/', '- ', $text);
        }

        return trim($text);
    }

    /**
     * Génère un devis structuré via OpenAI avec Structured Outputs
     *
     * @param string $description Description du chantier
     * @param string|null $transcription Transcription audio optionnelle
     * @param array $imageUrls URLs des images optionnelles (base64 ou URLs)
     * @return array Le devis structuré
     * @throws \RuntimeException
     */
    public function generateQuoteJson(
        string $description,
        ?string $transcription = null,
        array $imageUrls = []
    ): array {
        $this->log('INFO', 'Début génération devis', [
            'description_length' => strlen($description),
            'has_transcription' => !empty($transcription),
            'images_count' => count($imageUrls)
        ]);

        // Charger la configuration du schéma
        $schemaConfig = require __DIR__ . '/../../config/quote_schema.php';

        // Analyser la description pour détecter les normes et équipements obligatoires
        $fullText = $description . ($transcription ? ' ' . $transcription : '');
        $normsContext = $this->normsService->generatePromptContext($fullText);

        $this->log('INFO', 'Normes détectées', [
            'has_norms' => !empty($normsContext),
            'norms_length' => strlen($normsContext)
        ]);

        // Construire le prompt utilisateur avec le contexte des normes
        $userPrompt = $this->buildUserPrompt(
            $schemaConfig['user_prompt_template'],
            $description,
            $transcription,
            $imageUrls,
            $normsContext
        );

        // Construire les messages
        $messages = [
            [
                'role' => 'system',
                'content' => $schemaConfig['system_prompt']
            ]
        ];

        // Si on a des images, on utilise le format multimodal
        if (!empty($imageUrls)) {
            $content = [
                ['type' => 'text', 'text' => $userPrompt]
            ];

            foreach ($imageUrls as $imageUrl) {
                $content[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $imageUrl,
                        'detail' => 'auto'
                    ]
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $content
            ];
        } else {
            $messages[] = [
                'role' => 'user',
                'content' => $userPrompt
            ];
        }

        try {
            $response = $this->httpClient->post('chat/completions', [
                'json' => [
                    'model' => $this->chatModel,
                    'messages' => $messages,
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => $schemaConfig['json_schema']
                    ],
                    'temperature' => 0.3, // Faible pour plus de cohérence
                    'max_tokens' => 4000
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // Extraire le contenu JSON de la réponse
            $content = $result['choices'][0]['message']['content'] ?? '';
            $quoteData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Réponse OpenAI non valide JSON');
            }

            $this->log('INFO', 'Génération devis réussie', [
                'taches_count' => count($quoteData['taches'] ?? []),
                'lignes_count' => count($quoteData['lignes'] ?? []),
                'questions_count' => count($quoteData['questions_a_poser'] ?? [])
            ]);

            return $quoteData;

        } catch (GuzzleException $e) {
            $this->log('ERROR', 'Erreur génération devis', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Erreur lors de la génération : ' . $e->getMessage());
        }
    }

    /**
     * Construit le prompt utilisateur à partir du template
     */
    private function buildUserPrompt(
        string $template,
        string $description,
        ?string $transcription,
        array $imageUrls,
        string $normsContext = ''
    ): string {
        $transcriptionText = '';
        if (!empty($transcription)) {
            $transcriptionText = "### Transcription audio\n" . $transcription;
        }

        $imagesText = '';
        if (!empty($imageUrls)) {
            $imagesText = "### Images fournies\n" . count($imageUrls) . " image(s) jointe(s) pour référence.";
        }

        // Ajouter le contexte des normes à la description
        $fullDescription = $description;
        if (!empty($normsContext)) {
            $fullDescription .= "\n\n" . $normsContext;
        }

        return str_replace(
            ['{description}', '{transcription}', '{contexte_images}'],
            [$fullDescription, $transcriptionText, $imagesText],
            $template
        );
    }

    /**
     * Parse une ligne de devis dictée et extrait les données structurées
     *
     * @param string $text Texte transcrit (ex: "15 mètres de câble R2V à 3 euros le mètre")
     * @return array{categorie: string, designation: string, quantite: float, unite: string, prix_unitaire_ht: float}
     */
    public function parseLineFromText(string $text): array
    {
        $this->log('INFO', 'Analyse ligne dictée', ['text' => $text]);

        // Charger la grille de prix pour référence
        $priceGrid = require __DIR__ . '/../../config/prices.php';
        $priceReference = $this->buildPriceReference($priceGrid);

        $systemPrompt = <<<PROMPT
Tu es un assistant spécialisé dans l'analyse de descriptions de matériel et main d'œuvre électrique.
Tu dois extraire les informations structurées à partir d'une description dictée.

SYSTÈME DE GAMMES DE PRIX:
La grille contient 3 niveaux de prix pour chaque produit:
- low: Entrée de gamme, produit économique, marque distributeur, "pas cher", "premier prix"
- mid: Milieu de gamme, bon rapport qualité/prix (À UTILISER PAR DÉFAUT)
- high: Haut de gamme, premium, grandes marques

MARQUES HAUT DE GAMME (→ gamme "high"):
- Legrand, Schneider Electric, Hager, ABB, Siemens
- Bticino, Niko, Busch-Jaeger, Gira
- Aiphone, Urmet, Comelit (interphones/vidéophones)
- CDVI, Intratone (contrôle d'accès)

MARQUES MILIEU DE GAMME (→ gamme "mid"):
- Debflex, Eur'ohm, Gewiss, Arnould
- Extel, SCS Sentinel, Avidsen
- Marques génériques de qualité correcte

MARQUES ENTRÉE DE GAMME (→ gamme "low"):
- Marques distributeur (Brico Dépôt, Leroy Merlin, etc.)
- "Pas cher", "économique", "premier prix", "entrée de gamme"

GRILLE DE PRIX DE RÉFÉRENCE:
{$priceReference}

Règles d'extraction:
1. MARQUE: Si une marque est mentionnée, extrais-la dans le champ "marque"
2. RÉFÉRENCE: Si une référence produit est mentionnée (ex: "369220", "A9F74206"), extrais-la
3. GAMME: Détermine la gamme selon la marque ou les mots-clés:
   - Marque haut de gamme OU "premium/qualité/haut de gamme" → "high"
   - Marque entrée de gamme OU "pas cher/économique/premier prix" → "low"
   - Sinon → "mid" (par défaut)
4. PRIX: Utilise le prix correspondant à la gamme déterminée
5. DÉSIGNATION: Inclus la marque et référence si mentionnées

Autres règles:
- Catégorie: "materiel", "main_oeuvre", ou "forfait"
- Unité courte: "m", "u", "h", "ml", "lot", "forfait"
- Quantité par défaut: 1
- Catégorie auto:
  - Câbles, prises, interrupteurs, tableaux, disjoncteurs → materiel
  - Heures de travail, pose, installation → main_oeuvre
  - Déplacement, mise en service → forfait
PROMPT;

        $userPrompt = "Analyse cette description et extrait les informations:\n\n\"{$text}\"\n\nRetourne uniquement un JSON valide.";

        try {
            $response = $this->httpClient->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-4o-mini', // Modèle plus rapide pour cette tâche simple
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt]
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'quote_line',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'categorie' => [
                                        'type' => 'string',
                                        'enum' => ['materiel', 'main_oeuvre', 'forfait'],
                                        'description' => 'Catégorie de la ligne'
                                    ],
                                    'designation' => [
                                        'type' => 'string',
                                        'description' => 'Description complète du produit (avec marque/ref si connues)'
                                    ],
                                    'marque' => [
                                        'type' => ['string', 'null'],
                                        'description' => 'Marque du produit si mentionnée (Legrand, Schneider, etc.)'
                                    ],
                                    'reference' => [
                                        'type' => ['string', 'null'],
                                        'description' => 'Référence produit si mentionnée (ex: 369220, A9F74206)'
                                    ],
                                    'gamme' => [
                                        'type' => 'string',
                                        'enum' => ['low', 'mid', 'high'],
                                        'description' => 'Gamme de prix: low (entrée), mid (milieu, défaut), high (premium)'
                                    ],
                                    'quantite' => [
                                        'type' => 'number',
                                        'description' => 'Quantité'
                                    ],
                                    'unite' => [
                                        'type' => 'string',
                                        'description' => 'Unité (m, u, h, ml, lot, forfait)'
                                    ],
                                    'prix_unitaire_ht' => [
                                        'type' => 'number',
                                        'description' => 'Prix unitaire HT en euros selon la gamme'
                                    ]
                                ],
                                'required' => ['categorie', 'designation', 'marque', 'reference', 'gamme', 'quantite', 'unite', 'prix_unitaire_ht'],
                                'additionalProperties' => false
                            ]
                        ]
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 500
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $content = $result['choices'][0]['message']['content'] ?? '';
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Réponse non valide');
            }

            $this->log('INFO', 'Ligne analysée avec succès', $parsed);

            // Si une référence est détectée, rechercher le prix réel
            if (!empty($parsed['reference']) && !empty($parsed['marque'])) {
                $priceResult = $this->searchProductPrice(
                    $parsed['marque'],
                    $parsed['reference'],
                    $parsed['designation'] ?? '',
                    $parsed['gamme'] ?? 'mid'
                );

                if ($priceResult !== null && isset($priceResult['prix_ht'])) {
                    $parsed['prix_unitaire_ht'] = $priceResult['prix_ht'];
                    $parsed['prix_source'] = $priceResult['source'] ?? 'search';
                    $parsed['prix_fiabilite'] = $priceResult['fiabilite'] ?? 50;

                    // Enrichir la désignation si disponible
                    if (!empty($priceResult['designation']) && strlen($priceResult['designation']) > strlen($parsed['designation'])) {
                        $parsed['designation'] = $priceResult['designation'];
                    }

                    $this->log('INFO', 'Prix trouvé via recherche', [
                        'reference' => $parsed['reference'],
                        'prix' => $priceResult['prix_ht'],
                        'source' => $priceResult['source']
                    ]);
                }
            }

            return $parsed;

        } catch (GuzzleException $e) {
            $this->log('ERROR', 'Erreur analyse ligne', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Erreur lors de l\'analyse: ' . $e->getMessage());
        }
    }

    /**
     * Recherche le prix réel d'un produit via le ProductSearchService
     *
     * @param string $marque Marque du produit
     * @param string $reference Référence produit
     * @param string $designation Description du produit (optionnel)
     * @param string $gamme Gamme de prix (low, mid, high)
     * @return array|null Données de prix ou null
     */
    private function searchProductPrice(
        string $marque,
        string $reference,
        string $designation = '',
        string $gamme = 'mid'
    ): ?array {
        // Créer le service s'il n'existe pas (sans BDD pour le cache)
        if ($this->productSearchService === null) {
            $this->productSearchService = new ProductSearchService();
        }

        try {
            $result = $this->productSearchService->searchProductPrice(
                $marque,
                $reference,
                $designation,
                $gamme
            );

            $this->log('INFO', 'Prix trouvé via ProductSearchService', [
                'marque' => $marque,
                'reference' => $reference,
                'prix_ht' => $result['prix_ht'],
                'source' => $result['source'],
                'fiabilite' => $result['fiabilite']
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->log('WARNING', 'Erreur recherche prix', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Construit une référence de prix lisible pour l'IA avec les 3 niveaux de gamme
     */
    private function buildPriceReference(array $priceGrid): string
    {
        $lines = [];
        $lines[] = "LÉGENDE DES PRIX:";
        $lines[] = "- Bas: Entrée de gamme / économique";
        $lines[] = "- Moyen: Milieu de gamme (UTILISER PAR DÉFAUT si non précisé)";
        $lines[] = "- Haut: Haut de gamme / premium";

        $categories = [
            'main_oeuvre' => 'MAIN D\'ŒUVRE',
            'materiel' => 'MATÉRIEL',
            'forfait' => 'FORFAITS'
        ];

        foreach ($categories as $catKey => $catLabel) {
            $items = array_filter($priceGrid, fn($item) => ($item['category'] ?? '') === $catKey);
            if (empty($items)) continue;

            $lines[] = "\n{$catLabel}:";
            foreach ($items as $code => $item) {
                $priceLow = $item['price_low'] ?? 0;
                $priceMid = $item['price_mid'] ?? 0;
                $priceHigh = $item['price_high'] ?? 0;
                $lines[] = "- {$item['label']}: {$priceLow}€/{$priceMid}€/{$priceHigh}€ (bas/moyen/haut) par {$item['unit']}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * GÉNÉRATION DEVIS V2 - Système hybride : calcul PHP + validation IA
     *
     * Pour les rénovations complètes (>50m²), utilise le MaterialEstimator PHP
     * pour garantir un devis complet et des prix cohérents.
     * Pour les autres cas, utilise OpenAI.
     *
     * @param string $description Description ou transcription du chantier
     * @param array $userParams Paramètres de l'utilisateur (hourly_rate, product_margin, travel_type, etc.)
     * @param array $imageUrls Images optionnelles
     * @return array Le devis structuré avec marques et références précises
     */
    public function generateQuoteV2(
        string $description,
        array $userParams = [],
        array $imageUrls = []
    ): array {
        $this->log('INFO', 'Début génération devis V2', [
            'description_length' => strlen($description),
            'images_count' => count($imageUrls),
            'user_params' => array_keys($userParams)
        ]);

        // === ÉTAPE 1 : Analyse de la description ===
        $parsedParams = $this->materialEstimator->parseDescription($description);

        $this->log('INFO', 'Paramètres détectés', [
            'surface' => $parsedParams['surface'],
            'type' => $parsedParams['type'],
            'nb_pieces' => count($parsedParams['pieces']),
            'gamme' => $parsedParams['gamme'],
            'options' => $parsedParams['options']
        ]);

        // === ÉTAPE 2 : Pour rénovations complètes (>50m²), utiliser le calculateur PHP ===
        if ($parsedParams['type'] === 'renovation_complete' && $parsedParams['surface'] >= 50) {
            $this->log('INFO', 'Utilisation du calculateur PHP (rénovation complète détectée)');

            $quoteData = $this->materialEstimator->calculerDevis($parsedParams);

            $this->log('INFO', 'Devis calculé par PHP', [
                'fournitures_count' => count($quoteData['fournitures']),
                'main_oeuvre_count' => count($quoteData['main_oeuvre']),
                'taches_count' => count($quoteData['taches']),
                'total_ttc' => $quoteData['totaux']['total_ttc']
            ]);

            // Ajouter des questions pertinentes
            $quoteData['questions_a_poser'] = [
                'Quel type de tableau existant (monophasé/triphasé) ?',
                'Y a-t-il un accès aux combles pour le passage des câbles ?',
                'Les murs sont-ils en placo, brique ou béton ?',
                'Souhaitez-vous des prises USB intégrées ?'
            ];

            return $quoteData;
        }

        // === ÉTAPE 3 : Pour les autres cas, utiliser OpenAI ===
        $this->log('INFO', 'Utilisation de OpenAI (cas non-rénovation ou petite surface)');

        // Charger la configuration du prompt V2
        $promptConfig = require __DIR__ . '/../../config/quote_prompt_v2.php';

        // Formater les paramètres utilisateur
        $formatParametres = $promptConfig['format_parametres'];
        $parametresText = $formatParametres($userParams);

        // Contexte images
        $imagesContext = '';
        if (!empty($imageUrls)) {
            $imagesContext = "### Images fournies\n" . count($imageUrls) . " image(s) jointe(s) - analyse-les pour comprendre le contexte.";
        }

        // Construire le prompt utilisateur
        $userPrompt = str_replace(
            ['{parametres}', '{transcription}', '{images_context}'],
            [$parametresText, $description, $imagesContext],
            $promptConfig['user_prompt_template']
        );

        // Construire les messages
        $messages = [
            [
                'role' => 'system',
                'content' => $promptConfig['system_prompt']
            ]
        ];

        // Si on a des images, format multimodal
        if (!empty($imageUrls)) {
            $content = [
                ['type' => 'text', 'text' => $userPrompt]
            ];

            foreach ($imageUrls as $imageUrl) {
                $content[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $imageUrl,
                        'detail' => 'high' // Haute qualité pour bien voir les détails
                    ]
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $content
            ];
        } else {
            $messages[] = [
                'role' => 'user',
                'content' => $userPrompt
            ];
        }

        try {
            $response = $this->httpClient->post('chat/completions', [
                'json' => [
                    'model' => $this->chatModel,
                    'messages' => $messages,
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => $promptConfig['json_schema']
                    ],
                    'temperature' => 0.7, // Plus élevé comme ChatGPT navigateur
                    'max_tokens' => 16000  // Beaucoup plus de tokens pour devis complets
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // Extraire le contenu JSON
            $content = $result['choices'][0]['message']['content'] ?? '';
            $quoteData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Réponse OpenAI non valide JSON: ' . json_last_error_msg());
            }

            // Log du premier résultat
            $this->log('INFO', 'Génération devis V2 - première passe', [
                'fournitures_count' => count($quoteData['fournitures'] ?? []),
                'main_oeuvre_count' => count($quoteData['main_oeuvre'] ?? []),
                'taches_count' => count($quoteData['taches'] ?? []),
                'total_ttc' => $quoteData['totaux']['total_ttc'] ?? 0
            ]);

            // === ÉTAPE 2 : VÉRIFICATION ET COMPLÉMENT ===
            $quoteData = $this->verifyAndCompleteQuote($quoteData, $description, $promptConfig, $userParams);

            // Log du résultat final
            $this->log('INFO', 'Génération devis V2 réussie (après vérification)', [
                'fournitures_count' => count($quoteData['fournitures'] ?? []),
                'main_oeuvre_count' => count($quoteData['main_oeuvre'] ?? []),
                'taches_count' => count($quoteData['taches'] ?? []),
                'total_ttc' => $quoteData['totaux']['total_ttc'] ?? 0,
                'questions_count' => count($quoteData['questions_a_poser'] ?? [])
            ]);

            return $quoteData;

        } catch (GuzzleException $e) {
            $this->log('ERROR', 'Erreur génération devis V2', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Erreur lors de la génération V2 : ' . $e->getMessage());
        }
    }

    /**
     * Vérifie et complète le devis généré en demandant à l'IA de relire
     *
     * @param array $quoteData Le devis initial
     * @param string $description La demande client originale
     * @param array $promptConfig Configuration du prompt
     * @param array $userParams Paramètres utilisateur
     * @return array Le devis vérifié et complété
     */
    private function verifyAndCompleteQuote(array $quoteData, string $description, array $promptConfig, array $userParams): array
    {
        $this->log('INFO', 'Début vérification devis V2');

        $verificationPrompt = <<<PROMPT
Tu es un VÉRIFICATEUR DE DEVIS ÉLECTRICIEN expert.

## DEMANDE CLIENT ORIGINALE :
{$description}

## DEVIS GÉNÉRÉ (à vérifier) :

### Tâches prévues :
PROMPT;

        // Ajouter les tâches actuelles
        foreach ($quoteData['taches'] ?? [] as $tache) {
            $verificationPrompt .= "\n- {$tache['titre']} ({$tache['duree_estimee_h']}h)";
        }

        $verificationPrompt .= "\n\n### Fournitures :";
        foreach ($quoteData['fournitures'] ?? [] as $f) {
            $verificationPrompt .= "\n- {$f['designation']} x{$f['quantite']} {$f['unite']}";
        }

        $verificationPrompt .= "\n\n### Main d'œuvre :";
        foreach ($quoteData['main_oeuvre'] ?? [] as $mo) {
            $verificationPrompt .= "\n- {$mo['designation']} ({$mo['heures']}h)";
        }

        $verificationPrompt .= <<<PROMPT


### Total actuel : {$quoteData['totaux']['total_ttc']}€ TTC

---

## TA MISSION DE VÉRIFICATION :

Relis attentivement la demande client ET le devis généré. Vérifie que RIEN n'a été oublié :

1. **TÂCHES** : Toutes les étapes de travail sont-elles listées ?
   - Dépose/démontage si rénovation ?
   - Saignées si encastré ?
   - Rebouchage après saignées ?
   - Tests et mise en service ?
   - Nettoyage de chantier ?

2. **MATÉRIEL** : Tout le matériel est-il présent ?
   - Tableau et protections (différentiels, disjoncteurs) ?
   - Câbles en quantité suffisante (prévoir +20% de marge) ?
   - Gaines ICTA ?
   - Appareillage (prises, interrupteurs, DCL) ?
   - Consommables (Wago, chevilles, boîtes, etc.) ?

3. **MAIN D'ŒUVRE** : Tous les temps sont-ils comptés ?
   - Préparation/repérage ?
   - Pose du matériel ?
   - Tirage de câbles ?
   - Raccordements ?
   - Tests ?

4. **PRESTATIONS ANNEXES** :
   - Déplacement ?
   - Mise en service ?
   - Consuel si nécessaire ?
   - Évacuation des déchets ?

## RÉPONSE ATTENDUE :

Retourne le devis COMPLET au format JSON avec :
- Les tâches manquantes AJOUTÉES
- Les lignes manquantes AJOUTÉES
- Les totaux RECALCULÉS

Si le devis est déjà complet, retourne-le tel quel avec les totaux vérifiés.
PROMPT;

        try {
            $response = $this->httpClient->post('chat/completions', [
                'json' => [
                    'model' => $this->chatModel,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $promptConfig['system_prompt']
                        ],
                        [
                            'role' => 'user',
                            'content' => $verificationPrompt
                        ]
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => $promptConfig['json_schema']
                    ],
                    'temperature' => 0.3, // Plus bas pour être plus précis en vérification
                    'max_tokens' => 16000
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $content = $result['choices'][0]['message']['content'] ?? '';
            $verifiedData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log('WARNING', 'Vérification devis: réponse JSON invalide, on garde l\'original');
                return $quoteData;
            }

            // Log des changements
            $fournituresAvant = count($quoteData['fournitures'] ?? []);
            $fournituresApres = count($verifiedData['fournitures'] ?? []);
            $moAvant = count($quoteData['main_oeuvre'] ?? []);
            $moApres = count($verifiedData['main_oeuvre'] ?? []);
            $tachesAvant = count($quoteData['taches'] ?? []);
            $tachesApres = count($verifiedData['taches'] ?? []);

            $this->log('INFO', 'Vérification devis terminée', [
                'fournitures_avant' => $fournituresAvant,
                'fournitures_apres' => $fournituresApres,
                'mo_avant' => $moAvant,
                'mo_apres' => $moApres,
                'taches_avant' => $tachesAvant,
                'taches_apres' => $tachesApres,
                'total_avant' => $quoteData['totaux']['total_ttc'] ?? 0,
                'total_apres' => $verifiedData['totaux']['total_ttc'] ?? 0
            ]);

            return $verifiedData;

        } catch (GuzzleException $e) {
            $this->log('WARNING', 'Erreur vérification devis, on garde l\'original', ['error' => $e->getMessage()]);
            return $quoteData; // En cas d'erreur, on retourne le devis original
        }
    }

    /**
     * Log structuré pour debug et monitoring
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];

        $logFile = __DIR__ . '/../../storage/logs/openai.log';
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
