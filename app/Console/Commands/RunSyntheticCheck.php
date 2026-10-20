<?php

namespace App\Console\Commands;

use App\Services\MetricsService;
use App\Support\CircuitBreaker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class RunSyntheticCheck extends Command
{
    protected $signature = 'sigap:synthetic-check';

    protected $description = 'Run an external synthetic readiness check with a circuit breaker';

    public function handle(MetricsService $metrics): int
    {
        if (! config('sigap.synthetic.enabled')) {
            $this->line('Synthetic monitoring is disabled.');

            return self::SUCCESS;
        }

        $url = (string) config('sigap.synthetic.url');
        $timeout = max(1, (int) config('sigap.synthetic.timeout_seconds', 10));
        $breaker = new CircuitBreaker('synthetic', 3, 300);

        try {
            $breaker->attempt(function () use ($url, $timeout): void {
                $response = Http::timeout($timeout)
                    ->withHeaders(['X-Request-ID' => 'synthetic-'.Str::uuid()])
                    ->get($url);

                if (! $response->successful()) {
                    throw new \RuntimeException('Synthetic check returned HTTP '.$response->status().'.');
                }
            });
        } catch (Throwable $exception) {
            $metrics->increment('synthetic_failures_total');
            $this->error('Synthetic check failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $metrics->increment('synthetic_checks_total');
        $this->info('Synthetic check passed: '.$url);

        return self::SUCCESS;
    }
}
