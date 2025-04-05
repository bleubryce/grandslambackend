<?php

namespace Tests\Integration\Analysis\Engine;

use Tests\TestCase;
use BaseballAnalytics\Analysis\Engine\AnalysisEngine;
use BaseballAnalytics\Analysis\Engine\TeamPerformanceAnalyzer;
use BaseballAnalytics\Analysis\Engine\PlayerPerformanceAnalyzer;
use BaseballAnalytics\Analysis\Engine\GameAnalyzer;
use BaseballAnalytics\Analysis\Engine\MachineLearningAnalyzer;

class AnalysisEngineTest extends TestCase
{
    private AnalysisEngine $engine;
    
    protected function needsDatabase(): bool
    {
        return true;
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test data in the database
        $this->setupTestData();
        
        // Create the analysis engine with test configuration
        $config = [
            'team' => [
                'min_games_played' => 10,
                'performance_window' => 30,
                'run_differential_weight' => 1.5
            ],
            'player' => [
                'min_plate_appearances' => 50,
                'min_innings_pitched' => 20,
                'correlation_threshold' => 0.7,
                'performance_window' => 30
            ],
            'game' => [
                'min_sample_size' => 10,
                'scoring_threshold' => 5,
                'performance_window' => 30
            ],
            'ml' => [
                'models' => [
                    'model_path' => '/tmp/test_models'
                ],
                'training' => [
                    'epochs' => 5,
                    'batch_size' => 32
                ],
                'evaluation' => [
                    'metrics' => ['accuracy', 'precision', 'recall']
                ],
                'optimization' => [
                    'learning_rate' => 0.001
                ]
            ]
        ];
        
        $this->engine = new AnalysisEngine($this->db, $config);
    }
    
    private function setupTestData(): void
    {
        // Create test tables
        $this->db->exec("
            CREATE TABLE teams (
                team_id INTEGER PRIMARY KEY,
                name TEXT NOT NULL
            )
        ");
        
        $this->db->exec("
            CREATE TABLE games (
                game_id INTEGER PRIMARY KEY,
                date TEXT NOT NULL,
                home_team_id INTEGER,
                away_team_id INTEGER,
                home_score INTEGER,
                away_score INTEGER,
                status TEXT,
                season INTEGER
            )
        ");
        
        $this->db->exec("
            CREATE TABLE players (
                player_id INTEGER PRIMARY KEY,
                full_name TEXT NOT NULL,
                team_id INTEGER
            )
        ");
        
        // Insert test data
        $this->insertTestTeams();
        $this->insertTestGames();
        $this->insertTestPlayers();
    }
    
    private function insertTestTeams(): void
    {
        $teams = [
            ['team_id' => 1, 'name' => 'Test Team A'],
            ['team_id' => 2, 'name' => 'Test Team B']
        ];
        
        foreach ($teams as $team) {
            $this->db->exec("
                INSERT INTO teams (team_id, name)
                VALUES ({$team['team_id']}, '{$team['name']}')
            ");
        }
    }
    
    private function insertTestGames(): void
    {
        $games = [
            [
                'game_id' => 1,
                'date' => '2024-04-01',
                'home_team_id' => 1,
                'away_team_id' => 2,
                'home_score' => 5,
                'away_score' => 3,
                'status' => 'completed',
                'season' => 2024
            ],
            [
                'game_id' => 2,
                'date' => '2024-04-02',
                'home_team_id' => 2,
                'away_team_id' => 1,
                'home_score' => 2,
                'away_score' => 6,
                'status' => 'completed',
                'season' => 2024
            ]
        ];
        
        foreach ($games as $game) {
            $this->db->exec("
                INSERT INTO games (game_id, date, home_team_id, away_team_id, 
                                 home_score, away_score, status, season)
                VALUES (
                    {$game['game_id']}, '{$game['date']}', {$game['home_team_id']},
                    {$game['away_team_id']}, {$game['home_score']}, {$game['away_score']},
                    '{$game['status']}', {$game['season']}
                )
            ");
        }
    }
    
    private function insertTestPlayers(): void
    {
        $players = [
            ['player_id' => 1, 'full_name' => 'Player One', 'team_id' => 1],
            ['player_id' => 2, 'full_name' => 'Player Two', 'team_id' => 1],
            ['player_id' => 3, 'full_name' => 'Player Three', 'team_id' => 2],
            ['player_id' => 4, 'full_name' => 'Player Four', 'team_id' => 2]
        ];
        
        foreach ($players as $player) {
            $this->db->exec("
                INSERT INTO players (player_id, full_name, team_id)
                VALUES (
                    {$player['player_id']}, '{$player['full_name']}', {$player['team_id']}
                )
            ");
        }
    }
    
    public function testAnalyze(): void
    {
        // Test the main analyze method
        $result = $this->engine->analyze();
        
        // Assert that the analysis was successful
        $this->assertTrue($result);
        
        // Get the analysis results
        $results = $this->engine->getResults();
        
        // Assert that we have results for each analyzer
        $this->assertArrayHasKey('team', $results);
        $this->assertArrayHasKey('player', $results);
        $this->assertArrayHasKey('game', $results);
        $this->assertArrayHasKey('ml', $results);
        
        // Test team analysis results
        $teamResults = $results['team'];
        $this->assertNotEmpty($teamResults);
        $this->assertArrayHasKey('performance_metrics', $teamResults);
        
        // Test player analysis results
        $playerResults = $results['player'];
        $this->assertNotEmpty($playerResults);
        $this->assertArrayHasKey('batting_analysis', $playerResults);
        $this->assertArrayHasKey('pitching_analysis', $playerResults);
        
        // Test game analysis results
        $gameResults = $results['game'];
        $this->assertNotEmpty($gameResults);
        $this->assertArrayHasKey('game_outcomes', $gameResults);
        
        // Test machine learning results
        $mlResults = $results['ml'];
        $this->assertNotEmpty($mlResults);
        $this->assertArrayHasKey('predictions', $mlResults);
    }
    
    public function testAnalyzeWithInvalidConfig(): void
    {
        // Create engine with invalid configuration
        $engine = new AnalysisEngine($this->db, []);
        
        // Test that analysis fails with invalid config
        $result = $engine->analyze();
        $this->assertFalse($result);
    }
    
    public function testAnalyzeWithNoData(): void
    {
        // Clear all test data
        $this->db->exec("DELETE FROM games");
        $this->db->exec("DELETE FROM players");
        $this->db->exec("DELETE FROM teams");
        
        // Test that analysis handles empty data gracefully
        $result = $this->engine->analyze();
        $this->assertTrue($result);
        
        $results = $this->engine->getResults();
        $this->assertEmpty($results['team']);
        $this->assertEmpty($results['player']);
        $this->assertEmpty($results['game']);
    }
} 