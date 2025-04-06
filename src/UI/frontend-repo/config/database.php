<?php
/**
 * Baseball Analytics System - Database Configuration
 * 
 * This file contains all database connection settings and configuration options.
 * Production values should be set via environment variables for security.
 */

return [
    // Main database connection
    'default' => [
        'driver' => getenv('DB_DRIVER') ?: 'pgsql',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '5432',
        'database' => getenv('DB_NAME') ?: 'baseball_analytics',
        'username' => getenv('DB_USER') ?: 'postgres',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8',
        'schema' => 'public',
        'sslmode' => 'prefer',
    ],

    // Connection pool settings
    'pool' => [
        'min_connections' => 1,
        'max_connections' => 10,
        'idle_timeout' => 60, // seconds
        'wait_timeout' => 5,  // seconds
    ],

    // Backup configuration
    'backup' => [
        'enabled' => true,
        'schedule' => 'daily',
        'time' => '00:00',
        'keep_for_days' => 30,
        'storage' => [
            'type' => 'local',
            'path' => '/backups/database',
        ],
    ],

    // Monitoring settings
    'monitoring' => [
        'enabled' => true,
        'log_queries' => false,
        'log_slow_queries' => true,
        'slow_query_threshold' => 1.0, // seconds
        'alert_on_errors' => true,
    ],

    // Development specific settings
    'development' => [
        'log_queries' => true,
        'strict_mode' => true,
        'debug_mode' => true,
    ],
]; 