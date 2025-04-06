<?php

namespace BaseballAnalytics\DataProcessing\Processors;

use BaseballAnalytics\DataProcessing\BaseProcessor;
use BaseballAnalytics\Database\Connection;

class GameStatsProcessor extends BaseProcessor
{
    protected array $requiredGameFields = [
        'game_id',
        'date',
        'home_team',
        'away_team',
        'home_score',
        'away_score',
        'innings_played'
    ];

    protected array $requiredInningFields = [
        'inning_number',
        'top_bottom',
        'runs_scored',
        'hits',
        'errors'
    ];

    public function __construct(Connection $db, array $config = [])
    {
        parent::__construct($db, $config);
    }

    public function process(): bool
    {
        $this->beginProcessing();

        try {
            // Fetch raw game data from staging table
            $rawGames = $this->fetchRawGameData();
            
            foreach ($rawGames as $rawGame) {
                if (!$this->validateData($rawGame, $this->requiredGameFields)) {
                    $this->incrementStat('records_skipped');
                    continue;
                }

                if (!$this->beginTransaction()) {
                    continue;
                }

                try {
                    // Process and transform game data
                    $transformedGame = $this->transformGameData($rawGame);
                    
                    // Save processed game data
                    $this->saveGameData($transformedGame);
                    
                    // Process inning-by-inning data if available
                    if (isset($rawGame['innings']) && is_array($rawGame['innings'])) {
                        foreach ($rawGame['innings'] as $inning) {
                            if ($this->validateData($inning, $this->requiredInningFields)) {
                                $transformedInning = $this->transformInningData($inning, $rawGame['game_id']);
                                $this->saveInningData($transformedInning);
                            }
                        }
                    }

                    $this->commitTransaction();
                    $this->incrementStat('records_transformed');
                } catch (\Exception $e) {
                    $this->rollbackTransaction();
                    $this->logError("Failed to process game ID: {$rawGame['game_id']}", $e);
                    continue;
                }

                $this->incrementStat('records_processed');
            }

            $this->endProcessing();
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to process games", $e);
            $this->endProcessing();
            return false;
        }
    }

    protected function fetchRawGameData(): array
    {
        $query = "SELECT * FROM staging_games WHERE processed = FALSE LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->config['batch_size'] ?? 100]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function transformGameData(array $rawGame): array
    {
        return [
            'game_id' => $rawGame['game_id'],
            'date' => $rawGame['date'],
            'home_team_id' => $this->getTeamId($rawGame['home_team']),
            'away_team_id' => $this->getTeamId($rawGame['away_team']),
            'home_score' => $rawGame['home_score'],
            'away_score' => $rawGame['away_score'],
            'innings_played' => $rawGame['innings_played'],
            'status' => $this->determineGameStatus($rawGame),
            'weather_conditions' => $rawGame['weather_conditions'] ?? null,
            'attendance' => $rawGame['attendance'] ?? null,
            'duration_minutes' => $rawGame['duration_minutes'] ?? null,
            'processed_at' => date('Y-m-d H:i:s')
        ];
    }

    protected function transformInningData(array $inning, string $gameId): array
    {
        return [
            'game_id' => $gameId,
            'inning_number' => $inning['inning_number'],
            'top_bottom' => $inning['top_bottom'],
            'runs_scored' => $inning['runs_scored'],
            'hits' => $inning['hits'],
            'errors' => $inning['errors'],
            'left_on_base' => $inning['left_on_base'] ?? 0
        ];
    }

    protected function saveGameData(array $game): void
    {
        $query = "INSERT INTO games (
            game_id, date, home_team_id, away_team_id, 
            home_score, away_score, innings_played, status,
            weather_conditions, attendance, duration_minutes, processed_at
        ) VALUES (
            :game_id, :date, :home_team_id, :away_team_id,
            :home_score, :away_score, :innings_played, :status,
            :weather_conditions, :attendance, :duration_minutes, :processed_at
        )";

        $stmt = $this->db->prepare($query);
        $stmt->execute($game);

        // Mark as processed in staging table
        $updateStaging = "UPDATE staging_games SET processed = TRUE WHERE game_id = :game_id";
        $stmt = $this->db->prepare($updateStaging);
        $stmt->execute(['game_id' => $game['game_id']]);
    }

    protected function saveInningData(array $inning): void
    {
        $query = "INSERT INTO game_innings (
            game_id, inning_number, top_bottom, 
            runs_scored, hits, errors, left_on_base
        ) VALUES (
            :game_id, :inning_number, :top_bottom,
            :runs_scored, :hits, :errors, :left_on_base
        )";

        $stmt = $this->db->prepare($query);
        $stmt->execute($inning);
    }

    protected function getTeamId(string $teamName): int
    {
        $query = "SELECT team_id FROM teams WHERE name = :name";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['name' => $teamName]);
        
        if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $result['team_id'];
        }
        
        throw new \RuntimeException("Team not found: {$teamName}");
    }

    protected function determineGameStatus(array $game): string
    {
        if ($game['status'] ?? null) {
            return $game['status'];
        }

        if ($game['innings_played'] < 9) {
            return 'shortened';
        }

        if ($game['innings_played'] > 9) {
            return 'extra_innings';
        }

        return 'completed';
    }
} 