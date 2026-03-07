<?php

namespace App\Controllers;

use App\Models\UserRepository;
use App\Models\CompanyRepository;
use App\Models\SessionRepository;
use App\Middleware\RateLimiter;

/**
 * Controller pour l'authentification
 * Architecture: Company (plan/quota) -> Users
 */
class AuthController
{
    private UserRepository $userRepo;
    private CompanyRepository $companyRepo;
    private SessionRepository $sessionRepo;
    private RateLimiter $rateLimiter;

    private const BCRYPT_COST = 12;
    private const TRIAL_DAYS = 14;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->companyRepo = new CompanyRepository();
        $this->sessionRepo = new SessionRepository();
        $this->rateLimiter = new RateLimiter();
    }

    /**
     * POST /api/auth/register
     */
    public function register(): void
    {
        try {
            if (!$this->checkRateLimit('register', 5)) {
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            // Validation
            $firstName = trim($input['first_name'] ?? '');
            $email = trim(strtolower($input['email'] ?? ''));
            $password = $input['password'] ?? '';

            if (strlen($firstName) < 2 || strlen($firstName) > 100) {
                $this->jsonError('Le prénom doit contenir entre 2 et 100 caractères', 400);
                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->jsonError('Adresse email invalide', 400);
                return;
            }

            if (strlen($password) < 8) {
                $this->jsonError('Le mot de passe doit contenir au moins 8 caractères', 400);
                return;
            }

            if ($this->userRepo->emailExists($email)) {
                $this->jsonError('Un compte existe déjà avec cette adresse email', 409, 'email_exists');
                return;
            }

            // 1. Créer la company (plan pro + trial 14 jours)
            $trialEndsAt = date('Y-m-d H:i:s', strtotime('+' . self::TRIAL_DAYS . ' days'));
            $companyId = $this->companyRepo->create([
                'plan' => 'pro',
                'trial_ends_at' => $trialEndsAt
            ]);

            // 2. Créer le user
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
            $userId = $this->userRepo->create([
                'company_id' => $companyId,
                'first_name' => $firstName,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => 'owner'
            ]);

            // 3. Créer une session
            $token = $this->sessionRepo->create(
                $userId,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            // 4. Charger le user complet
            $user = $this->userRepo->findById($userId);
            $this->userRepo->updateLastLogin($userId);

            $this->logAction('register', $userId, ['email' => $email]);

            $this->jsonSuccess([
                'token' => $token,
                'user' => $this->formatUserResponse($user),
                'redirect' => '/onboarding'
            ], 201);

        } catch (\Exception $e) {
            $this->logError('register', $e->getMessage());
            $this->jsonError('Erreur lors de l\'inscription', 500);
        }
    }

    /**
     * POST /api/auth/login
     */
    public function login(): void
    {
        try {
            if (!$this->checkRateLimit('login', 10)) {
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            $email = trim(strtolower($input['email'] ?? ''));
            $password = $input['password'] ?? '';

            if (empty($email) || empty($password)) {
                $this->jsonError('Email et mot de passe requis', 400);
                return;
            }

            $user = $this->userRepo->findByEmail($email);

            if (!$user) {
                password_hash('dummy', PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
                $this->jsonError('Email ou mot de passe incorrect', 401);
                return;
            }

            if (!password_verify($password, $user['password_hash'])) {
                $this->logAction('login_failed', $user['id'], ['reason' => 'bad_password']);
                $this->jsonError('Email ou mot de passe incorrect', 401);
                return;
            }

            if (!$user['is_active']) {
                $this->jsonError('Ce compte a été désactivé', 403);
                return;
            }

            $token = $this->sessionRepo->create(
                $user['id'],
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            $this->userRepo->updateLastLogin($user['id']);
            $this->logAction('login', $user['id']);

            $redirect = $user['onboarding_completed'] ? '/app' : '/onboarding';

            $this->jsonSuccess([
                'token' => $token,
                'user' => $this->formatUserResponse($user),
                'redirect' => $redirect
            ]);

        } catch (\Exception $e) {
            $this->logError('login', $e->getMessage());
            $this->jsonError('Erreur lors de la connexion', 500);
        }
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(): void
    {
        try {
            $token = $this->extractToken();
            if ($token) {
                $this->sessionRepo->delete($token);
            }
            $this->jsonSuccess(['message' => 'Déconnexion réussie']);
        } catch (\Exception $e) {
            $this->logError('logout', $e->getMessage());
            $this->jsonError('Erreur lors de la déconnexion', 500);
        }
    }

    /**
     * GET /api/auth/me
     */
    public function me(): void
    {
        try {
            $token = $this->extractToken();
            if (!$token) {
                $this->jsonError('Non authentifié', 401);
                return;
            }

            $userData = $this->sessionRepo->validateToken($token);
            if (!$userData) {
                $this->jsonError('Session invalide ou expirée', 401);
                return;
            }

            $this->jsonSuccess([
                'user' => $this->formatUserResponse($userData),
                'onboarding_checklist' => [
                    'account_created' => true,
                    'first_quote' => $userData['first_quote_done'],
                    'profile_completed' => $userData['company']['profile_completed']
                ]
            ]);

        } catch (\Exception $e) {
            $this->logError('me', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * POST /api/auth/onboarding
     */
    public function updateOnboarding(): void
    {
        try {
            $token = $this->extractToken();
            if (!$token) {
                $this->jsonError('Non authentifié', 401);
                return;
            }

            $userData = $this->sessionRepo->validateToken($token);
            if (!$userData) {
                $this->jsonError('Session invalide', 401);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            $updates = [];
            if (isset($input['onboarding_step'])) {
                $updates['onboarding_step'] = (int) $input['onboarding_step'];
            }
            if (isset($input['pwa_installed'])) {
                $updates['pwa_installed'] = (bool) $input['pwa_installed'];
            }
            if (isset($input['notifications_enabled'])) {
                $updates['notifications_enabled'] = (bool) $input['notifications_enabled'];
            }
            if (isset($input['onboarding_completed'])) {
                $updates['onboarding_completed'] = (bool) $input['onboarding_completed'];
            }

            if (!empty($updates)) {
                $this->userRepo->updateOnboarding($userData['id'], $updates);
            }

            // Données company (si fournies pendant l'onboarding)
            $companyUpdates = [];
            if (!empty($input['company_name'])) {
                $companyUpdates['name'] = htmlspecialchars(trim($input['company_name']));
            }
            if (!empty($input['siret'])) {
                $companyUpdates['siret'] = htmlspecialchars(trim($input['siret']));
            }
            if (!empty($input['phone'])) {
                $companyUpdates['phone'] = htmlspecialchars(trim($input['phone']));
            }

            if (!empty($companyUpdates)) {
                $this->companyRepo->update($userData['company_id'], $companyUpdates);
            }

            // Données de tarification utilisateur
            $pricingUpdates = [];
            if (isset($input['hourly_rate'])) {
                $pricingUpdates['hourly_rate'] = (float) $input['hourly_rate'];
            }
            if (isset($input['product_margin'])) {
                $pricingUpdates['product_margin'] = (float) $input['product_margin'];
            }
            if (isset($input['supplier_discount'])) {
                $pricingUpdates['supplier_discount'] = (float) $input['supplier_discount'];
            }
            if (isset($input['travel_type']) && in_array($input['travel_type'], ['free', 'fixed', 'per_km'])) {
                $pricingUpdates['travel_type'] = $input['travel_type'];
            }
            if (isset($input['travel_fixed_amount'])) {
                $pricingUpdates['travel_fixed_amount'] = (float) $input['travel_fixed_amount'];
            }
            if (isset($input['travel_per_km'])) {
                $pricingUpdates['travel_per_km'] = (float) $input['travel_per_km'];
            }
            if (isset($input['travel_free_radius'])) {
                $pricingUpdates['travel_free_radius'] = (int) $input['travel_free_radius'];
            }

            if (!empty($pricingUpdates)) {
                $this->userRepo->updatePricing($userData['id'], $pricingUpdates);
            }

            $this->logAction('onboarding_update', $userData['id']);

            $user = $this->userRepo->findById($userData['id']);
            $this->jsonSuccess(['user' => $this->formatUserResponse($user)]);

        } catch (\Exception $e) {
            $this->logError('updateOnboarding', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * PUT /api/auth/profile
     */
    public function updateProfile(): void
    {
        try {
            $token = $this->extractToken();
            if (!$token) {
                $this->jsonError('Non authentifié', 401);
                return;
            }

            $userData = $this->sessionRepo->validateToken($token);
            if (!$userData) {
                $this->jsonError('Session invalide', 401);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            // Champs user
            $userUpdates = [];
            if (isset($input['first_name'])) {
                $userUpdates['first_name'] = htmlspecialchars(trim($input['first_name']));
            }
            if (isset($input['last_name'])) {
                $userUpdates['last_name'] = htmlspecialchars(trim($input['last_name']));
            }

            if (!empty($userUpdates)) {
                $this->userRepo->update($userData['id'], $userUpdates);
            }

            // Champs company (owner/admin seulement)
            $companyFields = ['name', 'siret', 'phone', 'email_contact',
                             'address_line1', 'address_line2', 'postal_code', 'city'];
            $companyUpdates = [];

            foreach ($companyFields as $field) {
                $inputKey = $field === 'name' ? 'company_name' : $field;
                if (isset($input[$inputKey])) {
                    $companyUpdates[$field] = htmlspecialchars(trim($input[$inputKey]));
                }
            }

            if (!empty($companyUpdates)) {
                if (!in_array($userData['role'], ['owner', 'admin'])) {
                    $this->jsonError('Vous n\'avez pas les droits pour modifier les infos entreprise', 403);
                    return;
                }
                $this->companyRepo->update($userData['company_id'], $companyUpdates);
                $this->companyRepo->checkAndUpdateProfileCompletion($userData['company_id']);
            }

            $this->logAction('profile_update', $userData['id']);

            $user = $this->userRepo->findById($userData['id']);
            $this->jsonSuccess(['user' => $this->formatUserResponse($user)]);

        } catch (\Exception $e) {
            $this->logError('updateProfile', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * POST /api/auth/change-password
     */
    public function changePassword(): void
    {
        try {
            $token = $this->extractToken();
            if (!$token) {
                $this->jsonError('Non authentifié', 401);
                return;
            }

            $userData = $this->sessionRepo->validateToken($token);
            if (!$userData) {
                $this->jsonError('Session invalide', 401);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            $currentPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';

            if (!password_verify($currentPassword, $userData['password_hash'])) {
                $this->jsonError('Mot de passe actuel incorrect', 401);
                return;
            }

            if (strlen($newPassword) < 8) {
                $this->jsonError('Le nouveau mot de passe doit contenir au moins 8 caractères', 400);
                return;
            }

            $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
            $this->userRepo->updatePassword($userData['id'], $newHash);

            $this->logAction('password_change', $userData['id']);
            $this->jsonSuccess(['message' => 'Mot de passe modifié avec succès']);

        } catch (\Exception $e) {
            $this->logError('changePassword', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * GET /api/auth/sessions
     */
    public function listSessions(): void
    {
        try {
            $token = $this->extractToken();
            if (!$token) {
                $this->jsonError('Non authentifié', 401);
                return;
            }

            $userData = $this->sessionRepo->validateToken($token);
            if (!$userData) {
                $this->jsonError('Session invalide', 401);
                return;
            }

            $sessions = $this->sessionRepo->getActiveSessionsForUser($userData['id']);
            $this->jsonSuccess(['sessions' => $sessions]);

        } catch (\Exception $e) {
            $this->logError('listSessions', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * DELETE /api/auth/sessions
     */
    public function logoutAll(): void
    {
        try {
            $token = $this->extractToken();
            if (!$token) {
                $this->jsonError('Non authentifié', 401);
                return;
            }

            $userData = $this->sessionRepo->validateToken($token);
            if (!$userData) {
                $this->jsonError('Session invalide', 401);
                return;
            }

            $this->sessionRepo->deleteAllForUser($userData['id']);

            $newToken = $this->sessionRepo->create(
                $userData['id'],
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            $this->logAction('logout_all', $userData['id']);

            $this->jsonSuccess([
                'token' => $newToken,
                'message' => 'Toutes les autres sessions ont été déconnectées'
            ]);

        } catch (\Exception $e) {
            $this->logError('logoutAll', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * GET /api/auth/quota
     */
    public function getQuota(): void
    {
        try {
            $token = $this->extractToken();
            if (!$token) {
                $this->jsonError('Non authentifié', 401);
                return;
            }

            $userData = $this->sessionRepo->validateToken($token);
            if (!$userData) {
                $this->jsonError('Session invalide', 401);
                return;
            }

            $company = $userData['company'];
            $enriched = $this->companyRepo->getEnrichedData($company);

            $this->jsonSuccess([
                'plan' => $enriched['plan'],
                'trial_active' => $enriched['trial_active'],
                'days_remaining' => $enriched['days_remaining'],
                'quotes_this_month' => $enriched['quotes_this_month'],
                'quotes_remaining' => $enriched['quotes_remaining']
            ]);

        } catch (\Exception $e) {
            $this->logError('getQuota', $e->getMessage());
            $this->jsonError('Erreur serveur', 500);
        }
    }

    /**
     * Extrait le token Bearer
     */
    private function extractToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        return $_COOKIE['auth_token'] ?? null;
    }

    /**
     * Formate la réponse user pour l'API
     */
    private function formatUserResponse(array $user): array
    {
        $company = $user['company'];
        $trialEndsAt = $company['trial_ends_at'];
        $trialActive = $trialEndsAt && strtotime($trialEndsAt) > time();
        $daysRemaining = $trialActive
            ? max(0, (int) ceil((strtotime($trialEndsAt) - time()) / 86400))
            : null;

        $quotesRemaining = $this->companyRepo->getQuotesRemaining($company['id']);

        return [
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'onboarding_step' => $user['onboarding_step'],
            'onboarding_completed' => $user['onboarding_completed'],
            'pwa_installed' => $user['pwa_installed'],
            'notifications_enabled' => $user['notifications_enabled'],
            'first_quote_done' => $user['first_quote_done'],
            'company' => [
                'id' => $company['id'],
                'name' => $company['name'],
                'siret' => $company['siret'],
                'phone' => $company['phone'],
                'email_contact' => $company['email_contact'],
                'address_line1' => $company['address_line1'],
                'address_line2' => $company['address_line2'],
                'postal_code' => $company['postal_code'],
                'city' => $company['city'],
                'logo_path' => $company['logo_path'],
                'plan' => $company['plan'],
                'trial_ends_at' => $trialEndsAt,
                'trial_active' => $trialActive,
                'days_remaining' => $daysRemaining,
                'quotes_remaining' => $quotesRemaining,
                'profile_completed' => $company['profile_completed']
            ]
        ];
    }

    private function checkRateLimit(string $endpoint, int $maxRequests = 30): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!$this->rateLimiter->check($ip, 'auth_' . $endpoint, $maxRequests)) {
            $retryAfter = $this->rateLimiter->getRetryAfter($ip, 'auth_' . $endpoint);
            header('Retry-After: ' . $retryAfter);
            $this->jsonError('Trop de tentatives. Réessayez dans ' . $retryAfter . ' secondes.', 429);
            return false;
        }
        return true;
    }

    private function logAction(string $action, int $userId, array $metadata = []): void
    {
        try {
            $db = \App\Database\Connection::getInstance();
            $stmt = $db->prepare(
                "INSERT INTO logs (level, action, message, context, ip_address, user_agent)
                 VALUES ('INFO', :action, :message, :context, :ip, :ua)"
            );
            $stmt->execute([
                'action' => 'auth_' . $action,
                'message' => "User ID: {$userId}",
                'context' => json_encode($metadata),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            // Silent
        }
    }

    private function jsonSuccess(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    }

    private function jsonError(string $message, int $code = 400, ?string $errorCode = null): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        $response = ['success' => false, 'error' => $message];
        if ($errorCode) {
            $response['code'] = $errorCode;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    private function logError(string $action, string $message): void
    {
        try {
            $db = \App\Database\Connection::getInstance();
            $stmt = $db->prepare(
                "INSERT INTO logs (level, action, message, ip_address, user_agent)
                 VALUES ('ERROR', :action, :message, :ip, :ua)"
            );
            $stmt->execute([
                'action' => 'auth_' . $action,
                'message' => $message,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            // Fallback fichier
            $logFile = __DIR__ . '/../../storage/logs/auth.log';
            @mkdir(dirname($logFile), 0755, true);
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [ERROR] {$action}: {$message}\n", FILE_APPEND);
        }
    }
}
