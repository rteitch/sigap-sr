<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = ['name', 'npsn', 'level', 'address', 'phone', 'settings'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function incidentLocations(): HasMany
    {
        return $this->hasMany(IncidentLocation::class);
    }

    public function dorms(): HasMany
    {
        return $this->hasMany(Dorm::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
}
