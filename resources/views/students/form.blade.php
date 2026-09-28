@extends('layouts.admin')

@section('title', $student->exists ? 'Edit mahasiswa' : 'Tambah mahasiswa')

@section('content')
    <div class="page-heading"><div><h1>{{ $student->exists ? 'Edit mahasiswa' : 'Tambah mahasiswa' }}</h1><p>{{ $student->exists ? $student->student_number : 'Data akademik mahasiswa' }}</p></div></div>
    <section class="panel" style="max-width:850px">
        {{-- Satu form menangani pembuatan maupun pembaruan data mahasiswa. --}}
        <form method="POST" action="{{ $student->exists ? route('students.update', $student) : route('students.store') }}" class="form-stack" x-data="{ department: @js($selectedDepartment), program: @js(old('program', $student->program)), programsByDepartment: @js($programsByDepartment) }" x-init="$watch('department', value => { if (!programsByDepartment[value]?.includes(program)) program = '' })">
            @csrf
            @if ($student->exists) @method('PUT') @endif
            <div class="form-grid">
                <div class="form-field"><label class="field-label" for="student_number">NIM</label><input class="field" id="student_number" name="student_number" value="{{ old('student_number', $student->student_number) }}" required>@error('student_number')<small class="notice-error">{{ $message }}</small>@enderror</div>
                <div class="form-field"><label class="field-label" for="name">Nama lengkap</label><input class="field" id="name" name="name" value="{{ old('name', $student->name) }}" required>@error('name')<small class="notice-error">{{ $message }}</small>@enderror</div>
                <div class="form-field"><label class="field-label" for="department">Jurusan</label><select class="select" id="department" name="department" x-model="department" required><option value="">Pilih jurusan</option>@foreach ($programsByDepartment as $department => $programOptions)<option value="{{ $department }}" @selected($selectedDepartment === $department)>{{ $department }}</option>@endforeach</select>@error('department')<small class="notice-error">{{ $message }}</small>@enderror</div>
                <div class="form-field"><label class="field-label" for="program">Program studi</label><select class="select" id="program" name="program" x-model="program" x-bind:disabled="!department" required><option value="">Pilih program studi</option><template x-for="programOption in (programsByDepartment[department] || [])" :key="programOption"><option :value="programOption" x-text="programOption"></option></template></select>@error('program')<small class="notice-error">{{ $message }}</small>@enderror</div>
                <div class="form-field"><label class="field-label" for="class_name">Kelas (Pagi)</label><select class="select" id="class_name" name="class_name" required><option value="">Pilih kelas pagi</option>@foreach ($classOptions as $classOption)<option value="{{ $classOption }}" @selected(old('class_name', $student->class_name) === $classOption)>{{ $classOption }}</option>@endforeach</select>@error('class_name')<small class="notice-error">{{ $message }}</small>@enderror</div>
                <div class="form-field"><label class="field-label" for="email">Email</label><input class="field" id="email" type="email" name="email" value="{{ old('email', $student->email) }}" required>@error('email')<small class="notice-error">{{ $message }}</small>@enderror</div>
                <div class="form-field"><label class="field-label" for="phone">Nomor telepon</label><input class="field" id="phone" name="phone" value="{{ old('phone', $student->phone) }}" required>@error('phone')<small class="notice-error">{{ $message }}</small>@enderror</div>
                <div class="form-field"><label class="field-label" for="is_active">Status</label><select class="select" id="is_active" name="is_active" required><option value="" disabled @selected(old('is_active', $student->exists ? (string) (int) $student->is_active : '') === '')>Pilih status</option><option value="1" @selected((string) old('is_active', $student->exists ? (int) $student->is_active : '') === '1')>Aktif</option><option value="0" @selected((string) old('is_active', $student->exists ? (int) $student->is_active : '') === '0')>Nonaktif</option></select>@error('is_active')<small class="notice-error">{{ $message }}</small>@enderror</div>
            </div>
            <div class="form-actions"><a class="button button-light" href="{{ route('students.index') }}">Batal</a><button class="button button-primary" type="submit">{{ $student->exists ? 'Simpan perubahan' : 'Simpan mahasiswa' }}</button></div>
        </form>
    </section>
@endsection