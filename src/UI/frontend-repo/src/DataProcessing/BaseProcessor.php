<?php

namespace BaseballAnalytics\DataProcessing;

use BaseballAnalytics\Database\Connection;
use BaseballAnalytics\Utils\Logger;

abstract class BaseProcessor
{
    protected Connection $db;
    protected Logger $logger;
    protected array $config;
    protected array $processingStats;

    public function __construct(Connection $db, array $config = [])
    {
        $this->db = $db;
        $this->logger = new Logger('data_processing');
        $this->config = $config;
        $this->initializeStats();
    }

    abstract public function process(): bool;

    protected function initializeStats(): void
    {
        $this->processingStats = [
            'started_at' => null,
            'ended_at' => null,
            'records_processed' => 0,
            'records_transformed' => 0,
            'records_skipped' => 0,
            'errors' => [],
            'warnings' => [],
            'metrics' => []
        ];
    }

    protected function beginProcessing(): void
    {
        $this->processingStats['started_at'] = date('Y-m-d H:i:s');
        $this->logger->info(sprintf(
            "Starting processing for %s",
            static::class
        ));
    }

    protected function endProcessing(): void
    {
        $this->processingStats['ended_at'] = date('Y-m-d H:i:s');
        $this->logger->info(sprintf(
            "Completed processing for %s. Processed: %d, Transformed: %d, Skipped: %d, Errors: %d",
            static::class,
            $this->processingStats['records_processed'],
            $this->processingStats['records_transformed'],
            $this->processingStats['records_skipped'],
            count($this->processingStats['errors'])
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

        $this->processingStats['errors'][] = $error;
        $this->logger->error($message, $error);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        $warning = [
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => $context
        ];

        $this->processingStats['warnings'][] = $warning;
        $this->logger->warning($message, $warning);
    }

    protected function addMetric(string $name, $value): void
    {
        $this->processingStats['metrics'][$name] = $value;
    }

    public function getProcessingStats(): array
    {
        return $this->processingStats;
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
        if (isset($this->processingStats[$stat])) {
            $this->processingStats[$stat]++;
        }
    }

    protected function validateData(array $data, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $this->logWarning("Missing required field in data: {$field}", ['data' => $data]);
                return false;
            }
        }
        return true;
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