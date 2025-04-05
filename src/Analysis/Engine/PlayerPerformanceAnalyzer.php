<?php

namespace BaseballAnalytics\Analysis\Engine;

use BaseballAnalytics\Database\Connection;

class PlayerPerformanceAnalyzer extends BaseAnalyzer
{
    private const REQUIRED_CONFIG = [
        'min_plate_appearances',
        'min_innings_pitched',
        'correlation_threshold',
        'performance_window'
    ];

    private array $battingMetrics;
    private array $pitchingMetrics;

    public function __construct(Connection $db, array $config = [])
    {
        parent::__construct($db, $config);
        $this->initializeMetrics();
    }

    private function initializeMetrics(): void
    {
        $this->battingMetrics = [
            'avg' => 'Batting Average',
            'obp' => 'On-base Percentage',
            'slg' => 'Slugging Percentage',
            'ops' => 'OPS',
            'iso' => 'Isolated Power',
            'babip' => 'BABIP',
            'bb_rate' => 'Walk Rate',
            'k_rate' => 'Strikeout Rate'
        ];

        $this->pitchingMetrics = [
            'era' => 'Earned Run Average',
            'whip' => 'WHIP',
            'k_9' => 'K/9',
            'bb_9' => 'BB/9',
            'hr_9' => 'HR/9',
            'fip' => 'FIP',
            'lob_pct' => 'LOB%',
            'gb_rate' => 'Ground Ball Rate'
        ];
    }

    public function analyze(): bool
    {
        if (!$this->validateConfig(self::REQUIRED_CONFIG)) {
            return false;
        }

        $this->beginAnalysis();

        try {
            $this->analyzeBattingPerformance();
            $this->analyzePitchingPerformance();
            $this->analyzePerformanceTrends();
            $this->identifyOutliers();
            $this->generatePredictions();

            $this->endAnalysis();
            return true;
        } catch (\Exception $e) {
            $this->logError("Analysis failed", $e);
            return false;
        }
    }

    private function analyzeBattingPerformance(): void
    {
        $sql = "SELECT p.player_id, p.full_name, 
                       SUM(bs.at_bats) as ab, SUM(bs.hits) as h,
                       SUM(bs.doubles) as doubles, SUM(bs.triples) as triples,
                       SUM(bs.home_runs) as hr, SUM(bs.walks) as bb,
                       SUM(bs.strikeouts) as k, SUM(bs.plate_appearances) as pa
                FROM players p
                JOIN batting_stats bs ON p.player_id = bs.player_id
                WHERE bs.season = :season
                GROUP BY p.player_id, p.full_name
                HAVING SUM(bs.plate_appearances) >= :min_pa";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'season' => date('Y'),
            'min_pa' => $this->config['min_plate_appearances']
        ]);

        $battingStats = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $stats = $this->calculateBattingMetrics($row);
            $battingStats[$row['player_id']] = array_merge($row, $stats);
            $this->incrementStat('records_analyzed');
        }

        $this->results['batting_analysis'] = $this->analyzeBattingMetrics($battingStats);
        $this->incrementStat('metrics_computed');
    }

    private function analyzePitchingPerformance(): void
    {
        $sql = "SELECT p.player_id, p.full_name,
                       SUM(ps.innings_pitched) as ip, SUM(ps.earned_runs) as er,
                       SUM(ps.hits_allowed) as hits, SUM(ps.walks) as bb,
                       SUM(ps.strikeouts) as k, SUM(ps.home_runs_allowed) as hr,
                       SUM(ps.batters_faced) as bf
                FROM players p
                JOIN pitching_stats ps ON p.player_id = ps.player_id
                WHERE ps.season = :season
                GROUP BY p.player_id, p.full_name
                HAVING SUM(ps.innings_pitched) >= :min_ip";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'season' => date('Y'),
            'min_ip' => $this->config['min_innings_pitched']
        ]);

        $pitchingStats = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $stats = $this->calculatePitchingMetrics($row);
            $pitchingStats[$row['player_id']] = array_merge($row, $stats);
            $this->incrementStat('records_analyzed');
        }

        $this->results['pitching_analysis'] = $this->analyzePitchingMetrics($pitchingStats);
        $this->incrementStat('metrics_computed');
    }

    private function calculateBattingMetrics(array $stats): array
    {
        $metrics = [];
        
        // Basic metrics
        $metrics['avg'] = $stats['ab'] > 0 ? round($stats['h'] / $stats['ab'], 3) : 0;
        $metrics['obp'] = $stats['pa'] > 0 ? 
            round(($stats['h'] + $stats['bb']) / $stats['pa'], 3) : 0;
        
        // Power metrics
        $total_bases = $stats['h'] + $stats['doubles'] + (2 * $stats['triples']) + 
                      (3 * $stats['hr']);
        $metrics['slg'] = $stats['ab'] > 0 ? round($total_bases / $stats['ab'], 3) : 0;
        $metrics['ops'] = $metrics['obp'] + $metrics['slg'];
        $metrics['iso'] = $metrics['slg'] - $metrics['avg'];
        
        // Plate discipline
        $metrics['bb_rate'] = $stats['pa'] > 0 ? round($stats['bb'] / $stats['pa'], 3) : 0;
        $metrics['k_rate'] = $stats['pa'] > 0 ? round($stats['k'] / $stats['pa'], 3) : 0;
        
        return $metrics;
    }

    private function calculatePitchingMetrics(array $stats): array
    {
        $metrics = [];
        
        // Basic metrics
        $metrics['era'] = $stats['ip'] > 0 ? 
            round((9 * $stats['er']) / $stats['ip'], 2) : 0;
        $metrics['whip'] = $stats['ip'] > 0 ? 
            round(($stats['hits'] + $stats['bb']) / $stats['ip'], 2) : 0;
        
        // Rate stats
        $metrics['k_9'] = $stats['ip'] > 0 ? 
            round((9 * $stats['k']) / $stats['ip'], 2) : 0;
        $metrics['bb_9'] = $stats['ip'] > 0 ? 
            round((9 * $stats['bb']) / $stats['ip'], 2) : 0;
        $metrics['hr_9'] = $stats['ip'] > 0 ? 
            round((9 * $stats['hr']) / $stats['ip'], 2) : 0;
        
        // Advanced metrics
        $metrics['fip'] = $this->calculateFIP($stats);
        
        return $metrics;
    }

    private function calculateFIP(array $stats): float
    {
        if ($stats['ip'] <= 0) {
            return 0.0;
        }

        $constant = 3.10; // League average FIP constant
        return round(
            ((13 * $stats['hr']) + (3 * $stats['bb']) - (2 * $stats['k'])) / $stats['ip'] + $constant,
            2
        );
    }

    private function analyzeBattingMetrics(array $battingStats): array
    {
        $analysis = [];
        
        foreach ($this->battingMetrics as $metric => $description) {
            $values = array_column($battingStats, $metric);
            
            $analysis[$metric] = [
                'description' => $description,
                'mean' => $this->calculateMean($values),
                'std_dev' => $this->calculateStandardDeviation($values),
                'percentiles' => [
                    25 => $this->calculatePercentile($values, 25),
                    50 => $this->calculatePercentile($values, 50),
                    75 => $this->calculatePercentile($values, 75),
                    90 => $this->calculatePercentile($values, 90)
                ]
            ];
        }
        
        return $analysis;
    }

    private function analyzePitchingMetrics(array $pitchingStats): array
    {
        $analysis = [];
        
        foreach ($this->pitchingMetrics as $metric => $description) {
            $values = array_column($pitchingStats, $metric);
            
            $analysis[$metric] = [
                'description' => $description,
                'mean' => $this->calculateMean($values),
                'std_dev' => $this->calculateStandardDeviation($values),
                'percentiles' => [
                    25 => $this->calculatePercentile($values, 25),
                    50 => $this->calculatePercentile($values, 50),
                    75 => $this->calculatePercentile($values, 75),
                    90 => $this->calculatePercentile($values, 90)
                ]
            ];
        }
        
        return $analysis;
    }

    private function analyzePerformanceTrends(): void
    {
        $window = $this->config['performance_window'];
        
        $sql = "SELECT p.player_id, p.full_name, g.game_date,
                       bs.hits, bs.at_bats, bs.home_runs
                FROM players p
                JOIN batting_stats bs ON p.player_id = bs.player_id
                JOIN games g ON bs.game_id = g.game_id
                WHERE g.game_date >= DATE_SUB(CURRENT_DATE, INTERVAL :window DAY)
                ORDER BY p.player_id, g.game_date";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['window' => $window]);

        $trends = [];
        $currentPlayer = null;
        $playerStats = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($currentPlayer !== $row['player_id']) {
                if ($currentPlayer !== null) {
                    $trends[$currentPlayer] = $this->calculateTrends($playerStats);
                }
                $currentPlayer = $row['player_id'];
                $playerStats = [];
            }
            $playerStats[] = $row;
        }

        if ($currentPlayer !== null) {
            $trends[$currentPlayer] = $this->calculateTrends($playerStats);
        }

        $this->results['performance_trends'] = $trends;
        $this->incrementStat('metrics_computed');
    }

    private function calculateTrends(array $stats): array
    {
        $dates = array_column($stats, 'game_date');
        $averages = array_map(function($stat) {
            return $stat['at_bats'] > 0 ? $stat['hits'] / $stat['at_bats'] : 0;
        }, $stats);

        $window = min(count($averages), 7); // 7-day moving average
        $movingAvg = $this->calculateMovingAverage($averages, $window);

        return [
            'dates' => $dates,
            'values' => $averages,
            'moving_average' => $movingAvg,
            'trend_direction' => $this->determineTrendDirection($movingAvg)
        ];
    }

    private function determineTrendDirection(array $values): string
    {
        if (count($values) < 2) {
            return 'stable';
        }

        $last = end($values);
        $first = reset($values);
        $diff = $last - $first;

        if (abs($diff) < 0.020) { // Less than 20 points change
            return 'stable';
        }

        return $diff > 0 ? 'improving' : 'declining';
    }

    private function identifyOutliers(): void
    {
        $outliers = [
            'batting' => $this->findBattingOutliers(),
            'pitching' => $this->findPitchingOutliers()
        ];

        $this->results['outliers'] = $outliers;
        $this->incrementStat('metrics_computed');
    }

    private function findBattingOutliers(): array
    {
        $outliers = [];
        foreach ($this->battingMetrics as $metric => $description) {
            if (isset($this->results['batting_analysis'][$metric])) {
                $analysis = $this->results['batting_analysis'][$metric];
                $threshold = $analysis['std_dev'] * 2; // Two standard deviations

                $sql = "SELECT p.player_id, p.full_name, bs.$metric as value
                       FROM players p
                       JOIN batting_stats bs ON p.player_id = bs.player_id
                       WHERE ABS(bs.$metric - :mean) > :threshold
                       AND bs.season = :season";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'mean' => $analysis['mean'],
                    'threshold' => $threshold,
                    'season' => date('Y')
                ]);

                $outliers[$metric] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        }
        return $outliers;
    }

    private function findPitchingOutliers(): array
    {
        $outliers = [];
        foreach ($this->pitchingMetrics as $metric => $description) {
            if (isset($this->results['pitching_analysis'][$metric])) {
                $analysis = $this->results['pitching_analysis'][$metric];
                $threshold = $analysis['std_dev'] * 2; // Two standard deviations

                $sql = "SELECT p.player_id, p.full_name, ps.$metric as value
                       FROM players p
                       JOIN pitching_stats ps ON p.player_id = ps.player_id
                       WHERE ABS(ps.$metric - :mean) > :threshold
                       AND ps.season = :season";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'mean' => $analysis['mean'],
                    'threshold' => $threshold,
                    'season' => date('Y')
                ]);

                $outliers[$metric] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        }
        return $outliers;
    }

    private function generatePredictions(): void
    {
        // This is a placeholder for future implementation of predictive modeling
        // We'll use machine learning models here to predict future performance
        $this->logWarning("Predictive modeling not yet implemented");
    }
} 