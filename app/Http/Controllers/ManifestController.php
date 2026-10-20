<?php

namespace App\Http\Controllers;

use App\Services\BrandingService;
use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    public function __invoke(BrandingService $branding): JsonResponse
    {
        $branding = $branding->for(request()->user()?->school);
        $name = trim(($branding['app_name'] ?? 'SIGAP-SR').' — '.($branding['school_name'] ?? 'Sekolah Rakyat'));

        $icons = $branding['logo_url']
            ? [[
                'src' => $branding['logo_url'],
                'sizes' => 'any',
                'type' => $this->mimeType($branding['logo_url']),
                'purpose' => 'any',
            ]]
            : [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any'],
            ];

        return response()->json([
            'name' => $name,
            'short_name' => $branding['app_name'] ?? 'SIGAP-SR',
            'description' => ($branding['tagline'] ?? '').'. Data anak dilindungi dan akses dibatasi sesuai peran.',
            'lang' => 'id',
            'dir' => 'ltr',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#f8fafc',
            'theme_color' => $branding['primary_color'],
            'categories' => ['education', 'productivity'],
            'icons' => $icons,
            'shortcuts' => [
                [
                    'name' => 'Laporkan insiden',
                    'short_name' => 'Insiden',
                    'url' => '/insiden/buat',
                    'icons' => $icons,
                ],
                [
                    'name' => 'Presensi',
                    'short_name' => 'Presensi',
                    'url' => '/presensi',
                    'icons' => $icons,
                ],
            ],
        ])->header('Cache-Control', 'public, max-age=300');
    }

    private function mimeType(string $url): string
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? $url, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/jpeg',
        };
    }
}
