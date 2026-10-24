<?php

namespace App\Http\Controllers;

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

    public function store(Request $request): RedirectResponse
    {
        Student::create($this->validatedData($request));

        $destination = $request->input('origin') === 'fingerprint' ? 'fingerprint.index' : 'students.index';

        return redirect()->route($destination)->with('success', 'Data mahasiswa berhasil ditambahkan.');
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

    private function classOptions(): array
    {
        return config('academic.class_options');
    }
}
