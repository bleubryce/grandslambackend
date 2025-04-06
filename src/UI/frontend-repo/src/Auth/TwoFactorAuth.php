<?php
namespace BaseballAnalytics\Auth;

use BaseballAnalytics\Database\Connection;
use PDO;

class TwoFactorAuth {
    private $db;
    private const SECRET_LENGTH = 32;
    private const CODE_LENGTH = 6;
    private const CODE_LIFETIME = 300; // 5 minutes in seconds
    private const MAX_VERIFICATION_ATTEMPTS = 3;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
        $this->initializeTable();
    }

    private function initializeTable(): void {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS two_factor_auth (
                    id SERIAL PRIMARY KEY,
                    user_id UUID NOT NULL REFERENCES users(id),
                    secret_key VARCHAR(64) NOT NULL,
                    backup_codes JSON,
                    is_enabled BOOLEAN DEFAULT false,
                    verification_attempts INTEGER DEFAULT 0,
                    last_verification TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(user_id)
                )
            ");
        } catch (\PDOException $e) {
            error_log("Error initializing 2FA table: " . $e->getMessage());
        }
    }

    public function setupTwoFactor(string $userId): array {
        try {
            // Generate secret key
            $secretKey = $this->generateSecretKey();
            
            // Generate backup codes
            $backupCodes = $this->generateBackupCodes();

            $stmt = $this->db->prepare("
                INSERT INTO two_factor_auth (user_id, secret_key, backup_codes)
                VALUES (:user_id, :secret_key, :backup_codes)
                ON CONFLICT (user_id)
                DO UPDATE SET 
                    secret_key = :secret_key,
                    backup_codes = :backup_codes,
                    is_enabled = false,
                    verification_attempts = 0,
                    updated_at = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                'user_id' => $userId,
                'secret_key' => $secretKey,
                'backup_codes' => json_encode($backupCodes)
            ]);

            return [
                'secret_key' => $secretKey,
                'backup_codes' => $backupCodes
            ];
        } catch (\PDOException $e) {
            error_log("Error setting up 2FA: " . $e->getMessage());
            throw new \RuntimeException("Failed to set up two-factor authentication");
        }
    }

    public function verifyCode(string $userId, string $code): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT secret_key, verification_attempts, backup_codes, last_verification
                FROM two_factor_auth
                WHERE user_id = :user_id
            ");
            $stmt->execute(['user_id' => $userId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                return false;
            }

            // Check verification attempts
            if ($record['verification_attempts'] >= self::MAX_VERIFICATION_ATTEMPTS) {
                if (time() - strtotime($record['last_verification']) < self::CODE_LIFETIME) {
                    return false;
                }
                // Reset attempts after timeout
                $this->resetVerificationAttempts($userId);
            }

            // Check if it's a backup code
            $backupCodes = json_decode($record['backup_codes'], true);
            if (in_array($code, $backupCodes)) {
                $this->useBackupCode($userId, $code);
                return true;
            }

            // Verify TOTP code
            $isValid = $this->verifyTOTP($record['secret_key'], $code);

            // Update verification attempts
            $this->updateVerificationAttempts($userId, $isValid);

            return $isValid;
        } catch (\PDOException $e) {
            error_log("Error verifying 2FA code: " . $e->getMessage());
            return false;
        }
    }

    private function verifyTOTP(string $secretKey, string $code): bool {
        // Get current and adjacent time windows
        $timeWindow = floor(time() / 30);
        $validWindows = [$timeWindow - 1, $timeWindow, $timeWindow + 1];

        foreach ($validWindows as $window) {
            $expectedCode = $this->generateTOTP($secretKey, $window);
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    private function generateTOTP(string $secretKey, int $timeWindow): string {
        $data = pack('N*', 0) . pack('N*', $timeWindow);
        $hash = hash_hmac('sha1', $data, $secretKey, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, self::CODE_LENGTH);
        
        return str_pad($code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function generateSecretKey(): string {
        return bin2hex(random_bytes(self::SECRET_LENGTH));
    }

    private function generateBackupCodes(int $count = 8): array {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = bin2hex(random_bytes(4));
        }
        return $codes;
    }

    private function useBackupCode(string $userId, string $code): void {
        try {
            $stmt = $this->db->prepare("
                SELECT backup_codes
                FROM two_factor_auth
                WHERE user_id = :user_id
            ");
            $stmt->execute(['user_id' => $userId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($record) {
                $backupCodes = json_decode($record['backup_codes'], true);
                $backupCodes = array_diff($backupCodes, [$code]);

                $stmt = $this->db->prepare("
                    UPDATE two_factor_auth
                    SET backup_codes = :backup_codes,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id
                ");
                $stmt->execute([
                    'user_id' => $userId,
                    'backup_codes' => json_encode(array_values($backupCodes))
                ]);
            }
        } catch (\PDOException $e) {
            error_log("Error using backup code: " . $e->getMessage());
        }
    }

    private function updateVerificationAttempts(string $userId, bool $successful): void {
        try {
            if ($successful) {
                $sql = "
                    UPDATE two_factor_auth
                    SET verification_attempts = 0,
                        last_verification = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id
                ";
            } else {
                $sql = "
                    UPDATE two_factor_auth
                    SET verification_attempts = verification_attempts + 1,
                        last_verification = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id
                ";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
        } catch (\PDOException $e) {
            error_log("Error updating verification attempts: " . $e->getMessage());
        }
    }

    private function resetVerificationAttempts(string $userId): void {
        try {
            $stmt = $this->db->prepare("
                UPDATE two_factor_auth
                SET verification_attempts = 0,
                    last_verification = NULL
                WHERE user_id = :user_id
            ");
            $stmt->execute(['user_id' => $userId]);
        } catch (\PDOException $e) {
            error_log("Error resetting verification attempts: " . $e->getMessage());
        }
    }

    public function enableTwoFactor(string $userId): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE two_factor_auth
                SET is_enabled = true,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
            ");
            return $stmt->execute(['user_id' => $userId]);
        } catch (\PDOException $e) {
            error_log("Error enabling 2FA: " . $e->getMessage());
            return false;
        }
    }

    public function disableTwoFactor(string $userId): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE two_factor_auth
                SET is_enabled = false,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
            ");
            return $stmt->execute(['user_id' => $userId]);
        } catch (\PDOException $e) {
            error_log("Error disabling 2FA: " . $e->getMessage());
            return false;
        }
    }

    public function isTwoFactorEnabled(string $userId): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT is_enabled
                FROM two_factor_auth
                WHERE user_id = :user_id
            ");
            $stmt->execute(['user_id' => $userId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            return $record ? (bool)$record['is_enabled'] : false;
        } catch (\PDOException $e) {
            error_log("Error checking 2FA status: " . $e->getMessage());
            return false;
        }
    }

    public function getBackupCodes(string $userId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT backup_codes
                FROM two_factor_auth
                WHERE user_id = :user_id
            ");
            $stmt->execute(['user_id' => $userId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            return $record ? json_decode($record['backup_codes'], true) : [];
        } catch (\PDOException $e) {
            error_log("Error getting backup codes: " . $e->getMessage());
            return [];
        }
    }

    public function regenerateBackupCodes(string $userId): array {
        try {
            $backupCodes = $this->generateBackupCodes();

            $stmt = $this->db->prepare("
                UPDATE two_factor_auth
                SET backup_codes = :backup_codes,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                'user_id' => $userId,
                'backup_codes' => json_encode($backupCodes)
            ]);

            return $backupCodes;
        } catch (\PDOException $e) {
            error_log("Error regenerating backup codes: " . $e->getMessage());
            return [];
        }
    }
} 