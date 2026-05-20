<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowTestRun extends Model
{
    protected $fillable = [
        'status',
        'results',
        'summary',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'results' => 'array',
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
