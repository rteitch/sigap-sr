<?php

namespace App\Http\Controllers;

use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'sigap-sr',
            'version' => config('sigap.app_version'),
            'timestamp' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store');
    }

    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::select('select 1')),
            'cache' => $this->check(function (): void {
                Cache::put('sigap:health:probe', 'ok', 10);
                abort_unless(Cache::get('sigap:health:probe') === 'ok', 500);
                Cache::forget('sigap:health:probe');
            }),
            'storage' => $this->check(function (): void {
                Storage::disk('local')->put('health/probe.txt', 'ok');
                abort_unless(Storage::disk('local')->exists('health/probe.txt'), 500);
                Storage::disk('local')->delete('health/probe.txt');
            }),
        ];

        $healthy = ! in_array('fail', array_column($checks, 'status'), true);
        $status = $healthy ? 200 : 503;

        return response()->json([
            'status' => $healthy ? 'ready' : 'not_ready',
            'service' => 'sigap-sr',
            'version' => config('sigap.app_version'),
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $status)->header('Cache-Control', 'no-store');
    }

    public function heartbeat(): JsonResponse
    {
        $last = (int) Cache::get('sigap:heartbeat_at', 0);
        $ttl = max(30, (int) config('sigap.health.heartbeat_ttl_seconds', 180));
        $fresh = $last > 0 && (time() - $last) <= $ttl;

        return response()->json([
            'status' => $fresh ? 'ok' : 'stale',
            'last_heartbeat_at' => $last > 0 ? date(DATE_ATOM, $last) : null,
            'age_seconds' => $last > 0 ? time() - $last : null,
            'ttl_seconds' => $ttl,
            'scheduler' => $fresh ? 'healthy' : 'stale_or_not_running',
            'timestamp' => now()->toIso8601String(),
        ], $fresh ? 200 : 503)->header('Cache-Control', 'no-store');
    }

    public function metrics(Request $request, MetricsService $metrics): Response
    {
        $token = (string) config('sigap.health.metrics_token', '');
        $provided = (string) ($request->bearerToken() ?: $request->header('X-Metrics-Token', ''));

        abort_if($token === '' || $provided === '' || ! hash_equals($token, $provided), 403, 'Metrics token tidak valid.');

        return response($metrics->renderPrometheus(), 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function check(callable $callback): array
    {
        try {
            $callback();

            return ['status' => 'ok'];
        } catch (Throwable $exception) {
            return [
                'status' => 'fail',
                'message' => config('app.debug') ? $exception->getMessage() : 'Pemeriksaan gagal.',
            ];
        }
    }
}
