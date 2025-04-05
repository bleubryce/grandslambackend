<?php
namespace BaseballAnalytics\Auth;

use BaseballAnalytics\Database\Connection;
use PDO;

class UserManager {
    private $db;
    private static $instance = null;

    private function __construct() {
        $this->db = Connection::getInstance()->getConnection();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function createUser(string $username, string $email, string $password, string $role = 'user'): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, role)
                VALUES (:username, :email, :password_hash, :role)
            ");

            return $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'role' => $role
            ]);
        } catch (\PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    public function authenticateUser(string $username, string $password): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, password_hash, role, is_active
                FROM users
                WHERE username = :username AND is_active = true
            ");
            
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login
                $this->updateLastLogin($user['id']);
                
                // Remove password hash from return data
                unset($user['password_hash']);
                return $user;
            }
            
            return null;
        } catch (\PDOException $e) {
            error_log("Error authenticating user: " . $e->getMessage());
            return null;
        }
    }

    private function updateLastLogin(string $userId): void {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET last_login = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute(['id' => $userId]);
        } catch (\PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
        }
    }

    public function updateUser(string $userId, array $data): bool {
        try {
            $allowedFields = ['email', 'role', 'is_active'];
            $updates = [];
            $params = ['id' => $userId];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = :$field";
                    $params[$field] = $value;
                }
            }

            if (empty($updates)) {
                return false;
            }

            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    public function updatePassword(string $userId, string $newPassword): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password_hash = :password_hash
                WHERE id = :id
            ");
            
            return $stmt->execute([
                'id' => $userId,
                'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT)
            ]);
        } catch (\PDOException $e) {
            error_log("Error updating password: " . $e->getMessage());
            return false;
        }
    }

    public function getUserById(string $userId): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, role, is_active, created_at, last_login
                FROM users
                WHERE id = :id
            ");
            
            $stmt->execute(['id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Error getting user: " . $e->getMessage());
            return null;
        }
    }

    public function getUserByEmail(string $email): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, role, is_active, created_at, last_login
                FROM users
                WHERE email = :email
            ");
            
            $stmt->execute(['email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Error getting user by email: " . $e->getMessage());
            return null;
        }
    }

    public function deactivateUser(string $userId): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET is_active = false
                WHERE id = :id
            ");
            
            return $stmt->execute(['id' => $userId]);
        } catch (\PDOException $e) {
            error_log("Error deactivating user: " . $e->getMessage());
            return false;
        }
    }

    public function listUsers(int $limit = 100, int $offset = 0): array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, role, is_active, created_at, last_login
                FROM users
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error listing users: " . $e->getMessage());
            return [];
        }
    }

    public function getUsersByRole(string $role): array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, role, is_active, created_at, last_login
                FROM users
                WHERE role = :role
                ORDER BY created_at DESC
            ");
            
            $stmt->execute(['role' => $role]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error getting users by role: " . $e->getMessage());
            return [];
        }
    }
} 