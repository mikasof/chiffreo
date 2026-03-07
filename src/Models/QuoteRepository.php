<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

/**
 * Repository pour les opérations sur les devis
 */
class QuoteRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Génère une référence unique pour le devis
     */
    public function generateReference(): string
    {
        $year = date('Y');
        $prefix = "DEV-{$year}-";

        // Trouver le dernier numéro de l'année
        $stmt = $this->db->prepare(
            "SELECT reference FROM quotes
             WHERE reference LIKE :prefix
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last) {
            $lastNum = (int) substr($last, -4);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }

        return $prefix . str_pad($newNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Crée un nouveau devis
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO quotes (
            reference, company_id, user_id,
            client_name, client_company, client_email, client_phone, client_address,
            titre, localisation, perimetre,
            chantier_adresse, chantier_code_postal, chantier_ville,
            description_originale, transcription_audio, ai_response, quote_data,
            total_ht, total_tva, total_ttc, taux_tva, status, expires_at
        ) VALUES (
            :reference, :company_id, :user_id,
            :client_name, :client_company, :client_email, :client_phone, :client_address,
            :titre, :localisation, :perimetre,
            :chantier_adresse, :chantier_code_postal, :chantier_ville,
            :description_originale, :transcription_audio, :ai_response, :quote_data,
            :total_ht, :total_tva, :total_ttc, :taux_tva, :status, :expires_at
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'reference' => $data['reference'],
            'company_id' => $data['company_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'client_name' => $data['client_name'] ?? null,
            'client_company' => $data['client_company'] ?? null,
            'client_email' => $data['client_email'] ?? null,
            'client_phone' => $data['client_phone'] ?? null,
            'client_address' => $data['client_address'] ?? null,
            'titre' => $data['titre'] ?? null,
            'localisation' => $data['localisation'] ?? null,
            'perimetre' => $data['perimetre'] ?? null,
            'chantier_adresse' => $data['chantier_adresse'] ?? null,
            'chantier_code_postal' => $data['chantier_code_postal'] ?? null,
            'chantier_ville' => $data['chantier_ville'] ?? null,
            'description_originale' => $data['description_originale'] ?? null,
            'transcription_audio' => $data['transcription_audio'] ?? null,
            'ai_response' => json_encode($data['ai_response'] ?? null, JSON_UNESCAPED_UNICODE),
            'quote_data' => json_encode($data['quote_data'] ?? null, JSON_UNESCAPED_UNICODE),
            'total_ht' => $data['total_ht'] ?? 0,
            'total_tva' => $data['total_tva'] ?? 0,
            'total_ttc' => $data['total_ttc'] ?? 0,
            'taux_tva' => $data['taux_tva'] ?? 20.00,
            'status' => $data['status'] ?? 'draft',
            'expires_at' => $data['expires_at'] ?? date('Y-m-d', strtotime('+30 days'))
        ]);

        $quoteId = (int) $this->db->lastInsertId();

        // Insérer les lignes de devis
        if (!empty($data['quote_data']['lignes'])) {
            $this->insertQuoteItems($quoteId, $data['quote_data']['lignes']);
        }

        return $quoteId;
    }

    /**
     * Insère les lignes du devis
     */
    private function insertQuoteItems(int $quoteId, array $lignes): void
    {
        $sql = "INSERT INTO quote_items (
            quote_id, designation, categorie, unite, quantite,
            prix_unitaire_ht, total_ht, prix_ref_code, commentaire, ordre
        ) VALUES (
            :quote_id, :designation, :categorie, :unite, :quantite,
            :prix_unitaire_ht, :total_ht, :prix_ref_code, :commentaire, :ordre
        )";

        $stmt = $this->db->prepare($sql);
        $ordre = 0;

        foreach ($lignes as $ligne) {
            $stmt->execute([
                'quote_id' => $quoteId,
                'designation' => $ligne['designation'],
                'categorie' => $ligne['categorie'],
                'unite' => $ligne['unite'],
                'quantite' => $ligne['quantite'],
                'prix_unitaire_ht' => $ligne['prix_unitaire_ht'],
                'total_ht' => $ligne['total_ligne_ht'],
                'prix_ref_code' => $ligne['prix_ref_code'],
                'commentaire' => $ligne['commentaire'] ?? null,
                'ordre' => $ordre++
            ]);
        }
    }

    /**
     * Récupère un devis par son ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM quotes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $quote = $stmt->fetch();

        if (!$quote) {
            return null;
        }

        // Décoder les champs JSON
        $quote['ai_response'] = json_decode($quote['ai_response'], true);
        $quote['quote_data'] = json_decode($quote['quote_data'], true);

        return $quote;
    }

    /**
     * Récupère un devis par sa référence
     */
    public function findByReference(string $reference): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM quotes WHERE reference = :reference");
        $stmt->execute(['reference' => $reference]);
        $quote = $stmt->fetch();

        if (!$quote) {
            return null;
        }

        $quote['ai_response'] = json_decode($quote['ai_response'], true);
        $quote['quote_data'] = json_decode($quote['quote_data'], true);

        return $quote;
    }

    /**
     * Liste les devis avec pagination
     */
    public function findAll(int $limit = 20, int $offset = 0, ?int $companyId = null): array
    {
        $sql = "SELECT id, reference, titre, client_name, total_ttc, status, created_at
                FROM quotes";

        if ($companyId !== null) {
            $sql .= " WHERE company_id = :company_id";
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        if ($companyId !== null) {
            $stmt->bindValue('company_id', $companyId, PDO::PARAM_INT);
        }

        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste les devis d'une company
     */
    public function findByCompanyId(int $companyId, int $limit = 50, int $offset = 0): array
    {
        return $this->findAll($limit, $offset, $companyId);
    }

    /**
     * Compte les devis d'une company
     */
    public function countByCompanyId(int $companyId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM quotes WHERE company_id = :company_id"
        );
        $stmt->execute(['company_id' => $companyId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Met à jour le statut d'un devis
     */
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE quotes SET status = :status WHERE id = :id"
        );
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    /**
     * Ajoute un attachement à un devis
     */
    public function addAttachment(int $quoteId, array $attachment): int
    {
        $sql = "INSERT INTO attachments (
            quote_id, type, filename, filepath, mime_type, file_size
        ) VALUES (
            :quote_id, :type, :filename, :filepath, :mime_type, :file_size
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'quote_id' => $quoteId,
            'type' => $attachment['type'],
            'filename' => $attachment['filename'],
            'filepath' => $attachment['filepath'],
            'mime_type' => $attachment['mime_type'],
            'file_size' => $attachment['file_size']
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Récupère les attachements d'un devis
     */
    public function getAttachments(int $quoteId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM attachments WHERE quote_id = :quote_id"
        );
        $stmt->execute(['quote_id' => $quoteId]);
        return $stmt->fetchAll();
    }

    /**
     * Liste les devis d'un utilisateur
     */
    public function findByUserId(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, reference, titre, client_name, total_ttc, status, created_at
             FROM quotes
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Supprime un devis et ses lignes associées
     */
    public function delete(int $id): bool
    {
        // Supprimer les lignes du devis
        $stmt = $this->db->prepare("DELETE FROM quote_items WHERE quote_id = :id");
        $stmt->execute(['id' => $id]);

        // Supprimer les attachements
        $stmt = $this->db->prepare("DELETE FROM attachments WHERE quote_id = :id");
        $stmt->execute(['id' => $id]);

        // Supprimer le devis
        $stmt = $this->db->prepare("DELETE FROM quotes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Met à jour un devis existant
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        // Champs possibles à mettre à jour
        $allowedFields = [
            'client_name', 'client_company', 'client_email', 'client_phone', 'client_address',
            'chantier_adresse', 'chantier_code_postal', 'chantier_ville',
            'titre', 'perimetre',
            'total_ht', 'total_tva', 'total_ttc', 'taux_tva',
            'status'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        // quote_data nécessite un traitement JSON
        if (array_key_exists('quote_data', $data)) {
            $fields[] = "quote_data = :quote_data";
            $params['quote_data'] = json_encode($data['quote_data'], JSON_UNESCAPED_UNICODE);
        }

        if (empty($fields)) {
            return true; // Rien à mettre à jour
        }

        $sql = "UPDATE quotes SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);

        // Mettre à jour les lignes du devis si quote_data contient des lignes
        if (isset($data['quote_data']['lignes'])) {
            // Supprimer les anciennes lignes
            $stmt = $this->db->prepare("DELETE FROM quote_items WHERE quote_id = :id");
            $stmt->execute(['id' => $id]);

            // Insérer les nouvelles lignes
            $this->insertQuoteItems($id, $data['quote_data']['lignes']);
        }

        return $result;
    }
}
