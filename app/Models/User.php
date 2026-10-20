<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'school_id', 'is_active', 'phone',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function homeroomClasses(): HasMany
    {
        return $this->hasMany(Classroom::class, 'homeroom_teacher_id');
    }

    public function dormAssignments(): HasMany
    {
        return $this->hasMany(Dorm::class, 'wali_id');
    }

    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reported_by');
    }

    public function assignedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'assigned_to');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isHeadmaster(): bool
    {
        return $this->role === 'kepala_sekolah';
    }

    public function isManagement(): bool
    {
        return in_array($this->role, ['admin', 'kepala_sekolah', 'tppk', 'bk'], true);
    }

    public function canViewConfidentialIncidents(): bool
    {
        return in_array($this->role, ['kepala_sekolah', 'tppk', 'bk'], true);
    }

    public function canManageIncidents(): bool
    {
        return in_array($this->role, ['kepala_sekolah', 'tppk', 'bk'], true);
    }

    public function canManageStudents(): bool
    {
        return in_array($this->role, ['admin', 'kepala_sekolah'], true);
    }

    public function canManageUsers(): bool
    {
        return $this->role === 'admin';
    }

    public function canViewAudit(): bool
    {
        return in_array($this->role, ['admin', 'kepala_sekolah'], true);
    }

    public function canViewStudentIdentity(Student $student): bool
    {
        if ($this->isManagement()) {
            return true;
        }

        return $student->classroom?->homeroom_teacher_id === $this->id
            || $student->dorm?->wali_id === $this->id;
    }

    public function roleLabel(): string
    {
        return config('sigap.roles.'.$this->role, $this->role);
    }
}
