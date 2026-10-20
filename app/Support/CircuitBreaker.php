<?php

namespace App\Support;

use App\Services\MetricsService;
use Closure;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class CircuitBreaker
{
    public function __construct(
        private string $name,
        private int $failureThreshold = 5,
        private int $cooldownSeconds = 60,
    ) {}

    public function isOpen(): bool
    {
        return (int) Cache::get($this->key('open_until'), 0) > time();
    }

    public function failures(): int
    {
        return (int) Cache::get($this->key('failures'), 0);
    }

    public function recordSuccess(): void
    {
        Cache::forget($this->key('failures'));
        Cache::forget($this->key('open_until'));
    }

    public function recordFailure(): void
    {
        $key = $this->key('failures');
        Cache::add($key, 0, 86400);
        $failures = (int) Cache::increment($key);

        if ($failures >= $this->failureThreshold) {
            Cache::put($this->key('open_until'), time() + $this->cooldownSeconds, $this->cooldownSeconds);
            app(MetricsService::class)->increment('circuit_breaker_open_total');
        }
    }

    public function attempt(Closure $action, ?Closure $fallback = null): mixed
    {
        if ($this->isOpen()) {
            if ($fallback !== null) {
                return $fallback(new RuntimeException('Circuit breaker is open.'));
            }

            throw new RuntimeException('Circuit breaker is open.');
        }

        try {
            $result = $action();
            $this->recordSuccess();

            return $result;
        } catch (Throwable $exception) {
            $this->recordFailure();

            if ($fallback !== null) {
                return $fallback($exception);
            }

            throw $exception;
        }
    }

    public function reset(): void
    {
        Cache::forget($this->key('failures'));
        Cache::forget($this->key('open_until'));
    }

    private function key(string $suffix): string
    {
        return 'sigap:circuit:'.$this->name.':'.$suffix;
    }
}
