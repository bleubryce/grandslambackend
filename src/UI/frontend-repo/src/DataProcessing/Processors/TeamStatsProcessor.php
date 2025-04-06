<?php

namespace BaseballAnalytics\DataProcessing\Processors;

use BaseballAnalytics\DataProcessing\BaseProcessor;
use BaseballAnalytics\Database\Connection;

class TeamStatsProcessor extends BaseProcessor
{
    protected array $requiredFields = [
        'team_id',
        'season',
        'games_played',
        'wins',
        'losses'
    ];

    public function __construct(Connection $db, array $config = [])
    {
        parent::__construct($db, $config);
    }

    public function process(): bool
    {
        $this->beginProcessing();

        try {
            // Calculate team statistics from game data
            $teams = $this->fetchTeams();
            $season = date('Y'); // Current season, can be configured

            foreach ($teams as $team) {
                if (!$this->beginTransaction()) {
                    continue;
                }

                try {
                    // Calculate team stats
                    $teamStats = $this->calculateTeamStats($team['team_id'], $season);
                    
                    if ($this->validateData($teamStats, $this->requiredFields)) {
                        // Save team statistics
                        $this->saveTeamStats($teamStats);
                        
                        // Calculate and save advanced metrics
                        $advancedStats = $this->calculateAdvancedStats($team['team_id'], $season);
                        $this->saveAdvancedStats($advancedStats);

                        $this->commitTransaction();
                        $this->incrementStat('records_transformed');
                    } else {
                        $this->rollbackTransaction();
                        $this->incrementStat('records_skipped');
                    }
                } catch (\Exception $e) {
                    $this->rollbackTransaction();
                    $this->logError("Failed to process team ID: {$team['team_id']}", $e);
                    continue;
                }

                $this->incrementStat('records_processed');
            }

            $this->endProcessing();
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to process team statistics", $e);
            $this->endProcessing();
            return false;
        }
    }

    protected function fetchTeams(): array
    {
        $query = "SELECT team_id, name FROM teams WHERE active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function calculateTeamStats(int $teamId, int $season): array
    {
        $query = "
            SELECT 
                COUNT(*) as games_played,
                SUM(CASE 
                    WHEN (home_team_id = :team_id AND home_score > away_score) 
                    OR (away_team_id = :team_id AND away_score > home_score) 
                    THEN 1 ELSE 0 
                END) as wins,
                SUM(CASE 
                    WHEN (home_team_id = :team_id AND home_score < away_score) 
                    OR (away_team_id = :team_id AND away_score < home_score) 
                    THEN 1 ELSE 0 
                END) as losses,
                AVG(CASE WHEN home_team_id = :team_id THEN home_score ELSE away_score END) as avg_runs_scored,
                AVG(CASE WHEN home_team_id = :team_id THEN away_score ELSE home_score END) as avg_runs_allowed
            FROM games 
            WHERE (home_team_id = :team_id OR away_team_id = :team_id)
            AND YEAR(date) = :season
            AND status = 'completed'
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'team_id' => $teamId,
            'season' => $season
        ]);

        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return [
            'team_id' => $teamId,
            'season' => $season,
            'games_played' => (int)$stats['games_played'],
            'wins' => (int)$stats['wins'],
            'losses' => (int)$stats['losses'],
            'avg_runs_scored' => round($stats['avg_runs_scored'], 2),
            'avg_runs_allowed' => round($stats['avg_runs_allowed'], 2),
            'win_percentage' => $stats['games_played'] > 0 
                ? round($stats['wins'] / $stats['games_played'], 3) 
                : 0.000
        ];
    }

    protected function calculateAdvancedStats(int $teamId, int $season): array
    {
        // Calculate advanced metrics like run differential, pythagorean expectation, etc.
        $query = "
            SELECT 
                SUM(CASE WHEN home_team_id = :team_id THEN home_score ELSE away_score END) as total_runs_scored,
                SUM(CASE WHEN home_team_id = :team_id THEN away_score ELSE home_score END) as total_runs_allowed,
                COUNT(DISTINCT CASE 
                    WHEN (home_team_id = :team_id AND home_score > away_score + 3) 
                    OR (away_team_id = :team_id AND away_score > home_score + 3) 
                    THEN game_id 
                END) as blowout_wins,
                COUNT(DISTINCT CASE 
                    WHEN (home_team_id = :team_id AND home_score + 3 < away_score) 
                    OR (away_team_id = :team_id AND away_score + 3 < home_score) 
                    THEN game_id 
                END) as blowout_losses
            FROM games 
            WHERE (home_team_id = :team_id OR away_team_id = :team_id)
            AND YEAR(date) = :season
            AND status = 'completed'
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'team_id' => $teamId,
            'season' => $season
        ]);

        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Calculate Pythagorean expectation (Bill James formula)
        $pythag = $this->calculatePythagoreanExpectation(
            $stats['total_runs_scored'],
            $stats['total_runs_allowed']
        );

        return [
            'team_id' => $teamId,
            'season' => $season,
            'run_differential' => $stats['total_runs_scored'] - $stats['total_runs_allowed'],
            'pythagorean_expectation' => $pythag,
            'blowout_wins' => (int)$stats['blowout_wins'],
            'blowout_losses' => (int)$stats['blowout_losses'],
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }

    protected function calculatePythagoreanExpectation(float $runsScored, float $runsAllowed): float
    {
        if ($runsAllowed == 0) return 1.000;
        if ($runsScored == 0) return 0.000;

        $exponent = 1.83; // Bill James' pythagorean exponent
        $runsScored = pow($runsScored, $exponent);
        $runsAllowed = pow($runsAllowed, $exponent);
        
        return round($runsScored / ($runsScored + $runsAllowed), 3);
    }

    protected function saveTeamStats(array $stats): void
    {
        $query = "INSERT INTO team_stats (
            team_id, season, games_played, wins, losses,
            avg_runs_scored, avg_runs_allowed, win_percentage
        ) VALUES (
            :team_id, :season, :games_played, :wins, :losses,
            :avg_runs_scored, :avg_runs_allowed, :win_percentage
        ) ON DUPLICATE KEY UPDATE 
            games_played = VALUES(games_played),
            wins = VALUES(wins),
            losses = VALUES(losses),
            avg_runs_scored = VALUES(avg_runs_scored),
            avg_runs_allowed = VALUES(avg_runs_allowed),
            win_percentage = VALUES(win_percentage)";

        $stmt = $this->db->prepare($query);
        $stmt->execute($stats);
    }

    protected function saveAdvancedStats(array $stats): void
    {
        $query = "INSERT INTO team_advanced_stats (
            team_id, season, run_differential, pythagorean_expectation,
            blowout_wins, blowout_losses, calculated_at
        ) VALUES (
            :team_id, :season, :run_differential, :pythagorean_expectation,
            :blowout_wins, :blowout_losses, :calculated_at
        ) ON DUPLICATE KEY UPDATE 
            run_differential = VALUES(run_differential),
            pythagorean_expectation = VALUES(pythagorean_expectation),
            blowout_wins = VALUES(blowout_wins),
            blowout_losses = VALUES(blowout_losses),
            calculated_at = VALUES(calculated_at)";

        $stmt = $this->db->prepare($query);
        $stmt->execute($stats);
    }
} 