<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = Carbon::today();
        $weekStart = $today->copy()->subDays(6);

        $weekly = Attendance::query()
            ->selectRaw('attendance_date, COUNT(*) as total')
            ->whereBetween('attendance_date', [$weekStart, $today])
            ->whereIn('status', ['present', 'late'])
            ->groupBy('attendance_date')
            ->pluck('total', 'attendance_date');

        $chart = collect(range(6, 0))->map(function (int $daysAgo) use ($today, $weekly) {
            $date = $today->copy()->subDays($daysAgo);

            return [
                'label' => $date->translatedFormat('D'),
                'date' => $date->toDateString(),
                'total' => (int) ($weekly[$date->toDateString()] ?? 0),
            ];
        });

        return view('dashboard', [
            'studentCount' => Student::where('is_active', true)->count(),
            'todayCount' => Attendance::whereDate('attendance_date', $today)
                ->whereIn('status', ['present', 'late'])->count(),
            'lateCount' => Attendance::whereDate('attendance_date', $today)
                ->where('status', 'late')->count(),
            'fingerprintCount' => Student::whereNotNull('fingerprint_id')->count(),
            'recentAttendances' => Attendance::with('student')
                ->whereDate('attendance_date', $today)
                ->latest('updated_at')
                ->limit(6)
                ->get(),
            'chart' => $chart,
            'chartMaximum' => max(1, (int) $chart->max('total')),
        ]);
    }
}
