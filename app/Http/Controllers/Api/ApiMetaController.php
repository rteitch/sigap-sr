<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\BrandingService;
use Illuminate\Http\JsonResponse;

class ApiMetaController extends Controller
{
    public function show(BrandingService $branding): JsonResponse
    {
        $school = School::query()->orderBy('id')->first();
        $brand = $branding->for($school);

        return response()->json([
            'data' => [
                'name' => $brand['app_name'],
                'tagline' => $brand['tagline'],
                'version' => config('sigap.app_version'),
                'api_version' => 'v1',
                'license' => config('sigap.license'),
                'developer' => config('sigap.developer'),
                'school' => $school ? [
                    'name' => $school->name,
                    'level' => $school->level,
                    'npsn' => $school->npsn,
                ] : null,
            ],
            'links' => [
                'documentation' => url('/api/docs'),
                'openapi' => url('/api/openapi.json'),
                'health' => url('/health/ready'),
            ],
            'timestamp' => now()->toIso8601String(),
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
