<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnershipHistory extends Model
{
    protected $table = 'ownership_history';

    public $timestamps = false;

    protected $fillable = [
        'animal_id', 'from_user_id', 'to_user_id', 'transfer_id',
        'transfer_type', 'reference_type', 'reference_id',
        'commission_amount', 'agreed_price', 'metadata', 'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'commission_amount' => 'decimal:2',
        'agreed_price' => 'decimal:2',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(OwnershipTransfer::class, 'transfer_id');
    }
}
