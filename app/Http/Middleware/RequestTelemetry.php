<?php

namespace App\Http\Middleware;

use App\Services\MetricsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RequestTelemetry
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) $request->headers->get('X-Request-ID', '');

        if (preg_match('/^[A-Za-z0-9._-]{8,64}$/', $requestId) !== 1) {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set('request_id', $requestId);
        $startedAt = hrtime(true);
        $response = $next($request);
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        $response->headers->set('X-Request-ID', $requestId);

        if (! $request->is('health', 'health/*')) {
            try {
                app(MetricsService::class)->observeHttp($response->getStatusCode(), $durationMs);
            } catch (Throwable) {
                // Telemetry must never break application requests.
            }
        }

        if ((bool) config('sigap.telemetry.enabled', true)) {
            Log::info('request.completed', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => '/'.ltrim($request->path(), '/'),
                'route' => $request->route()?->getName(),
                'status' => $response->getStatusCode(),
                'duration_ms' => round($durationMs, 2),
                'user_id' => $request->user()?->id,
            ]);
        }

        return $response;
    }
}
