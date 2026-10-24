@extends('layouts.admin')

@section('title', 'Absensi & laporan')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Absensi &amp; laporan</h1>
            <p>Riwayat {{ $day ? \Carbon\CarbonImmutable::createFromDate($year, $monthNumber, $day)->translatedFormat('l, d F Y') : ($historyDate ? \Carbon\CarbonImmutable::parse($historyDate)->translatedFormat('l, d F Y') : 'harian · '.$periodLabel) }}</p>
        </div>
    </div>

    <section class="panel" style="margin-bottom:16px">
        <div class="panel-heading">
            <div><div class="panel-title">Pilih periode laporan</div><div class="panel-subtitle">Telusuri tahun, bulan, lalu hari dan tanggal absensi.</div></div>
        </div>
        <div class="toolbar">
            <form class="toolbar" method="GET" action="{{ route('attendance.index') }}">
                <label class="filter-field">
                    <span class="field-label">Tahun</span>
                    <select class="select" name="year">
                        @foreach ($years as $availableYear)
                            <option value="{{ $availableYear }}" @selected($year === $availableYear)>{{ $availableYear }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="filter-field">
                    <span class="field-label">Bulan</span>
                    <select class="select" name="month_number">
                        @foreach (range(1, 12) as $monthOption)
                            <option value="{{ $monthOption }}" @selected($monthNumber === $monthOption)>{{ \Carbon\CarbonImmutable::createFromDate(2020, $monthOption, 1)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="filter-field">
                    <span class="field-label">Hari / tanggal</span>
                    <select class="select" name="day">
                        <option value="">Semua hari pada bulan ini</option>
                        @foreach (range(1, $daysInMonth) as $dayOption)
                            <option value="{{ $dayOption }}" @selected($day === $dayOption)>{{ \Carbon\CarbonImmutable::createFromDate($year, $monthNumber, $dayOption)->translatedFormat('l, d F') }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="filter-field">
                    <span class="field-label">Kelas</span>
                    <select class="select" name="class_name">
                        <option value="">Semua kelas</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class }}" @selected(request('class_name') === $class)>{{ $class }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="filter-field">
                    <span class="field-label">Status</span>
                    <select class="select" name="status">
                        <option value="">Semua status</option>
                        <option value="present" @selected(request('status') === 'present')>Hadir</option>
                        <option value="late" @selected(request('status') === 'late')>Terlambat</option>
                        <option value="absent" @selected(request('status') === 'absent')>Alpha</option>
                        <option value="excused" @selected(request('status') === 'excused')>Izin</option>
                    </select>
                </label>
                <button class="button button-light" type="submit">Tampilkan riwayat</button>
            </form>
            <a class="button button-primary" href="{{ route('attendance.export', ['month' => sprintf('%04d-%02d', $year, $monthNumber), 'day' => $day, 'class_name' => request('class_name'), 'status' => request('status')]) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3"/></svg>
                Ekspor CSV
            </a>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <div class="panel-title">{{ $day ? 'Riwayat absensi per kelas' : 'Riwayat absensi per hari' }}</div>
                <div class="panel-subtitle">{{ $historyDays->total() }} hari tercatat · {{ $attendances->count() }} absensi pada hari ini</div>
            </div>
        </div>
        @if ($attendances->isEmpty())
            <div class="empty-state">Belum ada absensi pada tanggal atau filter ini.</div>
        @else
            @if (! $day)
                <div class="date-divider daily-date-heading">{{ \Carbon\CarbonImmutable::parse($historyDate)->translatedFormat('l, d F Y') }}</div>
            @endif
            @php($dailyClasses = $attendances->groupBy(fn ($attendance) => $attendance->student->class_name ?: 'Kelas belum ditentukan'))
            @foreach ($dailyClasses as $className => $classAttendances)
                <section class="daily-class">
                    <div class="daily-class-heading">
                        <strong>{{ $className }}</strong>
                        <span>{{ $classAttendances->count() }} mahasiswa</span>
                    </div>
                    <div class="table-wrap"><table class="data-table">
                        <thead><tr><th>Mahasiswa</th><th>Jam masuk</th><th>Jam pulang</th><th>Status</th><th>Sumber</th></tr></thead>
                        <tbody>
                            @foreach ($classAttendances as $attendance)
                                <tr>
                                    <td><div class="person-name">{{ $attendance->student->name }}</div><span class="person-id">{{ $attendance->student->student_number }}</span></td>
                                    <td>{{ $attendance->check_in ? substr($attendance->check_in, 0, 5) : '—' }}</td>
                                    <td>{{ $attendance->check_out ? substr($attendance->check_out, 0, 5) : '—' }}</td>
                                    <td><span class="badge badge-{{ $attendance->status }}">{{ ['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Alpha', 'excused' => 'Izin'][$attendance->status] }}</span></td>
                                    <td>{{ ['generated' => 'Otomatis', 'manual' => 'Manual', 'fingerprint' => 'Fingerprint'][$attendance->source] ?? $attendance->source }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                </section>
            @endforeach
        @endif

        @if ($historyDays->hasPages())
            <div class="pagination">
                <span>Hari {{ $historyDays->firstItem() }}–{{ $historyDays->lastItem() }} dari {{ $historyDays->total() }}</span>
                {{ $historyDays->links() }}
            </div>
        @endif
    </section>
@endsection
