<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\FingerprintLog;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function fingerprintForm(): View
    {
        return view('fingerprint.index', [
            'recentLogs' => FingerprintLog::with('student')->latest()->limit(12)->get(),
            'classes' => config('academic.class_options'),
            'deviceApiConfigured' => filled(config('services.fingerprint.device_token')),
        ]);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month_number' => ['nullable', 'integer', 'between:1,12'],
            'day' => ['nullable', 'integer', 'between:1,31'],
            'class_name' => ['nullable', Rule::in(config('academic.class_options'))],
            'status' => ['nullable', 'in:present,late,absent,excused'],
        ]);
        $legacyMonth = isset($filters['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $filters['month'])
            : null;
        $year = (int) ($filters['year'] ?? $legacyMonth?->year ?? now()->year);
        $monthNumber = (int) ($filters['month_number'] ?? $legacyMonth?->month ?? now()->month);
        $hasPeriodFilter = isset($filters['year']) || isset($filters['month_number']) || $legacyMonth !== null;
        $day = isset($filters['day']) ? (int) $filters['day'] : ($hasPeriodFilter ? null : now()->day);

        if ($day !== null && ! checkdate($monthNumber, $day, $year)) {
            throw ValidationException::withMessages([
                'day' => 'Tanggal tidak valid untuk bulan dan tahun yang dipilih.',
            ]);
        }

        $periodStart = CarbonImmutable::createFromDate($year, $monthNumber, 1);
        $historyDays = DB::table('attendances')
            ->select('attendance_date')
            ->whereBetween('attendance_date', [$periodStart->startOfMonth(), $periodStart->endOfMonth()])
            ->when($day !== null, fn ($query) => $query->whereDay('attendance_date', $day))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['class_name']), fn ($query) => $query->whereExists(
                fn ($query) => $query->selectRaw('1')
                    ->from('students')
                    ->whereColumn('students.id', 'attendances.student_id')
                    ->where('students.class_name', $filters['class_name']),
            ))
            ->distinct()
            ->orderByDesc('attendance_date')
            ->paginate(1, ['attendance_date'], 'history_page')
            ->withQueryString();
        $dates = $historyDays->getCollection()->pluck('attendance_date');
        $attendances = Attendance::with('student')
            ->whereIn('attendance_date', $dates)
            ->where(function ($query) use ($filters): void {
                $query
                    ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
                    ->when(isset($filters['class_name']), fn ($query) => $query->whereHas(
                        'student',
                        fn ($query) => $query->where('class_name', $filters['class_name']),
                    ));
            })
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->orderByRaw($this->classOrderSql(), config('academic.class_options'))
            ->orderBy('students.name')
            ->select('attendances.*')
            ->get();

        return view('attendance.index', [
            'attendances' => $attendances,
            'historyDays' => $historyDays,
            'historyDate' => $dates->first(),
            'year' => $year,
            'monthNumber' => $monthNumber,
            'day' => $day,
            'classes' => config('academic.class_options'),
            'years' => $this->availableYears(),
            'daysInMonth' => $periodStart->daysInMonth,
            'periodLabel' => $periodStart->translatedFormat('F Y'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'day' => ['nullable', 'integer', 'between:1,31'],
            'class_name' => ['nullable', Rule::in(config('academic.class_options'))],
            'status' => ['nullable', 'in:present,late,absent,excused'],
        ]);
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $validated['month'].'-01');
        if (isset($validated['day']) && ! checkdate($start->month, (int) $validated['day'], $start->year)) {
            throw ValidationException::withMessages([
                'day' => 'Tanggal tidak valid untuk bulan dan tahun yang dipilih.',
            ]);
        }

        $rows = Attendance::with('student')
            ->whereBetween('attendance_date', [$start->startOfMonth(), $start->endOfMonth()])
            ->when(isset($validated['day']), fn ($query) => $query->whereDay('attendance_date', $validated['day']))
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['class_name']), fn ($query) => $query->whereHas(
                'student',
                fn ($query) => $query->where('class_name', $validated['class_name']),
            ))
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->orderByRaw($this->classOrderSql(), config('academic.class_options'))
            ->orderBy('attendance_date')
            ->orderBy('student_id')
            ->select('attendances.*')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NIM', 'Nama', 'Program Studi', 'Kelas', 'Tanggal', 'Status', 'Jam Masuk', 'Jam Keluar', 'Sumber']);

            foreach ($rows as $row) {
                fputcsv($file, [
                    $row->student->student_number,
                    $row->student->name,
                    $row->student->program,
                    $row->student->class_name,
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

    // ==============================================================
    // FUNGSI API UNTUK DEVICE IOT / WOKWI
    // ==============================================================
    public function recordDeviceFingerprint(Request $request)
    {
        if ($response = $this->authorizeDevice($request)) {
            return $response;
        }

        $validated = $request->validate([
            'fingerprint_id' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:check_in,check_out'],
            'device_id' => ['nullable', 'string', 'max:100'],
        ]);

        $student = Student::where('fingerprint_id', $validated['fingerprint_id'])
            ->where('is_active', true)
            ->first();

        if (! $student) {
            FingerprintLog::create([
                'fingerprint_id' => $validated['fingerprint_id'],
                'event_type' => 'attendance_scan',
                'result' => 'failed',
                'device_id' => $validated['device_id'] ?? null,
                'message' => 'Sidik jari tidak terdaftar pada mahasiswa aktif.',
            ]);

            return response()->json(['message' => 'Sidik jari tidak terdaftar'], 404);
        }

        $now = now();
        $attendance = Attendance::firstOrNew([
            'student_id' => $student->id,
            'attendance_date' => $now->toDateString(),
        ]);

        if ($validated['type'] === 'check_in') {
            if ($attendance->check_in) {
                return response()->json(['message' => 'Anda sudah check-in hari ini'], 400);
            }
            $attendance->check_in = $now->format('H:i:s');
            $startTime = User::query()->value('attendance_start') ?: '07:30';
            $attendance->status = $now->format('H:i') > $startTime ? 'late' : 'present';
            $attendance->source = 'fingerprint';
            $attendance->save();
            $eventType = 'attendance_check_in';

            $message = 'Check-in berhasil';
        } else {
            if (! $attendance->check_in) {
                return response()->json(['message' => 'Anda belum check-in hari ini'], 400);
            }
            if ($attendance->check_out) {
                return response()->json(['message' => 'Anda sudah check-out hari ini'], 400);
            }

            $attendance->check_out = $now->format('H:i:s');
            $attendance->save();
            $eventType = 'attendance_check_out';
            $message = 'Check-out berhasil';
        }

        FingerprintLog::create([
            'student_id' => $student->id,
            'fingerprint_id' => $validated['fingerprint_id'],
            'event_type' => $eventType,
            'result' => 'success',
            'device_id' => $validated['device_id'] ?? null,
            'message' => $message,
        ]);

        return response()->json(['message' => $message, 'data' => $student->name], 200);
    }

    public function registerDeviceFingerprint(Request $request): JsonResponse
    {
        if ($response = $this->authorizeDevice($request)) {
            return $response;
        }

        $validated = $request->validate([
            'student_number' => ['required', 'string', 'max:50'],
            'fingerprint_id' => ['required', 'string', 'max:100'],
            'device_id' => ['nullable', 'string', 'max:100'],
        ]);
        $student = Student::where('student_number', $validated['student_number'])->first();

        if (! $student || ! $student->is_active) {
            return response()->json(['message' => 'Mahasiswa aktif tidak ditemukan'], 404);
        }

        $request->validate([
            'fingerprint_id' => [Rule::unique('students')->ignore($student->id)],
        ]);
        $eventType = $student->fingerprint_id ? 'updated' : 'registered';
        $student->update([
            'fingerprint_id' => $validated['fingerprint_id'],
            'fingerprint_registered_at' => now(),
        ]);
        FingerprintLog::create([
            'student_id' => $student->id,
            'fingerprint_id' => $validated['fingerprint_id'],
            'event_type' => $eventType,
            'result' => 'success',
            'device_id' => $validated['device_id'] ?? null,
            'message' => 'Fingerprint didaftarkan melalui perangkat.',
        ]);

        return response()->json(['message' => 'Fingerprint berhasil didaftarkan', 'data' => $student->name]);
    }

    private function authorizeDevice(Request $request): ?JsonResponse
    {
        $token = config('services.fingerprint.device_token');
        if (! is_string($token) || $token === '') {
            return response()->json(['message' => 'Token perangkat belum dikonfigurasi'], 503);
        }

        $providedToken = $request->bearerToken();
        if (! is_string($providedToken) || ! hash_equals($token, $providedToken)) {
            return response()->json(['message' => 'Token perangkat tidak valid'], 401);
        }

        return null;
    }

    private function availableYears(): array
    {
        $oldestDate = Attendance::min('attendance_date');
        $newestDate = Attendance::max('attendance_date');
        $oldestYear = $oldestDate ? CarbonImmutable::parse($oldestDate)->year : now()->year;
        $newestYear = $newestDate ? CarbonImmutable::parse($newestDate)->year : now()->year;

        return range(max(now()->year, $newestYear), min(now()->year, $oldestYear));
    }

    private function classOrderSql(): string
    {
        $cases = collect(config('academic.class_options'))
            ->map(fn (string $className, int $index) => 'WHEN ? THEN '.($index + 1))
            ->implode(' ');

        return "CASE students.class_name {$cases} ELSE 999 END";
    }
}
