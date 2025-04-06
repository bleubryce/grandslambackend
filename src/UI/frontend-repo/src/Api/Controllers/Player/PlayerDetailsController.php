
<?php
namespace BaseballAnalytics\Api\Controllers\Player;

use BaseballAnalytics\Api\ApiResponse;
use PDO;

class PlayerDetailsController extends BasePlayerController {
    
    public function getPlayer(int $playerId): void {
        if (!$this->checkAuth()) {
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
}
