<?php

namespace Tests\Integration\Analysis\Engine;

use Tests\TestCase;
use BaseballAnalytics\Analysis\Engine\MachineLearningAnalyzer;

class MachineLearningAnalyzerTest extends TestCase
{
    private MachineLearningAnalyzer $analyzer;
    
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
            'prediction_window' => 10,
            'confidence_threshold' => 0.7,
            'min_sample_size' => 30,
            'features' => [
                'batting_average',
                'on_base_percentage',
                'slugging_percentage',
                'earned_run_average',
                'walks_hits_per_inning'
            ]
        ];
        
        $this->analyzer = new MachineLearningAnalyzer($this->db, $config);
    }
    
    private function setupTestData(): void
    {
        // Create test tables
        $this->db->exec("
            CREATE TABLE player_predictions (
                prediction_id INTEGER PRIMARY KEY,
                player_id INTEGER,
                prediction_type TEXT,
                predicted_value FLOAT,
                confidence_score FLOAT,
                features_used TEXT,
                prediction_date DATE
            )
        ");
        
        $this->db->exec("
            CREATE TABLE player_stats_historical (
                stat_id INTEGER PRIMARY KEY,
                player_id INTEGER,
                game_id INTEGER,
                batting_average FLOAT,
                on_base_percentage FLOAT,
                slugging_percentage FLOAT,
                earned_run_average FLOAT,
                walks_hits_per_inning FLOAT,
                game_date DATE
            )
        ");
        
        // Insert historical player stats
        $playerStats = [
            // Player 1 - Consistent performer
            [
                'stat_id' => 1,
                'player_id' => 1,
                'game_id' => 1,
                'batting_average' => 0.325,
                'on_base_percentage' => 0.400,
                'slugging_percentage' => 0.550,
                'earned_run_average' => null,
                'walks_hits_per_inning' => null,
                'game_date' => '2024-03-20'
            ],
            [
                'stat_id' => 2,
                'player_id' => 1,
                'game_id' => 2,
                'batting_average' => 0.330,
                'on_base_percentage' => 0.405,
                'slugging_percentage' => 0.560,
                'earned_run_average' => null,
                'walks_hits_per_inning' => null,
                'game_date' => '2024-03-21'
            ],
            // Player 2 - Pitcher with improving trend
            [
                'stat_id' => 3,
                'player_id' => 2,
                'game_id' => 1,
                'batting_average' => null,
                'on_base_percentage' => null,
                'slugging_percentage' => null,
                'earned_run_average' => 3.50,
                'walks_hits_per_inning' => 1.25,
                'game_date' => '2024-03-20'
            ],
            [
                'stat_id' => 4,
                'player_id' => 2,
                'game_id' => 2,
                'batting_average' => null,
                'on_base_percentage' => null,
                'slugging_percentage' => null,
                'earned_run_average' => 3.25,
                'walks_hits_per_inning' => 1.20,
                'game_date' => '2024-03-21'
            ],
            // Player 3 - Inconsistent performer
            [
                'stat_id' => 5,
                'player_id' => 3,
                'game_id' => 1,
                'batting_average' => 0.280,
                'on_base_percentage' => 0.350,
                'slugging_percentage' => 0.480,
                'earned_run_average' => null,
                'walks_hits_per_inning' => null,
                'game_date' => '2024-03-20'
            ],
            [
                'stat_id' => 6,
                'player_id' => 3,
                'game_id' => 2,
                'batting_average' => 0.245,
                'on_base_percentage' => 0.320,
                'slugging_percentage' => 0.420,
                'earned_run_average' => null,
                'walks_hits_per_inning' => null,
                'game_date' => '2024-03-21'
            ]
        ];
        
        foreach ($playerStats as $stat) {
            $this->db->exec("
                INSERT INTO player_stats_historical (
                    stat_id, player_id, game_id, batting_average, on_base_percentage,
                    slugging_percentage, earned_run_average, walks_hits_per_inning, game_date
                ) VALUES (
                    {$stat['stat_id']}, {$stat['player_id']}, {$stat['game_id']},
                    " . ($stat['batting_average'] === null ? "NULL" : $stat['batting_average']) . ",
                    " . ($stat['on_base_percentage'] === null ? "NULL" : $stat['on_base_percentage']) . ",
                    " . ($stat['slugging_percentage'] === null ? "NULL" : $stat['slugging_percentage']) . ",
                    " . ($stat['earned_run_average'] === null ? "NULL" : $stat['earned_run_average']) . ",
                    " . ($stat['walks_hits_per_inning'] === null ? "NULL" : $stat['walks_hits_per_inning']) . ",
                    '{$stat['game_date']}'
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
        $this->assertArrayHasKey('player_predictions', $results);
        $this->assertArrayHasKey('model_metrics', $results);
        
        // Test player predictions
        $predictions = $results['player_predictions'];
        $this->assertNotEmpty($predictions);
        
        // Test predictions for Player 1 (Consistent batter)
        $player1Predictions = $this->findPlayerPredictions($predictions, 1);
        $this->assertNotNull($player1Predictions);
        $this->assertArrayHasKey('batting_average_prediction', $player1Predictions);
        $this->assertGreaterThan(0.7, $player1Predictions['confidence_score']);
        
        // Test predictions for Player 2 (Improving pitcher)
        $player2Predictions = $this->findPlayerPredictions($predictions, 2);
        $this->assertNotNull($player2Predictions);
        $this->assertArrayHasKey('earned_run_average_prediction', $player2Predictions);
        $this->assertLessThan(3.50, $player2Predictions['earned_run_average_prediction']);
        
        // Test predictions for Player 3 (Inconsistent batter)
        $player3Predictions = $this->findPlayerPredictions($predictions, 3);
        $this->assertNotNull($player3Predictions);
        $this->assertArrayHasKey('confidence_score', $player3Predictions);
        $this->assertLessThan(0.7, $player3Predictions['confidence_score']);
        
        // Test model metrics
        $modelMetrics = $results['model_metrics'];
        $this->assertArrayHasKey('accuracy', $modelMetrics);
        $this->assertArrayHasKey('mae', $modelMetrics);
        $this->assertArrayHasKey('rmse', $modelMetrics);
    }
    
    public function testAnalyzeWithInvalidConfig(): void
    {
        // Create analyzer with invalid configuration
        $analyzer = new MachineLearningAnalyzer($this->db, []);
        
        // Test that analysis fails with invalid config
        $result = $analyzer->analyze();
        $this->assertFalse($result);
    }
    
    public function testAnalyzeWithInsufficientData(): void
    {
        // Clear historical data
        $this->db->exec("DELETE FROM player_stats_historical");
        
        // Test that analysis handles insufficient data gracefully
        $result = $this->analyzer->analyze();
        $this->assertTrue($result);
        
        $results = $this->analyzer->getResults();
        $this->assertEmpty($results['player_predictions']);
        $this->assertArrayHasKey('model_metrics', $results);
        $this->assertEquals(0, $results['model_metrics']['accuracy']);
    }
    
    private function findPlayerPredictions(array $predictions, int $playerId): ?array
    {
        foreach ($predictions as $prediction) {
            if ($prediction['player_id'] === $playerId) {
                return $prediction;
            }
        }
        return null;
    }
} 