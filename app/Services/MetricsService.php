<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class MetricsService
{
    private const TTL_SECONDS = 604800;

    /** @var array<int, string> */
    public const COUNTERS = [
        'http_requests_total',
        'http_responses_2xx_total',
        'http_responses_4xx_total',
        'http_responses_5xx_total',
        'http_request_duration_ms_total',
        'http_request_duration_ms_count',
        'login_success_total',
        'login_failed_total',
        'api_requests_total',
        'api_auth_failed_total',
        'circuit_breaker_open_total',
        'synthetic_checks_total',
        'synthetic_failures_total',
    ];

    public function increment(string $counter, int $value = 1): void
    {
        if (! in_array($counter, self::COUNTERS, true)) {
            return;
        }

        $key = $this->key($counter);
        Cache::add($key, 0, self::TTL_SECONDS);
        Cache::increment($key, $value);
    }

    public function observeHttp(int $status, float $durationMs): void
    {
        $this->increment('http_requests_total');
        $this->increment('http_request_duration_ms_total', max(0, (int) round($durationMs)));
        $this->increment('http_request_duration_ms_count');

        if ($status >= 500) {
            $this->increment('http_responses_5xx_total');
        } elseif ($status >= 400) {
            $this->increment('http_responses_4xx_total');
        } elseif ($status >= 200) {
            $this->increment('http_responses_2xx_total');
        }
    }

    /**
     * @return array<string, int>
     */
    public function snapshot(): array
    {
        $values = [];

        foreach (self::COUNTERS as $counter) {
            $values[$counter] = (int) Cache::get($this->key($counter), 0);
        }

        return $values;
    }

    public function renderPrometheus(): string
    {
        $lines = [];

        foreach ($this->snapshot() as $counter => $value) {
            $metric = 'sigap_'.$counter;
            $type = str_contains($counter, 'duration_ms') ? 'counter' : 'counter';
            $lines[] = '# HELP '.$metric.' SIGAP-SR application metric.';
            $lines[] = '# TYPE '.$metric.' '.$type;
            $lines[] = $metric.' '.$value;
        }

        $lines[] = '# HELP sigap_heartbeat_timestamp_seconds Last scheduler heartbeat timestamp.';
        $lines[] = '# TYPE sigap_heartbeat_timestamp_seconds gauge';
        $lines[] = 'sigap_heartbeat_timestamp_seconds '.(int) Cache::get('sigap:heartbeat_at', 0);

        return implode("\n", $lines)."\n";
    }

    public function reset(): void
    {
        foreach (self::COUNTERS as $counter) {
            Cache::forget($this->key($counter));
        }

        Cache::forget('sigap:heartbeat_at');
    }

    private function key(string $counter): string
    {
        return 'sigap:metrics:'.$counter;
    }
}
