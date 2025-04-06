<?php
namespace BaseballAnalytics\Api\Controllers;

use BaseballAnalytics\Api\ApiResponse;
use BaseballAnalytics\Api\ApiMiddleware;
use BaseballAnalytics\Auth\SessionManager;
use BaseballAnalytics\Database\Connection;
use PDO;

class GameController {
    private $db;
    private $sessionManager;
    private $middleware;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
        $this->sessionManager = SessionManager::getInstance();
        $this->middleware = new ApiMiddleware();
    }

    public function listGames(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        if (!$this->middleware->validateContentType()) {
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data) {
            return;
        }

        $conditions = [];
        $params = [];

        if (isset($data['date'])) {
            $conditions[] = "g.game_date = :date";
            $params[':date'] = $data['date'];
        }

        if (isset($data['team_id'])) {
            $conditions[] = "(g.home_team_id = :team_id OR g.away_team_id = :team_id)";
            $params[':team_id'] = $data['team_id'];
        }

        if (isset($data['status'])) {
            $conditions[] = "g.status = :status";
            $params[':status'] = $data['status'];
        }

        if (isset($data['season'])) {
            $conditions[] = "g.season = :season";
            $params[':season'] = $data['season'];
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $query = $this->db->prepare("
            SELECT 
                g.id,
                g.game_date,
                g.season,
                g.status,
                g.home_team_id,
                ht.name as home_team_name,
                g.away_team_id,
                at.name as away_team_name,
                g.home_team_runs,
                g.away_team_runs,
                g.winner_id,
                g.loser_id,
                g.stadium,
                g.attendance,
                g.weather_conditions,
                g.game_duration
            FROM games g
            JOIN teams ht ON g.home_team_id = ht.id
            JOIN teams at ON g.away_team_id = at.id
            {$whereClause}
            ORDER BY g.game_date DESC, g.id
        ");

        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch games')->send();
            return;
        }

        $games = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['games' => $games])->send();
    }

    public function getGame(int $gameId): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            SELECT 
                g.*,
                ht.name as home_team_name,
                at.name as away_team_name,
                (
                    SELECT JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'inning', i.inning_number,
                            'half', i.half,
                            'home_team_runs', i.home_team_runs,
                            'away_team_runs', i.away_team_runs,
                            'hits', i.hits,
                            'errors', i.errors,
                            'left_on_base', i.left_on_base
                        )
                        ORDER BY i.inning_number, i.half
                    )
                    FROM innings i
                    WHERE i.game_id = g.id
                ) as innings,
                (
                    SELECT JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'player_id', p.id,
                            'first_name', p.first_name,
                            'last_name', p.last_name,
                            'team_id', p.team_id,
                            'position', bs.position_played,
                            'batting_order', bs.batting_order,
                            'at_bats', bs.at_bats,
                            'hits', bs.hits,
                            'runs', bs.runs,
                            'runs_batted_in', bs.runs_batted_in,
                            'doubles', bs.doubles,
                            'triples', bs.triples,
                            'home_runs', bs.home_runs,
                            'walks', bs.walks,
                            'strikeouts', bs.strikeouts
                        )
                    )
                    FROM batting_stats bs
                    JOIN players p ON bs.player_id = p.id
                    WHERE bs.game_id = g.id
                ) as batting_stats,
                (
                    SELECT JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'player_id', p.id,
                            'first_name', p.first_name,
                            'last_name', p.last_name,
                            'team_id', p.team_id,
                            'game_started', ps.game_started,
                            'innings_pitched', ps.innings_pitched,
                            'hits_allowed', ps.hits_allowed,
                            'runs_allowed', ps.runs_allowed,
                            'earned_runs', ps.earned_runs,
                            'walks', ps.walks,
                            'strikeouts', ps.strikeouts,
                            'home_runs_allowed', ps.home_runs_allowed,
                            'pitches_thrown', ps.pitches_thrown
                        )
                    )
                    FROM pitching_stats ps
                    JOIN players p ON ps.player_id = p.id
                    WHERE ps.game_id = g.id
                ) as pitching_stats,
                (
                    SELECT JSON_AGG(
                        JSON_BUILD_OBJECT(
                            'player_id', p.id,
                            'first_name', p.first_name,
                            'last_name', p.last_name,
                            'team_id', p.team_id,
                            'position_played', fs.position_played,
                            'innings_played', fs.innings_played,
                            'putouts', fs.putouts,
                            'assists', fs.assists,
                            'errors', fs.errors,
                            'double_plays', fs.double_plays
                        )
                    )
                    FROM fielding_stats fs
                    JOIN players p ON fs.player_id = p.id
                    WHERE fs.game_id = g.id
                ) as fielding_stats
            FROM games g
            JOIN teams ht ON g.home_team_id = ht.id
            JOIN teams at ON g.away_team_id = at.id
            WHERE g.id = :game_id
        ");

        $query->bindParam(':game_id', $gameId, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch game details')->send();
            return;
        }

        $game = $query->fetch(PDO::FETCH_ASSOC);
        if (!$game) {
            ApiResponse::notFound('Game not found')->send();
            return;
        }

        ApiResponse::success(['game' => $game])->send();
    }

    public function getGamePlays(int $gameId): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            SELECT 
                p.*,
                bp.first_name as batter_first_name,
                bp.last_name as batter_last_name,
                pp.first_name as pitcher_first_name,
                pp.last_name as pitcher_last_name
            FROM plays p
            JOIN players bp ON p.batter_id = bp.id
            JOIN players pp ON p.pitcher_id = pp.id
            WHERE p.game_id = :game_id
            ORDER BY p.inning_number, p.half, p.play_number
        ");

        $query->bindParam(':game_id', $gameId, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch game plays')->send();
            return;
        }

        $plays = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['plays' => $plays])->send();
    }

    public function getGameSituationalStats(int $gameId): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            SELECT 
                g.id as game_id,
                ht.name as home_team_name,
                at.name as away_team_name,
                (
                    SELECT JSON_BUILD_OBJECT(
                        'runners_in_scoring_position', (
                            SELECT JSON_BUILD_OBJECT(
                                'at_bats', COUNT(*),
                                'hits', SUM(CASE WHEN p.result_type IN ('single', 'double', 'triple', 'home_run') THEN 1 ELSE 0 END),
                                'avg', ROUND(SUM(CASE WHEN p.result_type IN ('single', 'double', 'triple', 'home_run') THEN 1 ELSE 0 END)::float / COUNT(*), 3)
                            )
                            FROM plays p
                            WHERE p.game_id = g.id
                            AND (p.runners_on LIKE '%2%' OR p.runners_on LIKE '%3%')
                        ),
                        'leadoff_at_bats', (
                            SELECT JSON_BUILD_OBJECT(
                                'at_bats', COUNT(*),
                                'on_base', SUM(CASE WHEN p.result_type IN ('single', 'double', 'triple', 'home_run', 'walk', 'hit_by_pitch') THEN 1 ELSE 0 END),
                                'percentage', ROUND(SUM(CASE WHEN p.result_type IN ('single', 'double', 'triple', 'home_run', 'walk', 'hit_by_pitch') THEN 1 ELSE 0 END)::float / COUNT(*), 3)
                            )
                            FROM plays p
                            WHERE p.game_id = g.id
                            AND p.outs = 0
                            AND p.half_inning_at_bat = 1
                        ),
                        'two_outs', (
                            SELECT JSON_BUILD_OBJECT(
                                'at_bats', COUNT(*),
                                'runs', SUM(p.runs_scored),
                                'hits', SUM(CASE WHEN p.result_type IN ('single', 'double', 'triple', 'home_run') THEN 1 ELSE 0 END)
                            )
                            FROM plays p
                            WHERE p.game_id = g.id
                            AND p.outs = 2
                        )
                    )
                ) as situational_stats
            FROM games g
            JOIN teams ht ON g.home_team_id = ht.id
            JOIN teams at ON g.away_team_id = at.id
            WHERE g.id = :game_id
        ");

        $query->bindParam(':game_id', $gameId, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch game situational statistics')->send();
            return;
        }

        $stats = $query->fetch(PDO::FETCH_ASSOC);
        if (!$stats) {
            ApiResponse::notFound('Game not found')->send();
            return;
        }

        ApiResponse::success(['situational_stats' => $stats])->send();
    }
} 