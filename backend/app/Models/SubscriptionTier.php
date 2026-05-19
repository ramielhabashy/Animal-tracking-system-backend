<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionTier extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'trial_days',
        'max_animals',
        'max_devices',
        'max_users',
        'has_geofencing',
        'has_auctions',
        'has_medical_records',
        'has_tasks',
        'has_advanced_reports',
        'has_api_access',
        'has_ai_assistant',
        'sort_order',
        'is_active',
        'is_featured',
        'is_yearly_only',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'trial_days' => 'integer',
        'max_animals' => 'integer',
        'max_devices' => 'integer',
        'max_users' => 'integer',
        'has_geofencing' => 'boolean',
        'has_auctions' => 'boolean',
        'has_medical_records' => 'boolean',
        'has_tasks' => 'boolean',
        'has_advanced_reports' => 'boolean',
        'has_api_access' => 'boolean',
        'has_ai_assistant' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_yearly_only' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'subscription_tier_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'tier_id');
    }

    public function isFree(): bool
    {
        return $this->price_monthly == 0 && $this->price_yearly == 0;
    }

    public function isTrialActive(int $trialDays): bool
    {
        return $trialDays > 0;
    }
}