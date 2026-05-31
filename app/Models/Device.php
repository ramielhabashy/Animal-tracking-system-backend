<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Device extends Model
{
    use HasFactory;
    public $translatable = ['name'];

    protected $fillable = [
        'device_id',
        'name',
        'type',
        'serial_number',
        'firmware_version',
        'battery_level',
        'signal_strength',
        'temperature',
        'speed',
        'is_lost',
        'status',
        'update_interval',
        'advanced_tracking',
        'animal_id',
        'owner_id',
        'gps_lat',
        'gps_lng',
        'last_ping',
        'last_temperature_update',
        'user_subscription_id',
        'data_source',
        'driver',
    ];

    protected $casts = [
        'battery_level' => 'integer',
        'signal_strength' => 'integer',
        'temperature' => 'float',
        'speed' => 'float',
        'is_lost' => 'boolean',
        'advanced_tracking' => 'boolean',
        'last_ping' => 'datetime',
        'last_temperature_update' => 'datetime',
        'data_source' => 'string',
        'driver' => 'string',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function userSubscription()
    {
        return $this->belongsTo(UserSubscription::class);
    }

    public function isSimulated(): bool
    {
        return $this->data_source === 'simulated';
    }

    public function isReal(): bool
    {
        return $this->data_source === 'real';
    }
}