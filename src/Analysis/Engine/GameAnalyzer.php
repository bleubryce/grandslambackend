<?php

namespace BaseballAnalytics\Analysis\Engine;

use BaseballAnalytics\Database\Connection;

class GameAnalyzer extends BaseAnalyzer
{
    private const REQUIRED_CONFIG = [
        'min_sample_size',
        'scoring_threshold',
        'performance_window'
    ];

    private array $gameMetrics;

    public function __construct(Connection $db, array $config = [])
    {
        parent::__construct($db, $config);
        $this->initializeMetrics();
    }

    private function initializeMetrics(): void
    {
        $this->gameMetrics = [
            'total_runs' => 'Total Runs Scored',
            'run_differential' => 'Run Differential',
            'innings_played' => 'Innings Played',
            'lead_changes' => 'Lead Changes',
            'extra_innings' => 'Extra Innings',
            'shutouts' => 'Shutouts',
            'one_run_games' => 'One-Run Games',
            'blowouts' => 'Blowout Games'
        ];
    }

    public function analyze(): bool
    {
        if (!$this->validateConfig(self::REQUIRED_CONFIG)) {
            return false;
        }

        $this->beginAnalysis();

        try {
            $this->analyzeGameScoring();
            $this->analyzeInningPatterns();
            $this->analyzeGameOutcomes();
            $this->analyzeHomeFieldAdvantage();
            $this->analyzeWeatherImpact();
            $this->generateGameTrends();

            $this->endAnalysis();
            return true;
        } catch (\Exception $e) {
            $this->logError("Analysis failed", $e);
            return false;
        }
    }

    private function analyzeGameScoring(): void
    {
        $sql = "SELECT 
                    g.game_id,
                    g.home_score + g.away_score as total_runs,
                    ABS(g.home_score - g.away_score) as run_differential,
                    g.innings as innings_played,
                    g.weather_condition,
                    g.temperature,
                    g.wind_speed,
                    EXTRACT(MONTH FROM g.game_date) as month
                FROM games g
                WHERE g.season = :season
                AND g.status = 'completed'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $scoringStats = [
            'total_runs' => [],
            'run_differential' => [],
            'innings_played' => [],
            'by_month' => [],
            'by_weather' => []
        ];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $scoringStats['total_runs'][] = $row['total_runs'];
            $scoringStats['run_differential'][] = $row['run_differential'];
            $scoringStats['innings_played'][] = $row['innings_played'];
            
            // Monthly aggregation
            $month = $row['month'];
            if (!isset($scoringStats['by_month'][$month])) {
                $scoringStats['by_month'][$month] = ['runs' => [], 'games' => 0];
            }
            $scoringStats['by_month'][$month]['runs'][] = $row['total_runs'];
            $scoringStats['by_month'][$month]['games']++;
            
            // Weather impact
            $weather = $row['weather_condition'];
            if (!isset($scoringStats['by_weather'][$weather])) {
                $scoringStats['by_weather'][$weather] = ['runs' => [], 'games' => 0];
            }
            $scoringStats['by_weather'][$weather]['runs'][] = $row['total_runs'];
            $scoringStats['by_weather'][$weather]['games']++;
            
            $this->incrementStat('records_analyzed');
        }

        // Calculate summary statistics
        $this->results['scoring_analysis'] = [
            'runs_per_game' => [
                'mean' => $this->calculateMean($scoringStats['total_runs']),
                'std_dev' => $this->calculateStandardDeviation($scoringStats['total_runs']),
                'percentiles' => [
                    25 => $this->calculatePercentile($scoringStats['total_runs'], 25),
                    50 => $this->calculatePercentile($scoringStats['total_runs'], 50),
                    75 => $this->calculatePercentile($scoringStats['total_runs'], 75)
                ]
            ],
            'run_differential' => [
                'mean' => $this->calculateMean($scoringStats['run_differential']),
                'std_dev' => $this->calculateStandardDeviation($scoringStats['run_differential'])
            ],
            'monthly_trends' => $this->calculateMonthlyTrends($scoringStats['by_month']),
            'weather_impact' => $this->calculateWeatherImpact($scoringStats['by_weather'])
        ];

        $this->incrementStat('metrics_computed');
    }

    private function analyzeInningPatterns(): void
    {
        $sql = "SELECT 
                    g.game_id,
                    g.home_score,
                    g.away_score,
                    g.innings,
                    ip.inning,
                    ip.home_runs as home_runs_inning,
                    ip.away_runs as away_runs_inning
                FROM games g
                JOIN inning_plays ip ON g.game_id = ip.game_id
                WHERE g.season = :season
                AND g.status = 'completed'
                ORDER BY g.game_id, ip.inning";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $inningPatterns = [
            'scoring_by_inning' => array_fill(1, 9, ['runs' => 0, 'games' => 0]),
            'lead_changes' => [],
            'comeback_wins' => 0,
            'extra_inning_patterns' => []
        ];

        $currentGame = null;
        $gameLeadChanges = 0;
        $maxLead = 0;

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($currentGame !== $row['game_id']) {
                if ($currentGame !== null) {
                    $inningPatterns['lead_changes'][] = $gameLeadChanges;
                }
                $currentGame = $row['game_id'];
                $gameLeadChanges = 0;
                $maxLead = 0;
            }

            $inning = min($row['inning'], 9);
            $totalRunsInning = $row['home_runs_inning'] + $row['away_runs_inning'];
            $inningPatterns['scoring_by_inning'][$inning]['runs'] += $totalRunsInning;
            $inningPatterns['scoring_by_inning'][$inning]['games']++;

            // Track lead changes
            $currentLead = $row['home_runs_inning'] - $row['away_runs_inning'];
            if (abs($currentLead) > $maxLead) {
                $maxLead = abs($currentLead);
            }
            if ($currentLead * $maxLead < 0) {
                $gameLeadChanges++;
            }

            // Extra innings analysis
            if ($row['inning'] > 9) {
                if (!isset($inningPatterns['extra_inning_patterns'][$row['inning']])) {
                    $inningPatterns['extra_inning_patterns'][$row['inning']] = [
                        'runs' => 0,
                        'games' => 0
                    ];
                }
                $inningPatterns['extra_inning_patterns'][$row['inning']]['runs'] += $totalRunsInning;
                $inningPatterns['extra_inning_patterns'][$row['inning']]['games']++;
            }

            $this->incrementStat('records_analyzed');
        }

        // Add final game's lead changes
        if ($currentGame !== null) {
            $inningPatterns['lead_changes'][] = $gameLeadChanges;
        }

        // Calculate summary statistics
        $this->results['inning_analysis'] = [
            'scoring_distribution' => $this->calculateInningDistribution($inningPatterns['scoring_by_inning']),
            'lead_changes' => [
                'mean' => $this->calculateMean($inningPatterns['lead_changes']),
                'max' => max($inningPatterns['lead_changes']),
                'distribution' => array_count_values($inningPatterns['lead_changes'])
            ],
            'extra_innings' => $this->calculateExtraInningPatterns($inningPatterns['extra_inning_patterns'])
        ];

        $this->incrementStat('metrics_computed');
    }

    private function analyzeGameOutcomes(): void
    {
        $sql = "SELECT 
                    COUNT(*) as total_games,
                    SUM(CASE WHEN ABS(home_score - away_score) = 1 THEN 1 ELSE 0 END) as one_run_games,
                    SUM(CASE WHEN home_score = 0 OR away_score = 0 THEN 1 ELSE 0 END) as shutouts,
                    SUM(CASE WHEN ABS(home_score - away_score) >= 5 THEN 1 ELSE 0 END) as blowouts,
                    SUM(CASE WHEN innings > 9 THEN 1 ELSE 0 END) as extra_inning_games,
                    AVG(CASE WHEN home_score > away_score THEN 1 ELSE 0 END) as home_win_pct
                FROM games
                WHERE season = :season
                AND status = 'completed'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $outcomes = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->results['game_outcomes'] = [
            'total_games' => $outcomes['total_games'],
            'one_run_game_pct' => round($outcomes['one_run_games'] / $outcomes['total_games'], 3),
            'shutout_pct' => round($outcomes['shutouts'] / $outcomes['total_games'], 3),
            'blowout_pct' => round($outcomes['blowouts'] / $outcomes['total_games'], 3),
            'extra_inning_pct' => round($outcomes['extra_inning_games'] / $outcomes['total_games'], 3),
            'home_win_pct' => round($outcomes['home_win_pct'], 3)
        ];

        $this->incrementStat('metrics_computed');
    }

    private function analyzeHomeFieldAdvantage(): void
    {
        $sql = "SELECT 
                    t.team_id,
                    t.name,
                    COUNT(*) as total_home_games,
                    SUM(CASE WHEN g.home_score > g.away_score THEN 1 ELSE 0 END) as home_wins,
                    AVG(g.home_score) as avg_runs_scored,
                    AVG(g.away_score) as avg_runs_allowed,
                    AVG(attendance) as avg_attendance
                FROM teams t
                JOIN games g ON t.team_id = g.home_team_id
                WHERE g.season = :season
                AND g.status = 'completed'
                GROUP BY t.team_id, t.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $homeAdvantage = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $homeAdvantage[$row['team_id']] = [
                'team_name' => $row['name'],
                'home_win_pct' => round($row['home_wins'] / $row['total_home_games'], 3),
                'run_differential' => round($row['avg_runs_scored'] - $row['avg_runs_allowed'], 2),
                'avg_attendance' => round($row['avg_attendance']),
                'total_games' => $row['total_home_games']
            ];
            $this->incrementStat('records_analyzed');
        }

        $this->results['home_field_advantage'] = $homeAdvantage;
        $this->incrementStat('metrics_computed');
    }

    private function analyzeWeatherImpact(): void
    {
        $sql = "SELECT 
                    weather_condition,
                    temperature,
                    wind_speed,
                    home_score + away_score as total_runs,
                    COUNT(*) as games,
                    AVG(home_score + away_score) as avg_runs,
                    AVG(ABS(home_score - away_score)) as avg_run_diff
                FROM games
                WHERE season = :season
                AND status = 'completed'
                AND weather_condition IS NOT NULL
                GROUP BY weather_condition, temperature, wind_speed";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $weatherImpact = [
            'by_condition' => [],
            'by_temperature' => [],
            'by_wind_speed' => []
        ];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            // Group by weather condition
            if (!isset($weatherImpact['by_condition'][$row['weather_condition']])) {
                $weatherImpact['by_condition'][$row['weather_condition']] = [
                    'games' => 0,
                    'avg_runs' => 0,
                    'avg_run_diff' => 0
                ];
            }
            $weatherImpact['by_condition'][$row['weather_condition']]['games'] += $row['games'];
            $weatherImpact['by_condition'][$row['weather_condition']]['avg_runs'] = round($row['avg_runs'], 2);
            $weatherImpact['by_condition'][$row['weather_condition']]['avg_run_diff'] = round($row['avg_run_diff'], 2);

            // Group by temperature range
            $tempRange = floor($row['temperature'] / 10) * 10;
            if (!isset($weatherImpact['by_temperature'][$tempRange])) {
                $weatherImpact['by_temperature'][$tempRange] = [
                    'games' => 0,
                    'avg_runs' => 0,
                    'runs' => []
                ];
            }
            $weatherImpact['by_temperature'][$tempRange]['games'] += $row['games'];
            $weatherImpact['by_temperature'][$tempRange]['runs'][] = $row['total_runs'];

            // Group by wind speed range
            $windRange = floor($row['wind_speed'] / 5) * 5;
            if (!isset($weatherImpact['by_wind_speed'][$windRange])) {
                $weatherImpact['by_wind_speed'][$windRange] = [
                    'games' => 0,
                    'avg_runs' => 0,
                    'runs' => []
                ];
            }
            $weatherImpact['by_wind_speed'][$windRange]['games'] += $row['games'];
            $weatherImpact['by_wind_speed'][$windRange]['runs'][] = $row['total_runs'];

            $this->incrementStat('records_analyzed');
        }

        // Calculate averages for temperature and wind speed ranges
        foreach ($weatherImpact['by_temperature'] as $range => $data) {
            $weatherImpact['by_temperature'][$range]['avg_runs'] = 
                round($this->calculateMean($data['runs']), 2);
        }

        foreach ($weatherImpact['by_wind_speed'] as $range => $data) {
            $weatherImpact['by_wind_speed'][$range]['avg_runs'] = 
                round($this->calculateMean($data['runs']), 2);
        }

        $this->results['weather_impact'] = $weatherImpact;
        $this->incrementStat('metrics_computed');
    }

    private function generateGameTrends(): void
    {
        $sql = "SELECT 
                    DATE_TRUNC('week', game_date) as week,
                    COUNT(*) as games,
                    AVG(home_score + away_score) as avg_runs,
                    AVG(ABS(home_score - away_score)) as avg_run_diff,
                    SUM(CASE WHEN innings > 9 THEN 1 ELSE 0 END) as extra_inning_games,
                    AVG(CASE WHEN home_score > away_score THEN 1 ELSE 0 END) as home_win_pct
                FROM games
                WHERE season = :season
                AND status = 'completed'
                GROUP BY DATE_TRUNC('week', game_date)
                ORDER BY week";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $trends = [
            'weekly' => [],
            'moving_averages' => []
        ];

        $runsByWeek = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $week = $row['week'];
            $trends['weekly'][$week] = [
                'games' => $row['games'],
                'avg_runs' => round($row['avg_runs'], 2),
                'avg_run_diff' => round($row['avg_run_diff'], 2),
                'extra_inning_pct' => round($row['extra_inning_games'] / $row['games'], 3),
                'home_win_pct' => round($row['home_win_pct'], 3)
            ];
            $runsByWeek[] = $row['avg_runs'];
            $this->incrementStat('records_analyzed');
        }

        // Calculate moving averages
        $trends['moving_averages'] = [
            'runs' => $this->calculateMovingAverage($runsByWeek, 4), // 4-week moving average
        ];

        $this->results['game_trends'] = $trends;
        $this->incrementStat('metrics_computed');
    }

    private function calculateMonthlyTrends(array $monthlyData): array
    {
        $trends = [];
        foreach ($monthlyData as $month => $data) {
            $trends[$month] = [
                'games' => $data['games'],
                'avg_runs' => round($this->calculateMean($data['runs']), 2),
                'std_dev' => round($this->calculateStandardDeviation($data['runs']), 2)
            ];
        }
        return $trends;
    }

    private function calculateWeatherImpact(array $weatherData): array
    {
        $impact = [];
        foreach ($weatherData as $condition => $data) {
            $impact[$condition] = [
                'games' => $data['games'],
                'avg_runs' => round($this->calculateMean($data['runs']), 2),
                'std_dev' => round($this->calculateStandardDeviation($data['runs']), 2)
            ];
        }
        return $impact;
    }

    private function calculateInningDistribution(array $inningData): array
    {
        $distribution = [];
        foreach ($inningData as $inning => $data) {
            if ($data['games'] > 0) {
                $distribution[$inning] = [
                    'avg_runs' => round($data['runs'] / $data['games'], 2),
                    'games' => $data['games']
                ];
            }
        }
        return $distribution;
    }

    private function calculateExtraInningPatterns(array $extraInningData): array
    {
        $patterns = [];
        foreach ($extraInningData as $inning => $data) {
            if ($data['games'] > 0) {
                $patterns[$inning] = [
                    'avg_runs' => round($data['runs'] / $data['games'], 2),
                    'games' => $data['games']
                ];
            }
        }
        return $patterns;
    }
} 