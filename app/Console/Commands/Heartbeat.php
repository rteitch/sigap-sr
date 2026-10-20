<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class Heartbeat extends Command
{
    protected $signature = 'sigap:heartbeat';

    protected $description = 'Record a scheduler heartbeat for SIGAP-SR health monitoring';

    public function handle(): int
    {
        Cache::put('sigap:heartbeat_at', time(), 86400);
        Cache::put('sigap:heartbeat_iso', now()->toIso8601String(), 86400);

        $this->info('Heartbeat recorded at '.now()->toIso8601String().'.');

        return self::SUCCESS;
    }
}
