@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="page-heading">
        <div><h1>Ringkasan</h1><p>{{ now()->translatedFormat('l, d F Y') }}</p></div>
        <a class="button button-primary" href="{{ route('fingerprint.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M7 8.5a5 5 0 0 1 10 0c0 4.6-.8 8.1-2.4 11M4.6 10.5c0 3.4-.4 6-1.2 7.8M19.4 10.5c0 1.8-.1 3.4-.4 4.8M8.5 11c-.1 3.4-.5 5.7-1.4 7.5m5-6.5c0 2.6-.4 4.8-1.3 6.6"/></svg>Scan fingerprint</a>
    </div>

    <section class="stat-grid" aria-label="Statistik hari ini">
        <article class="stat-card"><div class="stat-top">Mahasiswa aktif <span class="stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.2"/><path stroke-linecap="round" d="M3.5 19v-1a5.5 5.5 0 0 1 11 0v1m2-9a3.2 3.2 0 0 0 0-6m1 15v-1a5.5 5.5 0 0 0-3.2-5"/></svg></span></div><div class="stat-value">{{ number_format($studentCount) }}</div><div class="stat-note">Terdaftar di sistem</div></article>
        <article class="stat-card"><div class="stat-top">Hadir hari ini <span class="stat-icon lime"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg></span></div><div class="stat-value">{{ number_format($todayCount) }}</div><div class="stat-note">Termasuk terlambat</div></article>
        <article class="stat-card"><div class="stat-top">Terlambat <span class="stat-icon coral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg></span></div><div class="stat-value">{{ number_format($lateCount) }}</div><div class="stat-note">Absensi hari ini</div></article>
        <article class="stat-card"><div class="stat-top">Fingerprint terdaftar <span class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M7 8.5a5 5 0 0 1 10 0c0 4.6-.8 8.1-2.4 11M4.6 10.5c0 3.4-.4 6-1.2 7.8M19.4 10.5c0 1.8-.1 3.4-.4 4.8M8.5 11c-.1 3.4-.5 5.7-1.4 7.5m5-6.5c0 2.6-.4 4.8-1.3 6.6"/></svg></span></div><div class="stat-value">{{ number_format($fingerprintCount) }}</div><div class="stat-note">ID template tersimpan</div></article>
    </section>

    <div class="dashboard-grid">
        <section class="panel">
            <div class="panel-heading"><div><div class="panel-title">Kehadiran 7 hari</div><div class="panel-subtitle">Jumlah mahasiswa hadir per hari</div></div><a class="text-link" href="{{ route('attendance.index') }}">Lihat laporan <span aria-hidden="true">→</span></a></div>
            <div class="chart" role="img" aria-label="Grafik kehadiran tujuh hari terakhir">
                @foreach ($chart as $item)
                    @php($barHeight = max(4, (int) (($item['total'] / $chartMaximum) * 142)))
                    <div class="chart-item" title="{{ $item['date'] }} · {{ $item['total'] }} mahasiswa">
                        <span class="chart-value">{{ $item['total'] ?: '' }}</span>
                        <svg class="chart-bar-wrap" viewBox="0 0 28 142" aria-hidden="true">
                            <rect class="chart-bar" x="0" y="{{ 142 - $barHeight }}" width="28" height="{{ $barHeight }}" rx="4" />
                        </svg>
                        <span class="chart-label">{{ $item['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="panel">
            <div class="panel-heading"><div><div class="panel-title">Aktivitas hari ini</div><div class="panel-subtitle">Absensi terbaru yang tercatat</div></div><a class="text-link" href="{{ route('attendance.index') }}">Semua <span aria-hidden="true">→</span></a></div>
            @if ($recentAttendances->isEmpty())
                <div class="empty-state">Belum ada absensi tercatat hari ini.</div>
            @else
                <div class="table-wrap"><table class="data-table"><thead><tr><th>Mahasiswa</th><th>Masuk</th><th>Status</th></tr></thead><tbody>
                    @foreach ($recentAttendances as $attendance)
                        <tr><td><div class="person-cell"><span class="avatar">{{ mb_substr($attendance->student->name, 0, 1) }}</span><span><span class="person-name">{{ $attendance->student->name }}</span><span class="person-id">{{ $attendance->student->student_number }}</span></span></div></td><td>{{ $attendance->check_in ? substr($attendance->check_in, 0, 5) : '—' }}</td><td><span class="badge badge-{{ $attendance->status }}">{{ ['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Alpha', 'excused' => 'Izin'][$attendance->status] }}</span></td></tr>
                    @endforeach
                </tbody></table></div>
            @endif
        </section>
    </div>
@endsection