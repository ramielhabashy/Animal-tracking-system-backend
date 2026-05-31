<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $fillable = [
        'label',
        'label_key',
        'icon',
        'path',
        'parent_id',
        'sort_order',
        'roles',
        'is_active',
    ];

    protected $casts = [
        'roles' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForRole($query, ?string $role)
    {
        if (!$role) return $query;

        return $query->where(function ($q) use ($role) {
            $q->whereNull('roles')
              ->orWhere('roles', '[]')
              ->orWhereJsonContains('roles', $role);
        });
    }
}
