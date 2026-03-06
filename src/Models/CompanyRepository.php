<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

/**
 * Repository pour les entreprises
 */
class CompanyRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Crée une nouvelle entreprise
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO companies (
            name, siret, phone, email_contact,
            address_line1, address_line2, postal_code, city,
            plan, trial_ends_at, quotes_month_reset
        ) VALUES (
            :name, :siret, :phone, :email_contact,
            :address_line1, :address_line2, :postal_code, :city,
            :plan, :trial_ends_at, :quotes_month_reset
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $data['name'] ?? null,
            'siret' => $data['siret'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email_contact' => $data['email_contact'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'address_line2' => $data['address_line2'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'plan' => $data['plan'] ?? 'pro',
            'trial_ends_at' => $data['trial_ends_at'] ?? null,
            'quotes_month_reset' => date('Y-m-01')
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Recherche une entreprise par ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM companies WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $company = $stmt->fetch();

        return $company ?: null;
    }

    /**
     * Met à jour une entreprise
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'name', 'siret', 'phone', 'email_contact',
            'address_line1', 'address_line2', 'postal_code', 'city',
            'logo_path', 'plan', 'trial_ends_at',
            'default_tva_rate', 'quote_validity_days', 'profile_completed'
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

        $sql = "UPDATE companies SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Incrémente le compteur de devis du mois
     * Reset automatique si nouveau mois
     */
    public function incrementQuoteCount(int $id): void
    {
        $company = $this->findById($id);
        if (!$company) {
            return;
        }

        $currentMonth = date('Y-m-01');

        if ($company['quotes_month_reset'] !== $currentMonth) {
            // Nouveau mois, réinitialiser le compteur
            $stmt = $this->db->prepare(
                "UPDATE companies SET
                    quotes_this_month = 1,
                    quotes_month_reset = :month
                WHERE id = :id"
            );
            $stmt->execute(['id' => $id, 'month' => $currentMonth]);
        } else {
            // Même mois, incrémenter
            $stmt = $this->db->prepare(
                "UPDATE companies SET quotes_this_month = quotes_this_month + 1 WHERE id = :id"
            );
            $stmt->execute(['id' => $id]);
        }
    }

    /**
     * Retourne le nombre de devis restants pour le mois
     * @return int|string Nombre restant ou "unlimited"
     */
    public function getQuotesRemaining(int $id): int|string
    {
        $company = $this->findById($id);
        if (!$company) {
            return 0;
        }

        // Trial actif = illimité
        if ($company['trial_ends_at'] && strtotime($company['trial_ends_at']) > time()) {
            return 'unlimited';
        }

        // Plans payants = illimité
        if (in_array($company['plan'], ['pro', 'equipe'])) {
            return 'unlimited';
        }

        // Plan découverte = 10 - utilisés
        $currentMonth = date('Y-m-01');
        $used = ($company['quotes_month_reset'] === $currentMonth)
            ? $company['quotes_this_month']
            : 0;

        return max(0, 10 - $used);
    }

    /**
     * Vérifie si le profil est complet et met à jour le flag
     */
    public function checkAndUpdateProfileCompletion(int $id): bool
    {
        $company = $this->findById($id);
        if (!$company) {
            return false;
        }

        $isComplete = !empty($company['name'])
            && !empty($company['siret'])
            && !empty($company['postal_code'])
            && !empty($company['city']);

        if ($isComplete !== (bool) $company['profile_completed']) {
            $this->update($id, ['profile_completed' => $isComplete]);
        }

        return $isComplete;
    }

    /**
     * Retourne les infos enrichies pour l'API
     */
    public function getEnrichedData(array $company): array
    {
        $trialEndsAt = $company['trial_ends_at'];
        $trialActive = $trialEndsAt && strtotime($trialEndsAt) > time();

        $daysRemaining = null;
        if ($trialActive) {
            $daysRemaining = max(0, ceil((strtotime($trialEndsAt) - time()) / 86400));
        }

        $quotesRemaining = $this->getQuotesRemaining($company['id']);

        // Compter les devis du mois
        $currentMonth = date('Y-m-01');
        $quotesThisMonth = ($company['quotes_month_reset'] === $currentMonth)
            ? $company['quotes_this_month']
            : 0;

        return [
            'id' => (int) $company['id'],
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
            'quotes_this_month' => $quotesThisMonth,
            'quotes_remaining' => $quotesRemaining,
            'profile_completed' => (bool) $company['profile_completed'],
            'default_tva_rate' => (float) $company['default_tva_rate'],
            'quote_validity_days' => (int) $company['quote_validity_days']
        ];
    }

    /**
     * Compte les utilisateurs d'une entreprise
     */
    public function getUserCount(int $id): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM users WHERE company_id = :id AND is_active = 1"
        );
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Compte les devis totaux d'une entreprise
     */
    public function getQuotesCount(int $id): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM quotes WHERE company_id = :id"
        );
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Retourne le montant total des devis d'une entreprise
     */
    public function getQuotesTotalAmount(int $id): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total_ttc), 0) FROM quotes WHERE company_id = :id"
        );
        $stmt->execute(['id' => $id]);
        return (float) $stmt->fetchColumn();
    }
}
