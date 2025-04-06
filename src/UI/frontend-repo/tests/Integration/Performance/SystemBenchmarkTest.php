<?php

namespace Tests\Integration\Performance;

use Tests\TestCase;
use BaseballAnalytics\Analysis\Engine\AnalysisEngine;
use BaseballAnalytics\DataProcessing\Pipeline;
use BaseballAnalytics\Database\DatabaseManager;
use BaseballAnalytics\DataCollection\DataCollector;

class SystemBenchmarkTest extends TestCase
{
    private Pipeline $pipeline;
    private AnalysisEngine $analysisEngine;
    private DatabaseManager $dbManager;
    private DataCollector $dataCollector;
    
    // Performance thresholds
    private const MAX_PROCESSING_TIME = 5.0; // seconds
    private const MAX_ANALYSIS_TIME = 3.0; // seconds
    private const MAX_MEMORY_USAGE = 128 * 1024 * 1024; // 128MB
    private const BATCH_SIZE = 100;
    
    protected function needsDatabase(): bool
    {
        return true;
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test environment
        $this->setupTestEnvironment();
        
        // Initialize components
        $this->initializeComponents();
        
        // Generate test data
        $this->generateTestData();
    }
    
    private function setupTestEnvironment(): void
    {
        // Create necessary tables
        $this->createSystemTables();
        
        // Set up initial configuration
        $this->setupConfiguration();
    }
    
    private function createSystemTables(): void
    {
        // Create performance metrics table
        $this->db->exec("
            CREATE TABLE performance_metrics (
                metric_id INTEGER PRIMARY KEY,
                component TEXT,
                operation TEXT,
                execution_time FLOAT,
                memory_usage INTEGER,
                data_size INTEGER,
                timestamp TIMESTAMP
            )
        ");
        
        // Create other necessary tables
        $this->createDataTables();
    }
    
    private function createDataTables(): void
    {
        // Create standard tables needed for testing
        $tables = [
            'games',
            'game_stats',
            'teams',
            'team_stats',
            'players',
            'player_stats',
            'analysis_results'
        ];
        
        foreach ($tables as $table) {
            $this->ensureTableExists($table);
        }
    }
    
    private function ensureTableExists(string $table): void
    {
        switch ($table) {
            case 'games':
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS games (
                        game_id INTEGER PRIMARY KEY,
                        home_team_id INTEGER,
                        away_team_id INTEGER,
                        game_date DATE,
                        status TEXT,
                        season INTEGER
                    )
                ");
                break;
            
            case 'game_stats':
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS game_stats (
                        stat_id INTEGER PRIMARY KEY,
                        game_id INTEGER,
                        team_id INTEGER,
                        runs_scored INTEGER,
                        hits INTEGER,
                        errors INTEGER
                    )
                ");
                break;
            
            // Add other table creation statements as needed
        }
    }
    
    private function setupConfiguration(): void
    {
        $config = [
            'analysis_window' => 10,
            'confidence_threshold' => 0.7,
            'min_sample_size' => 3,
            'batch_size' => self::BATCH_SIZE
        ];
        
        $this->dbManager = new DatabaseManager($this->db);
        $this->dataCollector = new DataCollector($this->db, $config);
        $this->pipeline = new Pipeline($this->db, $config);
        $this->analysisEngine = new AnalysisEngine($this->db, $config);
    }
    
    private function generateTestData(): void
    {
        $timestamp = date('Y-m-d H:i:s');
        
        // Generate test teams
        for ($i = 1; $i <= 30; $i++) {
            $this->db->exec("
                INSERT INTO teams (team_id, name, city, active) 
                VALUES ($i, 'Team $i', 'City $i', true)
            ");
        }
        
        // Generate test players (20 per team)
        for ($team = 1; $team <= 30; $team++) {
            for ($p = 1; $p <= 20; $p++) {
                $playerId = ($team - 1) * 20 + $p;
                $this->db->exec("
                    INSERT INTO players (player_id, team_id, name, position, active)
                    VALUES ($playerId, $team, 'Player $playerId', 'Position $p', true)
                ");
            }
        }
        
        // Generate test games (100 games)
        for ($i = 1; $i <= 100; $i++) {
            $homeTeam = rand(1, 30);
            $awayTeam = rand(1, 30);
            while ($awayTeam == $homeTeam) {
                $awayTeam = rand(1, 30);
            }
            
            $this->db->exec("
                INSERT INTO games (game_id, home_team_id, away_team_id, game_date, status, season)
                VALUES ($i, $homeTeam, $awayTeam, '2024-04-01', 'FINAL', 2024)
            ");
            
            // Generate game stats
            $homeRuns = rand(0, 10);
            $awayRuns = rand(0, 10);
            $this->db->exec("
                INSERT INTO game_stats (stat_id, game_id, team_id, runs_scored, hits, errors)
                VALUES 
                ($i * 2 - 1, $i, $homeTeam, $homeRuns, " . rand(4, 15) . ", " . rand(0, 3) . "),
                ($i * 2, $i, $awayTeam, $awayRuns, " . rand(4, 15) . ", " . rand(0, 3) . ")
            ");
        }
    }
    
    public function testProcessingPerformance(): void
    {
        // Measure processing performance
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Process data through pipeline
        $result = $this->pipeline->process();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        // Calculate metrics
        $executionTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;
        
        // Log performance metrics
        $this->logPerformanceMetrics('Pipeline', 'process', $executionTime, $memoryUsage, self::BATCH_SIZE);
        
        // Assert performance requirements
        $this->assertTrue($result);
        $this->assertLessThan(self::MAX_PROCESSING_TIME, $executionTime);
        $this->assertLessThan(self::MAX_MEMORY_USAGE, $memoryUsage);
    }
    
    public function testAnalysisPerformance(): void
    {
        // Measure analysis performance
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Run analysis
        $result = $this->analysisEngine->analyze();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        // Calculate metrics
        $executionTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;
        
        // Log performance metrics
        $this->logPerformanceMetrics('AnalysisEngine', 'analyze', $executionTime, $memoryUsage, self::BATCH_SIZE);
        
        // Assert performance requirements
        $this->assertTrue($result);
        $this->assertLessThan(self::MAX_ANALYSIS_TIME, $executionTime);
        $this->assertLessThan(self::MAX_MEMORY_USAGE, $memoryUsage);
    }
    
    public function testConcurrentOperations(): void
    {
        // Simulate concurrent operations
        $processes = [];
        
        // Start multiple analysis processes
        for ($i = 0; $i < 3; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            // Run analysis
            $result = $this->analysisEngine->analyze();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            
            $processes[] = [
                'execution_time' => $endTime - $startTime,
                'memory_usage' => $endMemory - $startMemory,
                'result' => $result
            ];
        }
        
        // Verify all processes completed successfully
        foreach ($processes as $process) {
            $this->assertTrue($process['result']);
            $this->assertLessThan(self::MAX_ANALYSIS_TIME * 1.5, $process['execution_time']);
            $this->assertLessThan(self::MAX_MEMORY_USAGE, $process['memory_usage']);
        }
    }
    
    public function testSystemLoad(): void
    {
        // Test system under load
        $iterations = 5;
        $totalExecutionTime = 0;
        $maxMemoryUsage = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            // Process data
            $this->pipeline->process();
            
            // Run analysis
            $this->analysisEngine->analyze();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            
            $executionTime = $endTime - $startTime;
            $memoryUsage = $endMemory - $startMemory;
            
            $totalExecutionTime += $executionTime;
            $maxMemoryUsage = max($maxMemoryUsage, $memoryUsage);
            
            // Log performance metrics
            $this->logPerformanceMetrics('System', 'load_test', $executionTime, $memoryUsage, self::BATCH_SIZE);
        }
        
        // Calculate averages
        $avgExecutionTime = $totalExecutionTime / $iterations;
        
        // Assert performance under load
        $this->assertLessThan(self::MAX_PROCESSING_TIME + self::MAX_ANALYSIS_TIME, $avgExecutionTime);
        $this->assertLessThan(self::MAX_MEMORY_USAGE, $maxMemoryUsage);
    }
    
    private function logPerformanceMetrics(
        string $component,
        string $operation,
        float $executionTime,
        int $memoryUsage,
        int $dataSize
    ): void {
        $timestamp = date('Y-m-d H:i:s');
        
        $this->db->exec("
            INSERT INTO performance_metrics (
                component,
                operation,
                execution_time,
                memory_usage,
                data_size,
                timestamp
            ) VALUES (
                '$component',
                '$operation',
                $executionTime,
                $memoryUsage,
                $dataSize,
                '$timestamp'
            )
        ");
    }
} 