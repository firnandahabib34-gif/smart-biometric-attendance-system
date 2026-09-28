<?php

namespace App\Http\Controllers;

use App\Models\FingerprintLog;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', Rule::in(array_keys(config('academic.departments')))],
            'program' => ['nullable', 'string', 'max:120'],
            'class_name' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $legacyPrograms = array_keys(array_filter(
            config('academic.legacy_department_by_program'),
            fn (string $department) => $department === ($filters['department'] ?? null),
        ));

        $students = Student::query()
            ->when($request->filled('q'), function ($query) use ($filters) {
                $term = $filters['q'];
                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('student_number', 'like', "%{$term}%")
                    ->orWhere('program', 'like', "%{$term}%")
                    ->orWhere('department', 'like', "%{$term}%"));
            })
            ->when(isset($filters['department']), function ($query) use ($filters, $legacyPrograms) {
                $query->where(function ($query) use ($filters, $legacyPrograms) {
                    $query->where('department', $filters['department']);

                    if ($legacyPrograms !== []) {
                        $query->orWhere(function ($query) use ($legacyPrograms) {
                            $query->whereNull('department')->whereIn('program', $legacyPrograms);
                        });
                    }
                });
            })
            ->when($request->filled('program'), fn ($query) => $query->where('program', $filters['program']))
            ->when($request->filled('class_name'), fn ($query) => $query->where('class_name', $filters['class_name']))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $programsByDepartment = config('academic.departments');
        $departments = array_keys($programsByDepartment);
        $programs = isset($filters['department'])
            ? collect($programsByDepartment[$filters['department']])
            : collect($programsByDepartment)->flatten()->unique()->sort()->values();
        $classes = $this->classOptions();

        if ($request->expectsJson()) {
            return response()->json([
                'rows' => view('students.partials.rows', compact('students'))->render(),
                'pagination' => view('students.partials.pagination', compact('students'))->render(),
                'total' => $students->total(),
            ]);
        }

        return view('students.index', compact('students', 'departments', 'programs', 'programsByDepartment', 'classes'));
    }

    public function create(): View
    {
        return view('students.form', $this->formData(new Student));
    }

    public function store(Request $request): RedirectResponse
    {
        Student::create($this->validatedData($request));

        return redirect()->route('students.index')->with('success', 'Data mahasiswa berhasil ditambahkan.');
    }

    public function edit(Student $student): View
    {
        return view('students.form', $this->formData($student));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $student->update($this->validatedData($request, $student));

        return redirect()->route('students.index')->with('success', 'Data mahasiswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        if ($student->attendances()->exists()) {
            $student->update(['is_active' => false]);

            return redirect()->route('students.index')->with('success', 'Mahasiswa dinonaktifkan agar riwayat absensinya tetap tersimpan.');
        }

        $student->delete();

        return redirect()->route('students.index')->with('success', 'Data mahasiswa berhasil dihapus.');
    }

    public function registerFingerprint(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'fingerprint_id' => ['required', 'string', 'max:100', Rule::unique('students')->ignore($student->id)],
        ]);

        $eventType = $student->fingerprint_id ? 'updated' : 'registered';

        $student->update([
            'fingerprint_id' => $validated['fingerprint_id'],
            'fingerprint_registered_at' => now(),
        ]);

        FingerprintLog::create([
            'student_id' => $student->id,
            'fingerprint_id' => $validated['fingerprint_id'],
            'event_type' => $eventType,
            'result' => 'success',
            'message' => 'ID template fingerprint disimpan oleh admin.',
        ]);

        return back()->with('success', 'ID template fingerprint berhasil disimpan.');
    }

    private function validatedData(Request $request, ?Student $student = null): array
    {
        $classOptions = $this->classOptions();
        $programOptions = config('academic.departments.'.$request->input('department'), []);

        return $request->validate([
            'student_number' => ['required', 'string', 'max:50', Rule::unique('students')->ignore($student?->id)],
            'name' => ['required', 'string', 'max:255'],
            'department' => ['required', Rule::in(array_keys(config('academic.departments')))],
            'email' => ['required', 'email', 'max:255'],
            'program' => ['required', Rule::in($programOptions)],
            'class_name' => ['required', Rule::in($classOptions)],
            'phone' => ['required', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function formData(Student $student): array
    {
        $programsByDepartment = config('academic.departments');
        $selectedDepartment = $this->departmentFor($student);

        return [
            'student' => $student,
            'programsByDepartment' => $programsByDepartment,
            'selectedDepartment' => old('department', $selectedDepartment),
            'classOptions' => $this->classOptions(),
        ];
    }

    private function classOptions(): array
    {
        return collect(config('academic.class_levels'))
            ->flatMap(fn (int $level) => collect(config('academic.class_groups'))
                ->map(fn (string $group) => 'Pagi '.$level.'-'.$group))
            ->all();
    }

    private function departmentFor(Student $student): ?string
    {
        if ($student->department) {
            return $student->department;
        }

        foreach (config('academic.departments') as $department => $programs) {
            if (in_array($student->program, $programs, true)) {
                return $department;
            }
        }

        return config('academic.legacy_department_by_program.'.$student->program);
    }
}
