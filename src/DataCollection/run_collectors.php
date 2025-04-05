<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use BaseballAnalytics\Database\Connection;
use BaseballAnalytics\DataCollection\Collectors\MLBStatsCollector;
use BaseballAnalytics\Utils\Logger;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Initialize logger
$logger = new Logger('collector_runner');

try {
    // Load collector configurations
    $config = require __DIR__ . '/Config/collectors.php';
    
    // Initialize database connection
    $db = new Connection();
    
    // Initialize collectors
    $collectors = [];
    
    // MLB Stats Collector
    if ($config['mlb_stats']['enabled']) {
        $collectors[] = new MLBStatsCollector($db, $config['mlb_stats']);
    }
    
    // Run enabled collectors
    foreach ($collectors as $collector) {
        $collectorName = get_class($collector);
        $logger->info("Starting collector: {$collectorName}");
        
        try {
            $result = $collector->collect();
            $stats = $collector->getLastRunStats();
            
            if ($result) {
                $logger->info("Successfully completed collector: {$collectorName}", $stats);
            } else {
                $logger->error("Collector failed: {$collectorName}", $stats);
            }
        } catch (\Exception $e) {
            $logger->error("Error running collector {$collectorName}: " . $e->getMessage(), [
                'exception' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }
    
    $logger->info("Data collection process completed");
} catch (\Exception $e) {
    $logger->error("Fatal error in collection process: " . $e->getMessage(), [
        'exception' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    exit(1);
} 