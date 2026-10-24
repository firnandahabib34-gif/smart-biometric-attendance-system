@extends('layouts.admin')

@section('title', 'Mahasiswa')

@section('content')
    <div class="page-heading">
        <div><h1>Mahasiswa</h1><p id="student-count">{{ $students->total() }} data mahasiswa</p></div>
    </div>

    <section class="panel">
        <form id="student-filter-form" class="toolbar" method="GET" action="{{ route('students.index') }}" style="margin-bottom:18px">
            <label class="filter-field"><span class="sr-only">Cari mahasiswa</span><input id="student-search" class="field" name="q" value="{{ request('q') }}" placeholder="Cari nama, NIM, atau program" autocomplete="off"></label>
            <label class="filter-field"><span class="sr-only">Jurusan</span><select id="student-department" class="select" name="department" data-programs-by-department='@json($programsByDepartment)'><option value="">Semua jurusan</option>@foreach ($departments as $department)<option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>@endforeach</select></label>
            <label class="filter-field"><span class="sr-only">Program studi</span><select id="student-program" class="select" name="program"><option value="">Semua program studi</option>@foreach ($programs as $program)<option value="{{ $program }}" @selected(request('program') === $program)>{{ $program }}</option>@endforeach</select></label>
            <label class="filter-field"><span class="sr-only">Kelas</span><select id="student-class" class="select" name="class_name"><option value="">Semua kelas</option>@foreach ($classes as $class)<option value="{{ $class }}" @selected(request('class_name') === $class)>{{ $class }}</option>@endforeach</select></label>
            <label class="filter-field"><span class="sr-only">Status mahasiswa</span><select id="student-status" class="select" name="status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select></label>
            <a class="button button-light" href="{{ route('students.index') }}" data-student-reset>Reset</a>
        </form>

        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Mahasiswa</th><th>Jurusan / program / kelas</th><th>Fingerprint</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody id="student-results">@include('students.partials.rows', ['students' => $students])</tbody>
        </table></div>
        <div id="student-pagination">@include('students.partials.pagination', ['students' => $students])</div>
    </section>

    <dialog id="student-edit-modal" class="modal-dialog" aria-labelledby="student-edit-title">
        <div class="modal-panel">
            <div class="panel-heading">
                <div><div class="panel-title" id="student-edit-title">Edit mahasiswa</div><div class="panel-subtitle" id="student-edit-description">Perbarui data mahasiswa.</div></div>
                <button class="modal-close" type="button" data-close-modal aria-label="Tutup">×</button>
            </div>
            <form id="student-edit-form" method="POST" class="form-stack">
                @csrf
                @method('PUT')
                <input type="hidden" name="student_id" value="{{ old('student_id') }}">
                <input type="hidden" name="department" value="{{ old('department', 'Teknik Informatika') }}">
                <input type="hidden" name="program" value="{{ old('program', 'Informatika') }}">
                <div class="form-grid">
                    <div class="form-field"><label class="field-label" for="edit_student_number">NIM</label><input class="field" id="edit_student_number" name="student_number" required></div>
                    <div class="form-field"><label class="field-label" for="edit_student_name">Nama lengkap</label><input class="field" id="edit_student_name" name="name" required></div>
                    <div class="form-field"><label class="field-label" for="edit_department_display">Jurusan</label><input class="field" id="edit_department_display" value="Teknik Informatika" disabled></div>
                    <div class="form-field"><label class="field-label" for="edit_program_display">Program studi</label><input class="field" id="edit_program_display" value="Informatika" disabled></div>
                    <div class="form-field"><label class="field-label" for="edit_class_name">Kelas pagi</label><select class="select" id="edit_class_name" name="class_name" required><option value="">Pilih kelas</option>@foreach ($classes as $class)<option value="{{ $class }}">{{ $class }}</option>@endforeach</select></div>
                    <div class="form-field"><label class="field-label" for="edit_student_email">Email</label><input class="field" id="edit_student_email" type="email" name="email" required></div>
                    <div class="form-field"><label class="field-label" for="edit_student_phone">Nomor telepon</label><input class="field" id="edit_student_phone" name="phone" required></div>
                    <div class="form-field"><label class="field-label" for="edit_student_status">Status</label><select class="select" id="edit_student_status" name="is_active" required><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
                </div>
                <div class="form-actions"><button class="button button-light" type="button" data-close-modal>Batal</button><button class="button button-primary" type="submit">Simpan perubahan</button></div>
            </form>
        </div>
    </dialog>

    <dialog id="student-delete-modal" class="modal-dialog modal-dialog-small" aria-labelledby="student-delete-title">
        <div class="modal-panel">
            <div class="panel-heading">
                <div><div class="panel-title" id="student-delete-title">Hapus mahasiswa</div><div class="panel-subtitle" id="student-delete-description"></div></div>
                <button class="modal-close" type="button" data-close-modal aria-label="Tutup">×</button>
            </div>
            <p class="modal-copy">Mahasiswa yang memiliki riwayat absensi akan dinonaktifkan agar riwayatnya tetap tersimpan.</p>
            <form id="student-delete-form" method="POST" class="form-actions">
                @csrf
                @method('DELETE')
                <button class="button button-light" type="button" data-close-modal>Batal</button>
                <button class="button button-danger" type="submit">Hapus mahasiswa</button>
            </form>
        </div>
    </dialog>

    <script id="student-modal-state" type="application/json">@json([
        'reopenId' => old('student_id'),
        'old' => old(),
    ])</script>
@endsection