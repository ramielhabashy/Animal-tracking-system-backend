<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Breed extends Model
{
    protected $fillable = ['name', 'description', 'name_json', 'description_json', 'species_id', 'is_active', 'sort_order'];
    public $translatable = ['name', 'description'];
    public $casts = ['is_active' => 'boolean'];

    public function species()
    {
        return $this->belongsTo(Species::class);
    }
}