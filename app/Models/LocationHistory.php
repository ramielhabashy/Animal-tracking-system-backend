<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationHistory extends Model
{
    protected $table = 'location_history';
    
    protected $fillable = [
        'device_id',
        'animal_id',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'recorded_at',
        'data_source',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
        'recorded_at' => 'datetime',
        'data_source' => 'string',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
