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
            'onboarding_completed' => ($data['onboarding_completed'] ?? false) ? 1 : 0,
            'pwa_installed' => ($data['pwa_installed'] ?? false) ? 1 : 0,
            'notifications_enabled' => ($data['notifications_enabled'] ?? false) ? 1 : 0,
            'first_quote_done' => ($data['first_quote_done'] ?? false) ? 1 : 0
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
     * Met à jour les paramètres de tarification utilisateur
     */
    public function updatePricing(int $userId, array $data): bool
    {
        $allowedFields = [
            'hourly_rate', 'product_margin', 'supplier_discount', 'default_tier',
            'travel_type', 'travel_fixed_amount', 'travel_per_km', 'travel_free_radius'
        ];
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
     * Récupère les paramètres de tarification d'un utilisateur
     */
    public function getPricing(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT hourly_rate, product_margin, supplier_discount, travel_type,
                    travel_fixed_amount, travel_per_km, travel_free_radius
             FROM users WHERE id = :id"
        );
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!$row) {
            return [
                'hourly_rate' => 45.00,
                'product_margin' => 20.00,
                'supplier_discount' => 0.00,
                'travel_type' => 'free',
                'travel_fixed_amount' => 30.00,
                'travel_per_km' => 0.50,
                'travel_free_radius' => 20
            ];
        }

        return [
            'hourly_rate' => (float) $row['hourly_rate'],
            'product_margin' => (float) $row['product_margin'],
            'supplier_discount' => (float) ($row['supplier_discount'] ?? 0),
            'travel_type' => $row['travel_type'],
            'travel_fixed_amount' => (float) $row['travel_fixed_amount'],
            'travel_per_km' => (float) $row['travel_per_km'],
            'travel_free_radius' => (int) $row['travel_free_radius']
        ];
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

    /**
     * Récupère le profil complet d'un utilisateur (avec pricing et company)
     */
    public function getFullProfile(int $userId): ?array
    {
        $sql = "SELECT
            u.id, u.company_id, u.first_name, u.last_name, u.email,
            u.role, u.onboarding_step, u.onboarding_completed, u.pwa_installed,
            u.notifications_enabled, u.first_quote_done, u.is_active,
            u.hourly_rate, u.product_margin, u.supplier_discount, u.default_tier,
            u.travel_type, u.travel_fixed_amount, u.travel_per_km, u.travel_free_radius,
            u.created_at,
            c.name AS company_name, c.siret, c.vat_number, c.phone, c.email_contact,
            c.address_line1, c.address_line2, c.postal_code, c.city,
            c.logo_path, c.insurance_name, c.insurance_number,
            c.plan, c.trial_ends_at, c.quotes_this_month, c.profile_completed
        FROM users u
        JOIN companies c ON u.company_id = c.id
        WHERE u.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'company_id' => (int) $row['company_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'role' => $row['role'],
            'onboarding_completed' => (bool) $row['onboarding_completed'],
            // Pricing
            'hourly_rate' => $row['hourly_rate'] ? (float) $row['hourly_rate'] : null,
            'product_margin' => (float) ($row['product_margin'] ?? 20),
            'supplier_discount' => (float) ($row['supplier_discount'] ?? 0),
            'default_tier' => $row['default_tier'] ?? 'mid',
            'travel_type' => $row['travel_type'] ?? 'none',
            'travel_fixed_amount' => (float) ($row['travel_fixed_amount'] ?? 30),
            'travel_per_km' => (float) ($row['travel_per_km'] ?? 0.5),
            'travel_free_radius' => (int) ($row['travel_free_radius'] ?? 10),
            // Company
            'company_name' => $row['company_name'],
            'siret' => $row['siret'],
            'vat_number' => $row['vat_number'],
            'phone' => $row['phone'],
            'address_line1' => $row['address_line1'],
            'address_line2' => $row['address_line2'],
            'postal_code' => $row['postal_code'],
            'city' => $row['city'],
            'logo_path' => $row['logo_path'],
            'insurance_name' => $row['insurance_name'],
            'insurance_number' => $row['insurance_number'],
            // Plan
            'plan' => $row['plan'],
            'trial_ends_at' => $row['trial_ends_at'],
            'quotes_this_month' => (int) $row['quotes_this_month'],
            'profile_completed' => (bool) $row['profile_completed'],
            'created_at' => $row['created_at']
        ];
    }

    /**
     * Met à jour les informations de l'entreprise
     */
    public function updateCompany(int $companyId, array $data): bool
    {
        $allowedFields = [
            'name', 'siret', 'vat_number', 'phone', 'email_contact',
            'address_line1', 'address_line2', 'postal_code', 'city',
            'insurance_name', 'insurance_number', 'logo_path', 'profile_completed'
        ];

        $updates = [];
        $params = ['id' => $companyId];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE companies SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Met à jour le logo de l'entreprise
     */
    public function updateCompanyLogo(int $companyId, ?string $logoPath): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE companies SET logo_path = :logo_path WHERE id = :id"
        );
        return $stmt->execute(['id' => $companyId, 'logo_path' => $logoPath]);
    }

    /**
     * Récupère le company_id d'un utilisateur
     */
    public function getCompanyId(int $userId): ?int
    {
        $stmt = $this->db->prepare("SELECT company_id FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $result = $stmt->fetchColumn();
        return $result ? (int) $result : null;
    }
}
