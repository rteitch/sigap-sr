<?php

use App\Console\Commands\Heartbeat;
use Illuminate\Support\Facades\Schedule;

Schedule::command(Heartbeat::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->name('sigap-heartbeat');

Schedule::command('sigap:synthetic-check')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('sigap.synthetic.enabled'))
    ->name('sigap-synthetic');
