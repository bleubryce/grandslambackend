<?php

namespace Tests\Integration\CrossComponent;

use Tests\TestCase;
use BaseballAnalytics\Analysis\Engine\AnalysisEngine;
use BaseballAnalytics\DataProcessing\Processors\GameStatsProcessor;
use BaseballAnalytics\DataProcessing\Processors\PlayerStatsProcessor;
use BaseballAnalytics\DataProcessing\Processors\TeamStatsProcessor;
use BaseballAnalytics\DataProcessing\Processors\SeasonStatsProcessor;

class AnalysisWorkflowTest extends TestCase
{
    private AnalysisEngine $analysisEngine;
    private GameStatsProcessor $gameProcessor;
    private PlayerStatsProcessor $playerProcessor;
    private TeamStatsProcessor $teamProcessor;
    private SeasonStatsProcessor $seasonProcessor;
    
    protected function needsDatabase(): bool
    {
        return true;
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test data
        $this->setupTestData();
        
        // Initialize processors and engine
        $this->initializeComponents();
    }
    
    private function setupTestData(): void
    {
        // Create necessary test tables
        $this->createGameTables();
        $this->createPlayerTables();
        $this->createTeamTables();
        $this->createSeasonTables();
        
        // Insert test data
        $this->insertTestData();
    }
    
    private function createGameTables(): void
    {
        $this->db->exec("
            CREATE TABLE games (
                game_id INTEGER PRIMARY KEY,
                home_team_id INTEGER,
                away_team_id INTEGER,
                game_date DATE,
                status TEXT,
                season INTEGER
            )
        ");
        
        $this->db->exec("
            CREATE TABLE game_stats (
                stat_id INTEGER PRIMARY KEY,
                game_id INTEGER,
                team_id INTEGER,
                runs_scored INTEGER,
                hits INTEGER,
                errors INTEGER
            )
        ");
    }
    
    private function createPlayerTables(): void
    {
        $this->db->exec("
            CREATE TABLE players (
                player_id INTEGER PRIMARY KEY,
                team_id INTEGER,
                name TEXT,
                position TEXT,
                active BOOLEAN
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
                rbis INTEGER
            )
        ");
    }
    
    private function createTeamTables(): void
    {
        $this->db->exec("
            CREATE TABLE teams (
                team_id INTEGER PRIMARY KEY,
                name TEXT,
                city TEXT,
                active BOOLEAN
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
                runs_allowed INTEGER
            )
        ");
    }
    
    private function createSeasonTables(): void
    {
        $this->db->exec("
            CREATE TABLE season_stats (
                stat_id INTEGER PRIMARY KEY,
                season INTEGER,
                team_id INTEGER,
                total_games INTEGER,
                win_percentage FLOAT,
                run_differential INTEGER
            )
        ");
    }
    
    private function insertTestData(): void
    {
        // Insert test teams
        $this->db->exec("
            INSERT INTO teams (team_id, name, city, active) VALUES
            (1, 'Red Sox', 'Boston', true),
            (2, 'Yankees', 'New York', true),
            (3, 'Cubs', 'Chicago', true)
        ");
        
        // Insert test players
        $this->db->exec("
            INSERT INTO players (player_id, team_id, name, position, active) VALUES
            (1, 1, 'John Smith', 'Pitcher', true),
            (2, 1, 'Mike Johnson', 'Outfield', true),
            (3, 2, 'Dave Wilson', 'Infield', true)
        ");
        
        // Insert test games
        $this->db->exec("
            INSERT INTO games (game_id, home_team_id, away_team_id, game_date, status, season) VALUES
            (1, 1, 2, '2024-04-01', 'FINAL', 2024),
            (2, 2, 3, '2024-04-02', 'FINAL', 2024),
            (3, 1, 3, '2024-04-03', 'FINAL', 2024)
        ");
        
        // Insert test game stats
        $this->db->exec("
            INSERT INTO game_stats (stat_id, game_id, team_id, runs_scored, hits, errors) VALUES
            (1, 1, 1, 5, 10, 1),
            (2, 1, 2, 3, 8, 2),
            (3, 2, 2, 4, 9, 0),
            (4, 2, 3, 2, 6, 1),
            (5, 3, 1, 6, 12, 0),
            (6, 3, 3, 4, 9, 2)
        ");
        
        // Insert test player stats
        $this->db->exec("
            INSERT INTO player_stats (stat_id, player_id, game_id, hits, at_bats, runs, rbis) VALUES
            (1, 1, 1, 2, 4, 1, 2),
            (2, 2, 1, 3, 5, 2, 1),
            (3, 3, 2, 2, 4, 1, 1)
        ");
        
        // Insert test team stats
        $this->db->exec("
            INSERT INTO team_stats (stat_id, team_id, season, wins, losses, runs_scored, runs_allowed) VALUES
            (1, 1, 2024, 2, 0, 11, 7),
            (2, 2, 2024, 0, 2, 7, 9),
            (3, 3, 2024, 0, 2, 6, 10)
        ");
    }
    
    private function initializeComponents(): void
    {
        $config = [
            'analysis_window' => 10,
            'confidence_threshold' => 0.7,
            'min_sample_size' => 3
        ];
        
        $this->gameProcessor = new GameStatsProcessor($this->db, $config);
        $this->playerProcessor = new PlayerStatsProcessor($this->db, $config);
        $this->teamProcessor = new TeamStatsProcessor($this->db, $config);
        $this->seasonProcessor = new SeasonStatsProcessor($this->db, $config);
        $this->analysisEngine = new AnalysisEngine($this->db, $config);
    }
    
    public function testCompleteAnalysisWorkflow(): void
    {
        // Process game stats
        $gameResult = $this->gameProcessor->process();
        $this->assertTrue($gameResult);
        
        // Process player stats
        $playerResult = $this->playerProcessor->process();
        $this->assertTrue($playerResult);
        
        // Process team stats
        $teamResult = $this->teamProcessor->process();
        $this->assertTrue($teamResult);
        
        // Process season stats
        $seasonResult = $this->seasonProcessor->process();
        $this->assertTrue($seasonResult);
        
        // Run analysis engine
        $analysisResult = $this->analysisEngine->analyze();
        $this->assertTrue($analysisResult);
        
        // Verify analysis results
        $results = $this->analysisEngine->getResults();
        
        // Check team analysis
        $this->assertArrayHasKey('team_analysis', $results);
        $teamAnalysis = $results['team_analysis'];
        $this->assertNotEmpty($teamAnalysis);
        
        // Verify Red Sox (team_id = 1) stats
        $redSoxAnalysis = $this->findTeamAnalysis($teamAnalysis, 1);
        $this->assertNotNull($redSoxAnalysis);
        $this->assertEquals(1.000, $redSoxAnalysis['win_percentage']);
        $this->assertGreaterThan(0, $redSoxAnalysis['run_differential']);
        
        // Check player analysis
        $this->assertArrayHasKey('player_analysis', $results);
        $playerAnalysis = $results['player_analysis'];
        $this->assertNotEmpty($playerAnalysis);
        
        // Verify John Smith (player_id = 1) stats
        $smithAnalysis = $this->findPlayerAnalysis($playerAnalysis, 1);
        $this->assertNotNull($smithAnalysis);
        $this->assertEquals(0.500, $smithAnalysis['batting_average']);
        
        // Check game analysis
        $this->assertArrayHasKey('game_analysis', $results);
        $gameAnalysis = $results['game_analysis'];
        $this->assertNotEmpty($gameAnalysis);
        
        // Verify first game analysis
        $game1Analysis = $this->findGameAnalysis($gameAnalysis, 1);
        $this->assertNotNull($game1Analysis);
        $this->assertEquals(2, $game1Analysis['run_differential']);
    }
    
    public function testErrorHandlingAndRecovery(): void
    {
        // Test with invalid game data
        $this->db->exec("INSERT INTO games (game_id, home_team_id, away_team_id, game_date, status, season) 
                        VALUES (99, 999, 998, '2024-04-04', 'FINAL', 2024)");
        
        // Process should continue despite invalid team references
        $gameResult = $this->gameProcessor->process();
        $this->assertTrue($gameResult);
        
        // Analysis should still work for valid data
        $analysisResult = $this->analysisEngine->analyze();
        $this->assertTrue($analysisResult);
        
        $results = $this->analysisEngine->getResults();
        $this->assertNotEmpty($results);
    }
    
    public function testDataConsistency(): void
    {
        // Process all stats
        $this->gameProcessor->process();
        $this->playerProcessor->process();
        $this->teamProcessor->process();
        $this->seasonProcessor->process();
        
        // Verify team wins match game results
        $teamStats = $this->db->query("SELECT * FROM team_stats WHERE team_id = 1")->fetch();
        $this->assertEquals(2, $teamStats['wins']);
        
        // Verify player stats consistency
        $playerStats = $this->db->query("SELECT * FROM player_stats WHERE player_id = 1")->fetch();
        $this->assertEquals($playerStats['hits'], 2);
        $this->assertEquals($playerStats['at_bats'], 4);
    }
    
    private function findTeamAnalysis(array $analyses, int $teamId): ?array
    {
        foreach ($analyses as $analysis) {
            if ($analysis['team_id'] === $teamId) {
                return $analysis;
            }
        }
        return null;
    }
    
    private function findPlayerAnalysis(array $analyses, int $playerId): ?array
    {
        foreach ($analyses as $analysis) {
            if ($analysis['player_id'] === $playerId) {
                return $analysis;
            }
        }
        return null;
    }
    
    private function findGameAnalysis(array $analyses, int $gameId): ?array
    {
        foreach ($analyses as $analysis) {
            if ($analysis['game_id'] === $gameId) {
                return $analysis;
            }
        }
        return null;
    }
} 