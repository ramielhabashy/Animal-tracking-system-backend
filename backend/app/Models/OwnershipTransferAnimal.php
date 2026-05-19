<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OwnershipTransferAnimal extends Pivot
{
    protected $table = 'ownership_transfer_animals';

    protected $fillable = [
        'ownership_transfer_id', 'animal_id',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(OwnershipTransfer::class, 'ownership_transfer_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
