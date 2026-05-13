<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskLogType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'allows_media',
        'is_status',
        'is_active',
    ];

    protected $casts = [
        'allows_media' => 'boolean',
        'is_status' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeStatuses($query)
    {
        return $query->where('is_status', true);
    }
}
