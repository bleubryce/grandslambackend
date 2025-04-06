<?php

namespace BaseballAnalytics\DataCollection\Utils;

class RateLimiter
{
    private int $requestsPerMinute;
    private bool $pauseOnLimit;
    private array $requestTimes = [];

    public function __construct(int $requestsPerMinute, bool $pauseOnLimit = true)
    {
        $this->requestsPerMinute = $requestsPerMinute;
        $this->pauseOnLimit = $pauseOnLimit;
    }

    public function checkLimit(): bool
    {
        $now = microtime(true);
        $oneMinuteAgo = $now - 60;

        // Remove requests older than 1 minute
        $this->requestTimes = array_filter(
            $this->requestTimes,
            fn($time) => $time >= $oneMinuteAgo
        );

        if (count($this->requestTimes) >= $this->requestsPerMinute) {
            if ($this->pauseOnLimit) {
                $oldestRequest = min($this->requestTimes);
                $sleepTime = 60 - ($now - $oldestRequest);
                if ($sleepTime > 0) {
                    usleep($sleepTime * 1000000); // Convert to microseconds
                }
                return true;
            }
            return false;
        }

        $this->requestTimes[] = $now;
        return true;
    }

    public function getRemainingRequests(): int
    {
        $this->checkLimit(); // Clean up old requests
        return max(0, $this->requestsPerMinute - count($this->requestTimes));
    }

    public function reset(): void
    {
        $this->requestTimes = [];
    }
} 