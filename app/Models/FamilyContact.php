<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyContact extends Model
{
    protected $fillable = ['student_id', 'name', 'relation', 'phone', 'is_primary', 'can_pickup', 'notes'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'can_pickup' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
