<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Conversation extends Model
{
    protected $fillable = [
        'subject',
        'type',
        'status',
        'priority',
        'assigned_to_id',
        'created_by_id',
        'linkable_type',
        'linkable_id',
    ];

    protected $casts = [
        'type' => 'string',
        'status' => 'string',
        'priority' => 'string',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->hasRole('Admin')) return $query;

        return $query->whereHas('participants', fn ($q) => $q->where('user_id', $user->id));
    }

    public function isTicket(): bool
    {
        return $this->type === 'ticket';
    }

    public function unreadCountFor(int $userId): int
    {
        $pivot = $this->participants()->where('user_id', $userId)->first()?->pivot;
        if (!$pivot || !$pivot->last_read_at) return $this->messages()->count();

        return $this->messages()->where('created_at', '>', $pivot->last_read_at)
            ->where('sender_id', '!=', $userId)
            ->count();
    }
}
