<?php
namespace BaseballAnalytics\Auth;

class SessionManager {
    private static $instance = null;
    private const SESSION_NAME = 'baseball_analytics_session';
    private const SESSION_LIFETIME = 3600; // 1 hour

    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            $this->initializeSession();
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeSession(): void {
        // Set secure session parameters
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', self::SESSION_LIFETIME);

        session_name(self::SESSION_NAME);
        session_start();

        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateSession();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // Every 5 minutes
            $this->regenerateSession();
        }
    }

    private function regenerateSession(): void {
        // Save current session data
        $sessionData = $_SESSION;
        
        // Clear and regenerate session
        session_unset();
        session_regenerate_id(true);
        
        // Restore session data and update regeneration time
        $_SESSION = $sessionData;
        $_SESSION['last_regeneration'] = time();
    }

    public function setUser(array $user): void {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        $_SESSION['authenticated'] = true;
        $_SESSION['auth_time'] = time();
    }

    public function getUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public function isAuthenticated(): bool {
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            return false;
        }

        // Check session age
        if (time() - ($_SESSION['auth_time'] ?? 0) > self::SESSION_LIFETIME) {
            $this->destroy();
            return false;
        }

        return true;
    }

    public function hasRole(string $role): bool {
        return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
    }

    public function destroy(): void {
        session_unset();
        session_destroy();
        setcookie(
            self::SESSION_NAME,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    public function refresh(): void {
        if ($this->isAuthenticated()) {
            $_SESSION['auth_time'] = time();
        }
    }

    public function setCsrfToken(): string {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    public function validateCsrfToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }

    public function setFlashMessage(string $type, string $message): void {
        $_SESSION['flash_messages'][$type] = $message;
    }

    public function getFlashMessages(): array {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
} 