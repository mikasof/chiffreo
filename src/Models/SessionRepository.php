<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

/**
 * Repository pour les sessions utilisateurs
 */
class SessionRepository
{
    private PDO $db;

    // Durée de validité d'une session (30 jours)
    private const SESSION_DURATION = 30 * 24 * 60 * 60;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Génère un token de session sécurisé
     */
    public function generateToken(): string
    {
        return bin2hex(random_bytes(32)); // 64 caractères hex
    }

    /**
     * Crée une nouvelle session
     */
    public function create(int $userId, ?string $userAgent = null, ?string $ipAddress = null): string
    {
        $token = $this->generateToken();
        $deviceType = $this->detectDeviceType($userAgent);
        $expiresAt = date('Y-m-d H:i:s', time() + self::SESSION_DURATION);

        $sql = "INSERT INTO user_sessions (
            user_id, token, user_agent, ip_address, device_type, expires_at, last_activity_at
        ) VALUES (
            :user_id, :token, :user_agent, :ip_address, :device_type, :expires_at, NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'token' => $token,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'ip_address' => $ipAddress,
            'device_type' => $deviceType,
            'expires_at' => $expiresAt
        ]);

        return $token;
    }

    /**
     * Valide un token et retourne l'utilisateur avec sa company
     */
    public function validateToken(string $token): ?array
    {
        $sql = "SELECT
            s.id AS session_id, s.user_id, s.token, s.expires_at AS session_expires_at,
            s.device_type, s.user_agent AS session_user_agent, s.ip_address, s.last_activity_at,
            u.id, u.company_id, u.first_name, u.last_name, u.email, u.password_hash,
            u.role, u.onboarding_step, u.onboarding_completed, u.pwa_installed,
            u.notifications_enabled, u.first_quote_done, u.is_active,
            u.email_verified_at, u.created_at, u.updated_at, u.last_login_at,
            c.id AS c_id, c.name AS c_name, c.siret AS c_siret, c.phone AS c_phone,
            c.email_contact AS c_email_contact, c.address_line1 AS c_address_line1,
            c.address_line2 AS c_address_line2, c.postal_code AS c_postal_code,
            c.city AS c_city, c.logo_path AS c_logo_path, c.plan AS c_plan,
            c.trial_ends_at AS c_trial_ends_at, c.quotes_this_month AS c_quotes_this_month,
            c.quotes_month_reset AS c_quotes_month_reset, c.default_tva_rate AS c_default_tva_rate,
            c.quote_validity_days AS c_quote_validity_days, c.profile_completed AS c_profile_completed
        FROM user_sessions s
        JOIN users u ON s.user_id = u.id
        JOIN companies c ON u.company_id = c.id
        WHERE s.token = :token
        AND s.expires_at > NOW()
        AND u.is_active = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        // Mettre à jour la dernière activité
        $this->updateActivity($token);

        // Formater en structure user + company imbriquée
        return $this->formatUserWithCompany($row);
    }

    /**
     * Formate les données brutes en structure user + company imbriquée
     */
    private function formatUserWithCompany(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'company_id' => (int) $row['company_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'password_hash' => $row['password_hash'],
            'role' => $row['role'],
            'onboarding_step' => (int) $row['onboarding_step'],
            'onboarding_completed' => (bool) $row['onboarding_completed'],
            'pwa_installed' => (bool) $row['pwa_installed'],
            'notifications_enabled' => (bool) $row['notifications_enabled'],
            'first_quote_done' => (bool) $row['first_quote_done'],
            'is_active' => (bool) $row['is_active'],
            'email_verified_at' => $row['email_verified_at'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'last_login_at' => $row['last_login_at'],
            'company' => [
                'id' => (int) $row['c_id'],
                'name' => $row['c_name'],
                'siret' => $row['c_siret'],
                'phone' => $row['c_phone'],
                'email_contact' => $row['c_email_contact'],
                'address_line1' => $row['c_address_line1'],
                'address_line2' => $row['c_address_line2'],
                'postal_code' => $row['c_postal_code'],
                'city' => $row['c_city'],
                'logo_path' => $row['c_logo_path'],
                'plan' => $row['c_plan'],
                'trial_ends_at' => $row['c_trial_ends_at'],
                'quotes_this_month' => (int) $row['c_quotes_this_month'],
                'quotes_month_reset' => $row['c_quotes_month_reset'],
                'default_tva_rate' => (float) $row['c_default_tva_rate'],
                'quote_validity_days' => (int) $row['c_quote_validity_days'],
                'profile_completed' => (bool) $row['c_profile_completed']
            ]
        ];
    }

    /**
     * Met à jour la dernière activité de la session
     */
    public function updateActivity(string $token): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE user_sessions SET last_activity_at = NOW() WHERE token = :token"
        );
        return $stmt->execute(['token' => $token]);
    }

    /**
     * Supprime une session (déconnexion)
     */
    public function delete(string $token): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM user_sessions WHERE token = :token"
        );
        return $stmt->execute(['token' => $token]);
    }

    /**
     * Supprime toutes les sessions d'un utilisateur
     */
    public function deleteAllForUser(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM user_sessions WHERE user_id = :user_id"
        );
        return $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Supprime les sessions expirées (maintenance)
     */
    public function deleteExpired(): int
    {
        $stmt = $this->db->prepare(
            "DELETE FROM user_sessions WHERE expires_at < NOW()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Liste les sessions actives d'un utilisateur
     */
    public function getActiveSessionsForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, device_type, user_agent, ip_address, created_at, last_activity_at
             FROM user_sessions
             WHERE user_id = :user_id
             AND expires_at > NOW()
             ORDER BY last_activity_at DESC"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Prolonge la durée d'une session
     */
    public function extendSession(string $token): bool
    {
        $expiresAt = date('Y-m-d H:i:s', time() + self::SESSION_DURATION);

        $stmt = $this->db->prepare(
            "UPDATE user_sessions SET expires_at = :expires_at WHERE token = :token"
        );
        return $stmt->execute(['token' => $token, 'expires_at' => $expiresAt]);
    }

    /**
     * Détecte le type d'appareil à partir du User-Agent
     */
    private function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($userAgent);

        // Mobile
        if (preg_match('/(iphone|android.*mobile|ipod|blackberry|windows phone)/i', $userAgent)) {
            return 'mobile';
        }

        // Tablet
        if (preg_match('/(ipad|android(?!.*mobile)|tablet)/i', $userAgent)) {
            return 'tablet';
        }

        // Desktop
        if (preg_match('/(windows|macintosh|linux)/i', $userAgent)) {
            return 'desktop';
        }

        return 'unknown';
    }

    /**
     * Compte les sessions actives d'un utilisateur
     */
    public function countActiveSessions(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM user_sessions
             WHERE user_id = :user_id
             AND expires_at > NOW()"
        );
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
