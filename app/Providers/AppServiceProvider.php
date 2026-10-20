<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        Gate::define('manage-users', fn (User $user): bool => $user->canManageUsers());
        Gate::define('manage-students', fn (User $user): bool => $user->canManageStudents());
        Gate::define('manage-incidents', fn (User $user): bool => $user->canManageIncidents());
        Gate::define('view-audit', fn (User $user): bool => $user->canViewAudit());
        Gate::define('view-confidential-incidents', fn (User $user): bool => $user->canViewConfidentialIncidents());

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('health', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
