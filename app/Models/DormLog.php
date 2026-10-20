<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DormLog extends Model
{
    protected $fillable = [
        'school_id', 'dorm_id', 'log_date', 'shift', 'officer_id',
        'checkpoints', 'notes', 'handover_notes',
    ];

    protected function casts(): array
    {
        return [
            'checkpoints' => 'array',
        ];
    }

    public function dorm(): BelongsTo
    {
        return $this->belongsTo(Dorm::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }
}
