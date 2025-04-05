<?php

namespace Tests\Integration\Analysis\Engine;

use Tests\TestCase;
use BaseballAnalytics\Analysis\Engine\PlayerPerformanceAnalyzer;

class PlayerPerformanceAnalyzerTest extends TestCase
{
    private PlayerPerformanceAnalyzer $analyzer;
    
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
            'min_plate_appearances' => 10,
            'min_innings_pitched' => 5,
            'correlation_threshold' => 0.7,
            'performance_window' => 30
        ];
        
        $this->analyzer = new PlayerPerformanceAnalyzer($this->db, $config);
    }
    
    private function setupTestData(): void
    {
        // Create test tables
        $this->db->exec("
            CREATE TABLE players (
                player_id INTEGER PRIMARY KEY,
                full_name TEXT NOT NULL,
                team_id INTEGER
            )
        ");
        
        $this->db->exec("
            CREATE TABLE batting_stats (
                stat_id INTEGER PRIMARY KEY,
                player_id INTEGER,
                game_id INTEGER,
                at_bats INTEGER,
                hits INTEGER,
                doubles INTEGER,
                triples INTEGER,
                home_runs INTEGER,
                runs_batted_in INTEGER,
                walks INTEGER,
                strikeouts INTEGER,
                plate_appearances INTEGER,
                season INTEGER
            )
        ");
        
        $this->db->exec("
            CREATE TABLE pitching_stats (
                stat_id INTEGER PRIMARY KEY,
                player_id INTEGER,
                game_id INTEGER,
                innings_pitched REAL,
                earned_runs INTEGER,
                hits_allowed INTEGER,
                walks INTEGER,
                strikeouts INTEGER,
                home_runs_allowed INTEGER,
                batters_faced INTEGER,
                season INTEGER
            )
        ");
        
        // Insert test players
        $players = [
            ['player_id' => 1, 'full_name' => 'Batter One', 'team_id' => 1],
            ['player_id' => 2, 'full_name' => 'Pitcher One', 'team_id' => 1],
            ['player_id' => 3, 'full_name' => 'Two Way Player', 'team_id' => 2]
        ];
        
        foreach ($players as $player) {
            $this->db->exec("
                INSERT INTO players (player_id, full_name, team_id)
                VALUES (
                    {$player['player_id']}, '{$player['full_name']}', {$player['team_id']}
                )
            ");
        }
        
        // Insert test batting stats
        $battingStats = [
            // Batter One stats (good performance)
            [
                'stat_id' => 1,
                'player_id' => 1,
                'game_id' => 1,
                'at_bats' => 4,
                'hits' => 2,
                'doubles' => 1,
                'triples' => 0,
                'home_runs' => 1,
                'runs_batted_in' => 3,
                'walks' => 1,
                'strikeouts' => 1,
                'plate_appearances' => 5,
                'season' => 2024
            ],
            // Two Way Player batting stats
            [
                'stat_id' => 2,
                'player_id' => 3,
                'game_id' => 1,
                'at_bats' => 3,
                'hits' => 1,
                'doubles' => 0,
                'triples' => 0,
                'home_runs' => 0,
                'runs_batted_in' => 1,
                'walks' => 0,
                'strikeouts' => 2,
                'plate_appearances' => 3,
                'season' => 2024
            ]
        ];
        
        foreach ($battingStats as $stat) {
            $this->db->exec("
                INSERT INTO batting_stats (
                    stat_id, player_id, game_id, at_bats, hits, doubles, triples,
                    home_runs, runs_batted_in, walks, strikeouts, plate_appearances, season
                ) VALUES (
                    {$stat['stat_id']}, {$stat['player_id']}, {$stat['game_id']},
                    {$stat['at_bats']}, {$stat['hits']}, {$stat['doubles']}, {$stat['triples']},
                    {$stat['home_runs']}, {$stat['runs_batted_in']}, {$stat['walks']},
                    {$stat['strikeouts']}, {$stat['plate_appearances']}, {$stat['season']}
                )
            ");
        }
        
        // Insert test pitching stats
        $pitchingStats = [
            // Pitcher One stats (good performance)
            [
                'stat_id' => 1,
                'player_id' => 2,
                'game_id' => 1,
                'innings_pitched' => 7.0,
                'earned_runs' => 2,
                'hits_allowed' => 5,
                'walks' => 2,
                'strikeouts' => 8,
                'home_runs_allowed' => 1,
                'batters_faced' => 27,
                'season' => 2024
            ],
            // Two Way Player pitching stats
            [
                'stat_id' => 2,
                'player_id' => 3,
                'game_id' => 2,
                'innings_pitched' => 6.0,
                'earned_runs' => 3,
                'hits_allowed' => 6,
                'walks' => 3,
                'strikeouts' => 5,
                'home_runs_allowed' => 1,
                'batters_faced' => 25,
                'season' => 2024
            ]
        ];
        
        foreach ($pitchingStats as $stat) {
            $this->db->exec("
                INSERT INTO pitching_stats (
                    stat_id, player_id, game_id, innings_pitched, earned_runs,
                    hits_allowed, walks, strikeouts, home_runs_allowed, batters_faced, season
                ) VALUES (
                    {$stat['stat_id']}, {$stat['player_id']}, {$stat['game_id']},
                    {$stat['innings_pitched']}, {$stat['earned_runs']}, {$stat['hits_allowed']},
                    {$stat['walks']}, {$stat['strikeouts']}, {$stat['home_runs_allowed']},
                    {$stat['batters_faced']}, {$stat['season']}
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
        $this->assertArrayHasKey('batting_analysis', $results);
        $this->assertArrayHasKey('pitching_analysis', $results);
        
        // Test batting analysis
        $battingAnalysis = $results['batting_analysis'];
        $this->assertNotEmpty($battingAnalysis);
        
        // Test Batter One's metrics
        $batterOneMetrics = $this->findPlayerMetrics($battingAnalysis, 1);
        $this->assertNotNull($batterOneMetrics);
        $this->assertEquals(0.500, $batterOneMetrics['avg']); // 2 hits in 4 at-bats
        $this->assertEquals(0.600, $batterOneMetrics['obp']); // (2 hits + 1 walk) / 5 plate appearances
        $this->assertGreaterThan(1.000, $batterOneMetrics['slg']); // (2 + 2 + 4) / 4 at-bats
        
        // Test pitching analysis
        $pitchingAnalysis = $results['pitching_analysis'];
        $this->assertNotEmpty($pitchingAnalysis);
        
        // Test Pitcher One's metrics
        $pitcherOneMetrics = $this->findPlayerMetrics($pitchingAnalysis, 2);
        $this->assertNotNull($pitcherOneMetrics);
        $this->assertEquals(2.57, $pitcherOneMetrics['era']); // (2 ER * 9) / 7 IP
        $this->assertEquals(1.00, $pitcherOneMetrics['whip']); // (5 hits + 2 walks) / 7 IP
        $this->assertEquals(10.29, $pitcherOneMetrics['k_9']); // (8 K * 9) / 7 IP
        
        // Test Two Way Player's presence in both analyses
        $this->assertNotNull($this->findPlayerMetrics($battingAnalysis, 3));
        $this->assertNotNull($this->findPlayerMetrics($pitchingAnalysis, 3));
    }
    
    public function testAnalyzeWithInvalidConfig(): void
    {
        // Create analyzer with invalid configuration
        $analyzer = new PlayerPerformanceAnalyzer($this->db, []);
        
        // Test that analysis fails with invalid config
        $result = $analyzer->analyze();
        $this->assertFalse($result);
    }
    
    public function testAnalyzeWithNoData(): void
    {
        // Clear all test data
        $this->db->exec("DELETE FROM batting_stats");
        $this->db->exec("DELETE FROM pitching_stats");
        $this->db->exec("DELETE FROM players");
        
        // Test that analysis handles empty data gracefully
        $result = $this->analyzer->analyze();
        $this->assertTrue($result);
        
        $results = $this->analyzer->getResults();
        $this->assertEmpty($results['batting_analysis']);
        $this->assertEmpty($results['pitching_analysis']);
    }
    
    private function findPlayerMetrics(array $metrics, int $playerId): ?array
    {
        foreach ($metrics as $metric) {
            if ($metric['player_id'] === $playerId) {
                return $metric;
            }
        }
        return null;
    }
} 