<?php

namespace BaseballAnalytics\DataCollection\Collectors;

use BaseballAnalytics\DataCollection\BaseCollector;
use BaseballAnalytics\DataCollection\Utils\RateLimiter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class MLBStatsCollector extends BaseCollector
{
    private Client $client;
    private RateLimiter $rateLimiter;
    private array $requiredConfig = [
        'base_url',
        'endpoints',
        'rate_limit',
        'seasons'
    ];

    public function __construct($db, array $config)
    {
        parent::__construct($db, $config);
        
        if (!$this->validateConfig($this->requiredConfig)) {
            throw new \InvalidArgumentException('Invalid configuration for MLBStatsCollector');
        }

        $this->client = new Client([
            'base_uri' => $this->config['base_url'],
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'BaseballAnalytics/1.0',
                'Accept' => 'application/json',
            ]
        ]);

        $this->rateLimiter = new RateLimiter(
            $this->config['rate_limit']['requests_per_minute'],
            $this->config['rate_limit']['pause_on_limit']
        );
    }

    public function collect(): bool
    {
        try {
            $this->beginCollection();

            // Collect teams first
            $teams = $this->collectTeams();
            if (empty($teams)) {
                $this->logError('Failed to collect teams data');
                return false;
            }

            // Collect players for each team
            foreach ($teams as $team) {
                $this->collectTeamPlayers($team['id']);
            }

            // Collect games and stats
            $this->collectGames();

            $this->endCollection();
            return true;
        } catch (\Exception $e) {
            $this->logError('Collection process failed', $e);
            return false;
        }
    }

    private function collectTeams(): array
    {
        if (!$this->rateLimiter->checkLimit()) {
            $this->logError('Rate limit exceeded while collecting teams');
            return [];
        }

        try {
            $response = $this->client->get($this->config['endpoints']['teams']);
            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['teams'])) {
                $this->logError('Invalid response format for teams');
                return [];
            }

            foreach ($data['teams'] as $team) {
                $this->processTeam($team);
                $this->incrementStat('records_processed');
            }

            return $data['teams'];
        } catch (GuzzleException $e) {
            $this->logError('Failed to fetch teams', $e);
            return [];
        }
    }

    private function collectTeamPlayers(int $teamId): void
    {
        if (!$this->rateLimiter->checkLimit()) {
            $this->logError("Rate limit exceeded while collecting players for team {$teamId}");
            return;
        }

        try {
            $response = $this->client->get($this->config['endpoints']['players'], [
                'query' => ['teamId' => $teamId]
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['people'])) {
                $this->logError("Invalid response format for team {$teamId} players");
                return;
            }

            foreach ($data['people'] as $player) {
                $this->processPlayer($player);
                $this->incrementStat('records_processed');
            }
        } catch (GuzzleException $e) {
            $this->logError("Failed to fetch players for team {$teamId}", $e);
        }
    }

    private function collectGames(): void
    {
        $startYear = $this->config['seasons']['start_year'];
        $endYear = $this->config['seasons']['end_year'];

        for ($year = $startYear; $year <= $endYear; $year++) {
            if (!$this->rateLimiter->checkLimit()) {
                $this->logError("Rate limit exceeded while collecting games for year {$year}");
                continue;
            }

            try {
                $response = $this->client->get($this->config['endpoints']['games'], [
                    'query' => [
                        'season' => $year,
                        'sportId' => 1 // MLB
                    ]
                ]);
                $data = json_decode($response->getBody()->getContents(), true);

                if (!isset($data['dates'])) {
                    $this->logError("Invalid response format for games in year {$year}");
                    continue;
                }

                foreach ($data['dates'] as $date) {
                    foreach ($date['games'] as $game) {
                        $this->processGame($game);
                        $this->collectGameStats($game['gamePk']);
                        $this->incrementStat('records_processed');
                    }
                }
            } catch (GuzzleException $e) {
                $this->logError("Failed to fetch games for year {$year}", $e);
            }
        }
    }

    private function collectGameStats(int $gameId): void
    {
        if (!$this->rateLimiter->checkLimit()) {
            $this->logError("Rate limit exceeded while collecting stats for game {$gameId}");
            return;
        }

        try {
            $endpoint = str_replace('{gameId}', $gameId, $this->config['endpoints']['game_stats']);
            $response = $this->client->get($endpoint);
            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['teams'])) {
                $this->logError("Invalid response format for game {$gameId} stats");
                return;
            }

            $this->processGameStats($gameId, $data);
            $this->incrementStat('records_processed');
        } catch (GuzzleException $e) {
            $this->logError("Failed to fetch stats for game {$gameId}", $e);
        }
    }

    private function processTeam(array $team): void
    {
        try {
            $sql = "INSERT INTO teams (id, name, abbreviation, venue_name, league_id, division_id)
                    VALUES (:id, :name, :abbreviation, :venue_name, :league_id, :division_id)
                    ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    abbreviation = VALUES(abbreviation),
                    venue_name = VALUES(venue_name),
                    league_id = VALUES(league_id),
                    division_id = VALUES(division_id)";

            $params = [
                'id' => $team['id'],
                'name' => $team['name'],
                'abbreviation' => $team['abbreviation'],
                'venue_name' => $team['venue']['name'],
                'league_id' => $team['league']['id'],
                'division_id' => $team['division']['id']
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $this->incrementStat($stmt->rowCount() === 1 ? 'records_inserted' : 'records_updated');
        } catch (\PDOException $e) {
            $this->logError("Failed to process team {$team['id']}", $e);
        }
    }

    private function processPlayer(array $player): void
    {
        try {
            $sql = "INSERT INTO players (id, first_name, last_name, position, team_id, birth_date)
                    VALUES (:id, :first_name, :last_name, :position, :team_id, :birth_date)
                    ON DUPLICATE KEY UPDATE
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    position = VALUES(position),
                    team_id = VALUES(team_id),
                    birth_date = VALUES(birth_date)";

            $params = [
                'id' => $player['id'],
                'first_name' => $player['firstName'],
                'last_name' => $player['lastName'],
                'position' => $player['primaryPosition']['abbreviation'],
                'team_id' => $player['currentTeam']['id'],
                'birth_date' => $player['birthDate']
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $this->incrementStat($stmt->rowCount() === 1 ? 'records_inserted' : 'records_updated');
        } catch (\PDOException $e) {
            $this->logError("Failed to process player {$player['id']}", $e);
        }
    }

    private function processGame(array $game): void
    {
        try {
            $sql = "INSERT INTO games (id, date, home_team_id, away_team_id, status, venue_id)
                    VALUES (:id, :date, :home_team_id, :away_team_id, :status, :venue_id)
                    ON DUPLICATE KEY UPDATE
                    status = VALUES(status)";

            $params = [
                'id' => $game['gamePk'],
                'date' => $game['gameDate'],
                'home_team_id' => $game['teams']['home']['team']['id'],
                'away_team_id' => $game['teams']['away']['team']['id'],
                'status' => $game['status']['abstractGameState'],
                'venue_id' => $game['venue']['id']
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $this->incrementStat($stmt->rowCount() === 1 ? 'records_inserted' : 'records_updated');
        } catch (\PDOException $e) {
            $this->logError("Failed to process game {$game['gamePk']}", $e);
        }
    }

    private function processGameStats(int $gameId, array $data): void
    {
        try {
            // Process batting stats
            foreach (['home', 'away'] as $team) {
                foreach ($data['teams'][$team]['batters'] as $batterId) {
                    $stats = $data['teams'][$team]['players']["ID$batterId"]['stats']['batting'];
                    $this->processBattingStats($gameId, $batterId, $stats);
                }
            }

            // Process pitching stats
            foreach (['home', 'away'] as $team) {
                foreach ($data['teams'][$team]['pitchers'] as $pitcherId) {
                    $stats = $data['teams'][$team]['players']["ID$pitcherId"]['stats']['pitching'];
                    $this->processPitchingStats($gameId, $pitcherId, $stats);
                }
            }
        } catch (\Exception $e) {
            $this->logError("Failed to process stats for game {$gameId}", $e);
        }
    }

    private function processBattingStats(int $gameId, int $playerId, array $stats): void
    {
        try {
            $sql = "INSERT INTO batting_stats (game_id, player_id, at_bats, hits, runs, rbi, walks, strikeouts)
                    VALUES (:game_id, :player_id, :at_bats, :hits, :runs, :rbi, :walks, :strikeouts)
                    ON DUPLICATE KEY UPDATE
                    at_bats = VALUES(at_bats),
                    hits = VALUES(hits),
                    runs = VALUES(runs),
                    rbi = VALUES(rbi),
                    walks = VALUES(walks),
                    strikeouts = VALUES(strikeouts)";

            $params = [
                'game_id' => $gameId,
                'player_id' => $playerId,
                'at_bats' => $stats['atBats'] ?? 0,
                'hits' => $stats['hits'] ?? 0,
                'runs' => $stats['runs'] ?? 0,
                'rbi' => $stats['rbi'] ?? 0,
                'walks' => $stats['baseOnBalls'] ?? 0,
                'strikeouts' => $stats['strikeOuts'] ?? 0
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $this->incrementStat($stmt->rowCount() === 1 ? 'records_inserted' : 'records_updated');
        } catch (\PDOException $e) {
            $this->logError("Failed to process batting stats for player {$playerId} in game {$gameId}", $e);
        }
    }

    private function processPitchingStats(int $gameId, int $playerId, array $stats): void
    {
        try {
            $sql = "INSERT INTO pitching_stats (game_id, player_id, innings_pitched, hits, runs, earned_runs, walks, strikeouts)
                    VALUES (:game_id, :player_id, :innings_pitched, :hits, :runs, :earned_runs, :walks, :strikeouts)
                    ON DUPLICATE KEY UPDATE
                    innings_pitched = VALUES(innings_pitched),
                    hits = VALUES(hits),
                    runs = VALUES(runs),
                    earned_runs = VALUES(earned_runs),
                    walks = VALUES(walks),
                    strikeouts = VALUES(strikeouts)";

            $params = [
                'game_id' => $gameId,
                'player_id' => $playerId,
                'innings_pitched' => $stats['inningsPitched'] ?? 0,
                'hits' => $stats['hits'] ?? 0,
                'runs' => $stats['runs'] ?? 0,
                'earned_runs' => $stats['earnedRuns'] ?? 0,
                'walks' => $stats['baseOnBalls'] ?? 0,
                'strikeouts' => $stats['strikeOuts'] ?? 0
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $this->incrementStat($stmt->rowCount() === 1 ? 'records_inserted' : 'records_updated');
        } catch (\PDOException $e) {
            $this->logError("Failed to process pitching stats for player {$playerId} in game {$gameId}", $e);
        }
    }
} 