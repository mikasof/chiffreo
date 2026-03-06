<?php

namespace App\Middleware;

use App\Database\Connection;
use PDO;

/**
 * Rate Limiter simple basé sur IP
 */
class RateLimiter
{
    private PDO $db;
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 30, int $windowSeconds = 60)
    {
        $this->db = Connection::getInstance();
        $this->maxRequests = (int) ($_ENV['RATE_LIMIT_PER_MINUTE'] ?? $maxRequests);
        $this->windowSeconds = $windowSeconds;
    }

    /**
     * Vérifie si la requête est autorisée
     *
     * @param string $ip Adresse IP
     * @param string $endpoint Endpoint appelé
     * @return bool True si autorisé
     */
    public function check(string $ip, string $endpoint): bool
    {
        $this->cleanup();

        $stmt = $this->db->prepare(
            "SELECT request_count, window_start
             FROM rate_limits
             WHERE ip_address = :ip AND endpoint = :endpoint"
        );
        $stmt->execute(['ip' => $ip, 'endpoint' => $endpoint]);
        $record = $stmt->fetch();

        $now = time();

        if (!$record) {
            // Première requête
            $this->insert($ip, $endpoint);
            return true;
        }

        $windowStart = strtotime($record['window_start']);
        $windowEnd = $windowStart + $this->windowSeconds;

        if ($now > $windowEnd) {
            // Fenêtre expirée, réinitialiser
            $this->reset($ip, $endpoint);
            return true;
        }

        if ($record['request_count'] >= $this->maxRequests) {
            return false;
        }

        // Incrémenter le compteur
        $this->increment($ip, $endpoint);
        return true;
    }

    /**
     * Obtient le temps restant avant reset
     */
    public function getRetryAfter(string $ip, string $endpoint): int
    {
        $stmt = $this->db->prepare(
            "SELECT window_start FROM rate_limits
             WHERE ip_address = :ip AND endpoint = :endpoint"
        );
        $stmt->execute(['ip' => $ip, 'endpoint' => $endpoint]);
        $record = $stmt->fetch();

        if (!$record) {
            return 0;
        }

        $windowStart = strtotime($record['window_start']);
        $windowEnd = $windowStart + $this->windowSeconds;
        return max(0, $windowEnd - time());
    }

    private function insert(string $ip, string $endpoint): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO rate_limits (ip_address, endpoint, request_count, window_start)
             VALUES (:ip, :endpoint, 1, NOW())
             ON DUPLICATE KEY UPDATE request_count = 1, window_start = NOW()"
        );
        $stmt->execute(['ip' => $ip, 'endpoint' => $endpoint]);
    }

    private function increment(string $ip, string $endpoint): void
    {
        $stmt = $this->db->prepare(
            "UPDATE rate_limits SET request_count = request_count + 1
             WHERE ip_address = :ip AND endpoint = :endpoint"
        );
        $stmt->execute(['ip' => $ip, 'endpoint' => $endpoint]);
    }

    private function reset(string $ip, string $endpoint): void
    {
        $stmt = $this->db->prepare(
            "UPDATE rate_limits SET request_count = 1, window_start = NOW()
             WHERE ip_address = :ip AND endpoint = :endpoint"
        );
        $stmt->execute(['ip' => $ip, 'endpoint' => $endpoint]);
    }

    private function cleanup(): void
    {
        // Nettoyer les enregistrements de plus d'1 heure (1 fois sur 100 requêtes)
        if (rand(1, 100) === 1) {
            $this->db->exec(
                "DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
        }
    }
}
