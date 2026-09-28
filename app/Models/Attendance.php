<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'student_id',
        'attendance_date',
        'status',
        'check_in',
        'check_out',
        'source',
    ];

    protected function casts(): array
    {
        return [];
    }

    protected function attendanceDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? CarbonImmutable::parse($value) : null,
            set: fn (?string $value) => $value ? CarbonImmutable::parse($value)->toDateString() : null,
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
