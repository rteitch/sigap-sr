<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Database\QueryException;

class BrandingService
{
    /** @var array<string, array<string, mixed>> */
    private array $memo = [];

    /**
     * @return array<string, mixed>
     */
    public function for(?School $school = null): array
    {
        $key = $school?->id !== null ? 'school:'.$school->id : 'default';

        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        try {
            $school ??= School::query()->orderBy('id')->first();
        } catch (QueryException) {
            $school = null;
        }

        $settings = $school?->settings ?? [];
        $primary = $this->normalizeColor($settings['primary_color'] ?? config('sigap.branding.primary_color'));
        $logoPath = $settings['logo_path'] ?? null;
        $logoUrl = filled($logoPath) ? asset('uploads/'.ltrim((string) $logoPath, '/')) : null;

        return $this->memo[$key] = [
            'app_name' => $settings['app_name'] ?? config('sigap.branding.app_name'),
            'tagline' => $settings['tagline'] ?? config('sigap.branding.tagline'),
            'school_name' => $school?->name,
            'school_level' => $school?->level,
            'logo_url' => $logoUrl,
            'favicon_url' => $logoUrl ?? asset('icon.svg'),
            'primary_color' => $primary,
            'palette' => $this->palette($primary),
            'developer' => config('sigap.developer'),
            'license' => config('sigap.license'),
            'version' => config('sigap.app_version'),
            'demo' => [
                'enabled' => (bool) config('sigap.demo.enabled'),
                'password' => config('sigap.demo.password'),
                'accounts' => config('sigap.demo.enabled') ? config('sigap.demo.accounts') : [],
            ],
        ];
    }

    public function normalizeColor(?string $color): string
    {
        $color = trim((string) $color);

        if (preg_match('/^#([0-9a-fA-F]{3})$/', $color, $matches) === 1) {
            return '#'.strtolower($matches[1][0].$matches[1][0].$matches[1][1].$matches[1][1].$matches[1][2].$matches[1][2]);
        }

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1) {
            return strtolower($color);
        }

        return (string) config('sigap.branding.primary_color', '#0f766e');
    }

    /**
     * @return array<int, string>
     */
    public function palette(string $primary): array
    {
        $rgb = $this->hexToRgb($primary);
        $white = [255, 255, 255];
        $black = [0, 0, 0];

        return [
            50 => $this->mix($rgb, $white, 0.92),
            100 => $this->mix($rgb, $white, 0.86),
            200 => $this->mix($rgb, $white, 0.72),
            500 => $this->mix($rgb, $white, 0.12),
            600 => $primary,
            700 => $this->mix($rgb, $black, 0.18),
            800 => $this->mix($rgb, $black, 0.32),
            900 => $this->mix($rgb, $black, 0.45),
            950 => $this->mix($rgb, $black, 0.62),
        ];
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($this->normalizeColor($hex), '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     * @param  array{0: int, 1: int, 2: int}  $target
     */
    private function mix(array $rgb, array $target, float $targetWeight): string
    {
        $channels = [];

        foreach ($rgb as $index => $value) {
            $channels[] = (int) round($value * (1 - $targetWeight) + $target[$index] * $targetWeight);
        }

        return sprintf('#%02x%02x%02x', $channels[0], $channels[1], $channels[2]);
    }
}
