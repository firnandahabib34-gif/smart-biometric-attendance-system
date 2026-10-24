@forelse ($students as $student)
    <tr>
        <td><div class="person-cell"><span class="avatar">{{ mb_substr($student->name, 0, 1) }}</span><span><span class="person-name">{{ $student->name }}</span><span class="person-id">{{ $student->student_number }}</span></span></div></td>
        <td>{{ $student->department ?: config('academic.legacy_department_by_program.'.$student->program, 'Jurusan belum dipilih') }}<span class="person-id">{{ $student->program }} · {{ $student->class_name }}</span></td>
        <td><span class="badge {{ $student->fingerprint_id ? 'badge-present' : 'badge-inactive' }}">{{ $student->fingerprint_id ? 'Terdaftar' : 'Menunggu perangkat' }}</span></td>
        <td><span class="badge {{ $student->is_active ? 'badge-present' : 'badge-inactive' }}">{{ $student->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
        <td>
            <div class="table-actions">
                <button class="button button-light" type="button" data-edit-student
                    data-id="{{ $student->id }}"
                    data-url="{{ route('students.update', $student) }}"
                    data-name="{{ $student->name }}"
                    data-number="{{ $student->student_number }}"
                    data-email="{{ $student->email }}"
                    data-phone="{{ $student->phone }}"
                    data-class="{{ $student->class_name }}"
                    data-active="{{ (int) $student->is_active }}">Edit</button>
                <button class="button button-danger" type="button" data-delete-student
                    data-id="{{ $student->id }}"
                    data-url="{{ route('students.destroy', $student) }}"
                    data-name="{{ $student->name }}">Hapus</button>
            </div>
        </td>
    </tr>
@empty
    <tr><td colspan="5"><div class="empty-state">Tidak ada mahasiswa yang cocok dengan filter.</div></td></tr>
@endforelse