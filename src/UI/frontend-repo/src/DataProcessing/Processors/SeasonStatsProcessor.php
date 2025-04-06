<?php

namespace BaseballAnalytics\DataProcessing\Processors;

use BaseballAnalytics\DataProcessing\BaseProcessor;
use BaseballAnalytics\Database\Connection;

class SeasonStatsProcessor extends BaseProcessor
{
    protected array $requiredFields = [
        'season',
        'team_id',
        'player_id'
    ];

    public function __construct(Connection $db, array $config = [])
    {
        parent::__construct($db, $config);
    }

    public function process(): bool
    {
        $this->beginProcessing();

        try {
            // Process team season statistics
            $this->processTeamSeasonStats();
            
            // Process player season statistics
            $this->processPlayerSeasonStats();
            
            // Calculate league averages
            $this->calculateLeagueAverages();

            $this->endProcessing();
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to process season statistics", $e);
            $this->endProcessing();
            return false;
        }
    }

    protected function processTeamSeasonStats(): void
    {
        $season = date('Y'); // Current season, can be configured
        $teams = $this->fetchActiveTeams();

        foreach ($teams as $team) {
            if (!$this->beginTransaction()) {
                continue;
            }

            try {
                // Aggregate team statistics for the season
                $seasonStats = $this->aggregateTeamSeasonStats($team['team_id'], $season);
                $this->saveTeamSeasonStats($seasonStats);

                // Calculate team rankings
                $rankings = $this->calculateTeamRankings($team['team_id'], $season);
                $this->saveTeamRankings($rankings);

                $this->commitTransaction();
                $this->incrementStat('records_transformed');
            } catch (\Exception $e) {
                $this->rollbackTransaction();
                $this->logError("Failed to process season stats for team ID: {$team['team_id']}", $e);
                continue;
            }

            $this->incrementStat('records_processed');
        }
    }

    protected function processPlayerSeasonStats(): void
    {
        $season = date('Y');
        $players = $this->fetchActivePlayers();

        foreach ($players as $player) {
            if (!$this->beginTransaction()) {
                continue;
            }

            try {
                // Aggregate batting statistics
                $battingStats = $this->aggregatePlayerBattingStats($player['player_id'], $season);
                if ($battingStats) {
                    $this->savePlayerSeasonBattingStats($battingStats);
                }

                // Aggregate pitching statistics
                $pitchingStats = $this->aggregatePlayerPitchingStats($player['player_id'], $season);
                if ($pitchingStats) {
                    $this->savePlayerSeasonPitchingStats($pitchingStats);
                }

                $this->commitTransaction();
                $this->incrementStat('records_transformed');
            } catch (\Exception $e) {
                $this->rollbackTransaction();
                $this->logError("Failed to process season stats for player ID: {$player['player_id']}", $e);
                continue;
            }

            $this->incrementStat('records_processed');
        }
    }

    protected function fetchActiveTeams(): array
    {
        $query = "SELECT team_id, name FROM teams WHERE active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function fetchActivePlayers(): array
    {
        $query = "SELECT player_id, name FROM players WHERE active = TRUE";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function aggregateTeamSeasonStats(int $teamId, int $season): array
    {
        $query = "
            SELECT 
                COUNT(*) as games_played,
                SUM(CASE WHEN home_team_id = :team_id THEN home_score ELSE away_score END) as runs_scored,
                SUM(CASE WHEN home_team_id = :team_id THEN away_score ELSE home_score END) as runs_allowed,
                COUNT(DISTINCT CASE 
                    WHEN (home_team_id = :team_id AND home_score > away_score) 
                    OR (away_team_id = :team_id AND away_score > home_score) 
                    THEN game_id 
                END) as wins,
                COUNT(DISTINCT CASE 
                    WHEN (home_team_id = :team_id AND home_score < away_score) 
                    OR (away_team_id = :team_id AND away_score < home_score) 
                    THEN game_id 
                END) as losses
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

        return array_merge(
            $stmt->fetch(\PDO::FETCH_ASSOC),
            [
                'team_id' => $teamId,
                'season' => $season,
                'processed_at' => date('Y-m-d H:i:s')
            ]
        );
    }

    protected function aggregatePlayerBattingStats(int $playerId, int $season): array
    {
        $query = "
            SELECT 
                COUNT(DISTINCT game_id) as games_played,
                SUM(at_bats) as at_bats,
                SUM(hits) as hits,
                SUM(runs) as runs,
                SUM(rbis) as rbis,
                SUM(walks) as walks,
                SUM(strikeouts) as strikeouts,
                AVG(batting_average) as batting_average,
                AVG(on_base_percentage) as on_base_percentage,
                AVG(slugging_percentage) as slugging_percentage,
                AVG(ops) as ops
            FROM player_batting_stats
            WHERE player_id = :player_id
            AND YEAR(processed_at) = :season
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'player_id' => $playerId,
            'season' => $season
        ]);

        return array_merge(
            $stmt->fetch(\PDO::FETCH_ASSOC),
            [
                'player_id' => $playerId,
                'season' => $season,
                'processed_at' => date('Y-m-d H:i:s')
            ]
        );
    }

    protected function aggregatePlayerPitchingStats(int $playerId, int $season): array
    {
        $query = "
            SELECT 
                COUNT(DISTINCT game_id) as games_played,
                SUM(innings_pitched) as innings_pitched,
                SUM(earned_runs) as earned_runs,
                SUM(strikeouts) as strikeouts,
                SUM(walks) as walks,
                SUM(hits_allowed) as hits_allowed,
                AVG(era) as era,
                AVG(whip) as whip,
                AVG(k_per_9) as k_per_9,
                AVG(bb_per_9) as bb_per_9
            FROM player_pitching_stats
            WHERE player_id = :player_id
            AND YEAR(processed_at) = :season
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'player_id' => $playerId,
            'season' => $season
        ]);

        return array_merge(
            $stmt->fetch(\PDO::FETCH_ASSOC),
            [
                'player_id' => $playerId,
                'season' => $season,
                'processed_at' => date('Y-m-d H:i:s')
            ]
        );
    }

    protected function calculateTeamRankings(int $teamId, int $season): array
    {
        // Calculate various rankings (wins, run differential, etc.)
        $query = "
            WITH team_stats AS (
                SELECT 
                    t.team_id,
                    COUNT(DISTINCT CASE 
                        WHEN (g.home_team_id = t.team_id AND g.home_score > g.away_score) 
                        OR (g.away_team_id = t.team_id AND g.away_score > g.home_score) 
                        THEN g.game_id 
                    END) as wins,
                    SUM(CASE WHEN g.home_team_id = t.team_id THEN g.home_score ELSE g.away_score END) -
                    SUM(CASE WHEN g.home_team_id = t.team_id THEN g.away_score ELSE g.home_score END) as run_diff
                FROM teams t
                JOIN games g ON t.team_id = g.home_team_id OR t.team_id = g.away_team_id
                WHERE YEAR(g.date) = :season
                AND g.status = 'completed'
                GROUP BY t.team_id
            )
            SELECT 
                RANK() OVER (ORDER BY wins DESC) as wins_rank,
                RANK() OVER (ORDER BY run_diff DESC) as run_diff_rank
            FROM team_stats
            WHERE team_id = :team_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'team_id' => $teamId,
            'season' => $season
        ]);

        return array_merge(
            $stmt->fetch(\PDO::FETCH_ASSOC),
            [
                'team_id' => $teamId,
                'season' => $season,
                'calculated_at' => date('Y-m-d H:i:s')
            ]
        );
    }

    protected function calculateLeagueAverages(): void
    {
        $season = date('Y');
        
        if (!$this->beginTransaction()) {
            return;
        }

        try {
            // Calculate league batting averages
            $battingAverages = $this->calculateLeagueBattingAverages($season);
            $this->saveLeagueBattingAverages($battingAverages);

            // Calculate league pitching averages
            $pitchingAverages = $this->calculateLeaguePitchingAverages($season);
            $this->saveLeaguePitchingAverages($pitchingAverages);

            $this->commitTransaction();
            $this->incrementStat('records_transformed');
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            $this->logError("Failed to calculate league averages for season: {$season}", $e);
        }
    }

    protected function saveTeamSeasonStats(array $stats): void
    {
        $query = "INSERT INTO team_season_stats (
            team_id, season, games_played, runs_scored, runs_allowed,
            wins, losses, processed_at
        ) VALUES (
            :team_id, :season, :games_played, :runs_scored, :runs_allowed,
            :wins, :losses, :processed_at
        ) ON DUPLICATE KEY UPDATE 
            games_played = VALUES(games_played),
            runs_scored = VALUES(runs_scored),
            runs_allowed = VALUES(runs_allowed),
            wins = VALUES(wins),
            losses = VALUES(losses),
            processed_at = VALUES(processed_at)";

        $stmt = $this->db->prepare($query);
        $stmt->execute($stats);
    }

    protected function saveTeamRankings(array $rankings): void
    {
        $query = "INSERT INTO team_rankings (
            team_id, season, wins_rank, run_diff_rank, calculated_at
        ) VALUES (
            :team_id, :season, :wins_rank, :run_diff_rank, :calculated_at
        ) ON DUPLICATE KEY UPDATE 
            wins_rank = VALUES(wins_rank),
            run_diff_rank = VALUES(run_diff_rank),
            calculated_at = VALUES(calculated_at)";

        $stmt = $this->db->prepare($query);
        $stmt->execute($rankings);
    }

    protected function savePlayerSeasonBattingStats(array $stats): void
    {
        $query = "INSERT INTO player_season_batting_stats (
            player_id, season, games_played, at_bats, hits, runs,
            rbis, walks, strikeouts, batting_average, on_base_percentage,
            slugging_percentage, ops, processed_at
        ) VALUES (
            :player_id, :season, :games_played, :at_bats, :hits, :runs,
            :rbis, :walks, :strikeouts, :batting_average, :on_base_percentage,
            :slugging_percentage, :ops, :processed_at
        ) ON DUPLICATE KEY UPDATE 
            games_played = VALUES(games_played),
            at_bats = VALUES(at_bats),
            hits = VALUES(hits),
            runs = VALUES(runs),
            rbis = VALUES(rbis),
            walks = VALUES(walks),
            strikeouts = VALUES(strikeouts),
            batting_average = VALUES(batting_average),
            on_base_percentage = VALUES(on_base_percentage),
            slugging_percentage = VALUES(slugging_percentage),
            ops = VALUES(ops),
            processed_at = VALUES(processed_at)";

        $stmt = $this->db->prepare($query);
        $stmt->execute($stats);
    }

    protected function savePlayerSeasonPitchingStats(array $stats): void
    {
        $query = "INSERT INTO player_season_pitching_stats (
            player_id, season, games_played, innings_pitched, earned_runs,
            strikeouts, walks, hits_allowed, era, whip, k_per_9,
            bb_per_9, processed_at
        ) VALUES (
            :player_id, :season, :games_played, :innings_pitched, :earned_runs,
            :strikeouts, :walks, :hits_allowed, :era, :whip, :k_per_9,
            :bb_per_9, :processed_at
        ) ON DUPLICATE KEY UPDATE 
            games_played = VALUES(games_played),
            innings_pitched = VALUES(innings_pitched),
            earned_runs = VALUES(earned_runs),
            strikeouts = VALUES(strikeouts),
            walks = VALUES(walks),
            hits_allowed = VALUES(hits_allowed),
            era = VALUES(era),
            whip = VALUES(whip),
            k_per_9 = VALUES(k_per_9),
            bb_per_9 = VALUES(bb_per_9),
            processed_at = VALUES(processed_at)";

        $stmt = $this->db->prepare($query);
        $stmt->execute($stats);
    }
} 