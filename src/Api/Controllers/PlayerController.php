<?php
namespace BaseballAnalytics\Api\Controllers;

use BaseballAnalytics\Api\ApiResponse;
use BaseballAnalytics\Api\ApiMiddleware;
use BaseballAnalytics\Auth\SessionManager;
use BaseballAnalytics\Database\Connection;
use PDO;

class PlayerController {
    private $db;
    private $sessionManager;
    private $middleware;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
        $this->sessionManager = SessionManager::getInstance();
        $this->middleware = new ApiMiddleware();
    }

    public function listPlayers(): void {
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
                p.active,
                t.name as team_name,
                t.id as team_id
            FROM players p
            LEFT JOIN teams t ON p.team_id = t.id
            ORDER BY p.active DESC, p.last_name, p.first_name
        ");

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch players')->send();
            return;
        }

        $players = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['players' => $players])->send();
    }

    public function getPlayer(int $playerId): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            SELECT 
                p.*,
                t.name as team_name,
                t.id as team_id,
                (
                    SELECT JSON_OBJECT(
                        'games', COUNT(DISTINCT g.id),
                        'at_bats', SUM(bs.at_bats),
                        'hits', SUM(bs.hits),
                        'home_runs', SUM(bs.home_runs),
                        'runs_batted_in', SUM(bs.runs_batted_in),
                        'stolen_bases', SUM(bs.stolen_bases),
                        'batting_average', ROUND(SUM(bs.hits)::float / NULLIF(SUM(bs.at_bats), 0), 3),
                        'on_base_percentage', ROUND((SUM(bs.hits) + SUM(bs.walks))::float / NULLIF(SUM(bs.at_bats) + SUM(bs.walks) + SUM(bs.sacrifice_flies), 0), 3)
                    )
                    FROM batting_stats bs
                    JOIN games g ON bs.game_id = g.id
                    WHERE bs.player_id = p.id
                    AND g.season = YEAR(CURRENT_DATE)
                ) as batting_stats,
                (
                    SELECT JSON_OBJECT(
                        'games', COUNT(DISTINCT g.id),
                        'games_started', COUNT(DISTINCT CASE WHEN ps.game_started THEN g.id END),
                        'innings_pitched', SUM(ps.innings_pitched),
                        'wins', SUM(ps.wins),
                        'losses', SUM(ps.losses),
                        'saves', SUM(ps.saves),
                        'strikeouts', SUM(ps.strikeouts),
                        'walks', SUM(ps.walks),
                        'earned_runs', SUM(ps.earned_runs),
                        'era', ROUND((SUM(ps.earned_runs) * 9.0) / NULLIF(SUM(ps.innings_pitched), 0), 2)
                    )
                    FROM pitching_stats ps
                    JOIN games g ON ps.game_id = g.id
                    WHERE ps.player_id = p.id
                    AND g.season = YEAR(CURRENT_DATE)
                ) as pitching_stats,
                (
                    SELECT JSON_OBJECT(
                        'games', COUNT(DISTINCT g.id),
                        'putouts', SUM(fs.putouts),
                        'assists', SUM(fs.assists),
                        'errors', SUM(fs.errors),
                        'fielding_percentage', ROUND((SUM(fs.putouts) + SUM(fs.assists))::float / NULLIF(SUM(fs.putouts) + SUM(fs.assists) + SUM(fs.errors), 0), 3)
                    )
                    FROM fielding_stats fs
                    JOIN games g ON fs.game_id = g.id
                    WHERE fs.player_id = p.id
                    AND g.season = YEAR(CURRENT_DATE)
                ) as fielding_stats
            FROM players p
            LEFT JOIN teams t ON p.team_id = t.id
            WHERE p.id = :player_id
        ");

        $query->bindParam(':player_id', $playerId, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch player details')->send();
            return;
        }

        $player = $query->fetch(PDO::FETCH_ASSOC);
        if (!$player) {
            ApiResponse::notFound('Player not found')->send();
            return;
        }

        ApiResponse::success(['player' => $player])->send();
    }

    public function getPlayerGameLog(int $playerId): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            SELECT 
                g.id as game_id,
                g.game_date,
                g.home_team_id,
                ht.name as home_team_name,
                g.away_team_id,
                at.name as away_team_name,
                g.status,
                bs.at_bats,
                bs.hits,
                bs.doubles,
                bs.triples,
                bs.home_runs,
                bs.runs_batted_in,
                bs.walks,
                bs.strikeouts as batting_strikeouts,
                ps.innings_pitched,
                ps.hits_allowed,
                ps.earned_runs,
                ps.walks as pitching_walks,
                ps.strikeouts as pitching_strikeouts,
                fs.putouts,
                fs.assists,
                fs.errors
            FROM games g
            JOIN teams ht ON g.home_team_id = ht.id
            JOIN teams at ON g.away_team_id = at.id
            LEFT JOIN batting_stats bs ON g.id = bs.game_id AND bs.player_id = :player_id
            LEFT JOIN pitching_stats ps ON g.id = ps.game_id AND ps.player_id = :player_id
            LEFT JOIN fielding_stats fs ON g.id = fs.game_id AND fs.player_id = :player_id
            WHERE (bs.player_id IS NOT NULL OR ps.player_id IS NOT NULL OR fs.player_id IS NOT NULL)
            AND g.season = YEAR(CURRENT_DATE)
            ORDER BY g.game_date DESC
        ");

        $query->bindParam(':player_id', $playerId, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch player game log')->send();
            return;
        }

        $gameLog = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['game_log' => $gameLog])->send();
    }

    public function getPlayerCareerStats(int $playerId): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        $query = $this->db->prepare("
            WITH career_batting AS (
                SELECT 
                    g.season,
                    COUNT(DISTINCT g.id) as games,
                    SUM(bs.at_bats) as at_bats,
                    SUM(bs.hits) as hits,
                    SUM(bs.doubles) as doubles,
                    SUM(bs.triples) as triples,
                    SUM(bs.home_runs) as home_runs,
                    SUM(bs.runs_batted_in) as runs_batted_in,
                    SUM(bs.walks) as walks,
                    SUM(bs.strikeouts) as strikeouts,
                    ROUND(SUM(bs.hits)::float / NULLIF(SUM(bs.at_bats), 0), 3) as batting_average,
                    ROUND((SUM(bs.hits) + SUM(bs.walks))::float / NULLIF(SUM(bs.at_bats) + SUM(bs.walks) + SUM(bs.sacrifice_flies), 0), 3) as on_base_percentage
                FROM batting_stats bs
                JOIN games g ON bs.game_id = g.id
                WHERE bs.player_id = :player_id
                GROUP BY g.season
            ),
            career_pitching AS (
                SELECT 
                    g.season,
                    COUNT(DISTINCT g.id) as games,
                    COUNT(DISTINCT CASE WHEN ps.game_started THEN g.id END) as games_started,
                    SUM(ps.innings_pitched) as innings_pitched,
                    SUM(ps.hits_allowed) as hits_allowed,
                    SUM(ps.earned_runs) as earned_runs,
                    SUM(ps.walks) as walks,
                    SUM(ps.strikeouts) as strikeouts,
                    SUM(ps.wins) as wins,
                    SUM(ps.losses) as losses,
                    SUM(ps.saves) as saves,
                    ROUND((SUM(ps.earned_runs) * 9.0) / NULLIF(SUM(ps.innings_pitched), 0), 2) as era
                FROM pitching_stats ps
                JOIN games g ON ps.game_id = g.id
                WHERE ps.player_id = :player_id
                GROUP BY g.season
            ),
            career_fielding AS (
                SELECT 
                    g.season,
                    COUNT(DISTINCT g.id) as games,
                    SUM(fs.putouts) as putouts,
                    SUM(fs.assists) as assists,
                    SUM(fs.errors) as errors,
                    ROUND((SUM(fs.putouts) + SUM(fs.assists))::float / NULLIF(SUM(fs.putouts) + SUM(fs.assists) + SUM(fs.errors), 0), 3) as fielding_percentage
                FROM fielding_stats fs
                JOIN games g ON fs.game_id = g.id
                WHERE fs.player_id = :player_id
                GROUP BY g.season
            )
            SELECT 
                COALESCE(cb.season, cp.season, cf.season) as season,
                JSON_BUILD_OBJECT(
                    'games', cb.games,
                    'at_bats', cb.at_bats,
                    'hits', cb.hits,
                    'doubles', cb.doubles,
                    'triples', cb.triples,
                    'home_runs', cb.home_runs,
                    'runs_batted_in', cb.runs_batted_in,
                    'walks', cb.walks,
                    'strikeouts', cb.strikeouts,
                    'batting_average', cb.batting_average,
                    'on_base_percentage', cb.on_base_percentage
                ) as batting,
                JSON_BUILD_OBJECT(
                    'games', cp.games,
                    'games_started', cp.games_started,
                    'innings_pitched', cp.innings_pitched,
                    'hits_allowed', cp.hits_allowed,
                    'earned_runs', cp.earned_runs,
                    'walks', cp.walks,
                    'strikeouts', cp.strikeouts,
                    'wins', cp.wins,
                    'losses', cp.losses,
                    'saves', cp.saves,
                    'era', cp.era
                ) as pitching,
                JSON_BUILD_OBJECT(
                    'games', cf.games,
                    'putouts', cf.putouts,
                    'assists', cf.assists,
                    'errors', cf.errors,
                    'fielding_percentage', cf.fielding_percentage
                ) as fielding
            FROM career_batting cb
            FULL OUTER JOIN career_pitching cp ON cb.season = cp.season
            FULL OUTER JOIN career_fielding cf ON COALESCE(cb.season, cp.season) = cf.season
            ORDER BY season DESC
        ");

        $query->bindParam(':player_id', $playerId, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch player career statistics')->send();
            return;
        }

        $careerStats = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['career_stats' => $careerStats])->send();
    }

    public function searchPlayers(): void {
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

        if (isset($data['name'])) {
            $conditions[] = "(p.first_name ILIKE :name OR p.last_name ILIKE :name)";
            $params[':name'] = '%' . $data['name'] . '%';
        }

        if (isset($data['position'])) {
            $conditions[] = "p.position = :position";
            $params[':position'] = $data['position'];
        }

        if (isset($data['team_id'])) {
            $conditions[] = "p.team_id = :team_id";
            $params[':team_id'] = $data['team_id'];
        }

        if (isset($data['active'])) {
            $conditions[] = "p.active = :active";
            $params[':active'] = $data['active'];
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        $query = $this->db->prepare("
            SELECT 
                p.id,
                p.first_name,
                p.last_name,
                p.jersey_number,
                p.position,
                p.active,
                t.name as team_name,
                t.id as team_id
            FROM players p
            LEFT JOIN teams t ON p.team_id = t.id
            {$whereClause}
            ORDER BY p.last_name, p.first_name
        ");

        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to search players')->send();
            return;
        }

        $players = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['players' => $players])->send();
    }
} 