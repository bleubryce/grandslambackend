<?php

namespace Tests\Integration\Analysis\Engine;

use Tests\TestCase;
use BaseballAnalytics\Analysis\Engine\TeamPerformanceAnalyzer;

class TeamPerformanceAnalyzerTest extends TestCase
{
    private TeamPerformanceAnalyzer $analyzer;
    
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
            'min_games_played' => 2,
            'performance_window' => 30,
            'run_differential_weight' => 1.5
        ];
        
        $this->analyzer = new TeamPerformanceAnalyzer($this->db, $config);
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
        
        // Insert test teams
        $teams = [
            ['team_id' => 1, 'name' => 'Test Team A'],
            ['team_id' => 2, 'name' => 'Test Team B'],
            ['team_id' => 3, 'name' => 'Test Team C']
        ];
        
        foreach ($teams as $team) {
            $this->db->exec("
                INSERT INTO teams (team_id, name)
                VALUES ({$team['team_id']}, '{$team['name']}')
            ");
        }
        
        // Insert test games
        $games = [
            // Team A vs Team B
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
            // Team B vs Team A
            [
                'game_id' => 2,
                'date' => '2024-04-02',
                'home_team_id' => 2,
                'away_team_id' => 1,
                'home_score' => 2,
                'away_score' => 6,
                'status' => 'completed',
                'season' => 2024
            ],
            // Team A vs Team C
            [
                'game_id' => 3,
                'date' => '2024-04-03',
                'home_team_id' => 1,
                'away_team_id' => 3,
                'home_score' => 4,
                'away_score' => 1,
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
    
    public function testAnalyze(): void
    {
        // Test the analyze method
        $result = $this->analyzer->analyze();
        
        // Assert that the analysis was successful
        $this->assertTrue($result);
        
        // Get the analysis results
        $results = $this->analyzer->getResults();
        
        // Test that we have the expected result structure
        $this->assertArrayHasKey('performance_metrics', $results);
        $this->assertArrayHasKey('team_rankings', $results);
        $this->assertArrayHasKey('home_field_advantage', $results);
        
        // Test Team A's performance metrics
        $teamAMetrics = $this->findTeamMetrics($results['performance_metrics'], 1);
        $this->assertNotNull($teamAMetrics);
        $this->assertEquals(0.750, $teamAMetrics['win_pct']); // Won 3 out of 4 games
        $this->assertGreaterThan(0, $teamAMetrics['run_differential']); // Scored more than allowed
        
        // Test Team B's performance metrics
        $teamBMetrics = $this->findTeamMetrics($results['performance_metrics'], 2);
        $this->assertNotNull($teamBMetrics);
        $this->assertEquals(0.000, $teamBMetrics['win_pct']); // Lost both games
        $this->assertLessThan(0, $teamBMetrics['run_differential']); // Allowed more than scored
        
        // Test home field advantage calculations
        $homeAdvantage = $results['home_field_advantage'];
        $this->assertArrayHasKey(1, $homeAdvantage); // Team A
        $this->assertArrayHasKey(2, $homeAdvantage); // Team B
        
        // Test that Team A has better home performance
        $teamAHomeAdvantage = $homeAdvantage[1];
        $this->assertEquals(1.000, $teamAHomeAdvantage['home_win_pct']); // Won all home games
    }
    
    public function testAnalyzeWithInvalidConfig(): void
    {
        // Create analyzer with invalid configuration
        $analyzer = new TeamPerformanceAnalyzer($this->db, []);
        
        // Test that analysis fails with invalid config
        $result = $analyzer->analyze();
        $this->assertFalse($result);
    }
    
    public function testAnalyzeWithNoData(): void
    {
        // Clear all test data
        $this->db->exec("DELETE FROM games");
        $this->db->exec("DELETE FROM teams");
        
        // Test that analysis handles empty data gracefully
        $result = $this->analyzer->analyze();
        $this->assertTrue($result);
        
        $results = $this->analyzer->getResults();
        $this->assertEmpty($results['performance_metrics']);
        $this->assertEmpty($results['team_rankings']);
    }
    
    private function findTeamMetrics(array $metrics, int $teamId): ?array
    {
        foreach ($metrics as $metric) {
            if ($metric['team_id'] === $teamId) {
                return $metric;
            }
        }
        return null;
    }
} 