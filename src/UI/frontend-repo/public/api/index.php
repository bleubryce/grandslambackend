<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/api_errors.log');

// Set timezone
date_default_timezone_set('UTC');

// Handle uncaught exceptions
set_exception_handler(function ($e) {
    error_log($e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'timestamp' => date('c')
    ]);
    exit();
});

// Initialize router
$router = new BaseballAnalytics\Api\Router();

// Handle request
$router->handle(); 