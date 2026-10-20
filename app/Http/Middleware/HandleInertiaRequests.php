<?php

namespace App\Http\Middleware;

use App\Models\AppNotification;
use App\Services\BrandingService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $branding = app(BrandingService::class)->for($user?->school);

        return [
            ...parent::share($request),
            'branding' => $branding,
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_label' => $user->roleLabel(),
                    'school_id' => $user->school_id,
                    'is_management' => $user->isManagement(),
                    'can_view_confidential' => $user->canViewConfidentialIncidents(),
                    'can_manage_incidents' => $user->canManageIncidents(),
                    'can_manage_students' => $user->canManageStudents(),
                    'can_manage_users' => $user->canManageUsers(),
                    'can_view_audit' => $user->canViewAudit(),
                    'can_access_dorm' => $user->isManagement() || $user->dormAssignments()->exists(),
                ] : null,
            ],
            'school' => $user?->school ? [
                'id' => $user->school->id,
                'name' => $user->school->name,
                'npsn' => $user->school->npsn,
                'address' => $user->school->address,
                'phone' => $user->school->phone,
                'settings' => $user->school->settings,
            ] : null,
            'options' => config('sigap'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'notifications' => fn () => $user
                ? AppNotification::query()
                    ->where('user_id', $user->id)
                    ->latest()
                    ->limit(8)
                    ->get(['id', 'title', 'body', 'link', 'read_at', 'created_at'])
                : [],
            'notificationCount' => fn () => $user
                ? AppNotification::query()
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count()
                : 0,
            'today' => now()->toDateString(),
        ];
    }
}
