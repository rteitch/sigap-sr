<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\MetricsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            app(MetricsService::class)->increment('login_failed_total');

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            app(MetricsService::class)->increment('login_failed_total');

            throw ValidationException::withMessages([
                'email' => 'Akun ini sedang dinonaktifkan. Hubungi administrator sekolah.',
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        app(MetricsService::class)->increment('login_success_total');

        AuditLogger::record('auth.login', $user, 'Pengguna masuk ke aplikasi.', $user);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLogger::record('auth.logout', $request->user(), 'Pengguna keluar dari aplikasi.');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
