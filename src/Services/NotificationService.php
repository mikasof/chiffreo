<?php

namespace App\Services;

use App\Database\Connection;
use PDO;

/**
 * Service de notifications push (Web Push)
 * Utilise le protocole Web Push pour envoyer des notifications aux navigateurs
 */
class NotificationService
{
    private PDO $db;

    // Clés VAPID (à générer et configurer dans .env)
    private ?string $vapidPublicKey;
    private ?string $vapidPrivateKey;

    public function __construct()
    {
        $this->db = Connection::getInstance();
        $this->vapidPublicKey = $_ENV['VAPID_PUBLIC_KEY'] ?? null;
        $this->vapidPrivateKey = $_ENV['VAPID_PRIVATE_KEY'] ?? null;
    }

    /**
     * Enregistre un abonnement push pour un utilisateur
     */
    public function subscribe(int $userId, array $subscription): int
    {
        // Vérifier si l'endpoint existe déjà
        $stmt = $this->db->prepare(
            "SELECT id FROM push_subscriptions WHERE endpoint = :endpoint"
        );
        $stmt->execute(['endpoint' => $subscription['endpoint']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Mettre à jour l'abonnement existant
            $stmt = $this->db->prepare(
                "UPDATE push_subscriptions SET
                    user_id = :user_id,
                    p256dh_key = :p256dh,
                    auth_key = :auth,
                    user_agent = :ua,
                    last_used_at = NOW()
                WHERE id = :id"
            );
            $stmt->execute([
                'id' => $existing['id'],
                'user_id' => $userId,
                'p256dh' => $subscription['keys']['p256dh'] ?? '',
                'auth' => $subscription['keys']['auth'] ?? '',
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            return $existing['id'];
        }

        // Créer un nouvel abonnement
        $stmt = $this->db->prepare(
            "INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key, user_agent)
             VALUES (:user_id, :endpoint, :p256dh, :auth, :ua)"
        );
        $stmt->execute([
            'user_id' => $userId,
            'endpoint' => $subscription['endpoint'],
            'p256dh' => $subscription['keys']['p256dh'] ?? '',
            'auth' => $subscription['keys']['auth'] ?? '',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Supprime un abonnement push
     */
    public function unsubscribe(string $endpoint): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM push_subscriptions WHERE endpoint = :endpoint"
        );
        return $stmt->execute(['endpoint' => $endpoint]);
    }

    /**
     * Envoie une notification à un utilisateur
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM push_subscriptions WHERE user_id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);
        $subscriptions = $stmt->fetchAll();

        $results = [
            'sent' => 0,
            'failed' => 0,
            'removed' => 0
        ];

        foreach ($subscriptions as $sub) {
            $result = $this->sendNotification($sub, $title, $body, $data);

            if ($result === true) {
                $results['sent']++;
            } elseif ($result === 'expired') {
                // Supprimer l'abonnement expiré
                $this->unsubscribe($sub['endpoint']);
                $results['removed']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Envoie une notification à tous les utilisateurs
     */
    public function sendToAll(string $title, string $body, array $data = []): array
    {
        $stmt = $this->db->query("SELECT DISTINCT user_id FROM push_subscriptions");
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $totalResults = [
            'users' => count($userIds),
            'sent' => 0,
            'failed' => 0,
            'removed' => 0
        ];

        foreach ($userIds as $userId) {
            $results = $this->sendToUser($userId, $title, $body, $data);
            $totalResults['sent'] += $results['sent'];
            $totalResults['failed'] += $results['failed'];
            $totalResults['removed'] += $results['removed'];
        }

        return $totalResults;
    }

    /**
     * Envoie une notification à un abonnement spécifique
     * Note: Cette implémentation est simplifiée. Pour la production,
     * utilisez la bibliothèque web-push-php
     */
    private function sendNotification(array $subscription, string $title, string $body, array $data = [])
    {
        if (!$this->vapidPublicKey || !$this->vapidPrivateKey) {
            // VAPID non configuré, logger et retourner false
            error_log('NotificationService: VAPID keys not configured');
            return false;
        }

        // Payload de la notification
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/icons/icon-192.png',
            'badge' => '/icons/badge-72.png',
            'data' => $data,
            'timestamp' => time() * 1000
        ]);

        // Pour une implémentation complète, utilisez:
        // composer require minishlink/web-push
        //
        // use Minishlink\WebPush\WebPush;
        // use Minishlink\WebPush\Subscription;
        //
        // $webPush = new WebPush(['VAPID' => [...]])
        // $webPush->sendOneNotification(Subscription::create([...]), $payload);

        // Implémentation simplifiée avec cURL
        try {
            $ch = curl_init($subscription['endpoint']);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload),
                    'TTL: 86400'
                ],
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // 201 = Created (succès)
            // 410 = Gone (abonnement expiré)
            // 404 = Not Found (abonnement invalide)
            if ($httpCode === 201) {
                // Mettre à jour last_used_at
                $stmt = $this->db->prepare(
                    "UPDATE push_subscriptions SET last_used_at = NOW() WHERE id = :id"
                );
                $stmt->execute(['id' => $subscription['id']]);
                return true;
            } elseif ($httpCode === 410 || $httpCode === 404) {
                return 'expired';
            } else {
                return false;
            }

        } catch (\Exception $e) {
            error_log('Push notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtient le nombre d'abonnements pour un utilisateur
     */
    public function getSubscriptionCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM push_subscriptions WHERE user_id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Nettoie les anciens abonnements non utilisés (>90 jours)
     */
    public function cleanupOldSubscriptions(): int
    {
        $stmt = $this->db->prepare(
            "DELETE FROM push_subscriptions
             WHERE last_used_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
             OR (last_used_at IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY))"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Retourne la clé publique VAPID pour le frontend
     */
    public function getPublicKey(): ?string
    {
        return $this->vapidPublicKey;
    }
}
