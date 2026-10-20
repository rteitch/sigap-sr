<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public static function record(string $action, ?Model $subject = null, ?string $description = null, ?User $user = null): AuditLog
    {
        $user ??= Auth::user();
        $request = Request::instance();

        return AuditLog::create([
            'school_id' => $user?->school_id ?? $subject?->getAttribute('school_id'),
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
