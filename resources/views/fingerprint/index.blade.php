@extends('layouts.admin')

@section('title', 'Fingerprint & perangkat')

@section('content')
    <div class="page-heading">
        <div><h1>Fingerprint &amp; perangkat</h1><p>Tambahkan mahasiswa di sini; proses fingerprint dan absensi berjalan melalui perangkat.</p></div>
        <button class="button button-primary" type="button" data-open-student-modal>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
            Tambah mahasiswa
        </button>
    </div>

    <section class="panel device-panel">
        <div class="panel-heading">
            <div><div class="panel-title">Integrasi otomatis</div><div class="panel-subtitle">Gunakan alamat API ini pada IoT Utility, Arduino IDE, atau firmware ESP32.</div></div>
            <span class="badge {{ $deviceApiConfigured ? 'badge-present' : 'badge-inactive' }}">{{ $deviceApiConfigured ? 'Token perangkat siap' : 'Atur token perangkat' }}</span>
        </div>
        <div class="device-endpoints">
            <div><span class="field-label">Endpoint fingerprint / absensi</span><code>{{ url('/api/device/fingerprint/scan') }}</code></div>
            <div><span class="field-label">Endpoint registrasi fingerprint</span><code>{{ url('/api/device/fingerprint/enroll') }}</code></div>
        </div>
        <p class="device-note">Tambahkan data mahasiswa melalui tombol di halaman ini. Sidik jari didaftarkan pada hardware, lalu perangkat mengirim hasilnya ke server. Tidak perlu mengetik ID fingerprint atau mencatat absensi secara manual di web.</p>
    </section>

    <section class="panel">
        <div class="panel-heading"><div><div class="panel-title">Riwayat perangkat</div><div class="panel-subtitle">{{ $recentLogs->count() }} aktivitas terbaru</div></div></div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Waktu</th><th>Mahasiswa</th><th>Fingerprint ID</th><th>Aktivitas</th><th>Perangkat</th><th>Hasil</th></tr></thead>
            <tbody>
                @forelse ($recentLogs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td>{{ $log->student?->name ?? 'Tidak dikenal' }}</td>
                        <td>{{ $log->fingerprint_id ?: '—' }}</td>
                        <td>{{ ['registered' => 'Registrasi', 'updated' => 'Pembaruan ID', 'attendance_scan' => 'Scan absensi', 'attendance_check_in' => 'Absen masuk', 'attendance_check_out' => 'Absen pulang'][$log->event_type] ?? $log->event_type }}</td>
                        <td>{{ $log->device_id ?: '—' }}</td>
                        <td><span class="badge {{ $log->result === 'success' ? 'badge-present' : ($log->result === 'failed' ? 'badge-absent' : 'badge-inactive') }}">{{ ['success' => 'Berhasil', 'failed' => 'Gagal', 'ignored' => 'Diabaikan'][$log->result] ?? $log->result }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state">Belum ada aktivitas dari perangkat fingerprint.</div></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <dialog id="student-create-modal" class="modal-dialog" aria-labelledby="student-create-title" @if ($errors->any() && old('origin') === 'fingerprint') open @endif>
        <div class="modal-panel">
            <div class="panel-heading">
                <div><div class="panel-title" id="student-create-title">Tambah mahasiswa</div><div class="panel-subtitle">Data mahasiswa baru untuk didaftarkan di perangkat fingerprint.</div></div>
                <button class="modal-close" type="button" data-close-modal aria-label="Tutup">×</button>
            </div>
            <form method="POST" action="{{ route('students.store') }}" class="form-stack">
                @csrf
                <input type="hidden" name="origin" value="fingerprint">
                <input type="hidden" name="department" value="Teknik Informatika">
                <input type="hidden" name="program" value="Informatika">
                <input type="hidden" name="is_active" value="1">
                <div class="form-grid">
                    <div class="form-field"><label class="field-label" for="new_student_number">NIM</label><input class="field" id="new_student_number" name="student_number" value="{{ old('student_number') }}" required>@error('student_number')<small class="field-error">{{ $message }}</small>@enderror</div>
                    <div class="form-field"><label class="field-label" for="new_student_name">Nama lengkap</label><input class="field" id="new_student_name" name="name" value="{{ old('name') }}" required>@error('name')<small class="field-error">{{ $message }}</small>@enderror</div>
                    <div class="form-field"><label class="field-label" for="new_student_department">Jurusan</label><input class="field" id="new_student_department" value="Teknik Informatika" disabled></div>
                    <div class="form-field"><label class="field-label" for="new_student_program">Program studi</label><input class="field" id="new_student_program" value="Informatika" disabled></div>
                    <div class="form-field"><label class="field-label" for="new_student_class">Kelas pagi</label><select class="select" id="new_student_class" name="class_name" required><option value="">Pilih kelas</option>@foreach ($classes as $class)<option value="{{ $class }}" @selected(old('class_name') === $class)>{{ $class }}</option>@endforeach</select>@error('class_name')<small class="field-error">{{ $message }}</small>@enderror</div>
                    <div class="form-field"><label class="field-label" for="new_student_email">Email</label><input class="field" id="new_student_email" type="email" name="email" value="{{ old('email') }}" required>@error('email')<small class="field-error">{{ $message }}</small>@enderror</div>
                    <div class="form-field"><label class="field-label" for="new_student_phone">Nomor telepon</label><input class="field" id="new_student_phone" name="phone" value="{{ old('phone') }}" required>@error('phone')<small class="field-error">{{ $message }}</small>@enderror</div>
                </div>
                <div class="form-actions"><button class="button button-light" type="button" data-close-modal>Batal</button><button class="button button-primary" type="submit">Simpan mahasiswa</button></div>
            </form>
        </div>
    </dialog>
    <script>
        const studentCreateModal = document.querySelector('#student-create-modal');
        document.querySelector('[data-open-student-modal]')?.addEventListener('click', () => studentCreateModal?.showModal());
        document.querySelectorAll('#student-create-modal [data-close-modal]').forEach((button) => button.addEventListener('click', () => studentCreateModal?.close()));
        if (studentCreateModal?.hasAttribute('open')) {
            studentCreateModal.removeAttribute('open');
            studentCreateModal.showModal();
        }
    </script>
@endsection
