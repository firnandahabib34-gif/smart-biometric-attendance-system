@extends('layouts.admin')

@section('title', 'Fingerprint')

@section('content')
    <div class="page-heading">
        <div><h1>Absensi fingerprint</h1><p>Scan pertama mencatat masuk, scan berikutnya mencatat pulang.</p></div>
    </div>

    <section class="panel" style="max-width:760px;margin-bottom:16px">
        <div class="panel-heading">
            <div><div class="panel-title">Scan fingerprint</div><div class="panel-subtitle">ID harus terdaftar pada mahasiswa aktif.</div></div>
        </div>
        <form class="toolbar" method="POST" action="{{ route('fingerprint.scan') }}">
            @csrf
            <label class="filter-field" style="flex:1"><span class="field-label">ID fingerprint</span><input class="field" id="fingerprint_id" name="fingerprint_id" type="text" autocomplete="off" placeholder="Scan atau masukkan ID fingerprint" autofocus required></label>
            <button class="button button-primary" type="submit" style="align-self:flex-end">Catat scan</button>
        </form>
        @error('fingerprint_id')<div class="notice notice-error" style="margin-top:12px">{{ $message }}</div>@enderror
    </section>

    <section class="panel">
        <div class="panel-heading"><div><div class="panel-title">Log fingerprint terbaru</div><div class="panel-subtitle">{{ $recentLogs->count() }} aktivitas terbaru</div></div></div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Waktu</th><th>Mahasiswa</th><th>ID</th><th>Aktivitas</th><th>Hasil</th></tr></thead>
            <tbody>
                @forelse ($recentLogs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td>{{ $log->student?->name ?? 'Tidak dikenal' }}</td>
                        <td>{{ $log->fingerprint_id ?: '—' }}</td>
                        <td>{{ ['registered' => 'Registrasi', 'updated' => 'Pembaruan ID', 'attendance_scan' => 'Scan absensi', 'attendance_check_in' => 'Absen masuk', 'attendance_check_out' => 'Absen pulang'][$log->event_type] ?? $log->event_type }}</td>
                        <td><span class="badge {{ $log->result === 'success' ? 'badge-present' : ($log->result === 'failed' ? 'badge-absent' : 'badge-inactive') }}">{{ ['success' => 'Berhasil', 'failed' => 'Gagal', 'ignored' => 'Diabaikan'][$log->result] ?? $log->result }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state">Belum ada aktivitas fingerprint.</div></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
@endsection