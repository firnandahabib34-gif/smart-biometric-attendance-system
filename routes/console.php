<?php

use App\Models\Student;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    $attendanceDate = now()->toDateString();
    $now = now();

    Student::query()
        ->where('is_active', true)
        ->select('id')
        ->chunkById(250, function ($students) use ($attendanceDate, $now): void {
            $rows = $students->map(fn (Student $student) => [
                'student_id' => $student->id,
                'attendance_date' => $attendanceDate,
                'status' => 'absent',
                'source' => 'generated',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::table('attendances')->insertOrIgnore($rows);
        });
})->weekdays()->dailyAt('23:59')->name('generate-daily-absences')->withoutOverlapping();
