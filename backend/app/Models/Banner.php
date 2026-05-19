<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Banner extends Model
{
    protected $fillable = [
        'type',
        'icon',
        'color_scheme',
        'translations',
        'button_text',
        'button_url',
        'sort_order',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeByType(Builder $query, string|array $types): Builder
    {
        return $query->whereIn('type', (array) $types);
    }

    public function getTitle(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $translations = $this->translations ?? [];
        return $translations[$locale]['title']
            ?? $translations['en']['title']
            ?? null;
    }

    public function getDescription(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $translations = $this->translations ?? [];
        return $translations[$locale]['description']
            ?? $translations['en']['description']
            ?? null;
    }

    public function getButtonText(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $translations = $this->translations ?? [];
        return $translations[$locale]['button_text']
            ?? $translations['en']['button_text']
            ?? $this->button_text
            ?? null;
    }
}
