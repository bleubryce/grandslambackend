<?php
namespace BaseballAnalytics\Api\Controllers;

use BaseballAnalytics\Api\ApiResponse;
use BaseballAnalytics\Api\ApiMiddleware;
use BaseballAnalytics\Auth\SessionManager;
use BaseballAnalytics\Database\Connection;
use PDO;

class StatsController {
    private $db;
    private $sessionManager;
    private $middleware;

    public function __construct() {
        $this->db = Connection::getInstance()->getConnection();
        $this->sessionManager = SessionManager::getInstance();
        $this->middleware = new ApiMiddleware();
    }

    public function getLeagueLeaders(): void {
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

        $season = $data['season'] ?? date('Y');
        $category = $data['category'] ?? 'batting';
        $limit = min(($data['limit'] ?? 10), 50);

        switch ($category) {
            case 'batting':
                $query = $this->db->prepare("
                    WITH batting_totals AS (
                        SELECT 
                            p.id,
                            p.first_name,
                            p.last_name,
                            t.name as team_name,
                            COUNT(DISTINCT g.id) as games_played,
                            SUM(bs.at_bats) as at_bats,
                            SUM(bs.hits) as hits,
                            SUM(bs.doubles) as doubles,
                            SUM(bs.triples) as triples,
                            SUM(bs.home_runs) as home_runs,
                            SUM(bs.runs_batted_in) as rbi,
                            SUM(bs.walks) as walks,
                            SUM(bs.strikeouts) as strikeouts,
                            SUM(bs.stolen_bases) as stolen_bases,
                            ROUND(SUM(bs.hits)::float / NULLIF(SUM(bs.at_bats), 0), 3) as batting_avg,
                            ROUND((SUM(bs.hits) + SUM(bs.walks))::float / NULLIF(SUM(bs.at_bats) + SUM(bs.walks) + SUM(bs.sacrifice_flies), 0), 3) as on_base_pct,
                            ROUND((SUM(bs.hits) + SUM(bs.doubles) + 2 * SUM(bs.triples) + 3 * SUM(bs.home_runs))::float / NULLIF(SUM(bs.at_bats), 0), 3) as slugging_pct
                        FROM players p
                        JOIN teams t ON p.team_id = t.id
                        JOIN batting_stats bs ON p.id = bs.player_id
                        JOIN games g ON bs.game_id = g.id
                        WHERE g.season = :season
                        GROUP BY p.id, p.first_name, p.last_name, t.name
                        HAVING SUM(bs.at_bats) >= (3.1 * (
                            SELECT COUNT(DISTINCT game_id) 
                            FROM batting_stats bs2 
                            JOIN games g2 ON bs2.game_id = g2.id 
                            WHERE g2.season = :season
                        ))
                    )
                    SELECT 
                        *,
                        ROUND(on_base_pct + slugging_pct, 3) as ops
                    FROM batting_totals
                    ORDER BY batting_avg DESC
                    LIMIT :limit
                ");
                break;

            case 'pitching':
                $query = $this->db->prepare("
                    SELECT 
                        p.id,
                        p.first_name,
                        p.last_name,
                        t.name as team_name,
                        COUNT(DISTINCT g.id) as games_played,
                        COUNT(DISTINCT CASE WHEN ps.game_started THEN g.id END) as games_started,
                        SUM(ps.innings_pitched) as innings_pitched,
                        SUM(ps.wins) as wins,
                        SUM(ps.losses) as losses,
                        SUM(ps.saves) as saves,
                        SUM(ps.hits_allowed) as hits_allowed,
                        SUM(ps.earned_runs) as earned_runs,
                        SUM(ps.walks) as walks,
                        SUM(ps.strikeouts) as strikeouts,
                        ROUND((SUM(ps.earned_runs) * 9.0) / NULLIF(SUM(ps.innings_pitched), 0), 2) as era,
                        ROUND((SUM(ps.walks) + SUM(ps.hits_allowed)) / NULLIF(SUM(ps.innings_pitched), 0), 3) as whip,
                        ROUND(SUM(ps.strikeouts) * 9.0 / NULLIF(SUM(ps.innings_pitched), 0), 2) as k_per_9
                    FROM players p
                    JOIN teams t ON p.team_id = t.id
                    JOIN pitching_stats ps ON p.id = ps.player_id
                    JOIN games g ON ps.game_id = g.id
                    WHERE g.season = :season
                    GROUP BY p.id, p.first_name, p.last_name, t.name
                    HAVING SUM(ps.innings_pitched) >= (
                        SELECT COUNT(DISTINCT game_id) 
                        FROM pitching_stats ps2 
                        JOIN games g2 ON ps2.game_id = g2.id 
                        WHERE g2.season = :season
                    )
                    ORDER BY era ASC
                    LIMIT :limit
                ");
                break;

            case 'fielding':
                $query = $this->db->prepare("
                    SELECT 
                        p.id,
                        p.first_name,
                        p.last_name,
                        t.name as team_name,
                        fs.position_played,
                        COUNT(DISTINCT g.id) as games_played,
                        SUM(fs.putouts) as putouts,
                        SUM(fs.assists) as assists,
                        SUM(fs.errors) as errors,
                        SUM(fs.double_plays) as double_plays,
                        ROUND((SUM(fs.putouts) + SUM(fs.assists))::float / NULLIF(SUM(fs.putouts) + SUM(fs.assists) + SUM(fs.errors), 0), 3) as fielding_pct
                    FROM players p
                    JOIN teams t ON p.team_id = t.id
                    JOIN fielding_stats fs ON p.id = fs.player_id
                    JOIN games g ON fs.game_id = g.id
                    WHERE g.season = :season
                    GROUP BY p.id, p.first_name, p.last_name, t.name, fs.position_played
                    HAVING COUNT(DISTINCT g.id) >= (
                        SELECT COUNT(DISTINCT game_id) * 0.75
                        FROM fielding_stats fs2 
                        JOIN games g2 ON fs2.game_id = g2.id 
                        WHERE g2.season = :season
                    )
                    ORDER BY fielding_pct DESC
                    LIMIT :limit
                ");
                break;

            default:
                ApiResponse::badRequest('Invalid category')->send();
                return;
        }

        $query->bindParam(':season', $season, PDO::PARAM_INT);
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch league leaders')->send();
            return;
        }

        $leaders = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['leaders' => $leaders])->send();
    }

    public function getTeamStats(): void {
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

        $season = $data['season'] ?? date('Y');

        $query = $this->db->prepare("
            WITH team_totals AS (
                SELECT 
                    t.id,
                    t.name,
                    t.league,
                    t.division,
                    COUNT(DISTINCT g.id) as games_played,
                    COUNT(CASE WHEN g.winner_id = t.id THEN 1 END) as wins,
                    COUNT(CASE WHEN g.loser_id = t.id THEN 1 END) as losses,
                    SUM(CASE WHEN g.home_team_id = t.id THEN g.home_team_runs ELSE g.away_team_runs END) as runs_scored,
                    SUM(CASE WHEN g.home_team_id = t.id THEN g.away_team_runs ELSE g.home_team_runs END) as runs_allowed
                FROM teams t
                LEFT JOIN games g ON (g.home_team_id = t.id OR g.away_team_id = t.id)
                    AND g.season = :season
                GROUP BY t.id, t.name, t.league, t.division
            )
            SELECT 
                *,
                ROUND(wins::float / NULLIF(wins + losses, 0), 3) as win_pct,
                ROUND(runs_scored::float / NULLIF(games_played, 0), 2) as runs_per_game,
                ROUND(runs_allowed::float / NULLIF(games_played, 0), 2) as runs_allowed_per_game,
                runs_scored - runs_allowed as run_differential,
                ROUND((1.0 * wins::float / NULLIF(wins + losses, 0) - 
                    (runs_scored::float * runs_scored::float) / 
                    NULLIF((runs_scored::float * runs_scored::float + runs_allowed::float * runs_allowed::float), 0)) * 1000, 3
                ) as luck_index
            FROM team_totals
            ORDER BY win_pct DESC
        ");

        $query->bindParam(':season', $season, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch team statistics')->send();
            return;
        }

        $stats = $query->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success(['team_stats' => $stats])->send();
    }

    public function getHeadToHead(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        if (!$this->middleware->validateContentType()) {
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data || !isset($data['team1_id'], $data['team2_id'])) {
            ApiResponse::badRequest('Missing team IDs')->send();
            return;
        }

        $season = $data['season'] ?? date('Y');

        $query = $this->db->prepare("
            WITH matchups AS (
                SELECT 
                    g.*,
                    CASE 
                        WHEN g.home_team_id = :team1_id THEN g.home_team_runs
                        ELSE g.away_team_runs
                    END as team1_runs,
                    CASE 
                        WHEN g.home_team_id = :team2_id THEN g.home_team_runs
                        ELSE g.away_team_runs
                    END as team2_runs
                FROM games g
                WHERE g.season = :season
                AND (
                    (g.home_team_id = :team1_id AND g.away_team_id = :team2_id)
                    OR
                    (g.home_team_id = :team2_id AND g.away_team_id = :team1_id)
                )
            )
            SELECT 
                (SELECT name FROM teams WHERE id = :team1_id) as team1_name,
                (SELECT name FROM teams WHERE id = :team2_id) as team2_name,
                COUNT(*) as games_played,
                COUNT(CASE WHEN 
                    (home_team_id = :team1_id AND winner_id = :team1_id)
                    OR
                    (away_team_id = :team1_id AND winner_id = :team1_id)
                THEN 1 END) as team1_wins,
                COUNT(CASE WHEN 
                    (home_team_id = :team2_id AND winner_id = :team2_id)
                    OR
                    (away_team_id = :team2_id AND winner_id = :team2_id)
                THEN 1 END) as team2_wins,
                SUM(team1_runs) as team1_runs,
                SUM(team2_runs) as team2_runs,
                ROUND(AVG(team1_runs), 2) as team1_runs_per_game,
                ROUND(AVG(team2_runs), 2) as team2_runs_per_game,
                JSON_AGG(
                    JSON_BUILD_OBJECT(
                        'game_date', game_date,
                        'home_team_id', home_team_id,
                        'away_team_id', away_team_id,
                        'team1_runs', team1_runs,
                        'team2_runs', team2_runs,
                        'winner_id', winner_id
                    )
                    ORDER BY game_date
                ) as games
            FROM matchups
        ");

        $query->bindParam(':team1_id', $data['team1_id'], PDO::PARAM_INT);
        $query->bindParam(':team2_id', $data['team2_id'], PDO::PARAM_INT);
        $query->bindParam(':season', $season, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch head-to-head statistics')->send();
            return;
        }

        $stats = $query->fetch(PDO::FETCH_ASSOC);
        if (!$stats) {
            ApiResponse::notFound('No head-to-head games found')->send();
            return;
        }

        ApiResponse::success(['head_to_head' => $stats])->send();
    }

    public function getAdvancedMetrics(): void {
        if (!$this->sessionManager->isAuthenticated()) {
            ApiResponse::unauthorized()->send();
            return;
        }

        if (!$this->middleware->validateContentType()) {
            return;
        }

        $data = $this->middleware->getRequestData();
        if (!$data || !isset($data['player_id'])) {
            ApiResponse::badRequest('Missing player ID')->send();
            return;
        }

        $season = $data['season'] ?? date('Y');

        $query = $this->db->prepare("
            WITH player_stats AS (
                SELECT 
                    p.id,
                    p.first_name,
                    p.last_name,
                    t.name as team_name,
                    COUNT(DISTINCT g.id) as games_played,
                    SUM(bs.plate_appearances) as plate_appearances,
                    SUM(bs.at_bats) as at_bats,
                    SUM(bs.hits) as hits,
                    SUM(bs.doubles) as doubles,
                    SUM(bs.triples) as triples,
                    SUM(bs.home_runs) as home_runs,
                    SUM(bs.walks) as walks,
                    SUM(bs.hit_by_pitch) as hit_by_pitch,
                    SUM(bs.sacrifice_flies) as sacrifice_flies,
                    SUM(bs.runs_batted_in) as rbi,
                    SUM(bs.runs_scored) as runs,
                    SUM(bs.stolen_bases) as stolen_bases,
                    SUM(bs.caught_stealing) as caught_stealing,
                    SUM(ps.innings_pitched) as innings_pitched,
                    SUM(ps.earned_runs) as earned_runs,
                    SUM(ps.strikeouts) as strikeouts_pitched,
                    SUM(ps.walks_allowed) as walks_allowed,
                    SUM(ps.home_runs_allowed) as home_runs_allowed
                FROM players p
                JOIN teams t ON p.team_id = t.id
                LEFT JOIN batting_stats bs ON p.id = bs.player_id
                LEFT JOIN pitching_stats ps ON p.id = ps.player_id
                JOIN games g ON COALESCE(bs.game_id, ps.game_id) = g.id
                WHERE p.id = :player_id
                AND g.season = :season
                GROUP BY p.id, p.first_name, p.last_name, t.name
            )
            SELECT 
                *,
                -- Batting Metrics
                ROUND(hits::float / NULLIF(at_bats, 0), 3) as batting_avg,
                ROUND((hits + walks + hit_by_pitch)::float / NULLIF(plate_appearances, 0), 3) as on_base_pct,
                ROUND((hits + doubles + 2 * triples + 3 * home_runs)::float / NULLIF(at_bats, 0), 3) as slugging_pct,
                ROUND(stolen_bases::float / NULLIF(stolen_bases + caught_stealing, 0), 3) as stolen_base_pct,
                ROUND((runs + rbi - home_runs)::float / NULLIF(plate_appearances, 0), 3) as runs_produced_rate,
                -- Pitching Metrics
                ROUND((earned_runs * 9.0) / NULLIF(innings_pitched, 0), 2) as era,
                ROUND((walks_allowed + hits)::float / NULLIF(innings_pitched, 0), 3) as whip,
                ROUND((strikeouts_pitched * 9.0) / NULLIF(innings_pitched, 0), 2) as k_per_9,
                ROUND((walks_allowed * 9.0) / NULLIF(innings_pitched, 0), 2) as bb_per_9,
                ROUND(strikeouts_pitched::float / NULLIF(walks_allowed, 0), 2) as k_bb_ratio,
                ROUND(home_runs_allowed::float / NULLIF(innings_pitched, 0) * 9, 2) as hr_per_9
            FROM player_stats
        ");

        $query->bindParam(':player_id', $data['player_id'], PDO::PARAM_INT);
        $query->bindParam(':season', $season, PDO::PARAM_INT);

        if (!$query->execute()) {
            ApiResponse::serverError('Failed to fetch advanced metrics')->send();
            return;
        }

        $metrics = $query->fetch(PDO::FETCH_ASSOC);
        if (!$metrics) {
            ApiResponse::notFound('No statistics found for player')->send();
            return;
        }

        ApiResponse::success(['advanced_metrics' => $metrics])->send();
    }
} 