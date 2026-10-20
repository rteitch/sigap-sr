<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->only(['q', 'action', 'date']);

        $logs = AuditLog::query()
            ->where('school_id', $request->user()->school_id)
            ->with('user:id,name')
            ->when($filters['q'] ?? null, function ($query, string $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('description', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', $date))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name ?? 'Sistem',
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        $actions = AuditLog::query()
            ->where('school_id', $request->user()->school_id)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return Inertia::render('Admin/Audit/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $actions,
        ]);
    }
}
