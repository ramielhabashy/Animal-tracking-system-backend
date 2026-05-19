<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OwnershipTransfer extends Model
{
    protected $fillable = [
        'from_user_id', 'to_user_id', 'status', 'transfer_type',
        'reference_type', 'reference_id', 'agreed_price',
        'commission_percentage', 'commission_amount', 'commission_paid',
        'notes', 'rejection_reason', 'expires_at',
        'accepted_at', 'completed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
        'commission_paid' => 'boolean',
        'agreed_price' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    protected $appends = ['animal_count'];

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function animals(): BelongsToMany
    {
        return $this->belongsToMany(Animal::class, 'ownership_transfer_animals');
    }

    public function historyEntries(): HasMany
    {
        return $this->hasMany(OwnershipHistory::class, 'transfer_id');
    }

    public function getAnimalCountAttribute(): int
    {
        return $this->animals()->count();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('from_user_id', $userId)->orWhere('to_user_id', $userId);
        });
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
