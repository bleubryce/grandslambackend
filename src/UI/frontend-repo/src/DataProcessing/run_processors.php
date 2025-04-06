<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use BaseballAnalytics\Database\Connection;
use BaseballAnalytics\DataProcessing\Processors\PlayerStatsProcessor;
use BaseballAnalytics\Utils\Logger;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Initialize logger
$logger = new Logger('processor_runner');

try {
    // Load processor configurations
    $config = require __DIR__ . '/Config/processors.php';
    
    // Initialize database connection
    $db = new Connection();
    
    // Initialize processors
    $processors = [];
    
    // Player Stats Processor
    if ($config['player_stats']['enabled']) {
        $processors[] = new PlayerStatsProcessor($db, $config['player_stats']);
    }
    
    // Run enabled processors
    foreach ($processors as $processor) {
        $processorName = get_class($processor);
        $logger->info("Starting processor: {$processorName}");
        
        try {
            $result = $processor->process();
            $stats = $processor->getProcessingStats();
            
            if ($result) {
                $logger->info("Successfully completed processor: {$processorName}", $stats);
            } else {
                $logger->error("Processor failed: {$processorName}", $stats);
            }
        } catch (\Exception $e) {
            $logger->error("Error running processor {$processorName}: " . $e->getMessage(), [
                'exception' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }
    
    $logger->info("Data processing completed");
} catch (\Exception $e) {
    $logger->error("Fatal error in processing: " . $e->getMessage(), [
        'exception' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    exit(1);
} 