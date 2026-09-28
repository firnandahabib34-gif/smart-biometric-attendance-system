@extends('layouts.admin')

@section('title', 'Pengaturan')

@section('content')
    <div class="page-heading"><div><h1>Pengaturan</h1><p>Informasi kampus dan jam operasional absensi</p></div></div>
    <div class="settings-grid">
        <section class="panel">
            <div class="panel-heading"><div><div class="panel-title">Operasional</div><div class="panel-subtitle">Konfigurasi sistem absensi</div></div></div>
            <form method="POST" action="{{ route('settings.update') }}" class="form-stack">
                @csrf @method('PUT')
                <div><label class="field-label" for="campus_name">Nama institusi</label><input class="field" id="campus_name" name="campus_name" value="{{ old('campus_name', $settings->get('campus_name') ?: 'Politeknik Negeri Batam') }}" required></div>
                <div class="form-grid">
                    <div><label class="field-label" for="attendance_start">Jam mulai absensi</label><input class="field" id="attendance_start" type="time" name="attendance_start" value="{{ old('attendance_start', $settings->get('attendance_start') ?: '07:30') }}" required></div>
                    <div><label class="field-label" for="attendance_end">Jam akhir absensi</label><input class="field" id="attendance_end" type="time" name="attendance_end" value="{{ old('attendance_end', $settings->get('attendance_end') ?: '17:00') }}" required></div>
                </div>
                @error('attendance_end')<div class="notice notice-error">{{ $message }}</div>@enderror
                <div class="form-actions"><button class="button button-primary" type="submit">Simpan pengaturan</button></div>
            </form>
        </section>
        <section class="panel">
            <div class="panel-heading"><div><div class="panel-title">Akun admin</div><div class="panel-subtitle">Sesi yang sedang digunakan</div></div></div>
            <div class="settings-list">
                <div class="settings-row"><span class="settings-key">Nama</span><span class="settings-value">{{ auth()->user()->name }}</span></div>
                <div class="settings-row"><span class="settings-key">Email</span><span class="settings-value">{{ auth()->user()->email }}</span></div>
                <div class="settings-row"><span class="settings-key">Hak akses</span><span class="settings-value">Administrator</span></div>
            </div>
            <a class="button button-light" href="{{ route('profile.edit') }}" style="margin-top:15px">Kelola profil</a>
        </section>
    </div>
@endsection