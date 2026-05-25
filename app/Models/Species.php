<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Species extends Model
{
    protected $fillable = ['name', 'description', 'name_json', 'description_json', 'is_active', 'sort_order'];
    public $translatable = ['name', 'description'];
    public $casts = ['is_active' => 'boolean'];

    public function breeds()
    {
        return $this->hasMany(Breed::class);
    }
}