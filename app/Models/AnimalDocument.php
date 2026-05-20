<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalDocument extends Model
{
    protected $fillable = [
        'animal_id',
        'type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'notes',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
