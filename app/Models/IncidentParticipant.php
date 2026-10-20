<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentParticipant extends Model
{
    protected $table = 'incident_students';

    protected $fillable = ['incident_id', 'student_id', 'role', 'notes'];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
