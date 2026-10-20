<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Services\MetricsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $metrics = app(MetricsService::class);
        $configuredKey = (string) config('sigap.api.key', '');
        $providedKey = (string) ($request->header('X-API-Key') ?: $request->bearerToken() ?: '');

        if ($configuredKey === '') {
            return response()->json([
                'message' => 'API belum dikonfigurasi. Atur SIGAP_API_KEY.',
            ], 503);
        }

        if ($providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            $metrics->increment('api_auth_failed_total');

            return response()->json([
                'message' => 'Kunci API tidak valid.',
            ], 401, ['WWW-Authenticate' => 'Bearer']);
        }

        $schoolId = config('sigap.api.school_id');
        $school = $schoolId
            ? School::query()->find((int) $schoolId)
            : School::query()->orderBy('id')->first();

        if (! $school) {
            return response()->json([
                'message' => 'Sekolah untuk API tidak ditemukan.',
            ], 503);
        }

        $request->attributes->set('api_school', $school);
        $metrics->increment('api_requests_total');

        return $next($request);
    }
}
