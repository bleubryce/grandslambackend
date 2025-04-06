<?php

namespace Tests\Integration\EndToEnd;

use Tests\TestCase;
use BaseballAnalytics\Analysis\Engine\AnalysisEngine;
use BaseballAnalytics\DataProcessing\Pipeline;
use BaseballAnalytics\Database\DatabaseManager;
use BaseballAnalytics\DataCollection\DataCollector;

class AnalysisSystemTest extends TestCase
{
    private Pipeline $pipeline;
    private AnalysisEngine $analysisEngine;
    private DatabaseManager $dbManager;
    private DataCollector $dataCollector;
    
    protected function needsDatabase(): bool
    {
        return true;
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test environment
        $this->setupTestEnvironment();
        
        // Initialize system components
        $this->initializeComponents();
    }
    
    private function setupTestEnvironment(): void
    {
        // Create all necessary tables
        $this->createSystemTables();
        
        // Set up initial test data
        $this->setupInitialData();
    }
    
    private function createSystemTables(): void
    {
        // Create core tables
        $this->db->exec("
            CREATE TABLE system_config (
                config_id INTEGER PRIMARY KEY,
                config_key TEXT,
                config_value TEXT,
                updated_at TIMESTAMP
            )
        ");
        
        $this->db->exec("
            CREATE TABLE analysis_logs (
                log_id INTEGER PRIMARY KEY,
                component TEXT,
                message TEXT,
                level TEXT,
                created_at TIMESTAMP
            )
        ");
        
        $this->db->exec("
            CREATE TABLE analysis_results (
                result_id INTEGER PRIMARY KEY,
                analysis_type TEXT,
                data JSON,
                created_at TIMESTAMP
            )
        ");
        
        // Create data tables
        $this->createDataTables();
    }
    
    private function createDataTables(): void
    {
        // Games and stats
        $this->db->exec("
            CREATE TABLE games (
                game_id INTEGER PRIMARY KEY,
                home_team_id INTEGER,
                away_team_id INTEGER,
                game_date DATE,
                status TEXT,
                season INTEGER,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ");
        
        $this->db->exec("
            CREATE TABLE game_stats (
                stat_id INTEGER PRIMARY KEY,
                game_id INTEGER,
                team_id INTEGER,
                runs_scored INTEGER,
                hits INTEGER,
                errors INTEGER,
                created_at TIMESTAMP
            )
        ");
        
        // Teams and stats
        $this->db->exec("
            CREATE TABLE teams (
                team_id INTEGER PRIMARY KEY,
                name TEXT,
                city TEXT,
                active BOOLEAN,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ");
        
        $this->db->exec("
            CREATE TABLE team_stats (
                stat_id INTEGER PRIMARY KEY,
                team_id INTEGER,
                season INTEGER,
                wins INTEGER,
                losses INTEGER,
                runs_scored INTEGER,
                runs_allowed INTEGER,
                created_at TIMESTAMP
            )
        ");
        
        // Players and stats
        $this->db->exec("
            CREATE TABLE players (
                player_id INTEGER PRIMARY KEY,
                team_id INTEGER,
                name TEXT,
                position TEXT,
                active BOOLEAN,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ");
        
        $this->db->exec("
            CREATE TABLE player_stats (
                stat_id INTEGER PRIMARY KEY,
                player_id INTEGER,
                game_id INTEGER,
                hits INTEGER,
                at_bats INTEGER,
                runs INTEGER,
                rbis INTEGER,
                created_at TIMESTAMP
            )
        ");
    }
    
    private function setupInitialData(): void
    {
        $timestamp = date('Y-m-d H:i:s');
        
        // Insert system configuration
        $this->db->exec("
            INSERT INTO system_config (config_key, config_value, updated_at) VALUES
            ('analysis_window', '10', '$timestamp'),
            ('confidence_threshold', '0.7', '$timestamp'),
            ('min_sample_size', '3', '$timestamp')
        ");
        
        // Insert test teams
        $this->db->exec("
            INSERT INTO teams (team_id, name, city, active, created_at, updated_at) VALUES
            (1, 'Red Sox', 'Boston', true, '$timestamp', '$timestamp'),
            (2, 'Yankees', 'New York', true, '$timestamp', '$timestamp'),
            (3, 'Cubs', 'Chicago', true, '$timestamp', '$timestamp')
        ");
        
        // Insert test players
        $this->db->exec("
            INSERT INTO players (player_id, team_id, name, position, active, created_at, updated_at) VALUES
            (1, 1, 'John Smith', 'Pitcher', true, '$timestamp', '$timestamp'),
            (2, 1, 'Mike Johnson', 'Outfield', true, '$timestamp', '$timestamp'),
            (3, 2, 'Dave Wilson', 'Infield', true, '$timestamp', '$timestamp')
        ");
    }
    
    private function initializeComponents(): void
    {
        $config = [
            'analysis_window' => 10,
            'confidence_threshold' => 0.7,
            'min_sample_size' => 3
        ];
        
        $this->dbManager = new DatabaseManager($this->db);
        $this->dataCollector = new DataCollector($this->db, $config);
        $this->pipeline = new Pipeline($this->db, $config);
        $this->analysisEngine = new AnalysisEngine($this->db, $config);
    }
    
    public function testCompleteSystemWorkflow(): void
    {
        // Step 1: Collect and store game data
        $gameData = [
            'game_id' => 1,
            'home_team_id' => 1,
            'away_team_id' => 2,
            'game_date' => '2024-04-01',
            'status' => 'FINAL',
            'season' => 2024,
            'stats' => [
                'home' => ['runs' => 5, 'hits' => 10, 'errors' => 1],
                'away' => ['runs' => 3, 'hits' => 8, 'errors' => 2]
            ]
        ];
        
        $collectionResult = $this->dataCollector->collectGameData($gameData);
        $this->assertTrue($collectionResult);
        
        // Step 2: Process collected data through pipeline
        $pipelineResult = $this->pipeline->process();
        $this->assertTrue($pipelineResult);
        
        // Step 3: Verify data processing results
        $this->verifyProcessedData();
        
        // Step 4: Run analysis engine
        $analysisResult = $this->analysisEngine->analyze();
        $this->assertTrue($analysisResult);
        
        // Step 5: Verify analysis results
        $this->verifyAnalysisResults();
        
        // Step 6: Check system logs
        $this->verifySystemLogs();
    }
    
    public function testSystemRecoveryAndResilience(): void
    {
        // Test system recovery from invalid data
        $invalidGameData = [
            'game_id' => 99,
            'home_team_id' => 999, // Invalid team
            'away_team_id' => 998, // Invalid team
            'game_date' => '2024-04-04',
            'status' => 'FINAL',
            'season' => 2024
        ];
        
        // System should handle invalid data gracefully
        $collectionResult = $this->dataCollector->collectGameData($invalidGameData);
        $this->assertTrue($collectionResult);
        
        // Pipeline should continue processing valid data
        $pipelineResult = $this->pipeline->process();
        $this->assertTrue($pipelineResult);
        
        // Analysis should work with valid data
        $analysisResult = $this->analysisEngine->analyze();
        $this->assertTrue($analysisResult);
        
        // Verify error logging
        $this->verifyErrorLogs();
    }
    
    public function testDataConsistencyAcrossSystem(): void
    {
        // Insert test game data
        $timestamp = date('Y-m-d H:i:s');
        $this->db->exec("
            INSERT INTO games (game_id, home_team_id, away_team_id, game_date, status, season, created_at, updated_at)
            VALUES (1, 1, 2, '2024-04-01', 'FINAL', 2024, '$timestamp', '$timestamp')
        ");
        
        $this->db->exec("
            INSERT INTO game_stats (stat_id, game_id, team_id, runs_scored, hits, errors, created_at)
            VALUES 
            (1, 1, 1, 5, 10, 1, '$timestamp'),
            (2, 1, 2, 3, 8, 2, '$timestamp')
        ");
        
        // Process data through pipeline
        $this->pipeline->process();
        
        // Run analysis
        $this->analysisEngine->analyze();
        
        // Verify data consistency across all tables
        $this->verifyDataConsistency();
    }
    
    private function verifyProcessedData(): void
    {
        // Verify game stats were processed
        $gameStats = $this->db->query("SELECT * FROM game_stats WHERE game_id = 1")->fetchAll();
        $this->assertCount(2, $gameStats);
        
        // Verify team stats were updated
        $teamStats = $this->db->query("SELECT * FROM team_stats WHERE team_id = 1")->fetch();
        $this->assertNotNull($teamStats);
        $this->assertEquals(1, $teamStats['wins']);
        
        // Verify player stats were recorded
        $playerStats = $this->db->query("SELECT * FROM player_stats WHERE game_id = 1")->fetchAll();
        $this->assertNotEmpty($playerStats);
    }
    
    private function verifyAnalysisResults(): void
    {
        $results = $this->analysisEngine->getResults();
        
        // Verify team analysis
        $this->assertArrayHasKey('team_analysis', $results);
        $teamAnalysis = $results['team_analysis'];
        $this->assertNotEmpty($teamAnalysis);
        
        // Verify game analysis
        $this->assertArrayHasKey('game_analysis', $results);
        $gameAnalysis = $results['game_analysis'];
        $this->assertNotEmpty($gameAnalysis);
        
        // Verify analysis was stored in database
        $storedAnalysis = $this->db->query("SELECT * FROM analysis_results ORDER BY created_at DESC LIMIT 1")->fetch();
        $this->assertNotNull($storedAnalysis);
    }
    
    private function verifySystemLogs(): void
    {
        $logs = $this->db->query("SELECT * FROM analysis_logs WHERE level = 'INFO'")->fetchAll();
        $this->assertNotEmpty($logs);
        
        // Verify log entries for each major component
        $componentLogs = [];
        foreach ($logs as $log) {
            $componentLogs[$log['component']] = true;
        }
        
        $this->assertArrayHasKey('DataCollector', $componentLogs);
        $this->assertArrayHasKey('Pipeline', $componentLogs);
        $this->assertArrayHasKey('AnalysisEngine', $componentLogs);
    }
    
    private function verifyErrorLogs(): void
    {
        $errorLogs = $this->db->query("SELECT * FROM analysis_logs WHERE level = 'ERROR'")->fetchAll();
        $this->assertNotEmpty($errorLogs);
        
        // Verify error logging for invalid team references
        $teamErrors = false;
        foreach ($errorLogs as $log) {
            if (strpos($log['message'], 'Invalid team reference') !== false) {
                $teamErrors = true;
                break;
            }
        }
        $this->assertTrue($teamErrors);
    }
    
    private function verifyDataConsistency(): void
    {
        // Verify game data consistency
        $game = $this->db->query("SELECT * FROM games WHERE game_id = 1")->fetch();
        $gameStats = $this->db->query("SELECT * FROM game_stats WHERE game_id = 1")->fetchAll();
        $this->assertEquals($game['home_team_id'], $gameStats[0]['team_id']);
        
        // Verify team stats match game results
        $teamStats = $this->db->query("SELECT * FROM team_stats WHERE team_id = 1")->fetch();
        $this->assertEquals(5, $teamStats['runs_scored']);
        
        // Verify analysis results match source data
        $analysisResults = $this->db->query("SELECT * FROM analysis_results ORDER BY created_at DESC LIMIT 1")->fetch();
        $analysisData = json_decode($analysisResults['data'], true);
        $this->assertEquals(2, $analysisData['game_analysis'][0]['run_differential']);
    }
} 