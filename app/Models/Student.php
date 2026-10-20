<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = [
        'school_id', 'classroom_id', 'dorm_id', 'code', 'gender', 'birth_date',
        'entry_date', 'status', 'special_needs', 'socioeconomic_desil',
        'consent_given_at', 'consent_scope', 'consent_document', 'consent_withdrawn_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'entry_date' => 'date',
            'consent_given_at' => 'datetime',
            'consent_withdrawn_at' => 'datetime',
            'socioeconomic_desil' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function dorm(): BelongsTo
    {
        return $this->belongsTo(Dorm::class);
    }

    public function identity(): HasOne
    {
        return $this->hasOne(StudentIdentity::class);
    }

    public function familyContacts(): HasMany
    {
        return $this->hasMany(FamilyContact::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function incidentLinks(): HasMany
    {
        return $this->hasMany(IncidentParticipant::class);
    }

    public function incidents(): BelongsToMany
    {
        return $this->belongsToMany(Incident::class, 'incident_students')
            ->withPivot(['role', 'notes'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isManagement()) {
            return $query;
        }

        $classIds = $user->homeroomClasses()->pluck('id');
        $dormIds = $user->dormAssignments()->pluck('id');

        if ($classIds->isEmpty() && $dormIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($classIds, $dormIds) {
            if ($classIds->isNotEmpty()) {
                $q->orWhereIn('classroom_id', $classIds);
            }
            if ($dormIds->isNotEmpty()) {
                $q->orWhereIn('dorm_id', $dormIds);
            }
        });
    }

    public function displayName(): string
    {
        return $this->identity?->full_name ?? $this->code;
    }

    public function hasActiveConsent(): bool
    {
        return $this->consent_given_at !== null && $this->consent_withdrawn_at === null;
    }
}
