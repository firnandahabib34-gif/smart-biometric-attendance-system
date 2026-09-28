@extends('layouts.admin')

@section('title', 'Absensi & laporan')

@section('content')
    <div class="page-heading"><div><h1>Absensi &amp; laporan</h1><p>Riwayat kehadiran bulan {{ \Carbon\Carbon::createFromFormat('!Y-m', $month)->translatedFormat('F Y') }}</p></div></div>

    <section class="panel" style="margin-bottom:16px">
        <div class="panel-heading"><div><div class="panel-title">Laporan bulanan</div><div class="panel-subtitle">Pilih bulan untuk melihat atau mengunduh data</div></div></div>
        <div class="toolbar">
            <form class="toolbar" method="GET" action="{{ route('attendance.index') }}">
                <label class="filter-field"><span class="sr-only">Bulan laporan</span><input class="field" type="month" name="month" value="{{ $month }}"></label>
                <label class="filter-field"><span class="sr-only">Filter status</span><select class="select" name="status"><option value="">Semua status</option><option value="present" @selected(request('status') === 'present')>Hadir</option><option value="late" @selected(request('status') === 'late')>Terlambat</option><option value="absent" @selected(request('status') === 'absent')>Alpha</option><option value="excused" @selected(request('status') === 'excused')>Izin</option></select></label>
                <button class="button button-light" type="submit">Tampilkan</button>
            </form>
            <form method="POST" action="{{ route('attendance.generate') }}">@csrf<input type="hidden" name="month" value="{{ $month }}"><button class="button button-lime" type="submit">Buat jadwal alpha</button></form>
            <a class="button button-primary" href="{{ route('attendance.export', ['month' => $month]) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3"/></svg>Ekspor CSV</a>
        </div>
    </section>

    <section class="panel" style="margin-bottom:16px" x-data="{ selected: [], allStudentIds: @js($students->modelKeys()) }">
        <div class="panel-heading">
            <div><div class="panel-title">Absensi massal</div><div class="panel-subtitle">Pilih tanggal dan waktu; centang mahasiswa yang hadir.</div></div>
            <span class="badge badge-present" x-text="`${selected.length} dipilih`">0 dipilih</span>
        </div>
        @if ($students->isEmpty())
            <div class="empty-state">Belum ada mahasiswa aktif.</div>
        @else
            <form method="POST" action="{{ route('attendance.bulk') }}" class="form-stack">
                @csrf
                <div class="toolbar">
                    <div class="filter-field"><label class="field-label" for="bulk_attendance_date">Tanggal</label><input class="field" id="bulk_attendance_date" type="date" name="attendance_date" value="{{ old('attendance_date', request('bulk_date', now()->format('Y-m-d'))) }}" required></div>
                    <div class="filter-field"><label class="field-label" for="bulk_check_in">Jam masuk</label><input class="field" id="bulk_check_in" type="time" name="check_in" value="{{ old('check_in', request('bulk_check_in', now()->format('H:i'))) }}"></div>
                    <div class="filter-field"><label class="field-label" for="bulk_check_out">Jam keluar</label><input class="field" id="bulk_check_out" type="time" name="check_out" value="{{ old('check_out', request('bulk_check_out')) }}"></div>
                    <label class="button button-light" style="align-self:flex-end;cursor:pointer">
                        <input type="checkbox" :checked="selected.length === allStudentIds.length" @change="selected = $event.target.checked ? [...allStudentIds] : []">
                        Pilih semua
                    </label>
                    <button class="button button-primary" type="submit" x-bind:disabled="selected.length === 0" x-bind:class="{ 'opacity-50 cursor-not-allowed': selected.length === 0 }">Simpan absensi terpilih</button>
                </div>
                @error('student_ids')<div class="notice notice-error">{{ $message }}</div>@enderror
                <div class="table-wrap"><table class="data-table">
                    <thead><tr><th>Pilih</th><th>NIM</th><th>Nama mahasiswa</th><th>Jurusan</th><th>Program studi</th><th>Kelas</th></tr></thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr>
                                <td><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" x-model.number="selected" aria-label="Tandai hadir {{ $student->name }}"></td>
                                <td>{{ $student->student_number }}</td>
                                <td><span class="person-name">{{ $student->name }}</span></td>
                                <td>{{ $student->department ?: config('academic.legacy_department_by_program.'.$student->program, '—') }}</td>
                                <td>{{ $student->program }}</td>
                                <td>{{ $student->class_name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </form>
        @endif
    </section>

    <section class="panel">
            <div class="panel-heading"><div><div class="panel-title">Riwayat absensi per tanggal</div><div class="panel-subtitle">{{ $attendances->total() }} catatan</div></div></div>
            <div class="table-wrap"><table class="data-table">
                <thead><tr><th>Tanggal</th><th>Mahasiswa</th><th>Masuk / keluar</th><th>Status</th><th>Sumber</th></tr></thead>
                <tbody>
                    @php($attendanceDays = $attendances->getCollection()->groupBy(fn ($attendance) => $attendance->attendance_date->toDateString()))
                    @forelse ($attendanceDays as $attendanceDate => $dayAttendances)
                        <tr class="date-divider"><td colspan="5"><strong>{{ \Carbon\CarbonImmutable::parse($attendanceDate)->translatedFormat('l, d F Y') }}</strong><span class="person-id">{{ $dayAttendances->count() }} catatan</span></td></tr>
                        @foreach ($dayAttendances as $attendance)
                            <tr><td>{{ $attendance->attendance_date->format('d M Y') }}</td><td><div class="person-name">{{ $attendance->student->name }}</div><span class="person-id">{{ $attendance->student->student_number }}</span></td><td>{{ $attendance->check_in ? substr($attendance->check_in, 0, 5) : '—' }} / {{ $attendance->check_out ? substr($attendance->check_out, 0, 5) : '—' }}</td><td><span class="badge badge-{{ $attendance->status }}">{{ ['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Alpha', 'excused' => 'Izin'][$attendance->status] }}</span></td><td>{{ ['generated' => 'Otomatis', 'manual' => 'Manual', 'fingerprint' => 'Fingerprint'][$attendance->source] ?? $attendance->source }}</td></tr>
                        @endforeach
                    @empty
                        <tr><td colspan="5"><div class="empty-state">Tidak ada catatan pada filter ini.</div></td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <div class="pagination"><span>{{ $attendances->firstItem() ?? 0 }}–{{ $attendances->lastItem() ?? 0 }} dari {{ $attendances->total() }}</span>{{ $attendances->links() }}</div>
    </section>
@endsection