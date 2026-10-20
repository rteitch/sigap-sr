<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentIdentity extends Model
{
    protected $fillable = [
        'student_id',
        'full_name',
        'nickname',
        'birth_place',
        'address',
        'notes',
        'photo_path',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return '/uploads/'.ltrim($this->photo_path, '/');
    }
}
