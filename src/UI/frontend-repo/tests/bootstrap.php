<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Set up error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set up timezone
date_default_timezone_set('UTC');

// Load test environment variables
if (file_exists(__DIR__ . '/../.env.testing')) {
    $dotenv = parse_ini_file(__DIR__ . '/../.env.testing');
    foreach ($dotenv as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Initialize test database if needed
require_once __DIR__ . '/TestDatabaseSetup.php'; 