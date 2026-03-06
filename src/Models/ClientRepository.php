<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

/**
 * Repository pour les opérations sur les clients
 * Données personnelles stockées localement uniquement
 */
class ClientRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Crée un nouveau client
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO clients (
            user_id, civilite, nom, prenom, societe,
            email, telephone, telephone_mobile,
            adresse_ligne1, adresse_ligne2, code_postal, ville, pays, notes
        ) VALUES (
            :user_id, :civilite, :nom, :prenom, :societe,
            :email, :telephone, :telephone_mobile,
            :adresse_ligne1, :adresse_ligne2, :code_postal, :ville, :pays, :notes
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'civilite' => $data['civilite'] ?? 'M.',
            'nom' => $data['nom'],
            'prenom' => $data['prenom'] ?? null,
            'societe' => $data['societe'] ?? null,
            'email' => $data['email'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'telephone_mobile' => $data['telephone_mobile'] ?? null,
            'adresse_ligne1' => $data['adresse'] ?? $data['adresse_ligne1'] ?? null,
            'adresse_ligne2' => $data['adresse_ligne2'] ?? null,
            'code_postal' => $data['code_postal'] ?? $data['codePostal'] ?? null,
            'ville' => $data['ville'] ?? null,
            'pays' => $data['pays'] ?? 'France',
            'notes' => $data['notes'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Trouve ou crée un client par téléphone/email
     * Évite les doublons
     */
    public function findOrCreate(array $data): int
    {
        // Chercher par téléphone ou email
        $existing = null;

        if (!empty($data['telephone'])) {
            $existing = $this->findByPhone($data['telephone']);
        }

        if (!$existing && !empty($data['email'])) {
            $existing = $this->findByEmail($data['email']);
        }

        if ($existing) {
            // Mettre à jour si nécessaire
            $this->update($existing['id'], $data);
            return (int) $existing['id'];
        }

        return $this->create($data);
    }

    /**
     * Trouve un client par téléphone
     */
    public function findByPhone(string $phone): ?array
    {
        // Normaliser le numéro
        $phoneClean = preg_replace('/[^\d+]/', '', $phone);

        $stmt = $this->db->prepare(
            "SELECT * FROM clients
             WHERE REPLACE(REPLACE(REPLACE(telephone, ' ', ''), '-', ''), '.', '') = :phone
             OR REPLACE(REPLACE(REPLACE(telephone_mobile, ' ', ''), '-', ''), '.', '') = :phone
             LIMIT 1"
        );
        $stmt->execute(['phone' => $phoneClean]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Trouve un client par email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM clients WHERE email = :email LIMIT 1"
        );
        $stmt->execute(['email' => strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Trouve un client par ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Met à jour un client
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = [
            'civilite', 'nom', 'prenom', 'societe', 'email',
            'telephone', 'telephone_mobile', 'adresse_ligne1',
            'adresse_ligne2', 'code_postal', 'ville', 'pays', 'notes'
        ];

        // Mapper les noms de champs alternatifs
        $fieldMapping = [
            'adresse' => 'adresse_ligne1',
            'codePostal' => 'code_postal'
        ];

        foreach ($data as $key => $value) {
            $dbField = $fieldMapping[$key] ?? $key;
            if (in_array($dbField, $allowedFields) && $value !== null) {
                $fields[] = "{$dbField} = :{$dbField}";
                $params[$dbField] = $value;
            }
        }

        if (empty($fields)) {
            return true;
        }

        $sql = "UPDATE clients SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Recherche de clients
     */
    public function search(string $query, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM clients
             WHERE MATCH(nom, prenom, societe, email, ville) AGAINST(:query IN BOOLEAN MODE)
             OR nom LIKE :like
             OR societe LIKE :like
             LIMIT :limit"
        );
        $stmt->bindValue('query', $query, PDO::PARAM_STR);
        $stmt->bindValue('like', "%{$query}%", PDO::PARAM_STR);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste les clients récents
     */
    public function findRecent(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM clients ORDER BY updated_at DESC LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
