<?php

namespace App\Controllers;

use App\Services\OpenAIClient;
use App\Services\QuoteCalculator;
use App\Services\QuotePdfRenderer;
use App\Services\NormsService;
use App\Services\QuotaService;
use App\Services\ProductSearchService;
use App\Models\QuoteRepository;
use App\Models\UserRepository;
use App\Models\CompanyRepository;
use App\Middleware\RateLimiter;
use App\Middleware\AuthMiddleware;
use App\Database\Connection;

/**
 * Controller principal de l'API
 */
class ApiController
{
    private OpenAIClient $openAI;
    private QuoteCalculator $calculator;
    private QuotePdfRenderer $pdfRenderer;
    private NormsService $normsService;
    private QuoteRepository $quoteRepo;
    private RateLimiter $rateLimiter;
    private AuthMiddleware $auth;
    private QuotaService $quotaService;
    private UserRepository $userRepo;
    private CompanyRepository $companyRepo;
    private ProductSearchService $productSearch;
    private \PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
        $this->openAI = new OpenAIClient($this->db);
        $this->calculator = new QuoteCalculator();
        $this->pdfRenderer = new QuotePdfRenderer();
        $this->normsService = new NormsService();
        $this->quoteRepo = new QuoteRepository();
        $this->rateLimiter = new RateLimiter();
        $this->auth = new AuthMiddleware();
        $this->quotaService = new QuotaService();
        $this->userRepo = new UserRepository();
        $this->companyRepo = new CompanyRepository();
        $this->productSearch = new ProductSearchService($this->db);
    }

    /**
     * POST /api/transcribe
     * Transcrit un fichier audio en texte
     */
    public function transcribe(): void
    {
        try {
            // Rate limiting
            if (!$this->checkRateLimit('transcribe')) {
                return;
            }

            // Vérifier la méthode
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Méthode non autorisée', 405);
                return;
            }

            // Vérifier le fichier
            if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
                $this->jsonError('Fichier audio requis', 400);
                return;
            }

            $file = $_FILES['audio'];

            // Valider le type de fichier
            $allowedMimes = [
                'audio/webm', 'audio/mp3', 'audio/mpeg', 'audio/mp4',
                'audio/m4a', 'audio/wav', 'audio/x-wav', 'audio/ogg',
                'audio/x-m4a', 'audio/aac', 'audio/x-aac',
                'video/webm', // Chrome enregistre parfois en video/webm
                'application/octet-stream', // Fallback générique
                'audio/opus', 'audio/x-opus+ogg'
            ];

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            // Log pour debug
            $this->log('INFO', 'transcribe_upload', 'Fichier reçu', [
                'mime_detected' => $mimeType,
                'original_name' => $file['name'],
                'size' => $file['size']
            ]);

            if (!in_array($mimeType, $allowedMimes)) {
                $this->jsonError('Format audio non supporté (' . $mimeType . '). Formats acceptés: webm, mp3, m4a, wav, ogg', 400);
                return;
            }

            // Vérifier la taille (max 10 MB par défaut)
            $maxSize = (int) ($_ENV['MAX_UPLOAD_SIZE_MB'] ?? 10) * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                $this->jsonError('Fichier trop volumineux (max ' . ($_ENV['MAX_UPLOAD_SIZE_MB'] ?? 10) . ' MB)', 400);
                return;
            }

            // Déplacer le fichier temporairement
            $tempPath = __DIR__ . '/../../storage/audio/' . uniqid('audio_') . '_' . basename($file['name']);
            $tempDir = dirname($tempPath);

            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
                $this->jsonError('Erreur lors du stockage du fichier', 500);
                return;
            }

            // Transcrire via OpenAI
            try {
                $result = $this->openAI->transcribe($tempPath, $mimeType);

                // Supprimer le fichier temporaire
                @unlink($tempPath);

                $this->jsonSuccess([
                    'text' => $result['text'],
                    'language' => $result['language'],
                    'duration' => $result['duration']
                ]);

            } catch (\Exception $e) {
                @unlink($tempPath);
                $this->logError('transcribe', $e->getMessage());
                $this->jsonError('Erreur de transcription: ' . $e->getMessage(), 500);
            }

        } catch (\Exception $e) {
            $this->logError('transcribe', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * POST /api/generate
     * Génère un devis à partir d'une description
     */
    public function generate(): void
    {
        try {
            // Rate limiting
            if (!$this->checkRateLimit('generate')) {
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Méthode non autorisée', 405);
                return;
            }

            // Authentification optionnelle (mais requise pour sauvegarder)
            $user = $this->auth->optionalAuth();
            $userId = $this->auth->getUserId();
            $companyId = $this->auth->getCompanyId();

            // Si connecté, vérifier le quota (basé sur la company)
            if ($user) {
                if (!$this->quotaService->canCreateQuote($user)) {
                    $this->jsonError('Quota mensuel atteint. Passez au plan Pro pour des devis illimités.', 403);
                    return;
                }
            }

            // Récupérer les données
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (strpos($contentType, 'application/json') !== false) {
                $input = json_decode(file_get_contents('php://input'), true);
            } else {
                // multipart/form-data pour fichiers
                $input = $_POST;
            }

            $description = trim($input['description'] ?? '');
            $transcription = trim($input['transcription'] ?? '');

            if (empty($description) && empty($transcription)) {
                $this->jsonError('Description ou transcription requise', 400);
                return;
            }

            // Récupérer les données client (JAMAIS envoyées à l'IA)
            $clientData = [];
            if (!empty($input['client'])) {
                $clientData = is_string($input['client'])
                    ? json_decode($input['client'], true)
                    : $input['client'];
            }

            // Récupérer les données chantier
            $chantierData = [];
            if (!empty($input['chantier'])) {
                $chantierData = is_string($input['chantier'])
                    ? json_decode($input['chantier'], true)
                    : $input['chantier'];
            }

            // Texte combiné (sans données personnelles client)
            $texteComplet = $description;
            if (!empty($transcription)) {
                $texteComplet = $description . "\n\n[Transcription audio] " . $transcription;
            }

            // Gérer les images uploadées (optionnel)
            $imageUrls = [];
            if (!empty($_FILES['images'])) {
                $imageUrls = $this->processUploadedImages($_FILES['images']);
            }

            // Appeler OpenAI pour générer le devis
            $aiResponse = $this->openAI->generateQuoteJson(
                $texteComplet,
                !empty($transcription) ? $transcription : null,
                $imageUrls
            );

            // Récupérer les paramètres de tarification de l'utilisateur
            $userPricing = $this->userRepo->getPricing($userId);

            // Configurer le calculateur avec les paramètres utilisateur
            $this->calculator->setUserPricing($userPricing);

            // Mode détail si demandé (pour debug/test)
            if (!empty($input['show_details']) || !empty($input['debug'])) {
                $this->calculator->setShowDetails(true);
            }

            // Calculer les montants avec la grille de prix
            // Le service TVA analyse automatiquement la description pour déterminer le taux
            $quoteWithTotals = $this->calculator->calculate(
                $aiResponse,
                null, // tier (gamme de prix)
                $texteComplet // description pour calcul TVA automatique
            );

            // Construire le nom complet du client
            $clientName = null;
            if (!empty($clientData)) {
                $clientName = trim(($clientData['prenom'] ?? '') . ' ' . ($clientData['nom'] ?? ''));
                if (empty($clientName)) {
                    $clientName = $clientData['nom'] ?? null;
                }
            }

            // Sauvegarder en base de données
            $reference = $this->quoteRepo->generateReference();
            $quoteId = $this->quoteRepo->create([
                'reference' => $reference,
                'company_id' => $companyId,
                'user_id' => $userId,
                // Infos client (stockées directement sur le devis)
                'client_name' => $clientName,
                'client_company' => $clientData['societe'] ?? null,
                'client_email' => $clientData['email'] ?? null,
                'client_phone' => $clientData['telephone'] ?? null,
                'client_address' => $clientData['adresse'] ?? null,
                // Infos chantier
                'titre' => $quoteWithTotals['chantier']['titre'] ?? 'Devis travaux électriques',
                'localisation' => $quoteWithTotals['chantier']['localisation'] ?? null,
                'perimetre' => $quoteWithTotals['chantier']['perimetre'] ?? null,
                'chantier_adresse' => $chantierData['adresse'] ?? null,
                'chantier_code_postal' => $chantierData['codePostal'] ?? null,
                'chantier_ville' => $chantierData['ville'] ?? null,
                // Contenu
                'description_originale' => $texteComplet,
                'transcription_audio' => $transcription ?: null,
                'ai_response' => $aiResponse,
                'quote_data' => $quoteWithTotals,
                // Totaux
                'total_ht' => $quoteWithTotals['totaux']['total_ht'] ?? 0,
                'total_tva' => $quoteWithTotals['totaux']['montant_tva'] ?? 0,
                'total_ttc' => $quoteWithTotals['totaux']['total_ttc'] ?? 0,
                'taux_tva' => $quoteWithTotals['totaux']['taux_tva'] ?? 20.00
            ]);

            // Incrémenter le compteur de devis de la company
            if ($companyId) {
                $this->companyRepo->incrementQuoteCount($companyId);
                // Marquer le premier devis si c'est le cas
                if ($userId) {
                    $this->userRepo->markFirstQuoteDone($userId);
                }
            }

            $this->logInfo('generate', 'Devis créé', [
                'id' => $quoteId,
                'ref' => $reference,
                'client_name' => $clientName,
                'user_id' => $userId,
                'company_id' => $companyId
            ]);

            $response = [
                'id' => $quoteId,
                'reference' => $reference,
                'quote' => $quoteWithTotals,
                'pdf_url' => '/pdf/quote/' . $quoteId
            ];

            // Inclure info client si présent (pour confirmation UI)
            if ($clientName) {
                $response['client_name'] = $clientName;
            }

            $this->jsonSuccess($response, 201);

        } catch (\Exception $e) {
            $this->logError('generate', $e->getMessage());
            $this->jsonError('Erreur lors de la génération: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/generate-v2
     * Génère un devis avec le nouveau système multi-agents
     * OpenAI gère tout : analyse, normes, matériel, prix avec marques/références
     */
    public function generateV2(): void
    {
        try {
            // Rate limiting
            if (!$this->checkRateLimit('generate')) {
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Méthode non autorisée', 405);
                return;
            }

            // Authentification optionnelle
            $user = $this->auth->optionalAuth();
            $userId = $this->auth->getUserId();
            $companyId = $this->auth->getCompanyId();

            // Vérifier quota si connecté
            if ($user) {
                if (!$this->quotaService->canCreateQuote($user)) {
                    $this->jsonError('Quota mensuel atteint.', 403);
                    return;
                }
            }

            // Récupérer les données
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $input = json_decode(file_get_contents('php://input'), true);
            } else {
                $input = $_POST;
            }

            $description = trim($input['description'] ?? '');
            $transcription = trim($input['transcription'] ?? '');

            if (empty($description) && empty($transcription)) {
                $this->jsonError('Description ou transcription requise', 400);
                return;
            }

            // Texte combiné
            $texteComplet = $description;
            if (!empty($transcription)) {
                $texteComplet = $description . "\n\n[Transcription audio] " . $transcription;
            }

            // Images uploadées
            $imageUrls = [];
            if (!empty($_FILES['images'])) {
                $imageUrls = $this->processUploadedImages($_FILES['images']);
            }

            // Récupérer les paramètres utilisateur pour le prompt
            $userParams = [];
            if ($userId) {
                $userPricing = $this->userRepo->getPricing($userId);
                $userParams = [
                    'hourly_rate' => $userPricing['hourly_rate'] ?? 45,
                    'product_margin' => $userPricing['product_margin'] ?? 20,
                    'travel_type' => $userPricing['travel_type'] ?? 'fixed',
                    'travel_fixed_amount' => $userPricing['travel_fixed_amount'] ?? 30,
                    'travel_per_km' => $userPricing['travel_per_km'] ?? 0.50,
                    'travel_free_radius' => $userPricing['travel_free_radius'] ?? 20,
                    'preferred_brand' => $userPricing['preferred_brand'] ?? 'Schneider Electric',
                ];
            } else {
                // Valeurs par défaut pour test sans auth
                $userParams = [
                    'hourly_rate' => 45,
                    'product_margin' => 20,
                    'travel_type' => 'fixed',
                    'travel_fixed_amount' => 30,
                    'preferred_brand' => 'Schneider Electric',
                ];
            }

            // Appeler OpenAI avec le nouveau système V2
            $quoteData = $this->openAI->generateQuoteV2(
                $texteComplet,
                $userParams,
                $imageUrls
            );

            // Log pour debug
            $this->logInfo('generate-v2', 'Devis V2 généré', [
                'fournitures' => count($quoteData['fournitures'] ?? []),
                'total_ttc' => $quoteData['totaux']['total_ttc'] ?? 0
            ]);

            // Récupérer données client (optionnel)
            $clientData = [];
            if (!empty($input['client'])) {
                $clientData = is_string($input['client'])
                    ? json_decode($input['client'], true)
                    : $input['client'];
            }

            $clientName = null;
            if (!empty($clientData)) {
                $clientName = trim(($clientData['prenom'] ?? '') . ' ' . ($clientData['nom'] ?? ''));
            }

            // Sauvegarder en BDD
            $reference = $this->quoteRepo->generateReference();
            $quoteId = $this->quoteRepo->create([
                'reference' => $reference,
                'company_id' => $companyId,
                'user_id' => $userId,
                'client_name' => $clientName,
                'client_email' => $clientData['email'] ?? null,
                'client_phone' => $clientData['telephone'] ?? null,
                'titre' => $quoteData['chantier']['titre'] ?? 'Devis travaux électriques',
                'perimetre' => $quoteData['chantier']['perimetre'] ?? null,
                'description_originale' => $texteComplet,
                'transcription_audio' => $transcription ?: null,
                'ai_response' => $quoteData, // Le JSON complet V2
                'quote_data' => $quoteData,
                'total_ht' => $quoteData['totaux']['total_ht'] ?? 0,
                'total_tva' => $quoteData['totaux']['tva_montant'] ?? 0,
                'total_ttc' => $quoteData['totaux']['total_ttc'] ?? 0,
                'taux_tva' => $quoteData['totaux']['tva_taux'] ?? 20.00
            ]);

            // Incrémenter compteur
            if ($companyId) {
                $this->companyRepo->incrementQuoteCount($companyId);
                if ($userId) {
                    $this->userRepo->markFirstQuoteDone($userId);
                }
            }

            $this->jsonSuccess([
                'id' => $quoteId,
                'reference' => $reference,
                'quote' => $quoteData,
                'version' => 'v2',
                'pdf_url' => '/pdf/quote/' . $quoteId
            ], 201);

        } catch (\Exception $e) {
            $this->logError('generate-v2', $e->getMessage());
            $this->jsonError('Erreur génération V2: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/quote/{id}
     * Récupère un devis par ID ou référence
     */
    public function getQuote(string $identifier): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->jsonError('Méthode non autorisée', 405);
                return;
            }

            // ID numérique ou référence ?
            if (is_numeric($identifier)) {
                $quote = $this->quoteRepo->findById((int) $identifier);
            } else {
                $quote = $this->quoteRepo->findByReference($identifier);
            }

            if (!$quote) {
                $this->jsonError('Devis non trouvé', 404);
                return;
            }

            // Récupérer les attachements
            $attachments = $this->quoteRepo->getAttachments((int) $quote['id']);

            $this->jsonSuccess([
                'id' => (int) $quote['id'],
                'reference' => $quote['reference'],
                'status' => $quote['status'],
                'created_at' => $quote['created_at'],
                'expires_at' => $quote['expires_at'],
                'quote' => $quote['quote_data'],
                'client' => [
                    'nom' => $quote['client_name'],
                    'societe' => $quote['client_company'],
                    'email' => $quote['client_email'],
                    'telephone' => $quote['client_phone'],
                    'adresse' => $quote['client_address']
                ],
                'chantier' => [
                    'adresse' => $quote['chantier_adresse'],
                    'codePostal' => $quote['chantier_code_postal'],
                    'ville' => $quote['chantier_ville']
                ],
                'attachments' => $attachments,
                'pdf_url' => '/pdf/quote/' . $quote['id']
            ]);

        } catch (\Exception $e) {
            $this->logError('getQuote', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * PUT /api/quote/{id}
     * Met à jour un devis existant
     */
    public function updateQuote(int $quoteId): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->jsonError('Méthode non autorisée', 405);
                return;
            }

            // Authentification requise
            $user = $this->auth->requireAuth();
            if (!$user) {
                return;
            }

            $userId = $this->auth->getUserId();

            // Récupérer le devis existant
            $quote = $this->quoteRepo->findById($quoteId);

            if (!$quote) {
                $this->jsonError('Devis non trouvé', 404);
                return;
            }

            // Vérifier que l'utilisateur est propriétaire du devis
            if ($quote['user_id'] !== $userId) {
                $this->jsonError('Accès refusé', 403);
                return;
            }

            // Récupérer les données de mise à jour
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                $this->jsonError('Données invalides', 400);
                return;
            }

            // Préparer les données à mettre à jour
            $updateData = [];

            // Données client
            if (isset($input['client'])) {
                $clientData = $input['client'];
                $clientName = trim(($clientData['prenom'] ?? '') . ' ' . ($clientData['nom'] ?? ''));
                if (empty($clientName)) {
                    $clientName = $clientData['nom'] ?? null;
                }
                $updateData['client_name'] = $clientName;
                $updateData['client_company'] = $clientData['societe'] ?? null;
                $updateData['client_email'] = $clientData['email'] ?? null;
                $updateData['client_phone'] = $clientData['telephone'] ?? null;
                $updateData['client_address'] = $clientData['adresse'] ?? null;
            }

            // Données chantier
            if (isset($input['chantier'])) {
                $chantierData = $input['chantier'];
                $updateData['chantier_adresse'] = $chantierData['adresse'] ?? null;
                $updateData['chantier_code_postal'] = $chantierData['codePostal'] ?? null;
                $updateData['chantier_ville'] = $chantierData['ville'] ?? null;
            }

            // Données du devis (lignes, totaux, etc.)
            if (isset($input['quote'])) {
                $quoteData = $input['quote'];
                $updateData['quote_data'] = $quoteData;
                $updateData['titre'] = $quoteData['chantier']['titre'] ?? null;
                $updateData['perimetre'] = $quoteData['chantier']['perimetre'] ?? null;
                $updateData['total_ht'] = $quoteData['totaux']['total_ht'] ?? 0;
                $updateData['total_tva'] = $quoteData['totaux']['montant_tva'] ?? 0;
                $updateData['total_ttc'] = $quoteData['totaux']['total_ttc'] ?? 0;
                $updateData['taux_tva'] = $quoteData['totaux']['taux_tva'] ?? 20;
            }

            // Mettre à jour
            $this->quoteRepo->update($quoteId, $updateData);

            $this->logInfo('updateQuote', 'Devis mis à jour', [
                'quote_id' => $quoteId,
                'user_id' => $userId
            ]);

            $this->jsonSuccess([
                'id' => $quoteId,
                'message' => 'Devis mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            $this->logError('updateQuote', $e->getMessage());
            $this->jsonError('Erreur lors de la mise à jour: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/quotes
     * Liste les devis (filtrés par company si authentifié)
     */
    public function listQuotes(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->jsonError('Méthode non autorisée', 405);
                return;
            }

            // Authentification optionnelle - si connecté, filtrer par company
            $this->auth->optionalAuth();
            $companyId = $this->auth->getCompanyId();

            $limit = min((int) ($_GET['limit'] ?? 20), 100);
            $offset = (int) ($_GET['offset'] ?? 0);

            $quotes = $this->quoteRepo->findAll($limit, $offset, $companyId);

            $this->jsonSuccess([
                'quotes' => $quotes,
                'limit' => $limit,
                'offset' => $offset
            ]);

        } catch (\Exception $e) {
            $this->logError('listQuotes', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * GET /pdf/quote/{id}
     * Génère et renvoie le PDF du devis
     */
    public function getPdf(string $identifier): void
    {
        try {
            // ID numérique ou référence ?
            if (is_numeric($identifier)) {
                $quote = $this->quoteRepo->findById((int) $identifier);
            } else {
                $quote = $this->quoteRepo->findByReference($identifier);
            }

            if (!$quote) {
                http_response_code(404);
                echo "Devis non trouvé";
                return;
            }

            // Récupérer les données de l'entreprise
            $company = [];
            if (!empty($quote['company_id'])) {
                $companyData = $this->companyRepo->findById((int) $quote['company_id']);
                if ($companyData) {
                    $company = [
                        'name' => $companyData['name'] ?? '',
                        'siret' => $companyData['siret'] ?? '',
                        'phone' => $companyData['phone'] ?? '',
                        'email' => $companyData['email_contact'] ?? '',
                        'address_line1' => $companyData['address_line1'] ?? '',
                        'address_line2' => $companyData['address_line2'] ?? '',
                        'postal_code' => $companyData['postal_code'] ?? '',
                        'city' => $companyData['city'] ?? '',
                        'logo_path' => $companyData['logo_path'] ?? '',
                        'legal_form' => $companyData['legal_form'] ?? '',
                        'capital' => $companyData['capital'] ?? '',
                        'rcs_number' => $companyData['rcs_number'] ?? '',
                        'rcs_city' => $companyData['rcs_city'] ?? '',
                        'vat_number' => $companyData['vat_number'] ?? '',
                        'insurance_name' => $companyData['insurance_name'] ?? '',
                        'insurance_number' => $companyData['insurance_number'] ?? '',
                        'insurance_coverage' => $companyData['insurance_coverage'] ?? '',
                        'default_tva_rate' => $companyData['default_tva_rate'] ?? 20.00,
                        'quote_validity_days' => $companyData['quote_validity_days'] ?? 30,
                    ];
                }
            }

            // Construire les données client depuis le devis
            $client = [
                'nom' => $quote['client_name'] ?? '',
                'societe' => $quote['client_company'] ?? '',
                'email' => $quote['client_email'] ?? '',
                'telephone' => $quote['client_phone'] ?? '',
                'adresse' => $quote['client_address'] ?? '',
            ];

            // Construire les données chantier
            $chantierInfo = [
                'adresse' => $quote['chantier_adresse'] ?? '',
                'codePostal' => $quote['chantier_code_postal'] ?? '',
                'ville' => $quote['chantier_ville'] ?? '',
            ];

            // Générer le PDF avec toutes les données
            $pdf = $this->pdfRenderer->render(
                $quote['quote_data'],
                $quote['reference'],
                $company,
                $client,
                $chantierInfo
            );

            // Headers pour téléchargement PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $quote['reference'] . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            header('Cache-Control: private, max-age=0, must-revalidate');

            echo $pdf;

        } catch (\Exception $e) {
            $this->logError('getPdf', $e->getMessage());
            http_response_code(500);
            echo "Erreur lors de la génération du PDF";
        }
    }

    /**
     * POST /api/parse-line
     * Transcrit et analyse une ligne dictée pour extraire les données structurées
     */
    public function parseLine(): void
    {
        try {
            // Rate limiting
            if (!$this->checkRateLimit('parse-line')) {
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Méthode non autorisée', 405);
                return;
            }

            // Vérifier le fichier audio
            if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
                $this->jsonError('Fichier audio requis', 400);
                return;
            }

            $file = $_FILES['audio'];

            // Valider le type de fichier
            $allowedMimes = [
                'audio/webm', 'audio/mp3', 'audio/mpeg', 'audio/mp4',
                'audio/m4a', 'audio/wav', 'audio/x-wav', 'audio/ogg',
                'audio/x-m4a', 'audio/aac', 'audio/x-aac',
                'video/webm', 'application/octet-stream',
                'audio/opus', 'audio/x-opus+ogg'
            ];

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $allowedMimes)) {
                $this->jsonError('Format audio non supporté', 400);
                return;
            }

            // Déplacer le fichier temporairement
            $tempPath = __DIR__ . '/../../storage/audio/' . uniqid('line_') . '.webm';
            $tempDir = dirname($tempPath);

            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
                $this->jsonError('Erreur lors du stockage du fichier', 500);
                return;
            }

            try {
                // 1. Transcrire l'audio
                $transcription = $this->openAI->transcribe($tempPath, $mimeType);
                $text = $transcription['text'] ?? '';

                @unlink($tempPath);

                if (empty($text)) {
                    $this->jsonError('Aucune transcription obtenue', 400);
                    return;
                }

                // 2. Analyser le texte pour extraire les données structurées
                $parsedData = $this->openAI->parseLineFromText($text);

                $this->logInfo('parse-line', 'Ligne analysée', [
                    'transcription' => $text,
                    'parsed' => $parsedData
                ]);

                $this->jsonSuccess($parsedData);

            } catch (\Exception $e) {
                @unlink($tempPath);
                throw $e;
            }

        } catch (\Exception $e) {
            $this->logError('parse-line', $e->getMessage());
            $this->jsonError('Erreur d\'analyse: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/check-tva
     * Vérifie le taux de TVA applicable pour une description
     */
    public function checkTva(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Méthode non autorisée', 405);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $description = trim($input['description'] ?? '');

            if (empty($description)) {
                $this->jsonError('Description requise', 400);
                return;
            }

            // Contexte optionnel
            $context = [];
            if (isset($input['type_batiment'])) {
                $context['type_batiment'] = $input['type_batiment'];
            }
            if (isset($input['anciennete'])) {
                $context['anciennete'] = $input['anciennete'];
            }
            if (isset($input['neuf'])) {
                $context['neuf'] = (bool) $input['neuf'];
            }

            $tvaInfo = $this->calculator->determinerTva($description, [], $context);

            $this->jsonSuccess([
                'taux' => $tvaInfo['taux'],
                'raison' => $tvaInfo['raison'],
                'message_devis' => $tvaInfo['message_devis'],
                'attestation' => $tvaInfo['attestation'],
                'questions' => $tvaInfo['questions']
            ]);

        } catch (\Exception $e) {
            $this->logError('check-tva', $e->getMessage());
            $this->jsonError('Erreur: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/check-norms
     * Analyse une description et retourne les normes et équipements applicables
     */
    public function checkNorms(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Méthode non autorisée', 405);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $description = trim($input['description'] ?? '');

            if (empty($description)) {
                $this->jsonError('Description requise', 400);
                return;
            }

            $analysis = $this->normsService->analyzeWorkDescription($description);

            $this->jsonSuccess([
                'types_travaux' => $analysis['detected_types'],
                'normes' => $analysis['normes'],
                'equipements_obligatoires' => $analysis['equipements_obligatoires'],
                'equipements_recommandes' => $analysis['equipements_recommandes'],
                'certifications' => $analysis['certifications'],
                'points_controle' => $analysis['points_controle'],
                'tva_applicable' => $analysis['tva_applicable'],
                'aides_disponibles' => $analysis['aides_disponibles']
            ]);

        } catch (\Exception $e) {
            $this->logError('check-norms', $e->getMessage());
            $this->jsonError('Erreur: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/norms-types
     * Liste tous les types de travaux avec leurs règles de normes
     */
    public function listNormsTypes(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->jsonError('Méthode non autorisée', 405);
                return;
            }

            $types = $this->normsService->listAvailableTypes();

            $this->jsonSuccess(['types' => $types]);

        } catch (\Exception $e) {
            $this->logError('list-norms-types', $e->getMessage());
            $this->jsonError('Erreur: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/prices
     * Retourne la grille de prix
     */
    public function getPrices(): void
    {
        try {
            $prices = $this->calculator->getPriceGrid();

            // Grouper par catégorie
            $grouped = [];
            foreach ($prices as $code => $item) {
                $cat = $item['category'];
                if (!isset($grouped[$cat])) {
                    $grouped[$cat] = [];
                }
                $grouped[$cat][$code] = $item;
            }

            $this->jsonSuccess(['prices' => $grouped]);

        } catch (\Exception $e) {
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * Traite les images uploadées
     */
    private function processUploadedImages(array $files): array
    {
        $imageUrls = [];
        $uploadDir = __DIR__ . '/../../storage/uploads/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Normaliser la structure (simple ou multiple)
        $images = [];
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $images[] = [
                        'name' => $files['name'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'type' => $files['type'][$i],
                        'size' => $files['size'][$i]
                    ];
                }
            }
        } else {
            if ($files['error'] === UPLOAD_ERR_OK) {
                $images[] = $files;
            }
        }

        // Limiter à 4 images
        $images = array_slice($images, 0, 4);

        foreach ($images as $image) {
            // Valider le type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($image['tmp_name']);

            if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
                continue;
            }

            // Vérifier la taille (max 5 MB par image)
            if ($image['size'] > 5 * 1024 * 1024) {
                continue;
            }

            // Convertir en base64 pour OpenAI
            $content = file_get_contents($image['tmp_name']);
            $base64 = base64_encode($content);
            $imageUrls[] = "data:{$mimeType};base64,{$base64}";
        }

        return $imageUrls;
    }

    /**
     * POST /api/price-correction
     * Enregistre une correction de prix faite par l'utilisateur
     */
    public function recordPriceCorrection(): void
    {
        try {
            // Authentification requise
            $user = $this->auth->authenticate();
            if (!$user) {
                $this->jsonError('Authentification requise', 401);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonError('Méthode non autorisée', 405);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            // Validation des champs requis
            $required = ['marque', 'designation', 'prix_initial', 'prix_corrige'];
            foreach ($required as $field) {
                if (empty($input[$field]) && $input[$field] !== 0) {
                    $this->jsonError("Le champ {$field} est requis", 400);
                    return;
                }
            }

            $success = $this->productSearch->recordPriceCorrection(
                $user['id'],
                $input['marque'],
                $input['reference'] ?? null,
                $input['designation'],
                (float) $input['prix_initial'],
                (float) $input['prix_corrige'],
                $input['gamme'] ?? 'mid',
                $input['quote_id'] ?? null,
                $input['source'] ?? null,
                $input['commentaire'] ?? null
            );

            if ($success) {
                $this->jsonSuccess([
                    'message' => 'Correction enregistrée',
                    'marque' => $input['marque'],
                    'reference' => $input['reference'] ?? null,
                    'prix_corrige' => (float) $input['prix_corrige']
                ]);
            } else {
                $this->jsonError('Erreur lors de l\'enregistrement', 500);
            }

        } catch (\Exception $e) {
            $this->logError('price_correction', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * GET /api/product-search
     * Recherche le prix d'un produit
     */
    public function searchProductPrice(): void
    {
        try {
            // Authentification requise
            $user = $this->auth->authenticate();
            if (!$user) {
                $this->jsonError('Authentification requise', 401);
                return;
            }

            $marque = $_GET['marque'] ?? '';
            $reference = $_GET['reference'] ?? '';
            $designation = $_GET['designation'] ?? '';
            $gamme = $_GET['gamme'] ?? 'mid';

            if (empty($marque) && empty($reference)) {
                $this->jsonError('Marque ou référence requise', 400);
                return;
            }

            $result = $this->productSearch->searchProductPrice(
                $marque,
                $reference,
                $designation,
                $gamme
            );

            $this->jsonSuccess($result);

        } catch (\Exception $e) {
            $this->logError('product_search', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * GET /api/price-stats
     * Statistiques sur le cache de prix (admin)
     */
    public function getPriceStats(): void
    {
        try {
            // Authentification requise
            $user = $this->auth->authenticate();
            if (!$user) {
                $this->jsonError('Authentification requise', 401);
                return;
            }

            $stats = $this->productSearch->getCacheStats();
            $this->jsonSuccess($stats);

        } catch (\Exception $e) {
            $this->logError('price_stats', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * Vérifie le rate limit
     */
    private function checkRateLimit(string $endpoint): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if (!$this->rateLimiter->check($ip, $endpoint)) {
            $retryAfter = $this->rateLimiter->getRetryAfter($ip, $endpoint);
            header('Retry-After: ' . $retryAfter);
            $this->jsonError('Trop de requêtes. Réessayez dans ' . $retryAfter . ' secondes.', 429);
            return false;
        }

        return true;
    }

    /**
     * Réponse JSON succès
     */
    private function jsonSuccess(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Réponse JSON erreur
     */
    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Log d'erreur
     */
    private function logError(string $action, string $message): void
    {
        $this->log('ERROR', $action, $message);
    }

    /**
     * Log d'info
     */
    private function logInfo(string $action, string $message, array $context = []): void
    {
        $this->log('INFO', $action, $message, $context);
    }

    /**
     * Écriture de log
     */
    private function log(string $level, string $action, string $message, array $context = []): void
    {
        try {
            $db = \App\Database\Connection::getInstance();
            $stmt = $db->prepare(
                "INSERT INTO logs (level, action, message, context, ip_address, user_agent)
                 VALUES (:level, :action, :message, :context, :ip, :ua)"
            );
            $stmt->execute([
                'level' => $level,
                'action' => $action,
                'message' => $message,
                'context' => json_encode($context),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            // Fallback fichier
            $logFile = __DIR__ . '/../../storage/logs/app.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            file_put_contents(
                $logFile,
                date('Y-m-d H:i:s') . " [{$level}] {$action}: {$message}\n",
                FILE_APPEND
            );
        }
    }
}
