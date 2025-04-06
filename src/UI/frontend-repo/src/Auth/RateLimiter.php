<?php
namespace BaseballAnalytics\Auth;

use BaseballAnalytics\Database\Connection;
use PDO;

class RateLimiter {
    private $db;
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 900; // 15 minutes in seconds
    private const ATTEMPT_WINDOW = 300; // 5 minutes in seconds

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
        $this->initializeTable();
    }

    private function initializeTable(): void {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    id SERIAL PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    endpoint VARCHAR(255) NOT NULL,
                    attempts INTEGER DEFAULT 1,
                    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    locked_until TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(ip_address, endpoint)
                )
            ");
        } catch (\PDOException $e) {
            error_log("Error initializing rate limit table: " . $e->getMessage());
        }
    }

    public function isAllowed(string $ipAddress, string $endpoint): bool {
        try {
            // Clean up old records
            $this->cleanup();

            $stmt = $this->db->prepare("
                SELECT attempts, locked_until, last_attempt
                FROM rate_limits
                WHERE ip_address = :ip AND endpoint = :endpoint
            ");
            $stmt->execute(['ip' => $ipAddress, 'endpoint' => $endpoint]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                $this->initializeRecord($ipAddress, $endpoint);
                return true;
            }

            // Check if currently locked out
            if ($record['locked_until'] && strtotime($record['locked_until']) > time()) {
                return false;
            }

            // Check if attempt window has expired
            if (time() - strtotime($record['last_attempt']) > self::ATTEMPT_WINDOW) {
                $this->resetAttempts($ipAddress, $endpoint);
                return true;
            }

            // Check if max attempts exceeded
            if ($record['attempts'] >= self::MAX_ATTEMPTS) {
                $this->lockout($ipAddress, $endpoint);
                return false;
            }

            return true;
        } catch (\PDOException $e) {
            error_log("Error checking rate limit: " . $e->getMessage());
            return true; // Fail open to prevent blocking legitimate users
        }
    }

    public function increment(string $ipAddress, string $endpoint): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO rate_limits (ip_address, endpoint, attempts, last_attempt)
                VALUES (:ip, :endpoint, 1, CURRENT_TIMESTAMP)
                ON CONFLICT (ip_address, endpoint)
                DO UPDATE SET 
                    attempts = rate_limits.attempts + 1,
                    last_attempt = CURRENT_TIMESTAMP
                WHERE rate_limits.ip_address = :ip AND rate_limits.endpoint = :endpoint
            ");
            $stmt->execute(['ip' => $ipAddress, 'endpoint' => $endpoint]);
        } catch (\PDOException $e) {
            error_log("Error incrementing rate limit: " . $e->getMessage());
        }
    }

    private function lockout(string $ipAddress, string $endpoint): void {
        try {
            $stmt = $this->db->prepare("
                UPDATE rate_limits
                SET locked_until = CURRENT_TIMESTAMP + INTERVAL ':duration SECONDS'
                WHERE ip_address = :ip AND endpoint = :endpoint
            ");
            $stmt->execute([
                'ip' => $ipAddress,
                'endpoint' => $endpoint,
                'duration' => self::LOCKOUT_DURATION
            ]);
        } catch (\PDOException $e) {
            error_log("Error setting lockout: " . $e->getMessage());
        }
    }

    private function resetAttempts(string $ipAddress, string $endpoint): void {
        try {
            $stmt = $this->db->prepare("
                UPDATE rate_limits
                SET attempts = 1,
                    last_attempt = CURRENT_TIMESTAMP,
                    locked_until = NULL
                WHERE ip_address = :ip AND endpoint = :endpoint
            ");
            $stmt->execute(['ip' => $ipAddress, 'endpoint' => $endpoint]);
        } catch (\PDOException $e) {
            error_log("Error resetting attempts: " . $e->getMessage());
        }
    }

    private function initializeRecord(string $ipAddress, string $endpoint): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO rate_limits (ip_address, endpoint)
                VALUES (:ip, :endpoint)
            ");
            $stmt->execute(['ip' => $ipAddress, 'endpoint' => $endpoint]);
        } catch (\PDOException $e) {
            error_log("Error initializing rate limit record: " . $e->getMessage());
        }
    }

    private function cleanup(): void {
        try {
            // Remove records older than 24 hours that are not locked
            $stmt = $this->db->prepare("
                DELETE FROM rate_limits
                WHERE created_at < CURRENT_TIMESTAMP - INTERVAL '24 HOURS'
                AND (locked_until IS NULL OR locked_until < CURRENT_TIMESTAMP)
            ");
            $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error cleaning up rate limits: " . $e->getMessage());
        }
    }

    public function getRemainingAttempts(string $ipAddress, string $endpoint): int {
        try {
            $stmt = $this->db->prepare("
                SELECT attempts
                FROM rate_limits
                WHERE ip_address = :ip AND endpoint = :endpoint
            ");
            $stmt->execute(['ip' => $ipAddress, 'endpoint' => $endpoint]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                return self::MAX_ATTEMPTS;
            }

            return max(0, self::MAX_ATTEMPTS - $record['attempts']);
        } catch (\PDOException $e) {
            error_log("Error getting remaining attempts: " . $e->getMessage());
            return self::MAX_ATTEMPTS;
        }
    }

    public function getLockoutTime(string $ipAddress, string $endpoint): ?int {
        try {
            $stmt = $this->db->prepare("
                SELECT locked_until
                FROM rate_limits
                WHERE ip_address = :ip AND endpoint = :endpoint
                AND locked_until > CURRENT_TIMESTAMP
            ");
            $stmt->execute(['ip' => $ipAddress, 'endpoint' => $endpoint]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record || !$record['locked_until']) {
                return null;
            }

            return max(0, strtotime($record['locked_until']) - time());
        } catch (\PDOException $e) {
            error_log("Error getting lockout time: " . $e->getMessage());
            return null;
        }
    }
} 