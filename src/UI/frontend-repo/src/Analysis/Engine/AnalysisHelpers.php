<?php

namespace BaseballAnalytics\Analysis\Engine;

trait AnalysisHelpers
{
    private function identifyStatisticalLeaders(array $performance): array
    {
        $leaders = [];
        foreach ($performance as $teamId => $stats) {
            foreach ($stats as $metric => $value) {
                if (!isset($leaders[$metric])) {
                    $leaders[$metric] = [];
                }
                $leaders[$metric][] = [
                    'team_id' => $teamId,
                    'value' => $value
                ];
            }
        }

        // Sort and get top performers for each metric
        foreach ($leaders as $metric => $values) {
            usort($values, fn($a, $b) => $b['value'] <=> $a['value']);
            $leaders[$metric] = array_slice($values, 0, 5);
        }

        return $leaders;
    }

    private function identifyPerformanceTrends(array $performance): array
    {
        $trends = [];
        $metrics = ['win_pct', 'runs_per_game', 'runs_allowed_per_game'];

        foreach ($metrics as $metric) {
            $values = array_column($performance, $metric);
            $trends[$metric] = [
                'mean' => $this->calculateMean($values),
                'std_dev' => $this->calculateStandardDeviation($values),
                'trend' => $this->calculateTrend($values)
            ];
        }

        return $trends;
    }

    private function calculateHomeFieldAdvantage(array $performance): array
    {
        $advantage = [];
        foreach ($performance as $teamId => $stats) {
            $advantage[$teamId] = [
                'home_win_pct' => $stats['home_win_pct'],
                'away_win_pct' => $stats['away_win_pct'],
                'differential' => $stats['home_win_pct'] - $stats['away_win_pct']
            ];
        }

        return $advantage;
    }

    private function analyzeRoadPerformance(array $performance): array
    {
        $roadStats = [];
        foreach ($performance as $teamId => $stats) {
            $roadStats[$teamId] = [
                'away_win_pct' => $stats['away_win_pct'],
                'away_runs_per_game' => $stats['away_runs_per_game'],
                'away_runs_allowed_per_game' => $stats['away_runs_allowed_per_game']
            ];
        }

        return $roadStats;
    }

    private function analyzePerformanceSplits(array $performance): array
    {
        $splits = [];
        foreach ($performance as $teamId => $stats) {
            $splits[$teamId] = [
                'home_away_differential' => $stats['home_win_pct'] - $stats['away_win_pct'],
                'run_differential_home' => $stats['home_runs_per_game'] - $stats['home_runs_allowed_per_game'],
                'run_differential_away' => $stats['away_runs_per_game'] - $stats['away_runs_allowed_per_game']
            ];
        }

        return $splits;
    }

    private function analyzeCloseGamePerformance(array $performance): array
    {
        $closeGames = [];
        foreach ($performance as $teamId => $stats) {
            $closeGames[$teamId] = [
                'one_run_win_pct' => $stats['one_run_win_pct'],
                'extra_innings_win_pct' => $stats['extra_innings_win_pct']
            ];
        }

        return $closeGames;
    }

    private function analyzeHighLeveragePerformance(array $performance): array
    {
        $highLeverage = [];
        foreach ($performance as $teamId => $stats) {
            $highLeverage[$teamId] = [
                'late_close_win_pct' => $stats['late_close_win_pct'] ?? 0,
                'comeback_wins' => $stats['comeback_wins'] ?? 0,
                'blown_leads' => $stats['blown_leads'] ?? 0
            ];
        }

        return $highLeverage;
    }

    private function analyzeMatchupPerformance(array $performance): array
    {
        $matchups = [];
        foreach ($performance as $teamId => $stats) {
            $matchups[$teamId] = [
                'vs_winning_teams' => $stats['vs_winning_teams_pct'] ?? 0,
                'vs_division' => $stats['vs_division_pct'] ?? 0,
                'interleague' => $stats['interleague_pct'] ?? 0
            ];
        }

        return $matchups;
    }

    private function identifyBattingLeaders(array $batting): array
    {
        $categories = ['avg', 'obp', 'slg', 'ops', 'hr', 'rbi'];
        $leaders = [];

        foreach ($categories as $category) {
            $sorted = $batting;
            usort($sorted, fn($a, $b) => $b[$category] <=> $a[$category]);
            $leaders[$category] = array_slice($sorted, 0, 10);
        }

        return $leaders;
    }

    private function analyzeBattingStatistics(array $batting): array
    {
        $metrics = ['avg', 'obp', 'slg', 'ops', 'bb_rate', 'k_rate'];
        $stats = [];

        foreach ($metrics as $metric) {
            $values = array_column($batting, $metric);
            $stats[$metric] = [
                'mean' => $this->calculateMean($values),
                'median' => $this->calculateMedian($values),
                'std_dev' => $this->calculateStandardDeviation($values)
            ];
        }

        return $stats;
    }

    private function identifyBattingIndicators(array $batting): array
    {
        $indicators = [];
        foreach ($batting as $playerId => $stats) {
            $indicators[$playerId] = [
                'babip' => $this->calculateBABIP($stats),
                'iso' => $stats['slg'] - $stats['avg'],
                'bb_k_ratio' => $stats['bb_rate'] / ($stats['k_rate'] ?: 1)
            ];
        }

        return $indicators;
    }

    private function identifyPitchingLeaders(array $pitching): array
    {
        $categories = ['era', 'whip', 'k_9', 'bb_9', 'wins', 'saves'];
        $leaders = [];

        foreach ($categories as $category) {
            $sorted = $pitching;
            usort($sorted, fn($a, $b) => $category === 'era' || $category === 'whip' || $category === 'bb_9' 
                ? $a[$category] <=> $b[$category] 
                : $b[$category] <=> $a[$category]);
            $leaders[$category] = array_slice($sorted, 0, 10);
        }

        return $leaders;
    }

    private function analyzePitchingStatistics(array $pitching): array
    {
        $metrics = ['era', 'whip', 'k_9', 'bb_9', 'hr_9', 'fip'];
        $stats = [];

        foreach ($metrics as $metric) {
            $values = array_column($pitching, $metric);
            $stats[$metric] = [
                'mean' => $this->calculateMean($values),
                'median' => $this->calculateMedian($values),
                'std_dev' => $this->calculateStandardDeviation($values)
            ];
        }

        return $stats;
    }

    private function identifyPitchingIndicators(array $pitching): array
    {
        $indicators = [];
        foreach ($pitching as $playerId => $stats) {
            $indicators[$playerId] = [
                'fip_era_diff' => $stats['fip'] - $stats['era'],
                'k_bb_ratio' => $stats['k_9'] / ($stats['bb_9'] ?: 1),
                'hr_fb_ratio' => $stats['hr_9'] / ($stats['fb_pct'] ?: 1)
            ];
        }

        return $indicators;
    }

    private function analyzeRunDistribution(array $scoring): array
    {
        $distribution = [];
        $runs = array_column($scoring, 'total_runs');
        
        $distribution['frequency'] = array_count_values($runs);
        $distribution['stats'] = [
            'mean' => $this->calculateMean($runs),
            'median' => $this->calculateMedian($runs),
            'std_dev' => $this->calculateStandardDeviation($runs)
        ];

        return $distribution;
    }

    private function analyzeInningPatterns(array $scoring): array
    {
        return [
            'scoring_by_inning' => $scoring['scoring_by_inning'] ?? [],
            'high_scoring_innings' => $this->identifyHighScoringInnings($scoring),
            'late_inning_trends' => $this->analyzeLateInningScoring($scoring)
        ];
    }

    private function identifyScoringTrends(array $scoring): array
    {
        return [
            'monthly_trends' => $scoring['monthly_trends'] ?? [],
            'weather_impact' => $scoring['weather_impact'] ?? [],
            'park_factors' => $scoring['park_factors'] ?? []
        ];
    }

    private function analyzeOutcomeDistribution(array $outcomes): array
    {
        return [
            'win_loss' => [
                'home_wins' => $outcomes['home_wins'] ?? 0,
                'away_wins' => $outcomes['away_wins'] ?? 0
            ],
            'margin_of_victory' => $this->analyzeVictoryMargins($outcomes),
            'extra_innings' => $outcomes['extra_innings'] ?? []
        ];
    }

    private function analyzeSituationalOutcomes(array $outcomes): array
    {
        return [
            'close_games' => $outcomes['one_run_games'] ?? [],
            'blowouts' => $outcomes['blowouts'] ?? [],
            'comebacks' => $outcomes['comebacks'] ?? []
        ];
    }

    private function identifyOutcomeTrends(array $outcomes): array
    {
        return [
            'seasonal_trends' => $outcomes['seasonal_trends'] ?? [],
            'home_away_splits' => $outcomes['home_away_splits'] ?? [],
            'matchup_trends' => $outcomes['matchup_trends'] ?? []
        ];
    }

    private function processBattingProjections(array $predictions): array
    {
        return [
            'season_projections' => $predictions['season'] ?? [],
            'improvement_candidates' => $predictions['improvement'] ?? [],
            'regression_candidates' => $predictions['regression'] ?? []
        ];
    }

    private function processPitchingProjections(array $predictions): array
    {
        return [
            'season_projections' => $predictions['season'] ?? [],
            'improvement_candidates' => $predictions['improvement'] ?? [],
            'regression_candidates' => $predictions['regression'] ?? []
        ];
    }

    private function processPerformanceTrends(array $predictions): array
    {
        return [
            'trending_up' => $predictions['trending_up'] ?? [],
            'trending_down' => $predictions['trending_down'] ?? [],
            'stable_performers' => $predictions['stable'] ?? []
        ];
    }

    private function processWinProbability(array $predictions): array
    {
        return [
            'game_predictions' => $predictions['games'] ?? [],
            'series_predictions' => $predictions['series'] ?? [],
            'playoff_odds' => $predictions['playoffs'] ?? []
        ];
    }

    private function processRunExpectancy(array $predictions): array
    {
        return [
            'game_totals' => $predictions['totals'] ?? [],
            'situational' => $predictions['situations'] ?? [],
            'matchup_based' => $predictions['matchups'] ?? []
        ];
    }

    private function processOutcomeProjections(array $predictions): array
    {
        return [
            'win_loss' => $predictions['records'] ?? [],
            'run_differential' => $predictions['differential'] ?? [],
            'playoff_scenarios' => $predictions['scenarios'] ?? []
        ];
    }

    private function calculateMean(array $values): float
    {
        return array_sum($values) / count($values);
    }

    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }

        return $values[$middle];
    }

    private function calculateStandardDeviation(array $values): float
    {
        $mean = $this->calculateMean($values);
        $variance = array_reduce($values, function($carry, $item) use ($mean) {
            return $carry + pow($item - $mean, 2);
        }, 0) / count($values);

        return sqrt($variance);
    }

    private function calculateTrend(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;

        $x = range(1, $n);
        $x_mean = ($n + 1) / 2;
        $y_mean = array_sum($values) / $n;

        $numerator = 0;
        $denominator = 0;

        for ($i = 0; $i < $n; $i++) {
            $x_diff = $x[$i] - $x_mean;
            $y_diff = $values[$i] - $y_mean;
            $numerator += $x_diff * $y_diff;
            $denominator += $x_diff * $x_diff;
        }

        return $denominator ? $numerator / $denominator : 0;
    }

    private function calculateBABIP(array $stats): float
    {
        $hits = $stats['hits'] - $stats['hr'];
        $atBats = $stats['at_bats'] - $stats['hr'] - $stats['k'] + $stats['sf'];
        
        return $atBats ? round($hits / $atBats, 3) : 0;
    }

    private function identifyHighScoringInnings(array $scoring): array
    {
        $highScoring = [];
        foreach ($scoring['scoring_by_inning'] ?? [] as $inning => $data) {
            if ($data['runs'] / $data['games'] > 0.75) { // 75% of average
                $highScoring[$inning] = $data;
            }
        }
        return $highScoring;
    }

    private function analyzeLateInningScoring(array $scoring): array
    {
        $lateInnings = array_filter(
            $scoring['scoring_by_inning'] ?? [],
            fn($k) => $k >= 7,
            ARRAY_FILTER_USE_KEY
        );

        return [
            'scoring_rate' => array_map(
                fn($data) => $data['runs'] / $data['games'],
                $lateInnings
            ),
            'frequency' => array_map(
                fn($data) => $data['games'],
                $lateInnings
            )
        ];
    }

    private function analyzeVictoryMargins(array $outcomes): array
    {
        $margins = array_map(
            fn($game) => abs($game['home_score'] - $game['away_score']),
            $outcomes['games'] ?? []
        );

        return [
            'distribution' => array_count_values($margins),
            'average' => $this->calculateMean($margins),
            'median' => $this->calculateMedian($margins)
        ];
    }
} 