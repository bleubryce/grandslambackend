<?php

namespace BaseballAnalytics\Analysis\Engine;

use BaseballAnalytics\Database\Connection;
use BaseballAnalytics\Analysis\Engine\PredictiveAnalytics\ModelTrainer;
use BaseballAnalytics\Analysis\Engine\Performance\PerformanceOptimizer;

class MachineLearningAnalyzer extends BaseAnalyzer
{
    private const REQUIRED_CONFIG = [
        'models',
        'training',
        'evaluation',
        'optimization'
    ];

    private array $modelMetrics;
    private array $trainingData;
    private array $testData;
    private ModelTrainer $modelTrainer;
    private PerformanceOptimizer $optimizer;
    private array $trainedModels = [];

    public function __construct(Connection $db, array $config = [])
    {
        parent::__construct($db, $config);
        $this->modelTrainer = new ModelTrainer($config['models']['model_path'] ?? '/tmp/models');
        $this->optimizer = new PerformanceOptimizer($config['optimization'] ?? []);
        $this->initializeMetrics();
    }

    private function initializeMetrics(): void
    {
        $this->modelMetrics = [
            'player_performance' => [
                'batting' => ['avg', 'obp', 'slg', 'ops', 'war'],
                'pitching' => ['era', 'whip', 'k_per_9', 'war']
            ],
            'game_outcome' => [
                'win_probability',
                'run_expectancy',
                'win_margin'
            ]
        ];
        
        $this->trainingData = [];
        $this->testData = [];
    }

    public function analyze(): bool
    {
        if (!$this->validateConfig(self::REQUIRED_CONFIG)) {
            return false;
        }

        $this->beginAnalysis();

        try {
            $this->prepareTrainingData();
            $this->trainPlayerPerformanceModels();
            $this->trainGameOutcomeModels();
            $this->evaluateModels();
            $this->generatePredictions();

            $this->endAnalysis();
            return true;
        } catch (\Exception $e) {
            $this->logError("Machine learning analysis failed", $e);
            return false;
        }
    }

    private function prepareTrainingData(): void
    {
        // Try to get cached training data
        $cacheKey = 'training_data_' . date('Y_m');
        $cachedData = $this->optimizer->getCachedResult($cacheKey);
        
        if ($cachedData !== null) {
            list($this->trainingData, $this->testData) = $cachedData;
            return;
        }

        // If not cached, process in batches
        $this->prepareBattingData();
        $this->preparePitchingData();

        // Cache the prepared data
        $this->optimizer->cacheResult($cacheKey, [
            $this->trainingData,
            $this->testData
        ], 86400); // Cache for 24 hours
    }

    private function prepareBattingData(): void
    {
        $sql = "WITH player_seasons AS (
            SELECT 
                p.player_id,
                p.full_name,
                bs.season,
                SUM(bs.plate_appearances) as pa,
                SUM(bs.at_bats) as ab,
                SUM(bs.hits) as h,
                SUM(bs.doubles) as doubles,
                SUM(bs.triples) as triples,
                SUM(bs.home_runs) as hr,
                SUM(bs.walks) as bb,
                SUM(bs.strikeouts) as k,
                SUM(bs.runs_batted_in) as rbi
            FROM players p
            JOIN batting_stats bs ON p.player_id = bs.player_id
            GROUP BY p.player_id, p.full_name, bs.season
            HAVING SUM(bs.plate_appearances) >= :min_pa
        )
        SELECT 
            ps.*,
            LEAD(h::float / NULLIF(ab, 0)) OVER (PARTITION BY player_id ORDER BY season) as next_season_avg,
            LEAD(hr) OVER (PARTITION BY player_id ORDER BY season) as next_season_hr,
            LEAD(rbi) OVER (PARTITION BY player_id ORDER BY season) as next_season_rbi
        FROM player_seasons ps
        ORDER BY ps.season DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['min_pa' => $this->config['models']['player_performance']['min_samples']['batting']]);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Process rows in batches
        $this->optimizer->processBatch($rows, function($batch) {
            foreach ($batch as $row) {
                if (!is_null($row['next_season_avg'])) {
                    $features = $this->extractBattingFeatures($row);
                    $labels = [
                        'avg' => $row['next_season_avg'],
                        'hr' => $row['next_season_hr'],
                        'rbi' => $row['next_season_rbi']
                    ];
                    
                    if ($row['season'] >= date('Y') - 1) {
                        $this->testData['batting'][] = ['features' => $features, 'labels' => $labels];
                    } else {
                        $this->trainingData['batting'][] = ['features' => $features, 'labels' => $labels];
                    }
                }
                $this->incrementStat('records_analyzed');
            }
            return []; // Return empty array as we're updating class properties directly
        });
    }

    private function preparePitchingData(): void
    {
        $sql = "WITH pitcher_seasons AS (
            SELECT 
                p.player_id,
                p.full_name,
                ps.season,
                SUM(ps.innings_pitched) as ip,
                SUM(ps.earned_runs) as er,
                SUM(ps.hits_allowed) as hits,
                SUM(ps.walks) as bb,
                SUM(ps.strikeouts) as k,
                SUM(ps.home_runs_allowed) as hr
            FROM players p
            JOIN pitching_stats ps ON p.player_id = ps.player_id
            GROUP BY p.player_id, p.full_name, ps.season
            HAVING SUM(ps.innings_pitched) >= :min_ip
        )
        SELECT 
            ps.*,
            LEAD((er * 9.0) / NULLIF(ip, 0)) OVER (PARTITION BY player_id ORDER BY season) as next_season_era,
            LEAD((k * 9.0) / NULLIF(ip, 0)) OVER (PARTITION BY player_id ORDER BY season) as next_season_k9,
            LEAD((bb + hits) / NULLIF(ip, 0)) OVER (PARTITION BY player_id ORDER BY season) as next_season_whip
        FROM pitcher_seasons ps
        ORDER BY ps.season DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['min_ip' => 50]); // Minimum innings pitched for reliable data

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Process rows in batches
        $this->optimizer->processBatch($rows, function($batch) {
            foreach ($batch as $row) {
                if (!is_null($row['next_season_era'])) {
                    $features = $this->extractPitchingFeatures($row);
                    $labels = [
                        'era' => $row['next_season_era'],
                        'k9' => $row['next_season_k9'],
                        'whip' => $row['next_season_whip']
                    ];
                    
                    if ($row['season'] >= date('Y') - 1) {
                        $this->testData['pitching'][] = ['features' => $features, 'labels' => $labels];
                    } else {
                        $this->trainingData['pitching'][] = ['features' => $features, 'labels' => $labels];
                    }
                }
                $this->incrementStat('records_analyzed');
            }
            return []; // Return empty array as we're updating class properties directly
        });
    }

    private function extractBattingFeatures(array $row): array
    {
        return [
            'avg' => $row['ab'] > 0 ? $row['h'] / $row['ab'] : 0,
            'pa' => $row['pa'],
            'hr_rate' => $row['pa'] > 0 ? $row['hr'] / $row['pa'] : 0,
            'bb_rate' => $row['pa'] > 0 ? $row['bb'] / $row['pa'] : 0,
            'k_rate' => $row['pa'] > 0 ? $row['k'] / $row['pa'] : 0,
            'xbh_rate' => $row['pa'] > 0 ? ($row['doubles'] + $row['triples'] + $row['hr']) / $row['pa'] : 0
        ];
    }

    private function extractPitchingFeatures(array $row): array
    {
        return [
            'era' => $row['ip'] > 0 ? ($row['er'] * 9.0) / $row['ip'] : 0,
            'ip' => $row['ip'],
            'k_rate' => $row['ip'] > 0 ? ($row['k'] * 9.0) / $row['ip'] : 0,
            'bb_rate' => $row['ip'] > 0 ? ($row['bb'] * 9.0) / $row['ip'] : 0,
            'hr_rate' => $row['ip'] > 0 ? ($row['hr'] * 9.0) / $row['ip'] : 0,
            'whip' => $row['ip'] > 0 ? ($row['bb'] + $row['hits']) / $row['ip'] : 0
        ];
    }

    private function trainPlayerPerformanceModels(): void
    {
        if (empty($this->trainingData)) {
            $this->logWarning("No training data available for player performance models");
            return;
        }

        $models = $this->config['models']['player_performance'];
        
        // Train models in parallel if enabled
        if ($this->config['optimization']['parallel_training'] ?? false) {
            $this->trainModelsParallel($models);
        } else {
            $this->trainModelsSequential($models);
        }
    }

    private function trainModelsParallel(array $models): void
    {
        $tasks = [];
        
        // Prepare tasks for parallel processing
        foreach (['batting', 'pitching'] as $type) {
            if (!empty($this->trainingData[$type])) {
                foreach ($models['algorithms'] as $algorithm => $settings) {
                    if ($settings['enabled']) {
                        $tasks[] = [
                            'type' => $type,
                            'algorithm' => $algorithm,
                            'data' => $this->trainingData[$type],
                            'settings' => $settings
                        ];
                    }
                }
            }
        }

        // Process tasks in parallel
        $results = $this->optimizer->processParallel(
            $tasks,
            __DIR__ . '/Scripts/train_model.php'
        );

        // Process results
        foreach ($results as $result) {
            if ($result['success']) {
                $this->trainedModels[$result['type']][$result['algorithm']] = $result['model_key'];
                $this->results['models'][$result['type']][$result['algorithm']] = $result['details'];
            } else {
                $this->logError(
                    "Failed to train model: {$result['type']} - {$result['algorithm']}",
                    new \Exception($result['error'])
                );
            }
        }
    }

    private function trainModelsSequential(array $models): void
    {
        // Existing sequential training code...
        // ... rest of the existing trainPlayerPerformanceModels code ...
    }

    private function trainGameOutcomeModels(): void
    {
        $sql = "SELECT 
                    g.game_id,
                    g.home_team_id,
                    g.away_team_id,
                    g.home_score,
                    g.away_score,
                    t1.win_pct as home_win_pct,
                    t2.win_pct as away_win_pct,
                    t1.runs_scored_avg as home_runs_avg,
                    t2.runs_scored_avg as away_runs_avg
                FROM games g
                JOIN team_stats t1 ON g.home_team_id = t1.team_id
                JOIN team_stats t2 ON g.away_team_id = t2.team_id
                WHERE g.season = :season
                AND g.status = 'completed'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $gameData = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $features = [
                'home_win_pct' => $row['home_win_pct'],
                'away_win_pct' => $row['away_win_pct'],
                'home_runs_avg' => $row['home_runs_avg'],
                'away_runs_avg' => $row['away_runs_avg']
            ];
            
            $labels = [
                'winner' => $row['home_score'] > $row['away_score'] ? 1 : 0,
                'run_diff' => $row['home_score'] - $row['away_score']
            ];
            
            $gameData[] = ['features' => $features, 'labels' => $labels];
            $this->incrementStat('records_analyzed');
        }

        // Split into training and test sets
        $splitIndex = (int)(count($gameData) * 0.8);
        $this->trainingData['games'] = array_slice($gameData, 0, $splitIndex);
        $this->testData['games'] = array_slice($gameData, $splitIndex);

        $models = $this->config['models']['game_outcome'];
        foreach ($models['algorithms'] as $algorithm => $settings) {
            if ($settings['enabled']) {
                $this->trainModel(
                    $algorithm,
                    $this->trainingData['games'],
                    'games',
                    $settings
                );
            }
        }

        $this->incrementStat('models_trained');
    }

    private function trainModel(string $algorithm, array $data, string $type, array $settings): void
    {
        $features = array_map(fn($item) => array_values($item['features']), $data);
        $labels = array_map(fn($item) => array_values($item['labels']), $data);

        $modelType = $this->determineModelType($type, $algorithm);
        
        try {
            $result = $this->modelTrainer->trainModel(
                $modelType,
                $algorithm,
                $features,
                $labels,
                $settings
            );

            $this->trainedModels[$type][$algorithm] = $result['model_key'];
            $this->results['models'][$type][$algorithm] = [
                'algorithm' => $algorithm,
                'settings' => $settings,
                'training_size' => count($data),
                'features' => array_keys(reset($data)['features']),
                'metrics' => $result['metrics'],
                'feature_importance' => $result['feature_importance'],
                'training_complete' => true
            ];
        } catch (\Exception $e) {
            $this->logError("Failed to train model: {$type} - {$algorithm}", $e);
            $this->results['models'][$type][$algorithm] = [
                'training_complete' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function determineModelType(string $type, string $algorithm): string
    {
        if ($type === 'games' && $algorithm === 'logistic_regression') {
            return 'classification';
        }
        return 'regression';
    }

    private function calculateMetrics(string $type): array
    {
        if (empty($this->trainedModels[$type])) {
            return [
                'mae' => 0.0,
                'rmse' => 0.0,
                'r2' => 0.0,
                'sample_size' => count($this->testData[$type])
            ];
        }

        $metrics = [];
        foreach ($this->trainedModels[$type] as $algorithm => $modelKey) {
            $metrics[$algorithm] = $this->modelTrainer->getModelMetrics($modelKey);
        }

        return $metrics;
    }

    private function generatePredictions(): void
    {
        $cacheKey = 'predictions_' . date('Y_m_d');
        $cachedPredictions = $this->optimizer->getCachedResult($cacheKey);
        
        if ($cachedPredictions !== null) {
            $this->results['predictions'] = $cachedPredictions;
            return;
        }

        $sql = "SELECT 
                    p.player_id,
                    p.full_name,
                    p.primary_position,
                    bs.season,
                    SUM(bs.plate_appearances) as pa,
                    SUM(bs.at_bats) as ab,
                    SUM(bs.hits) as h,
                    SUM(bs.home_runs) as hr,
                    SUM(bs.runs_batted_in) as rbi
                FROM players p
                JOIN batting_stats bs ON p.player_id = bs.player_id
                WHERE bs.season = :season
                AND p.is_active = true
                GROUP BY p.player_id, p.full_name, p.primary_position, bs.season
                HAVING SUM(bs.plate_appearances) >= :min_pa";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'season' => date('Y'),
            'min_pa' => 100
        ]);

        $predictions = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $features = $this->extractBattingFeatures($row);
            $predictions[$row['player_id']] = [
                'player_name' => $row['full_name'],
                'position' => $row['primary_position'],
                'current_stats' => $features,
                'predictions' => $this->predictNextSeasonStats($features)
            ];
            $this->incrementStat('records_analyzed');
        }

        $this->results['predictions'] = $predictions;
        $this->incrementStat('metrics_computed');

        // Cache the predictions
        $this->optimizer->cacheResult($cacheKey, $this->results['predictions'], 3600); // Cache for 1 hour
    }

    private function predictNextSeasonStats(array $features): array
    {
        if (empty($this->trainedModels['batting'])) {
            // Fallback to simple projections if no models are trained
            return [
                'avg' => $features['avg'],
                'hr' => round($features['hr_rate'] * 600),
                'rbi' => round($features['xbh_rate'] * 600 * 0.5)
            ];
        }

        $normalizer = $this->modelTrainer->getNormalizer();
        $normalizedFeatures = $normalizer->transform([$features]);

        $predictions = [];
        foreach ($this->trainedModels['batting'] as $algorithm => $modelKey) {
            try {
                $model = $this->modelTrainer->loadModel($modelKey);
                $modelPredictions = $model->predict($normalizedFeatures);
                
                foreach ($modelPredictions as $i => $prediction) {
                    $metric = array_keys($features)[$i];
                    if (!isset($predictions[$metric])) {
                        $predictions[$metric] = [];
                    }
                    $predictions[$metric][] = $prediction;
                }
            } catch (\Exception $e) {
                $this->logError("Failed to generate predictions using model: {$modelKey}", $e);
            }
        }

        // Average predictions from all models
        $finalPredictions = [];
        foreach ($predictions as $metric => $values) {
            $finalPredictions[$metric] = array_sum($values) / count($values);
        }

        return $finalPredictions;
    }

    public function cleanup(): void
    {
        // Optimize model storage
        $this->optimizer->optimizeModelStorage($this->config['models']['model_path']);
        
        // Add optimization metrics to results
        $this->results['performance_metrics'] = $this->optimizer->getMetrics();
    }
} 