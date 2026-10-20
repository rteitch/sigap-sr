<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\School;
use App\Models\User;

class NotificationService
{
    public static function send(User $user, string $title, string $body, ?string $link = null): AppNotification
    {
        return AppNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'link' => $link,
        ]);
    }

    public static function sendToRole(School $school, string $role, string $title, string $body, ?string $link = null): void
    {
        $users = User::query()
            ->where('school_id', $school->id)
            ->where('role', $role)
            ->where('is_active', true)
            ->get();

        foreach ($users as $user) {
            self::send($user, $title, $body, $link);
        }
    }
}
