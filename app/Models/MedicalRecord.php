<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    public $translatable = ['title', 'description', 'notes'];

    protected $fillable = [
        'record_id',
        'animal_id',
        'owner_id',
        'record_type',
        'title',
        'description',
        'record_date',
        'veterinarian',
        'medication',
        'dosage',
        'status',
        'health_status',
        'notes',
        'attachment_url',
        'next_follow_up',
    ];

    protected $casts = [
        'record_date' => 'date',
        'next_follow_up' => 'date',
        'health_status' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($record) {
            if (empty($record->record_id)) {
                $year = date('Y');
                $latest = static::where('record_id', 'like', "MR-{$year}-%")
                    ->orderByDesc('record_id')
                    ->first();

                $nextNum = 1;
                if ($latest) {
                    $parts = explode('-', $latest->record_id);
                    $nextNum = intval($parts[2] ?? 0) + 1;
                }

                $record->record_id = "MR-{$year}-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MedicalRecordAttachment::class);
    }

    public function scopeForOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeByType($query, $type)
    {
        if ($type && $type !== 'all') {
            return $query->where('record_type', $type);
        }
        return $query;
    }

    public function scopeByStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }
}