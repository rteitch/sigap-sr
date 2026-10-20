<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    protected $fillable = [
        'school_id', 'case_number', 'category_id', 'severity', 'occurred_at', 'location', 'incident_location_id',
        'description', 'initial_action', 'needs_medical', 'status', 'confidentiality',
        'reported_by', 'assigned_to', 'due_at', 'closed_at', 'sanction', 'resolution_notes',
        'is_revision', 'revision_of',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'due_at' => 'datetime',
            'closed_at' => 'datetime',
            'needs_medical' => 'boolean',
            'is_revision' => 'boolean',
            'severity' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'category_id');
    }

    public function locationInfo(): BelongsTo
    {
        return $this->belongsTo(IncidentLocation::class, 'incident_location_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(IncidentParticipant::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'incident_students')
            ->withPivot(['role', 'notes'])
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(IncidentTask::class)->orderBy('sort_order');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(IncidentFollowup::class)->orderBy('week');
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && ! in_array($this->status, ['selesai'], true)
            && $this->due_at->isPast();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['selesai']);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canViewConfidentialIncidents()) {
            return $query;
        }

        if ($user->isAdmin()) {
            return $query->where('confidentiality', '!=', 'sangat_rahasia');
        }

        $classIds = $user->homeroomClasses()->pluck('id');
        $dormIds = $user->dormAssignments()->pluck('id');

        return $query->where('confidentiality', 'normal')
            ->where(function (Builder $q) use ($user, $classIds, $dormIds) {
                $q->where('reported_by', $user->id)
                    ->orWhere('assigned_to', $user->id);

                if ($classIds->isNotEmpty()) {
                    $q->orWhereHas('students', fn (Builder $s) => $s->whereIn('classroom_id', $classIds));
                }
                if ($dormIds->isNotEmpty()) {
                    $q->orWhereHas('students', fn (Builder $s) => $s->whereIn('dorm_id', $dormIds));
                }
            });
    }

    public function canBeViewedBy(User $user): bool
    {
        if ($this->confidentiality === 'sangat_rahasia') {
            return $user->canViewConfidentialIncidents();
        }

        if ($user->canViewConfidentialIncidents() || $user->isAdmin()) {
            return true;
        }

        if ($this->reported_by === $user->id || $this->assigned_to === $user->id) {
            return true;
        }

        $classIds = $user->homeroomClasses()->pluck('id');
        $dormIds = $user->dormAssignments()->pluck('id');

        return $this->participants()
            ->whereHas('student', fn (Builder $q) => $q
                ->whereIn('classroom_id', $classIds)
                ->orWhereIn('dorm_id', $dormIds))
            ->exists();
    }
}
