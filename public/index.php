<?php

/**
 * Chiffreo MVP - Point d'entrée principal
 * Router simple pour l'API et les pages
 */

// Autoloader Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Configuration des erreurs selon l'environnement
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Headers CORS pour le développement
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Récupérer l'URI et retirer le préfixe du projet
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rawurldecode($uri);

// Retirer le préfixe /chiffreo/public si présent
$basePath = '/chiffreo/public';
if (str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}
// Aussi gérer /chiffreo/public/ -> /
if ($uri === '' || $uri === false) {
    $uri = '/';
}

// Router
try {
    // === Auth API Routes ===

    // POST /api/auth/register
    if ($uri === '/api/auth/register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new \App\Controllers\AuthController();
        $controller->register();
        exit;
    }

    // POST /api/auth/login
    if ($uri === '/api/auth/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new \App\Controllers\AuthController();
        $controller->login();
        exit;
    }

    // POST /api/auth/logout
    if ($uri === '/api/auth/logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new \App\Controllers\AuthController();
        $controller->logout();
        exit;
    }

    // GET /api/auth/me
    if ($uri === '/api/auth/me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller = new \App\Controllers\AuthController();
        $controller->me();
        exit;
    }

    // POST /api/auth/onboarding
    if ($uri === '/api/auth/onboarding' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new \App\Controllers\AuthController();
        $controller->updateOnboarding();
        exit;
    }

    // PUT/POST /api/auth/profile
    if ($uri === '/api/auth/profile' && in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) {
        $controller = new \App\Controllers\AuthController();
        $controller->updateProfile();
        exit;
    }

    // POST /api/auth/change-password
    if ($uri === '/api/auth/change-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new \App\Controllers\AuthController();
        $controller->changePassword();
        exit;
    }

    // GET /api/auth/sessions
    if ($uri === '/api/auth/sessions' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller = new \App\Controllers\AuthController();
        $controller->listSessions();
        exit;
    }

    // DELETE /api/auth/sessions
    if ($uri === '/api/auth/sessions' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $controller = new \App\Controllers\AuthController();
        $controller->logoutAll();
        exit;
    }

    // GET /api/auth/quota
    if ($uri === '/api/auth/quota' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller = new \App\Controllers\AuthController();
        $controller->getQuota();
        exit;
    }

    // === Push Notifications Routes ===

    // POST /api/push/subscribe
    if ($uri === '/api/push/subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Authentification requise
        $auth = new \App\Middleware\AuthMiddleware();
        $user = $auth->requireAuth();
        if (!$user) exit;

        $input = json_decode(file_get_contents('php://input'), true);
        $service = new \App\Services\NotificationService();

        try {
            $subId = $service->subscribe($auth->getUserId(), $input);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => ['subscription_id' => $subId]]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // DELETE /api/push/unsubscribe
    if ($uri === '/api/push/unsubscribe' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $service = new \App\Services\NotificationService();

        try {
            $service->unsubscribe($input['endpoint'] ?? '');
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // GET /api/push/vapid-key
    if ($uri === '/api/push/vapid-key' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $service = new \App\Services\NotificationService();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => ['publicKey' => $service->getPublicKey()]
        ]);
        exit;
    }

    // === API Routes ===

    // POST /api/transcribe
    if ($uri === '/api/transcribe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new \App\Controllers\ApiController();
        $controller->transcribe();
        exit;
    }

    // POST /api/generate
    if ($uri === '/api/generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new \App\Controllers\ApiController();
        $controller->generate();
        exit;
    }

    // GET /api/quotes (liste)
    if ($uri === '/api/quotes' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller = new \App\Controllers\ApiController();
        $controller->listQuotes();
        exit;
    }

    // GET /api/quote/{id}
    if (preg_match('#^/api/quote/([^/]+)$#', $uri, $matches)) {
        $controller = new \App\Controllers\ApiController();
        $controller->getQuote($matches[1]);
        exit;
    }

    // GET /api/prices
    if ($uri === '/api/prices' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller = new \App\Controllers\ApiController();
        $controller->getPrices();
        exit;
    }

    // POST /api/parse-line (analyse vocale d'une ligne)
    if ($uri === '/api/parse-line' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new \App\Controllers\ApiController();
        $controller->parseLine();
        exit;
    }

    // POST /api/check-tva (vérification TVA automatique)
    if ($uri === '/api/check-tva' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new \App\Controllers\ApiController();
        $controller->checkTva();
        exit;
    }

    // POST /api/check-norms (analyse des normes et équipements obligatoires)
    if ($uri === '/api/check-norms' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller = new \App\Controllers\ApiController();
        $controller->checkNorms();
        exit;
    }

    // GET /api/norms-types (liste des types de travaux avec normes)
    if ($uri === '/api/norms-types' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller = new \App\Controllers\ApiController();
        $controller->listNormsTypes();
        exit;
    }

    // === PDF Routes ===

    // GET /pdf/quote/{id}
    if (preg_match('#^/pdf/quote/([^/]+)$#', $uri, $matches)) {
        $controller = new \App\Controllers\ApiController();
        $controller->getPdf($matches[1]);
        exit;
    }

    // === Static Files (servis directement par Apache normalement) ===

    // Service Worker
    if ($uri === '/service-worker.js') {
        header('Content-Type: application/javascript');
        header('Service-Worker-Allowed: /');
        readfile(__DIR__ . '/service-worker.js');
        exit;
    }

    // Manifest
    if ($uri === '/manifest.json') {
        header('Content-Type: application/manifest+json');
        readfile(__DIR__ . '/manifest.json');
        exit;
    }

    // === Pages statiques ===

    // Page d'authentification
    if ($uri === '/auth' || $uri === '/auth.html' || $uri === '/login' || $uri === '/register') {
        $file = __DIR__ . '/auth.html';
        if (file_exists($file)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($file);
            exit;
        }
    }

    // Page d'onboarding
    if ($uri === '/onboarding' || $uri === '/onboarding.html') {
        $file = __DIR__ . '/onboarding.html';
        if (file_exists($file)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($file);
            exit;
        }
    }

    // Politique de confidentialité
    if ($uri === '/confidentialite' || $uri === '/confidentialite.html') {
        $file = __DIR__ . '/confidentialite.html';
        if (file_exists($file)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($file);
            exit;
        }
    }

    // Mentions légales
    if ($uri === '/mentions-legales' || $uri === '/mentions-legales.html') {
        $file = __DIR__ . '/mentions-legales.html';
        if (file_exists($file)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($file);
            exit;
        }
    }

    // === Landing Page ===
    // Page d'accueil marketing
    if ($uri === '/') {
        $landingFile = __DIR__ . '/landing.html';
        if (file_exists($landingFile)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($landingFile);
            exit;
        }
    }

    // === Application principale ===
    // L'app de création de devis
    if ($uri === '/app' || str_starts_with($uri, '/app/')) {
        $indexFile = __DIR__ . '/index.html';
        if (file_exists($indexFile)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($indexFile);
            exit;
        }
    }

    // Fallback pour les anciennes URLs sans /app (rétrocompatibilité temporaire)
    if (!str_starts_with($uri, '/api') && !str_starts_with($uri, '/pdf')) {
        $indexFile = __DIR__ . '/index.html';
        if (file_exists($indexFile)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($indexFile);
            exit;
        }
    }

    // 404
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Route non trouvée: ' . $uri]);

} catch (\Exception $e) {
    // Erreur serveur
    http_response_code(500);
    header('Content-Type: application/json');

    $response = ['success' => false, 'error' => 'Erreur serveur interne'];

    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        $response['debug'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
    }

    echo json_encode($response);
}
