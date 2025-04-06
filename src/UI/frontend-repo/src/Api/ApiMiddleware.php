<?php
namespace BaseballAnalytics\Api;

use BaseballAnalytics\Auth\SessionManager;
use BaseballAnalytics\Auth\RateLimiter;

class ApiMiddleware {
    private $sessionManager;
    private $rateLimiter;
    private static $publicEndpoints = [
        '/api/v1/auth/login',
        '/api/v1/auth/register',
        '/api/v1/auth/forgot-password',
    ];

    public function __construct() {
        $this->sessionManager = SessionManager::getInstance();
        $this->rateLimiter = new RateLimiter();
    }

    public function handle(): bool {
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        // Add API version headers
        header('X-API-Version: 1.0');

        // Check rate limiting
        if (!$this->rateLimiter->isAllowed($ipAddress, $requestPath)) {
            $lockoutTime = $this->rateLimiter->getLockoutTime($ipAddress, $requestPath);
            ApiResponse::tooManyRequests("Too many requests. Try again in {$lockoutTime} seconds.")
                ->withHeaders([
                    'Retry-After' => $lockoutTime,
                    'X-RateLimit-Reset' => time() + $lockoutTime
                ])
                ->send();
            return false;
        }

        // Allow public endpoints
        if (in_array($requestPath, self::$publicEndpoints)) {
            $this->rateLimiter->increment($ipAddress, $requestPath);
            return true;
        }

        // Check for API token
        $token = $this->getAuthToken();
        if (!$token) {
            ApiResponse::unauthorized('No authentication token provided')->send();
            return false;
        }

        // Validate session
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized('Invalid or expired token')->send();
            return false;
        }

        // Check CORS
        if (!$this->handleCors()) {
            return false;
        }

        // Increment rate limit counter
        $this->rateLimiter->increment($ipAddress, $requestPath);

        return true;
    }

    private function getAuthToken(): ?string {
        $headers = getallheaders();
        
        // Check Authorization header
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }

        // Check query parameter
        if (isset($_GET['token'])) {
            return $_GET['token'];
        }

        return null;
    }

    private function handleCors(): bool {
        $allowedOrigins = [
            'http://localhost:3000',
            'https://baseball-analytics.com'
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Check if origin is allowed
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Max-Age: 86400"); // 24 hours

            // Handle preflight requests
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                header("HTTP/1.1 204 No Content");
                exit();
            }

            return true;
        }

        // Block unauthorized origins
        ApiResponse::forbidden('Origin not allowed')->send();
        return false;
    }

    public static function addPublicEndpoint(string $endpoint): void {
        if (!in_array($endpoint, self::$publicEndpoints)) {
            self::$publicEndpoints[] = $endpoint;
        }
    }

    public function validateRole(array $allowedRoles): bool {
        $user = $this->sessionManager->getUser();
        if (!$user || !isset($user['role'])) {
            ApiResponse::forbidden('Access denied')->send();
            return false;
        }

        if (!in_array($user['role'], $allowedRoles)) {
            ApiResponse::forbidden('Insufficient permissions')->send();
            return false;
        }

        return true;
    }

    public function validateContentType(string $expectedType = 'application/json'): bool {
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
        if (strpos($contentType, $expectedType) === false) {
            ApiResponse::badRequest("Invalid content type. Expected {$expectedType}")->send();
            return false;
        }
        return true;
    }

    public function getRequestData(): ?array {
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            ApiResponse::badRequest('Invalid JSON payload')->send();
            return null;
        }
        return $data;
    }

    public function validateRequiredFields(array $data, array $requiredFields): bool {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            ApiResponse::validationError([
                'missing_fields' => $missing
            ], 'Required fields missing')->send();
            return false;
        }

        return true;
    }
} 