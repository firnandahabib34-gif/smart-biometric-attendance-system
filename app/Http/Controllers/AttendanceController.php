<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\FingerprintLog;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function fingerprintForm(): View
    {
        return view('fingerprint.index', [
            'recentLogs' => FingerprintLog::with('student')->latest()->limit(12)->get(),
        ]);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'in:present,late,absent,excused'],
        ]);
        $month = $filters['month'] ?? now()->format('Y-m');
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $month.'-01');

        $attendances = Attendance::with('student')
            ->whereBetween('attendance_date', [$date->startOfMonth(), $date->endOfMonth()])
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest('attendance_date')
            ->latest('check_in')
            ->paginate(12)
            ->withQueryString();

        return view('attendance.index', [
            'attendances' => $attendances,
            'month' => $date->format('Y-m'),
            'students' => Student::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'distinct', 'exists:students,id'],
        ]);

        $studentIds = Student::query()
            ->where('is_active', true)
            ->whereIn('id', $validated['student_ids'])
            ->pluck('id');

        if ($studentIds->count() !== count($validated['student_ids'])) {
            return back()->withErrors(['student_ids' => 'Pilih mahasiswa yang masih aktif.'])->withInput();
        }

        $startTime = $request->user()->attendance_start ?: '07:30';
        $status = ! empty($validated['check_in']) && $validated['check_in'] > $startTime
            ? 'late'
            : 'present';
        $now = now();
        $existingCheckOuts = Attendance::query()
            ->whereDate('attendance_date', $validated['attendance_date'])
            ->whereIn('student_id', $studentIds)
            ->pluck('check_out', 'student_id');
        $rows = $studentIds->map(fn ($studentId) => [
            'student_id' => $studentId,
            'attendance_date' => $validated['attendance_date'],
            'status' => $status,
            'check_in' => $validated['check_in'] ?? null,
            'check_out' => $validated['check_out'] ?? $existingCheckOuts->get($studentId),
            'source' => 'manual',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('attendances')->upsert(
            $rows,
            ['student_id', 'attendance_date'],
            ['status', 'check_in', 'check_out', 'source', 'updated_at'],
        );

        return redirect()->route('attendance.index', [
            'month' => substr($validated['attendance_date'], 0, 7),
            'bulk_date' => $validated['attendance_date'],
            'bulk_check_in' => $validated['check_in'] ?? '',
            'bulk_check_out' => $validated['check_out'] ?? '',
        ])->with('success', count($rows).' absensi berhasil dicatat.');
    }

    public function recordFingerprint(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fingerprint_id' => ['required', 'string', 'max:100'],
        ]);

        $fingerprintId = trim($validated['fingerprint_id']);
        $student = Student::where('fingerprint_id', $fingerprintId)
            ->where('is_active', true)
            ->first();

        if (! $student) {
            FingerprintLog::create([
                'fingerprint_id' => $fingerprintId,
                'event_type' => 'attendance_scan',
                'result' => 'failed',
                'message' => 'ID fingerprint tidak terdaftar pada mahasiswa aktif.',
            ]);

            return back()->withErrors(['fingerprint_id' => 'ID fingerprint tidak ditemukan pada mahasiswa aktif.']);
        }

        $now = now();
        $attendance = Attendance::firstOrNew([
            'student_id' => $student->id,
            'attendance_date' => $now->toDateString(),
        ]);

        if (! $attendance->check_in) {
            $checkIn = $now->format('H:i:s');
            $startTime = $request->user()->attendance_start ?: '07:30';

            $attendance->fill([
                'status' => substr($checkIn, 0, 5) > $startTime ? 'late' : 'present',
                'check_in' => $checkIn,
                'source' => 'fingerprint',
            ]);
            $eventType = 'attendance_check_in';
            $message = 'Absensi masuk tercatat melalui ID fingerprint.';
        } elseif (! $attendance->check_out) {
            $attendance->check_out = $now->format('H:i:s');
            $attendance->source = 'fingerprint';
            $eventType = 'attendance_check_out';
            $message = 'Absensi pulang tercatat melalui ID fingerprint.';
        } else {
            FingerprintLog::create([
                'student_id' => $student->id,
                'fingerprint_id' => $fingerprintId,
                'event_type' => 'attendance_scan',
                'result' => 'ignored',
                'message' => 'Absensi masuk dan pulang hari ini sudah lengkap.',
            ]);

            return redirect()->route('fingerprint.index')
                ->withErrors(['fingerprint_id' => 'Absensi masuk dan pulang hari ini sudah lengkap.']);
        }

        $attendance->save();
        FingerprintLog::create([
            'student_id' => $student->id,
            'fingerprint_id' => $fingerprintId,
            'event_type' => $eventType,
            'result' => 'success',
            'message' => $message,
        ]);

        return redirect()->route('fingerprint.index')
            ->with('success', $student->name.' · '.$message);
    }

    public function generateMonth(Request $request): RedirectResponse
    {
        $validated = $request->validate(['month' => ['required', 'date_format:Y-m']]);
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $validated['month'].'-01');
        $students = Student::where('is_active', true)->pluck('id');
        $rows = [];

        $lastDay = $start->endOfMonth()->min(CarbonImmutable::today());

        // Seed only past and current weekdays; preserve any attendance already recorded.
        for ($day = $start; $day <= $lastDay; $day = $day->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }

            foreach ($students as $studentId) {
                $rows[] = [
                    'student_id' => $studentId,
                    'attendance_date' => $day->toDateString(),
                    'status' => 'absent',
                    'source' => 'generated',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($rows) === 250) {
                    DB::table('attendances')->insertOrIgnore($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            DB::table('attendances')->insertOrIgnore($rows);
        }

        return redirect()->route('attendance.index', ['month' => $validated['month']])
            ->with('success', 'Jadwal hari kerja berhasil dibuat. Absensi yang sudah ada tetap dipertahankan.');
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate(['month' => ['required', 'date_format:Y-m']]);
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $validated['month'].'-01');
        $rows = Attendance::with('student')
            ->whereBetween('attendance_date', [$start->startOfMonth(), $start->endOfMonth()])
            ->orderBy('attendance_date')
            ->orderBy('student_id')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NIM', 'Nama', 'Program Studi', 'Tanggal', 'Status', 'Jam Masuk', 'Jam Keluar', 'Sumber']);

            foreach ($rows as $row) {
                fputcsv($file, [
                    $row->student->student_number,
                    $row->student->name,
                    $row->student->program,
                    $row->attendance_date->format('Y-m-d'),
                    $row->status,
                    $row->check_in,
                    $row->check_out,
                    $row->source,
                ]);
            }

            fclose($file);
        }, 'laporan-absensi-'.$validated['month'].'.csv');
    }
}
