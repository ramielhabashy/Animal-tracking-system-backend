<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordAttachment extends Model
{
    protected $fillable = [
        'medical_record_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
