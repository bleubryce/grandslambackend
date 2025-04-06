<?php
namespace BaseballAnalytics\Api\Controllers;

use BaseballAnalytics\Api\ApiResponse;
use BaseballAnalytics\Api\ApiMiddleware;
use BaseballAnalytics\Auth\SessionManager;
use BaseballAnalytics\Database\Connection;
use PDO;

class TeamController {
    private $db;
    private $sessionManager;
    private $middleware;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
        $this->sessionManager = SessionManager::getInstance();
        $this->middleware = new ApiMiddleware();
    }

    public function listTeams(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            SELECT 
                t.id,
                t.name,
                t.city,
                t.division,
                t.league,
                t.founded_year,
                t.stadium_name,
                t.stadium_capacity,
                (SELECT COUNT(*) FROM players p WHERE p.team_id = t.id AND p.active = true) as active_players
            FROM teams t
            ORDER BY t.league, t.division, t.name
        ");

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch teams')->send();
            return;
        }

        $teams = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['teams' => $teams])->send();
    }

    public function getTeam(int $teamId): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            SELECT 
                t.*,
                (SELECT COUNT(*) FROM players p WHERE p.team_id = t.id AND p.active = true) as active_players,
                (
                    SELECT JSON_OBJECT(
                        'wins', COUNT(CASE WHEN g.winner_id = t.id THEN 1 END),
                        'losses', COUNT(CASE WHEN g.loser_id = t.id THEN 1 END)
                    )
                    FROM games g 
                    WHERE (g.home_team_id = t.id OR g.away_team_id = t.id)
                    AND g.season = YEAR(CURRENT_DATE)
                ) as season_record
            FROM teams t
            WHERE t.id = :team_id
        ");

        $query->bindParam(':team_id', $teamId, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch team details')->send();
            return;
        }

        $team = $query->fetch(PDO::FETCH_ASSOC);
        if (!$team) {
            ApiResponse::notFound('Team not found')->send();
            return;
        }

        ApiResponse::success(['team' => $team])->send();
    }

    public function getTeamRoster(int $teamId): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            SELECT 
                p.id,
                p.first_name,
                p.last_name,
                p.jersey_number,
                p.position,
                p.bats,
                p.throws,
                p.height,
                p.weight,
                p.birth_date,
                p.active,
                (
                    SELECT JSON_OBJECT(
                        'avg', ROUND(AVG(CASE WHEN bs.at_bats > 0 THEN bs.hits::float / bs.at_bats END), 3),
                        'hr', SUM(bs.home_runs),
                        'rbi', SUM(bs.runs_batted_in)
                    )
                    FROM batting_stats bs
                    WHERE bs.player_id = p.id
                    AND bs.season = YEAR(CURRENT_DATE)
                ) as batting_stats,
                (
                    SELECT JSON_OBJECT(
                        'era', ROUND(AVG(CASE WHEN ps.innings_pitched > 0 THEN (ps.earned_runs * 9.0) / ps.innings_pitched END), 2),
                        'wins', SUM(ps.wins),
                        'saves', SUM(ps.saves)
                    )
                    FROM pitching_stats ps
                    WHERE ps.player_id = p.id
                    AND ps.season = YEAR(CURRENT_DATE)
                ) as pitching_stats
            FROM players p
            WHERE p.team_id = :team_id
            ORDER BY p.active DESC, p.position, p.jersey_number
        ");

        $query->bindParam(':team_id', $teamId, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch team roster')->send();
            return;
        }

        $roster = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['roster' => $roster])->send();
    }

    public function getTeamSchedule(int $teamId): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            SELECT 
                g.id,
                g.game_date,
                g.home_team_id,
                ht.name as home_team_name,
                g.away_team_id,
                at.name as away_team_name,
                g.status,
                g.winner_id,
                g.loser_id,
                g.home_team_runs,
                g.away_team_runs,
                g.stadium,
                g.attendance
            FROM games g
            JOIN teams ht ON g.home_team_id = ht.id
            JOIN teams at ON g.away_team_id = at.id
            WHERE (g.home_team_id = :team_id OR g.away_team_id = :team_id)
            AND g.season = YEAR(CURRENT_DATE)
            ORDER BY g.game_date
        ");

        $query->bindParam(':team_id', $teamId, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch team schedule')->send();
            return;
        }

        $schedule = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['schedule' => $schedule])->send();
    }

    public function getTeamStats(int $teamId): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            SELECT 
                ts.*,
                t.name as team_name,
                t.league,
                t.division
            FROM team_stats ts
            JOIN teams t ON ts.team_id = t.id
            WHERE ts.team_id = :team_id
            AND ts.season = YEAR(CURRENT_DATE)
        ");

        $query->bindParam(':team_id', $teamId, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch team statistics')->send();
            return;
        }

        $stats = $query->fetch(PDO::FETCH_ASSOC);
        if (!$stats) {
            ApiResponse::notFound('Team statistics not found')->send();
            return;
        }

        ApiResponse::success(['stats' => $stats])->send();
    }

    public function getTeamRankings(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            WITH team_records AS (
                SELECT 
                    t.id,
                    t.name,
                    t.league,
                    t.division,
                    COUNT(CASE WHEN g.winner_id = t.id THEN 1 END) as wins,
                    COUNT(CASE WHEN g.loser_id = t.id THEN 1 END) as losses,
                    ROUND(
                        COUNT(CASE WHEN g.winner_id = t.id THEN 1 END)::float / 
                        NULLIF(COUNT(CASE WHEN g.winner_id = t.id OR g.loser_id = t.id THEN 1 END), 0),
                        3
                    ) as win_pct
                FROM teams t
                LEFT JOIN games g ON (g.home_team_id = t.id OR g.away_team_id = t.id)
                    AND g.season = YEAR(CURRENT_DATE)
                GROUP BY t.id, t.name, t.league, t.division
            )
            SELECT 
                *,
                RANK() OVER (PARTITION BY league, division ORDER BY win_pct DESC) as division_rank,
                RANK() OVER (PARTITION BY league ORDER BY win_pct DESC) as league_rank
            FROM team_records
            ORDER BY league, division, win_pct DESC
        ");

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch team rankings')->send();
            return;
        }

        $rankings = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['rankings' => $rankings])->send();
    }
} 