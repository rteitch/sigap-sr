@php
    $branding = app(\App\Services\BrandingService::class)->for(auth()->user()?->school);
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full" style="@foreach ($branding['palette'] as $shade => $color)--color-brand-{{ $shade }}: {{ $color }}; @endforeach">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="{{ $branding['primary_color'] }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $branding['app_name'] }} — {{ $branding['tagline'] }}. Data anak dilindungi dan akses dibatasi.">
        <meta name="robots" content="noindex, nofollow">
        <meta name="developer" content="{{ $branding['developer']['name'] }}">
        <title inertia>{{ $branding['app_name'] }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead

        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="icon" href="{{ $branding['favicon_url'] }}">
        <link rel="apple-touch-icon" href="{{ $branding['favicon_url'] }}">
    </head>
    <body
        class="h-full bg-slate-50 text-slate-900 antialiased"
        data-branding="{{ json_encode($branding, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    >
        @inertia
    </body>
</html>
