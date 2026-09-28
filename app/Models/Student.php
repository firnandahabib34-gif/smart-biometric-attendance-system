<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_number',
        'name',
        'department',
        'email',
        'program',
        'class_name',
        'phone',
        'fingerprint_id',
        'fingerprint_registered_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fingerprint_registered_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function fingerprintLogs(): HasMany
    {
        return $this->hasMany(FingerprintLog::class);
    }
}
