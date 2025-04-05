<?php
namespace BaseballAnalytics\Api;

class Router {
    private $routes = [];
    private $middleware;
    private $basePath = '/api/v1';

    public function __construct() {
        $this->middleware = new ApiMiddleware();
        $this->registerRoutes();
    }

    private function registerRoutes(): void {
        // Auth routes
        $this->addRoute('POST', '/auth/login', 'AuthController@login');
        $this->addRoute('POST', '/auth/register', 'AuthController@register');
        $this->addRoute('POST', '/auth/verify-2fa', 'AuthController@verify2fa');
        $this->addRoute('POST', '/auth/setup-2fa', 'AuthController@setup2fa');
        $this->addRoute('POST', '/auth/enable-2fa', 'AuthController@enable2fa');
        $this->addRoute('POST', '/auth/disable-2fa', 'AuthController@disable2fa');
        $this->addRoute('POST', '/auth/logout', 'AuthController@logout');
        $this->addRoute('POST', '/auth/refresh-token', 'AuthController@refreshToken');
        $this->addRoute('GET', '/auth/backup-codes', 'AuthController@getBackupCodes');
        $this->addRoute('POST', '/auth/regenerate-backup-codes', 'AuthController@regenerateBackupCodes');

        // User routes (to be implemented)
        $this->addRoute('GET', '/users/profile', 'UserController@getProfile');
        $this->addRoute('PUT', '/users/profile', 'UserController@updateProfile');
        $this->addRoute('PUT', '/users/password', 'UserController@updatePassword');

        // Admin routes (to be implemented)
        $this->addRoute('GET', '/admin/users', 'AdminController@listUsers');
        $this->addRoute('GET', '/admin/users/{id}', 'AdminController@getUser');
        $this->addRoute('PUT', '/admin/users/{id}', 'AdminController@updateUser');
        $this->addRoute('DELETE', '/admin/users/{id}', 'AdminController@deleteUser');

        // Stats routes (to be implemented)
        $this->addRoute('GET', '/stats/players', 'StatsController@getPlayerStats');
        $this->addRoute('GET', '/stats/teams', 'StatsController@getTeamStats');
        $this->addRoute('GET', '/stats/games', 'StatsController@getGameStats');

        // Analytics routes (to be implemented)
        $this->addRoute('GET', '/analytics/performance', 'AnalyticsController@getPerformanceMetrics');
        $this->addRoute('GET', '/analytics/predictions', 'AnalyticsController@getPredictions');
        $this->addRoute('GET', '/analytics/trends', 'AnalyticsController@getTrends');
    }

    private function addRoute(string $method, string $path, string $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->basePath . $path,
            'handler' => $handler,
            'pattern' => $this->buildPattern($path)
        ];
    }

    private function buildPattern(string $path): string {
        return '#^' . $this->basePath . preg_replace('/{[^}]+}/', '([^/]+)', $path) . '$#';
    }

    public function handle(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Handle CORS preflight requests
        if ($method === 'OPTIONS') {
            header("HTTP/1.1 204 No Content");
            exit();
        }

        // Apply middleware
        if (!$this->middleware->handle()) {
            return;
        }

        foreach ($this->routes as $route) {
            if ($method !== $route['method']) {
                continue;
            }

            $matches = [];
            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches); // Remove full match
                $this->executeHandler($route['handler'], $matches);
                return;
            }
        }

        // No route found
        ApiResponse::notFound('Endpoint not found')->send();
    }

    private function executeHandler(string $handler, array $params = []): void {
        [$controller, $method] = explode('@', $handler);
        $controllerClass = "BaseballAnalytics\\Api\\Controllers\\{$controller}";

        if (!class_exists($controllerClass)) {
            ApiResponse::serverError("Controller {$controller} not found")->send();
            return;
        }

        $controller = new $controllerClass();
        if (!method_exists($controller, $method)) {
            ApiResponse::serverError("Method {$method} not found in controller {$controllerClass}")->send();
            return;
        }

        try {
            $controller->$method(...$params);
        } catch (\Exception $e) {
            error_log("Error executing {$handler}: " . $e->getMessage());
            ApiResponse::serverError('Internal server error')->send();
        }
    }

    public function addCustomRoute(string $method, string $path, callable $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->basePath . $path,
            'handler' => $handler,
            'pattern' => $this->buildPattern($path)
        ];
    }

    public function getRoutes(): array {
        return $this->routes;
    }
} 