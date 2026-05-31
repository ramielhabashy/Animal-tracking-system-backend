<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionOrder extends Model
{
    protected $fillable = [
        'user_id',
        'tier_id',
        'user_subscription_id',
        'amount',
        'currency',
        'billing_cycle',
        'shipping_address',
        'shipping_status',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'payment_method',
        'payment_status',
        'stripe_session_id',
        'payment_intent_id',
        'payment_reference',
        'activated_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'shipping_address' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class, 'tier_id');
    }

    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class);
    }
}
