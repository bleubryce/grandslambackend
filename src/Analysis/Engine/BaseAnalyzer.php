<?php

namespace BaseballAnalytics\Analysis\Engine;

use BaseballAnalytics\Database\Connection;
use BaseballAnalytics\Utils\Logger;

abstract class BaseAnalyzer
{
    protected Connection $db;
    protected Logger $logger;
    protected array $config;
    protected array $analysisStats;
    protected array $results;

    public function __construct(Connection $db, array $config = [])
    {
        $this->db = $db;
        $this->logger = new Logger('analysis_engine');
        $this->config = $config;
        $this->initializeStats();
        $this->results = [];
    }

    abstract public function analyze(): bool;

    protected function initializeStats(): void
    {
        $this->analysisStats = [
            'started_at' => null,
            'ended_at' => null,
            'records_analyzed' => 0,
            'metrics_computed' => 0,
            'models_trained' => 0,
            'errors' => [],
            'warnings' => [],
            'performance_metrics' => []
        ];
    }

    protected function beginAnalysis(): void
    {
        $this->analysisStats['started_at'] = date('Y-m-d H:i:s');
        $this->logger->info(sprintf(
            "Starting analysis for %s",
            static::class
        ));
    }

    protected function endAnalysis(): void
    {
        $this->analysisStats['ended_at'] = date('Y-m-d H:i:s');
        $this->logger->info(sprintf(
            "Completed analysis for %s. Analyzed: %d, Metrics: %d, Models: %d, Errors: %d",
            static::class,
            $this->analysisStats['records_analyzed'],
            $this->analysisStats['metrics_computed'],
            $this->analysisStats['models_trained'],
            count($this->analysisStats['errors'])
        ));
    }

    protected function logError(string $message, \Throwable $e = null): void
    {
        $error = [
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if ($e !== null) {
            $error['exception'] = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        $this->analysisStats['errors'][] = $error;
        $this->logger->error($message, $error);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        $warning = [
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => $context
        ];

        $this->analysisStats['warnings'][] = $warning;
        $this->logger->warning($message, $warning);
    }

    protected function addPerformanceMetric(string $name, $value): void
    {
        $this->analysisStats['performance_metrics'][$name] = $value;
    }

    public function getAnalysisStats(): array
    {
        return $this->analysisStats;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    protected function validateConfig(array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (!isset($this->config[$field])) {
                $this->logError("Missing required configuration field: {$field}");
                return false;
            }
        }
        return true;
    }

    protected function incrementStat(string $stat): void
    {
        if (isset($this->analysisStats[$stat])) {
            $this->analysisStats[$stat]++;
        }
    }

    protected function calculateCorrelation(array $x, array $y): ?float
    {
        if (count($x) !== count($y) || count($x) < 2) {
            return null;
        }

        $n = count($x);
        $sum_x = array_sum($x);
        $sum_y = array_sum($y);
        $sum_xy = 0;
        $sum_x2 = 0;
        $sum_y2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sum_xy += ($x[$i] * $y[$i]);
            $sum_x2 += ($x[$i] * $x[$i]);
            $sum_y2 += ($y[$i] * $y[$i]);
        }

        $denominator = sqrt(($n * $sum_x2 - $sum_x * $sum_x) * ($n * $sum_y2 - $sum_y * $sum_y));
        
        if ($denominator == 0) {
            return null;
        }

        return ($n * $sum_xy - $sum_x * $sum_y) / $denominator;
    }

    protected function calculateMean(array $values): float
    {
        return array_sum($values) / count($values);
    }

    protected function calculateStandardDeviation(array $values): float
    {
        $mean = $this->calculateMean($values);
        $variance = array_reduce($values, function($carry, $item) use ($mean) {
            return $carry + pow($item - $mean, 2);
        }, 0) / count($values);
        
        return sqrt($variance);
    }

    protected function calculateZScore(float $value, float $mean, float $stdDev): float
    {
        return $stdDev != 0 ? ($value - $mean) / $stdDev : 0;
    }

    protected function calculatePercentile(array $values, float $percentile): float
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        $floor = floor($index);
        $fraction = $index - $floor;

        if ($fraction == 0) {
            return $values[$floor];
        }

        return $values[$floor] + ($values[$floor + 1] - $values[$floor]) * $fraction;
    }

    protected function calculateMovingAverage(array $values, int $window): array
    {
        $result = [];
        $count = count($values);

        for ($i = 0; $i <= $count - $window; $i++) {
            $slice = array_slice($values, $i, $window);
            $result[] = array_sum($slice) / $window;
        }

        return $result;
    }

    protected function saveResults(string $type, array $data): void
    {
        try {
            if ($this->beginTransaction()) {
                $sql = "INSERT INTO analysis_results (type, data, created_at) 
                        VALUES (:type, :data, NOW())";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'type' => $type,
                    'data' => json_encode($data)
                ]);

                $this->commitTransaction();
            }
        } catch (\PDOException $e) {
            $this->rollbackTransaction();
            $this->logError("Failed to save analysis results", $e);
        }
    }

    protected function beginTransaction(): bool
    {
        try {
            $this->db->beginTransaction();
            return true;
        } catch (\PDOException $e) {
            $this->logError("Failed to begin transaction", $e);
            return false;
        }
    }

    protected function commitTransaction(): bool
    {
        try {
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->logError("Failed to commit transaction", $e);
            return false;
        }
    }

    protected function rollbackTransaction(): void
    {
        try {
            $this->db->rollBack();
        } catch (\PDOException $e) {
            $this->logError("Failed to rollback transaction", $e);
        }
    }
} 