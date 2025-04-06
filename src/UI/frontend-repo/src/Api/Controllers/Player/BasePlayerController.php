
<?php
namespace BaseballAnalytics\Api\Controllers\Player;

use BaseballAnalytics\Api\ApiResponse;
use BaseballAnalytics\Api\ApiMiddleware;
use BaseballAnalytics\Auth\SessionManager;
use BaseballAnalytics\Database\Connection;
use PDO;

class BasePlayerController {
    protected $db;
    protected $sessionManager;
    protected $middleware;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
        $this->sessionManager = SessionManager::getInstance();
        $this->middleware = new ApiMiddleware();
    }

    protected function checkAuth(): bool {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return false;
        }
        return true;
    }
}
