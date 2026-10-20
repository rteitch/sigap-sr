<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentCategory extends Model
{
    protected $fillable = [
        'school_id', 'name', 'default_confidentiality', 'is_positive', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_positive' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'category_id');
    }
}
