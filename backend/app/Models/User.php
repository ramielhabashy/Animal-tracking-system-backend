<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'location',
        'role',
        'language',
        'subscription_tier',
        'subscription_status',
        'avatar_url',
        'is_active',
        'subscription_tier_id',
        'managed_by',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function animals()
    {
        return $this->hasMany(Animal::class, 'owner_id');
    }

    public function devices()
    {
        return $this->hasMany(Device::class, 'owner_id');
    }

    public function subscriptionTier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class, 'subscription_tier_id');
    }

    public function subscription(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->subscription()->where('status', 'active')->latest()->first();
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }

    public function shepherds(): HasMany
    {
        return $this->hasMany(User::class, 'managed_by')->where('role', 'Shepherd');
    }

    public function managers(): HasMany
    {
        return $this->hasMany(User::class, 'managed_by')->where('role', 'Manager');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'Owner';
    }

    public function isManager(): bool
    {
        return $this->role === 'Manager';
    }

    public function isShepherd(): bool
    {
        return $this->role === 'Shepherd';
    }

    public function isDoctor(): bool
    {
        return $this->role === 'Doctor';
    }

    public function canManage(User $user): bool
    {
        if ($this->isAdmin()) return true;
        if ($this->isOwner() && $user->managed_by === $this->id) return true;
        if ($this->isManager() && $user->managed_by === $this->id) return true;
        return false;
    }

    public function getAnimalCount(): int
    {
        return $this->animals()->count();
    }

    public function getDeviceCount(): int
    {
        return $this->devices()->count();
    }

    public function getUserCount(): int
    {
        return User::where('managed_by', $this->id)->count();
    }

    public function isOverAnimalLimit(): bool
    {
        $tier = $this->subscriptionTier;
        if (!$tier || $tier->max_animals === 0) return false;
        return $this->getAnimalCount() > $tier->max_animals;
    }

    public function isOverDeviceLimit(): bool
    {
        $tier = $this->subscriptionTier;
        if (!$tier || $tier->max_devices === 0) return false;
        return $this->getDeviceCount() > $tier->max_devices;
    }

    public function isOverUserLimit(): bool
    {
        $tier = $this->subscriptionTier;
        if (!$tier || $tier->max_users === 0) return false;
        return $this->getUserCount() > $tier->max_users;
    }
}
