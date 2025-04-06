<?php

namespace BaseballAnalytics\DataCollection;

use BaseballAnalytics\Database\Connection;
use BaseballAnalytics\Utils\Logger;

abstract class BaseCollector
{
    protected Connection $db;
    protected Logger $logger;
    protected array $config;
    protected array $lastRunStats;

    public function __construct(Connection $db, array $config = [])
    {
        $this->db = $db;
        $this->logger = new Logger('data_collection');
        $this->config = $config;
        $this->lastRunStats = [
            'started_at' => null,
            'ended_at' => null,
            'records_processed' => 0,
            'records_inserted' => 0,
            'records_updated' => 0,
            'errors' => [],
        ];
    }

    abstract public function collect(): bool;

    protected function beginCollection(): void
    {
        $this->lastRunStats['started_at'] = date('Y-m-d H:i:s');
        $this->logger->info(sprintf(
            "Starting collection for %s",
            static::class
        ));
    }

    protected function endCollection(): void
    {
        $this->lastRunStats['ended_at'] = date('Y-m-d H:i:s');
        $this->logger->info(sprintf(
            "Completed collection for %s. Processed: %d, Inserted: %d, Updated: %d, Errors: %d",
            static::class,
            $this->lastRunStats['records_processed'],
            $this->lastRunStats['records_inserted'],
            $this->lastRunStats['records_updated'],
            count($this->lastRunStats['errors'])
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

        $this->lastRunStats['errors'][] = $error;
        $this->logger->error($message, $error);
    }

    public function getLastRunStats(): array
    {
        return $this->lastRunStats;
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
        if (isset($this->lastRunStats[$stat])) {
            $this->lastRunStats[$stat]++;
        }
    }
} 