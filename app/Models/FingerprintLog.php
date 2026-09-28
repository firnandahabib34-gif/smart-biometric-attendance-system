<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FingerprintLog extends Model
{
    protected $fillable = [
        'student_id',
        'fingerprint_id',
        'event_type',
        'result',
        'device_id',
        'message',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
