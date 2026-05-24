<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiQuickAction extends Model
{
    protected $fillable = [
        'role',
        'language',
        'type',
        'icon',
        'label',
        'prompt',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRole($query, ?string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->whereNull('role')->orWhere('role', $role);
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
