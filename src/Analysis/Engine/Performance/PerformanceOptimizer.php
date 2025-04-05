<?php

namespace BaseballAnalytics\Analysis\Engine\Performance;

use Phpml\ModelManager;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Process\Process;

class PerformanceOptimizer
{
    private const CACHE_NAMESPACE = 'baseball_analytics';
    private const CACHE_LIFETIME = 3600; // 1 hour default
    private const BATCH_SIZE = 1000;

    private CacheItemPoolInterface $cache;
    private array $config;
    private array $metrics = [];
    private array $activeProcesses = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->cache = new FilesystemAdapter(
            self::CACHE_NAMESPACE,
            $config['cache_lifetime'] ?? self::CACHE_LIFETIME,
            $config['cache_directory'] ?? sys_get_temp_dir()
        );
        $this->initializeMetrics();
    }

    private function initializeMetrics(): void
    {
        $this->metrics = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'batches_processed' => 0,
            'parallel_processes' => 0,
            'processing_time' => 0.0
        ];
    }

    public function getCachedResult(string $key): mixed
    {
        $cacheItem = $this->cache->getItem($key);
        
        if ($cacheItem->isHit()) {
            $this->metrics['cache_hits']++;
            return $cacheItem->get();
        }
        
        $this->metrics['cache_misses']++;
        return null;
    }

    public function cacheResult(string $key, mixed $data, int $lifetime = null): void
    {
        $cacheItem = $this->cache->getItem($key);
        $cacheItem->set($data);
        
        if ($lifetime !== null) {
            $cacheItem->expiresAfter($lifetime);
        }
        
        $this->cache->save($cacheItem);
    }

    public function processBatch(array $data, callable $processor): array
    {
        $results = [];
        $batches = array_chunk($data, $this->config['batch_size'] ?? self::BATCH_SIZE);
        
        foreach ($batches as $batch) {
            $startTime = microtime(true);
            $results = array_merge($results, $processor($batch));
            $this->metrics['processing_time'] += microtime(true) - $startTime;
            $this->metrics['batches_processed']++;
        }
        
        return $results;
    }

    public function processParallel(array $tasks, string $scriptPath): array
    {
        $maxThreads = $this->config['max_threads'] ?? 4;
        $results = [];
        $chunks = array_chunk($tasks, ceil(count($tasks) / $maxThreads));
        
        foreach ($chunks as $index => $chunk) {
            $process = new Process([
                'php',
                $scriptPath,
                '--chunk=' . $index,
                '--data=' . base64_encode(serialize($chunk))
            ]);
            
            $process->start();
            $this->activeProcesses[] = $process;
            $this->metrics['parallel_processes']++;
            
            // Limit concurrent processes
            if (count($this->activeProcesses) >= $maxThreads) {
                $this->waitForProcesses();
            }
        }
        
        // Wait for remaining processes
        $this->waitForProcesses();
        
        return $this->collectResults();
    }

    private function waitForProcesses(): void
    {
        foreach ($this->activeProcesses as $index => $process) {
            $process->wait();
            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                if ($output) {
                    $this->processOutput($output);
                }
            }
            unset($this->activeProcesses[$index]);
        }
    }

    private function processOutput(string $output): void
    {
        $data = unserialize(base64_decode($output));
        if ($data && isset($data['results'])) {
            // Store results for later collection
            $this->cacheResult(
                'parallel_results_' . uniqid(),
                $data['results'],
                300 // 5 minutes
            );
        }
    }

    private function collectResults(): array
    {
        $results = [];
        $pattern = 'parallel_results_*';
        
        foreach ($this->cache->getItems([$pattern]) as $item) {
            if ($item->isHit()) {
                $results = array_merge($results, $item->get());
                $this->cache->deleteItem($item->getKey());
            }
        }
        
        return $results;
    }

    public function optimizeModelStorage(string $modelPath): void
    {
        $modelManager = new ModelManager();
        $files = glob($modelPath . '/*.model');
        
        foreach ($files as $file) {
            $model = $modelManager->restoreFromFile($file);
            
            // Compress and optimize model
            if ($this->config['compression'] ?? true) {
                $compressedData = gzcompress(serialize($model), 9);
                file_put_contents($file . '.gz', $compressedData);
                unlink($file); // Remove original file
            }
        }
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function clearCache(): void
    {
        $this->cache->clear();
    }

    public function isProcessingComplete(): bool
    {
        return empty($this->activeProcesses);
    }
} 