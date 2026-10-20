<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dorm extends Model
{
    protected $fillable = ['school_id', 'name', 'gender', 'wali_id', 'capacity', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function wali(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DormLog::class);
    }
}
