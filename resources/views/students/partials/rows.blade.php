@forelse ($students as $student)
    <tr>
        <td><div class="person-cell"><span class="avatar">{{ mb_substr($student->name, 0, 1) }}</span><span><span class="person-name">{{ $student->name }}</span><span class="person-id">{{ $student->student_number }}</span></span></div></td>
        <td>{{ $student->department ?: config('academic.legacy_department_by_program.'.$student->program, 'Jurusan belum dipilih') }}<span class="person-id">{{ $student->program }} · {{ $student->class_name }}</span></td>
        <td>
            @if ($student->fingerprint_id)
                <span class="fingerprint-state">{{ $student->fingerprint_id }}</span><span class="person-id">Terdaftar {{ $student->fingerprint_registered_at?->format('d M Y') }}</span>
            @endif
            <form class="inline-form" method="POST" action="{{ route('students.fingerprint', $student) }}">
                @csrf @method('PATCH')
                <input class="field" name="fingerprint_id" placeholder="{{ $student->fingerprint_id ? 'Ganti ID' : 'ID template' }}" aria-label="ID template fingerprint {{ $student->name }}" required>
                <button class="button button-light" type="submit">{{ $student->fingerprint_id ? 'Perbarui' : 'Daftarkan' }}</button>
            </form>
        </td>
        <td><span class="badge {{ $student->is_active ? 'badge-present' : 'badge-inactive' }}">{{ $student->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
        <td><div class="table-actions"><a class="button button-light" href="{{ route('students.edit', $student) }}" aria-label="Edit {{ $student->name }}">Edit</a><form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Hapus data mahasiswa? Jika ada riwayat absensi, data akan dinonaktifkan.')">@csrf @method('DELETE')<button class="button button-danger" type="submit">Hapus</button></form></div></td>
    </tr>
@empty
    <tr><td colspan="5"><div class="empty-state">Tidak ada mahasiswa yang cocok dengan filter.</div></td></tr>
@endforelse