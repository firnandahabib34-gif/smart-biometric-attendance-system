@extends('layouts.admin')

@section('title', 'Mahasiswa')

@section('content')
    <div class="page-heading">
        <div><h1>Mahasiswa</h1><p id="student-count">{{ $students->total() }} data mahasiswa</p></div>
        <a class="button button-primary" href="{{ route('students.create') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>Tambah mahasiswa</a>
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
@endsection