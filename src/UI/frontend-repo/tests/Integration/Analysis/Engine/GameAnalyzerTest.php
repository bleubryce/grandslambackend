<?php

namespace Tests\Integration\Analysis\Engine;

use Tests\TestCase;
use BaseballAnalytics\Analysis\Engine\GameAnalyzer;

class GameAnalyzerTest extends TestCase
{
    private GameAnalyzer $analyzer;
    
    protected function needsDatabase(): bool
    {
        return true;
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test data
        $this->setupTestData();
        
        // Create analyzer with test configuration
        $config = [
            'score_differential_threshold' => 3,
            'high_scoring_threshold' => 10,
            'extra_innings_threshold' => 9,
            'analysis_window' => 30
        ];
        
        $this->analyzer = new GameAnalyzer($this->db, $config);
    }
    
    private function setupTestData(): void
    {
        // Create test tables
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
                errors INTEGER,
                left_on_base INTEGER,
                innings_played INTEGER,
                is_home_team BOOLEAN
            )
        ");
        
        // Insert test games
        $games = [
            [
                'game_id' => 1,
                'home_team_id' => 1,
                'away_team_id' => 2,
                'game_date' => '2024-04-01',
                'status' => 'FINAL',
                'season' => 2024
            ],
            [
                'game_id' => 2,
                'home_team_id' => 2,
                'away_team_id' => 3,
                'game_date' => '2024-04-01',
                'status' => 'FINAL',
                'season' => 2024
            ],
            [
                'game_id' => 3,
                'home_team_id' => 1,
                'away_team_id' => 3,
                'game_date' => '2024-04-02',
                'status' => 'FINAL',
                'season' => 2024
            ]
        ];
        
        foreach ($games as $game) {
            $this->db->exec("
                INSERT INTO games (game_id, home_team_id, away_team_id, game_date, status, season)
                VALUES (
                    {$game['game_id']}, {$game['home_team_id']}, {$game['away_team_id']},
                    '{$game['game_date']}', '{$game['status']}', {$game['season']}
                )
            ");
        }
        
        // Insert test game stats
        $gameStats = [
            // Close game
            [
                'stat_id' => 1,
                'game_id' => 1,
                'team_id' => 1,
                'runs_scored' => 4,
                'hits' => 8,
                'errors' => 1,
                'left_on_base' => 6,
                'innings_played' => 9,
                'is_home_team' => true
            ],
            [
                'stat_id' => 2,
                'game_id' => 1,
                'team_id' => 2,
                'runs_scored' => 3,
                'hits' => 7,
                'errors' => 2,
                'left_on_base' => 8,
                'innings_played' => 9,
                'is_home_team' => false
            ],
            // High scoring game
            [
                'stat_id' => 3,
                'game_id' => 2,
                'team_id' => 2,
                'runs_scored' => 12,
                'hits' => 15,
                'errors' => 0,
                'left_on_base' => 9,
                'innings_played' => 9,
                'is_home_team' => true
            ],
            [
                'stat_id' => 4,
                'game_id' => 2,
                'team_id' => 3,
                'runs_scored' => 8,
                'hits' => 12,
                'errors' => 1,
                'left_on_base' => 7,
                'innings_played' => 9,
                'is_home_team' => false
            ],
            // Extra innings game
            [
                'stat_id' => 5,
                'game_id' => 3,
                'team_id' => 1,
                'runs_scored' => 6,
                'hits' => 11,
                'errors' => 1,
                'left_on_base' => 10,
                'innings_played' => 11,
                'is_home_team' => true
            ],
            [
                'stat_id' => 6,
                'game_id' => 3,
                'team_id' => 3,
                'runs_scored' => 5,
                'hits' => 10,
                'errors' => 2,
                'left_on_base' => 8,
                'innings_played' => 11,
                'is_home_team' => false
            ]
        ];
        
        foreach ($gameStats as $stat) {
            $this->db->exec("
                INSERT INTO game_stats (
                    stat_id, game_id, team_id, runs_scored, hits, errors,
                    left_on_base, innings_played, is_home_team
                ) VALUES (
                    {$stat['stat_id']}, {$stat['game_id']}, {$stat['team_id']},
                    {$stat['runs_scored']}, {$stat['hits']}, {$stat['errors']},
                    {$stat['left_on_base']}, {$stat['innings_played']}, {$stat['is_home_team']}
                )
            ");
        }
    }
    
    public function testAnalyze(): void
    {
        // Test the analyze method
        $result = $this->analyzer->analyze();
        
        // Assert that the analysis was successful
        $this->assertTrue($result);
        
        // Get the analysis results
        $results = $this->analyzer->getResults();
        
        // Test that we have the expected result structure
        $this->assertArrayHasKey('game_analysis', $results);
        $this->assertArrayHasKey('scoring_patterns', $results);
        $this->assertArrayHasKey('game_duration_stats', $results);
        
        // Test game analysis
        $gameAnalysis = $results['game_analysis'];
        $this->assertNotEmpty($gameAnalysis);
        
        // Test close game analysis (Game 1)
        $game1Analysis = $this->findGameAnalysis($gameAnalysis, 1);
        $this->assertNotNull($game1Analysis);
        $this->assertEquals(1, $game1Analysis['run_differential']);
        $this->assertFalse($game1Analysis['is_high_scoring']);
        $this->assertFalse($game1Analysis['is_extra_innings']);
        
        // Test high scoring game analysis (Game 2)
        $game2Analysis = $this->findGameAnalysis($gameAnalysis, 2);
        $this->assertNotNull($game2Analysis);
        $this->assertEquals(4, $game2Analysis['run_differential']);
        $this->assertTrue($game2Analysis['is_high_scoring']);
        $this->assertFalse($game2Analysis['is_extra_innings']);
        
        // Test extra innings game analysis (Game 3)
        $game3Analysis = $this->findGameAnalysis($gameAnalysis, 3);
        $this->assertNotNull($game3Analysis);
        $this->assertEquals(1, $game3Analysis['run_differential']);
        $this->assertFalse($game3Analysis['is_high_scoring']);
        $this->assertTrue($game3Analysis['is_extra_innings']);
        
        // Test scoring patterns
        $scoringPatterns = $results['scoring_patterns'];
        $this->assertArrayHasKey('avg_runs_per_game', $scoringPatterns);
        $this->assertArrayHasKey('avg_hits_per_game', $scoringPatterns);
        $this->assertArrayHasKey('avg_errors_per_game', $scoringPatterns);
        
        // Test game duration stats
        $durationStats = $results['game_duration_stats'];
        $this->assertArrayHasKey('avg_innings_per_game', $durationStats);
        $this->assertArrayHasKey('extra_innings_percentage', $durationStats);
    }
    
    public function testAnalyzeWithInvalidConfig(): void
    {
        // Create analyzer with invalid configuration
        $analyzer = new GameAnalyzer($this->db, []);
        
        // Test that analysis fails with invalid config
        $result = $analyzer->analyze();
        $this->assertFalse($result);
    }
    
    public function testAnalyzeWithNoData(): void
    {
        // Clear all test data
        $this->db->exec("DELETE FROM game_stats");
        $this->db->exec("DELETE FROM games");
        
        // Test that analysis handles empty data gracefully
        $result = $this->analyzer->analyze();
        $this->assertTrue($result);
        
        $results = $this->analyzer->getResults();
        $this->assertEmpty($results['game_analysis']);
        $this->assertArrayHasKey('scoring_patterns', $results);
        $this->assertArrayHasKey('game_duration_stats', $results);
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