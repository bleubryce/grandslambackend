<?php

namespace BaseballAnalytics\Analysis\Engine;

use BaseballAnalytics\Database\Connection;

class TeamPerformanceAnalyzer extends BaseAnalyzer
{
    private const REQUIRED_CONFIG = [
        'min_games_played',
        'performance_window',
        'run_differential_weight'
    ];

    private array $teamMetrics;

    public function __construct(Connection $db, array $config = [])
    {
        parent::__construct($db, $config);
        $this->initializeMetrics();
    }

    private function initializeMetrics(): void
    {
        $this->teamMetrics = [
            'win_pct' => 'Win Percentage',
            'runs_per_game' => 'Runs Scored per Game',
            'runs_allowed_per_game' => 'Runs Allowed per Game',
            'run_differential' => 'Run Differential',
            'home_win_pct' => 'Home Win Percentage',
            'away_win_pct' => 'Away Win Percentage',
            'one_run_win_pct' => 'One-Run Game Win Percentage',
            'extra_innings_win_pct' => 'Extra Innings Win Percentage',
            'pythagorean_win_pct' => 'Pythagorean Win Percentage'
        ];
    }

    public function analyze(): bool
    {
        if (!$this->validateConfig(self::REQUIRED_CONFIG)) {
            return false;
        }

        $this->beginAnalysis();

        try {
            $this->analyzeOverallPerformance();
            $this->analyzeHomeAwayPerformance();
            $this->analyzeRunDifferential();
            $this->analyzeSituationalPerformance();
            $this->analyzeStrengthOfSchedule();
            $this->generateProjections();

            $this->endAnalysis();
            return true;
        } catch (\Exception $e) {
            $this->logError("Analysis failed", $e);
            return false;
        }
    }

    private function analyzeOverallPerformance(): void
    {
        $sql = "SELECT t.team_id, t.name,
                       COUNT(*) as games_played,
                       SUM(CASE WHEN g.home_team_id = t.team_id AND g.home_score > g.away_score THEN 1
                                WHEN g.away_team_id = t.team_id AND g.away_score > g.home_score THEN 1
                                ELSE 0 END) as wins,
                       SUM(CASE WHEN g.home_team_id = t.team_id THEN g.home_score
                                ELSE g.away_score END) as runs_scored,
                       SUM(CASE WHEN g.home_team_id = t.team_id THEN g.away_score
                                ELSE g.home_score END) as runs_allowed
                FROM teams t
                JOIN games g ON t.team_id IN (g.home_team_id, g.away_team_id)
                WHERE g.season = :season
                GROUP BY t.team_id, t.name
                HAVING COUNT(*) >= :min_games";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'season' => date('Y'),
            'min_games' => $this->config['min_games_played']
        ]);

        $teamStats = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $stats = $this->calculateTeamMetrics($row);
            $teamStats[$row['team_id']] = array_merge($row, $stats);
            $this->incrementStat('records_analyzed');
        }

        $this->results['overall_performance'] = $this->analyzeTeamMetrics($teamStats);
        $this->incrementStat('metrics_computed');
    }

    private function calculateTeamMetrics(array $stats): array
    {
        $metrics = [];
        
        // Win percentage
        $metrics['win_pct'] = $stats['games_played'] > 0 ? 
            round($stats['wins'] / $stats['games_played'], 3) : 0;
        
        // Runs per game
        $metrics['runs_per_game'] = $stats['games_played'] > 0 ? 
            round($stats['runs_scored'] / $stats['games_played'], 2) : 0;
        
        // Runs allowed per game
        $metrics['runs_allowed_per_game'] = $stats['games_played'] > 0 ? 
            round($stats['runs_allowed'] / $stats['games_played'], 2) : 0;
        
        // Run differential
        $metrics['run_differential'] = $stats['runs_scored'] - $stats['runs_allowed'];
        
        // Pythagorean win percentage
        $metrics['pythagorean_win_pct'] = $this->calculatePythagoreanWinPct(
            $stats['runs_scored'],
            $stats['runs_allowed']
        );
        
        return $metrics;
    }

    private function calculatePythagoreanWinPct(int $runsScored, int $runsAllowed): float
    {
        if ($runsAllowed === 0) {
            return 1.0;
        }

        $exponent = 1.83; // Bill James Pythagorean exponent
        $runsScored = pow($runsScored, $exponent);
        $runsAllowed = pow($runsAllowed, $exponent);
        
        return round($runsScored / ($runsScored + $runsAllowed), 3);
    }

    private function analyzeHomeAwayPerformance(): void
    {
        $sql = "SELECT t.team_id, t.name,
                       SUM(CASE WHEN g.home_team_id = t.team_id THEN 1 ELSE 0 END) as home_games,
                       SUM(CASE WHEN g.home_team_id = t.team_id AND g.home_score > g.away_score 
                                THEN 1 ELSE 0 END) as home_wins,
                       SUM(CASE WHEN g.away_team_id = t.team_id THEN 1 ELSE 0 END) as away_games,
                       SUM(CASE WHEN g.away_team_id = t.team_id AND g.away_score > g.home_score 
                                THEN 1 ELSE 0 END) as away_wins
                FROM teams t
                JOIN games g ON t.team_id IN (g.home_team_id, g.away_team_id)
                WHERE g.season = :season
                GROUP BY t.team_id, t.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $homeAwayStats = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $homeAwayStats[$row['team_id']] = [
                'team_name' => $row['name'],
                'home_win_pct' => $row['home_games'] > 0 ? 
                    round($row['home_wins'] / $row['home_games'], 3) : 0,
                'away_win_pct' => $row['away_games'] > 0 ? 
                    round($row['away_wins'] / $row['away_games'], 3) : 0,
                'home_road_differential' => round(
                    ($row['home_wins'] / max($row['home_games'], 1)) -
                    ($row['away_wins'] / max($row['away_games'], 1)),
                    3
                )
            ];
        }

        $this->results['home_away_performance'] = $homeAwayStats;
        $this->incrementStat('metrics_computed');
    }

    private function analyzeRunDifferential(): void
    {
        $sql = "SELECT t.team_id, t.name,
                       SUM(CASE 
                           WHEN g.home_team_id = t.team_id THEN g.home_score - g.away_score
                           ELSE g.away_score - g.home_score
                       END) as run_differential,
                       COUNT(*) as games_played
                FROM teams t
                JOIN games g ON t.team_id IN (g.home_team_id, g.away_team_id)
                WHERE g.season = :season
                GROUP BY t.team_id, t.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $runDiffStats = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $runDiffStats[$row['team_id']] = [
                'team_name' => $row['name'],
                'run_differential' => $row['run_differential'],
                'run_differential_per_game' => round(
                    $row['run_differential'] / max($row['games_played'], 1),
                    2
                )
            ];
        }

        $this->results['run_differential_analysis'] = $runDiffStats;
        $this->incrementStat('metrics_computed');
    }

    private function analyzeSituationalPerformance(): void
    {
        $sql = "SELECT t.team_id, t.name,
                       SUM(CASE WHEN ABS(g.home_score - g.away_score) = 1 THEN 1 ELSE 0 END) as one_run_games,
                       SUM(CASE 
                           WHEN ABS(g.home_score - g.away_score) = 1 
                           AND ((g.home_team_id = t.team_id AND g.home_score > g.away_score)
                                OR (g.away_team_id = t.team_id AND g.away_score > g.home_score))
                           THEN 1 ELSE 0 END) as one_run_wins,
                       SUM(CASE WHEN g.innings > 9 THEN 1 ELSE 0 END) as extra_inning_games,
                       SUM(CASE 
                           WHEN g.innings > 9 
                           AND ((g.home_team_id = t.team_id AND g.home_score > g.away_score)
                                OR (g.away_team_id = t.team_id AND g.away_score > g.home_score))
                           THEN 1 ELSE 0 END) as extra_inning_wins
                FROM teams t
                JOIN games g ON t.team_id IN (g.home_team_id, g.away_team_id)
                WHERE g.season = :season
                GROUP BY t.team_id, t.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $situationalStats = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $situationalStats[$row['team_id']] = [
                'team_name' => $row['name'],
                'one_run_win_pct' => $row['one_run_games'] > 0 ? 
                    round($row['one_run_wins'] / $row['one_run_games'], 3) : 0,
                'extra_innings_win_pct' => $row['extra_inning_games'] > 0 ? 
                    round($row['extra_inning_wins'] / $row['extra_inning_games'], 3) : 0
            ];
        }

        $this->results['situational_performance'] = $situationalStats;
        $this->incrementStat('metrics_computed');
    }

    private function analyzeStrengthOfSchedule(): void
    {
        // First, get opponent win percentages
        $sql = "WITH opponent_records AS (
                    SELECT 
                        t.team_id,
                        g2.home_team_id as opp_id,
                        COUNT(*) as games_played,
                        SUM(CASE 
                            WHEN g2.home_score > g2.away_score THEN 1
                            ELSE 0
                        END) as wins
                    FROM teams t
                    JOIN games g ON t.team_id IN (g.home_team_id, g.away_team_id)
                    JOIN games g2 ON (
                        g2.home_team_id != t.team_id 
                        AND g2.home_team_id IN (g.home_team_id, g.away_team_id)
                    )
                    WHERE g.season = :season AND g2.season = :season
                    GROUP BY t.team_id, g2.home_team_id
                )
                SELECT 
                    t.team_id,
                    t.name,
                    AVG(CASE 
                        WHEN or1.games_played > 0 
                        THEN or1.wins::float / or1.games_played 
                        ELSE 0 
                    END) as strength_of_schedule
                FROM teams t
                JOIN opponent_records or1 ON t.team_id = or1.team_id
                GROUP BY t.team_id, t.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $strengthOfSchedule = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $strengthOfSchedule[$row['team_id']] = [
                'team_name' => $row['name'],
                'sos' => round($row['strength_of_schedule'], 3)
            ];
        }

        $this->results['strength_of_schedule'] = $strengthOfSchedule;
        $this->incrementStat('metrics_computed');
    }

    private function generateProjections(): void
    {
        $sql = "SELECT t.team_id, t.name,
                       COUNT(*) as games_played,
                       SUM(CASE WHEN g.home_team_id = t.team_id AND g.home_score > g.away_score THEN 1
                                WHEN g.away_team_id = t.team_id AND g.away_score > g.home_score THEN 1
                                ELSE 0 END) as wins,
                       SUM(CASE WHEN g.home_team_id = t.team_id THEN g.home_score
                                ELSE g.away_score END) as runs_scored,
                       SUM(CASE WHEN g.home_team_id = t.team_id THEN g.away_score
                                ELSE g.home_score END) as runs_allowed
                FROM teams t
                JOIN games g ON t.team_id IN (g.home_team_id, g.away_team_id)
                WHERE g.season = :season
                GROUP BY t.team_id, t.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['season' => date('Y')]);

        $projections = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $pythag = $this->calculatePythagoreanWinPct(
                $row['runs_scored'],
                $row['runs_allowed']
            );
            
            $currentWinPct = $row['games_played'] > 0 ? 
                $row['wins'] / $row['games_played'] : 0;
            
            $projections[$row['team_id']] = [
                'team_name' => $row['name'],
                'current_win_pct' => round($currentWinPct, 3),
                'pythag_win_pct' => round($pythag, 3),
                'expected_regression' => round($pythag - $currentWinPct, 3),
                'projected_wins' => round(
                    ($currentWinPct * (162 - $row['games_played'])) + $row['wins']
                )
            ];
        }

        $this->results['projections'] = $projections;
        $this->incrementStat('metrics_computed');
    }
} 