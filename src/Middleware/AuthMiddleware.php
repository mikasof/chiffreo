<?php

namespace App\Middleware;

use App\Models\SessionRepository;
use App\Models\CompanyRepository;
use App\Services\QuotaService;

/**
 * Middleware d'authentification
 * Architecture: User avec Company imbriquée (plan/quota au niveau company)
 */
class AuthMiddleware
{
    private SessionRepository $sessionRepo;
    private CompanyRepository $companyRepo;
    private ?QuotaService $quotaService = null;

    // Utilisateur authentifié (avec company)
    private ?array $user = null;

    public function __construct()
    {
        $this->sessionRepo = new SessionRepository();
        $this->companyRepo = new CompanyRepository();
    }

    /**
     * Vérifie l'authentification
     */
    public function check(): bool
    {
        $token = $this->extractToken();

        if (!$token) {
            return false;
        }

        $userData = $this->sessionRepo->validateToken($token);

        if (!$userData) {
            return false;
        }

        $this->user = $userData;
        return true;
    }

    /**
     * Vérifie l'authentification et bloque si non authentifié
     */
    public function requireAuth(): ?array
    {
        if (!$this->check()) {
            $this->sendUnauthorized();
            return null;
        }

        return $this->user;
    }

    /**
     * Vérifie si l'utilisateur peut créer un devis (quota company)
     */
    public function checkQuota(): bool
    {
        if (!$this->user) {
            return false;
        }

        return $this->getQuotaService()->canCreateQuote($this->user);
    }

    /**
     * Vérifie le quota et bloque si atteint
     */
    public function requireQuota(): bool
    {
        if (!$this->checkQuota()) {
            $this->sendQuotaExceeded();
            return false;
        }

        return true;
    }

    /**
     * Retourne l'utilisateur authentifié (avec company)
     */
    public function getUser(): ?array
    {
        return $this->user;
    }

    /**
     * Retourne l'ID de l'utilisateur
     */
    public function getUserId(): ?int
    {
        return $this->user ? (int) $this->user['id'] : null;
    }

    /**
     * Retourne l'ID de la company
     */
    public function getCompanyId(): ?int
    {
        return $this->user ? (int) $this->user['company_id'] : null;
    }

    /**
     * Retourne la company de l'utilisateur
     */
    public function getCompany(): ?array
    {
        return $this->user ? $this->user['company'] : null;
    }

    /**
     * Vérifie si l'utilisateur a un plan spécifique
     */
    public function hasPlan(string $plan): bool
    {
        if (!$this->user) {
            return false;
        }

        return $this->user['company']['plan'] === $plan;
    }

    /**
     * Vérifie si la company est en période d'essai
     */
    public function isInTrial(): bool
    {
        if (!$this->user) {
            return false;
        }

        $trialEndsAt = $this->user['company']['trial_ends_at'];
        return $trialEndsAt && strtotime($trialEndsAt) > time();
    }

    /**
     * Vérifie si la company a un accès Pro (trial ou plan payant)
     */
    public function hasProAccess(): bool
    {
        if (!$this->user) {
            return false;
        }

        // En période d'essai = accès Pro
        if ($this->isInTrial()) {
            return true;
        }

        return in_array($this->user['company']['plan'], ['pro', 'equipe']);
    }

    /**
     * Vérifie si l'utilisateur peut modifier les infos company
     */
    public function canEditCompany(): bool
    {
        if (!$this->user) {
            return false;
        }

        return in_array($this->user['role'], ['owner', 'admin']);
    }

    /**
     * Incrémente le compteur de devis de la company
     */
    public function incrementQuoteCount(): void
    {
        if ($this->user) {
            $this->companyRepo->incrementQuoteCount($this->user['company_id']);
        }
    }

    /**
     * Authentification optionnelle (charge user si token présent)
     */
    public function optionalAuth(): ?array
    {
        $this->check();
        return $this->user;
    }

    /**
     * Extrait le token du header Authorization ou cookie
     */
    private function extractToken(): ?string
    {
        // Header Authorization: Bearer xxx
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        // Apache peut mettre le header différemment
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        // Fallback: cookie
        return $_COOKIE['auth_token'] ?? null;
    }

    /**
     * Envoie une réponse 401 Unauthorized
     */
    private function sendUnauthorized(): void
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Authentification requise',
            'code' => 'UNAUTHORIZED'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Envoie une réponse 403 Quota Exceeded
     */
    private function sendQuotaExceeded(): void
    {
        $quotaInfo = null;
        if ($this->user) {
            $company = $this->user['company'];
            $currentMonth = date('Y-m-01');
            $quotesUsed = ($company['quotes_month_reset'] === $currentMonth)
                ? $company['quotes_this_month']
                : 0;

            $quotaInfo = [
                'quotes_used' => $quotesUsed,
                'quotes_limit' => 10,
                'plan' => $company['plan']
            ];
        }

        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Quota mensuel atteint. Passez au plan Pro pour des devis illimités.',
            'code' => 'QUOTA_EXCEEDED',
            'quota' => $quotaInfo,
            'upgrade_url' => '/pricing'
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Lazy load du QuotaService
     */
    private function getQuotaService(): QuotaService
    {
        if ($this->quotaService === null) {
            $this->quotaService = new QuotaService();
        }
        return $this->quotaService;
    }
}
