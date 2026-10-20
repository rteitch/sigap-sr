<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentFollowup extends Model
{
    protected $fillable = ['incident_id', 'week', 'due_at', 'status', 'notes', 'done_by', 'done_at'];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'done_at' => 'datetime',
            'week' => 'integer',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
