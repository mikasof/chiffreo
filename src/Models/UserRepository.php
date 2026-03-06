<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

/**
 * Repository pour les utilisateurs
 * Architecture: User appartient à une Company (plan/quota au niveau company)
 */
class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO users (
            company_id, first_name, last_name, email, password_hash, role,
            onboarding_step, onboarding_completed, pwa_installed,
            notifications_enabled, first_quote_done
        ) VALUES (
            :company_id, :first_name, :last_name, :email, :password_hash, :role,
            :onboarding_step, :onboarding_completed, :pwa_installed,
            :notifications_enabled, :first_quote_done
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'company_id' => $data['company_id'],
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? null,
            'email' => strtolower(trim($data['email'])),
            'password_hash' => $data['password_hash'],
            'role' => $data['role'] ?? 'owner',
            'onboarding_step' => $data['onboarding_step'] ?? 0,
            'onboarding_completed' => $data['onboarding_completed'] ?? false,
            'pwa_installed' => $data['pwa_installed'] ?? false,
            'notifications_enabled' => $data['notifications_enabled'] ?? false,
            'first_quote_done' => $data['first_quote_done'] ?? false
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Recherche un utilisateur par email (avec données company)
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT
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
        FROM users u
        JOIN companies c ON u.company_id = c.id
        WHERE u.email = :email";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();

        return $row ? $this->formatUserWithCompany($row) : null;
    }

    /**
     * Recherche un utilisateur par ID (avec données company)
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT
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
        FROM users u
        JOIN companies c ON u.company_id = c.id
        WHERE u.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->formatUserWithCompany($row) : null;
    }

    /**
     * Liste tous les utilisateurs d'une entreprise
     */
    public function findByCompanyId(int $companyId): array
    {
        $sql = "SELECT id, first_name, last_name, email, role,
                       onboarding_completed, is_active, created_at, last_login_at
                FROM users
                WHERE company_id = :company_id
                ORDER BY created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['company_id' => $companyId]);
        return $stmt->fetchAll();
    }

    /**
     * Met à jour un utilisateur (mise à jour partielle)
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'first_name', 'last_name', 'email', 'password_hash', 'role',
            'onboarding_step', 'onboarding_completed', 'pwa_installed',
            'notifications_enabled', 'first_quote_done', 'is_active',
            'email_verified_at', 'last_login_at'
        ];

        $updates = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Met à jour la dernière connexion
     */
    public function updateLastLogin(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET last_login_at = NOW() WHERE id = :id"
        );
        return $stmt->execute(['id' => $userId]);
    }

    /**
     * Vérifie si un email existe déjà
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM users WHERE email = :email"
        );
        $stmt->execute(['email' => strtolower(trim($email))]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Met à jour le mot de passe
     */
    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET password_hash = :hash WHERE id = :id"
        );
        return $stmt->execute(['id' => $userId, 'hash' => $passwordHash]);
    }

    /**
     * Marque le premier devis comme fait
     */
    public function markFirstQuoteDone(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET first_quote_done = 1 WHERE id = :id AND first_quote_done = 0"
        );
        return $stmt->execute(['id' => $userId]);
    }

    /**
     * Met à jour les flags d'onboarding
     */
    public function updateOnboarding(int $userId, array $data): bool
    {
        $allowedFields = ['onboarding_step', 'onboarding_completed', 'pwa_installed', 'notifications_enabled'];
        $updates = [];
        $params = ['id' => $userId];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Formate les données brutes en structure user + company imbriquée
     */
    private function formatUserWithCompany(array $row): array
    {
        return [
            'id' => (int) $row['id'],
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
     * Vérifie si l'utilisateur a un rôle permettant de modifier la company
     */
    public function canEditCompany(int $userId): bool
    {
        $user = $this->findById($userId);
        if (!$user) {
            return false;
        }
        return in_array($user['role'], ['owner', 'admin']);
    }

    /**
     * Compte les utilisateurs actifs d'une company
     */
    public function countActiveByCompany(int $companyId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM users WHERE company_id = :company_id AND is_active = 1"
        );
        $stmt->execute(['company_id' => $companyId]);
        return (int) $stmt->fetchColumn();
    }
}
