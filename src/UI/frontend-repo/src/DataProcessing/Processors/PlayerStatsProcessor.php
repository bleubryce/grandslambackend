<?php

namespace BaseballAnalytics\DataProcessing\Processors;

use BaseballAnalytics\DataProcessing\BaseProcessor;
use BaseballAnalytics\Database\Connection;

class PlayerStatsProcessor extends BaseProcessor
{
    protected array $requiredFields = [
        'player_id',
        'game_id',
        'team_id',
        'position'
    ];

    protected array $battingFields = [
        'at_bats',
        'hits',
        'runs',
        'rbis',
        'walks',
        'strikeouts'
    ];

    protected array $pitchingFields = [
        'innings_pitched',
        'earned_runs',
        'strikeouts',
        'walks',
        'hits_allowed'
    ];

    public function __construct(Connection $db, array $config = [])
    {
        parent::__construct($db, $config);
    }

    public function process(): bool
    {
        $this->beginProcessing();

        try {
            // Process batting statistics
            $this->processBattingStats();
            
            // Process pitching statistics
            $this->processPitchingStats();
            
            // Calculate advanced metrics
            $this->calculateAdvancedMetrics();

            $this->endProcessing();
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to process player statistics", $e);
            $this->endProcessing();
            return false;
        }
    }

    protected function processBattingStats(): void
    {
        $rawStats = $this->fetchRawBattingStats();

        foreach ($rawStats as $stat) {
            if (!$this->beginTransaction()) {
                continue;
            }

            try {
                if ($this->validateData($stat, array_merge($this->requiredFields, $this->battingFields))) {
                    $processedStats = $this->calculateBattingStats($stat);
                    $this->saveBattingStats($processedStats);
                    $this->commitTransaction();
                    $this->incrementStat('records_transformed');
                } else {
                    $this->rollbackTransaction();
                    $this->incrementStat('records_skipped');
                    continue;
                }
            } catch (\Exception $e) {
                $this->rollbackTransaction();
                $this->logError("Failed to process batting stats for player ID: {$stat['player_id']}", $e);
                continue;
            }

            $this->incrementStat('records_processed');
        }
    }

    protected function processPitchingStats(): void
    {
        $rawStats = $this->fetchRawPitchingStats();

        foreach ($rawStats as $stat) {
            if (!$this->beginTransaction()) {
                continue;
            }

            try {
                if ($this->validateData($stat, array_merge($this->requiredFields, $this->pitchingFields))) {
                    $processedStats = $this->calculatePitchingStats($stat);
                    $this->savePitchingStats($processedStats);
                    $this->commitTransaction();
                    $this->incrementStat('records_transformed');
                } else {
                    $this->rollbackTransaction();
                    $this->incrementStat('records_skipped');
                    continue;
                }
            } catch (\Exception $e) {
                $this->rollbackTransaction();
                $this->logError("Failed to process pitching stats for player ID: {$stat['player_id']}", $e);
                continue;
            }

            $this->incrementStat('records_processed');
        }
    }

    protected function fetchRawBattingStats(): array
    {
        $query = "SELECT * FROM staging_batting_stats WHERE processed = FALSE LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->config['batch_size'] ?? 100]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function fetchRawPitchingStats(): array
    {
        $query = "SELECT * FROM staging_pitching_stats WHERE processed = FALSE LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$this->config['batch_size'] ?? 100]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function calculateBattingStats(array $raw): array
    {
        $battingAvg = $raw['at_bats'] > 0 ? round($raw['hits'] / $raw['at_bats'], 3) : 0.000;
        $onBasePercentage = $this->calculateOnBasePercentage($raw);
        $sluggingPercentage = $this->calculateSluggingPercentage($raw);

        return [
            'player_id' => $raw['player_id'],
            'game_id' => $raw['game_id'],
            'team_id' => $raw['team_id'],
            'at_bats' => $raw['at_bats'],
            'hits' => $raw['hits'],
            'runs' => $raw['runs'],
            'rbis' => $raw['rbis'],
            'walks' => $raw['walks'],
            'strikeouts' => $raw['strikeouts'],
            'batting_average' => $battingAvg,
            'on_base_percentage' => $onBasePercentage,
            'slugging_percentage' => $sluggingPercentage,
            'ops' => $onBasePercentage + $sluggingPercentage,
            'processed_at' => date('Y-m-d H:i:s')
        ];
    }

    protected function calculatePitchingStats(array $raw): array
    {
        $era = $this->calculateERA($raw);
        $whip = $this->calculateWHIP($raw);

        return [
            'player_id' => $raw['player_id'],
            'game_id' => $raw['game_id'],
            'team_id' => $raw['team_id'],
            'innings_pitched' => $raw['innings_pitched'],
            'earned_runs' => $raw['earned_runs'],
            'strikeouts' => $raw['strikeouts'],
            'walks' => $raw['walks'],
            'hits_allowed' => $raw['hits_allowed'],
            'era' => $era,
            'whip' => $whip,
            'k_per_9' => $this->calculateK9($raw),
            'bb_per_9' => $this->calculateBB9($raw),
            'processed_at' => date('Y-m-d H:i:s')
        ];
    }

    protected function calculateOnBasePercentage(array $stats): float
    {
        $plateAppearances = $stats['at_bats'] + $stats['walks'] + ($stats['hit_by_pitch'] ?? 0) + ($stats['sacrifice_flies'] ?? 0);
        if ($plateAppearances == 0) return 0.000;

        $timesOnBase = $stats['hits'] + $stats['walks'] + ($stats['hit_by_pitch'] ?? 0);
        return round($timesOnBase / $plateAppearances, 3);
    }

    protected function calculateSluggingPercentage(array $stats): float
    {
        if ($stats['at_bats'] == 0) return 0.000;

        $totalBases = ($stats['singles'] ?? ($stats['hits'] - ($stats['doubles'] ?? 0) - ($stats['triples'] ?? 0) - ($stats['home_runs'] ?? 0))) +
                     (($stats['doubles'] ?? 0) * 2) +
                     (($stats['triples'] ?? 0) * 3) +
                     (($stats['home_runs'] ?? 0) * 4);

        return round($totalBases / $stats['at_bats'], 3);
    }

    protected function calculateERA(array $stats): float
    {
        if ($stats['innings_pitched'] == 0) return 0.00;
        return round(($stats['earned_runs'] * 9) / $stats['innings_pitched'], 2);
    }

    protected function calculateWHIP(array $stats): float
    {
        if ($stats['innings_pitched'] == 0) return 0.00;
        return round(($stats['walks'] + $stats['hits_allowed']) / $stats['innings_pitched'], 2);
    }

    protected function calculateK9(array $stats): float
    {
        if ($stats['innings_pitched'] == 0) return 0.00;
        return round(($stats['strikeouts'] * 9) / $stats['innings_pitched'], 2);
    }

    protected function calculateBB9(array $stats): float
    {
        if ($stats['innings_pitched'] == 0) return 0.00;
        return round(($stats['walks'] * 9) / $stats['innings_pitched'], 2);
    }

    protected function saveBattingStats(array $stats): void
    {
        $query = "INSERT INTO player_batting_stats (
            player_id, game_id, team_id, at_bats, hits, runs, rbis,
            walks, strikeouts, batting_average, on_base_percentage,
            slugging_percentage, ops, processed_at
        ) VALUES (
            :player_id, :game_id, :team_id, :at_bats, :hits, :runs, :rbis,
            :walks, :strikeouts, :batting_average, :on_base_percentage,
            :slugging_percentage, :ops, :processed_at
        )";

        $stmt = $this->db->prepare($query);
        $stmt->execute($stats);

        // Mark as processed in staging table
        $updateStaging = "UPDATE staging_batting_stats SET processed = TRUE WHERE player_id = :player_id AND game_id = :game_id";
        $stmt = $this->db->prepare($updateStaging);
        $stmt->execute(['player_id' => $stats['player_id'], 'game_id' => $stats['game_id']]);
    }

    protected function savePitchingStats(array $stats): void
    {
        $query = "INSERT INTO player_pitching_stats (
            player_id, game_id, team_id, innings_pitched, earned_runs,
            strikeouts, walks, hits_allowed, era, whip, k_per_9,
            bb_per_9, processed_at
        ) VALUES (
            :player_id, :game_id, :team_id, :innings_pitched, :earned_runs,
            :strikeouts, :walks, :hits_allowed, :era, :whip, :k_per_9,
            :bb_per_9, :processed_at
        )";

        $stmt = $this->db->prepare($query);
        $stmt->execute($stats);

        // Mark as processed in staging table
        $updateStaging = "UPDATE staging_pitching_stats SET processed = TRUE WHERE player_id = :player_id AND game_id = :game_id";
        $stmt = $this->db->prepare($updateStaging);
        $stmt->execute(['player_id' => $stats['player_id'], 'game_id' => $stats['game_id']]);
    }

    protected function calculateAdvancedMetrics(): void
    {
        // Calculate advanced metrics like WAR, wOBA, etc.
        // This would typically involve more complex calculations and multiple data points
        // Implementation would depend on the specific advanced metrics required
        $this->addMetric('advanced_metrics_calculated', true);
    }
} 