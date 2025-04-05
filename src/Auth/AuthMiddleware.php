<?php
namespace BaseballAnalytics\Auth;

class AuthMiddleware {
    private $sessionManager;
    private static $publicRoutes = [
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
    ];

    public function __construct() {
        $this->sessionManager = SessionManager::getInstance();
    }

    public function handle(): bool {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Allow access to public routes
        if (in_array($currentPath, self::$publicRoutes)) {
            return true;
        }

        // Check if user is authenticated
        if (!$this->sessionManager->isAuthenticated()) {
            $this->sessionManager->setFlashMessage('error', 'Please log in to access this page.');
            header('Location: /login');
            exit();
        }

        // Validate CSRF token for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!$this->sessionManager->validateCsrfToken($token)) {
                $this->sessionManager->setFlashMessage('error', 'Invalid security token.');
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit();
            }
        }

        // Check role-based access if required
        $requiredRole = $this->getRequiredRole($currentPath);
        if ($requiredRole && !$this->sessionManager->hasRole($requiredRole)) {
            $this->sessionManager->setFlashMessage('error', 'You do not have permission to access this page.');
            header('Location: /dashboard');
            exit();
        }

        return true;
    }

    private function getRequiredRole(string $path): ?string {
        // Define role requirements for specific paths
        $roleRequirements = [
            '/admin' => 'admin',
            '/admin/' => 'admin',
            '/analytics/advanced' => 'analyst',
            '/users/manage' => 'admin',
            '/reports/create' => 'scout',
            '/settings' => 'admin'
        ];

        // Check if path starts with any protected route
        foreach ($roleRequirements as $route => $role) {
            if (strpos($path, $route) === 0) {
                return $role;
            }
        }

        return null;
    }

    public static function addPublicRoute(string $route): void {
        if (!in_array($route, self::$publicRoutes)) {
            self::$publicRoutes[] = $route;
        }
    }

    public function refreshSession(): void {
        if ($this->sessionManager->isAuthenticated()) {
            $this->sessionManager->refresh();
        }
    }

    public function generateCsrfToken(): string {
        return $this->sessionManager->setCsrfToken();
    }

    public function getCurrentUser(): ?array {
        return $this->sessionManager->getUser();
    }

    public function logout(): void {
        $this->sessionManager->destroy();
        header('Location: /login');
        exit();
    }
} 