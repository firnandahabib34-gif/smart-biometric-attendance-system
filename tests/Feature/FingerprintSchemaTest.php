<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FingerprintSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_four_application_tables_exist_without_app_settings_table(): void
    {
        foreach (['users', 'students', 'attendances', 'fingerprint_logs'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $this->assertFalse(Schema::hasTable('app_settings'));
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->assertTrue(Schema::hasColumn('users', 'attendance_start'));
        $this->assertTrue(Schema::hasColumn('users', 'attendance_end'));
        $this->assertTrue(Schema::hasColumn('students', 'department'));
    }

    public function test_student_form_contains_requested_departments_and_class_range(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('students.create'))
            ->assertOk()
            ->assertSee('D3 Teknik Elektronika Manufaktur')
            ->assertSee('D4 Teknik Robotika')
            ->assertSee('D3 Teknologi Geomatika')
            ->assertSee('D4 Animasi')
            ->assertSee('D3 Teknik Perencanaan dan Konstruksi Kapal')
            ->assertSee('D4 Teknologi Rekayasa Konstruksi Perkapalan')
            ->assertSee('D3 Akuntansi')
            ->assertSee('D4 Logistik Perdagangan Internasional')
            ->assertSee('Pagi 1-A')
            ->assertSee('Pagi 8-E')
            ->assertDontSee('Malam 3C');
    }

    public function test_unverified_admin_can_save_a_student_and_return_to_the_list(): void
    {
        $admin = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($admin)
            ->post(route('students.store'), [
                'student_number' => '2026009',
                'name' => 'Mahasiswa Uji',
                'department' => 'Teknik Informatika',
                'program' => 'D3 Teknik Informatika',
                'class_name' => 'Pagi 1-A',
                'email' => 'mahasiswa.uji@example.test',
                'phone' => '08123456789',
                'is_active' => '1',
            ])
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'student_number' => '2026009',
            'name' => 'Mahasiswa Uji',
        ]);
    }

    public function test_student_cannot_be_saved_when_required_details_are_missing(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'student_number' => '2026099',
                'name' => 'Data Belum Lengkap',
                'program' => 'D3 Teknik Informatika',
                'is_active' => '1',
            ])
            ->assertRedirect(route('students.create'))
            ->assertSessionHasErrors(['class_name', 'email', 'phone']);

        $this->assertDatabaseMissing('students', ['student_number' => '2026099']);
    }

    public function test_student_program_must_belong_to_the_selected_department(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('students.create'))
            ->post(route('students.store'), [
                'student_number' => '2026199',
                'name' => 'Jurusan Tidak Cocok',
                'department' => 'Teknik Mesin',
                'program' => 'D3 Teknik Informatika',
                'class_name' => 'Pagi 1-A',
                'email' => 'jurusan.test@example.test',
                'phone' => '08123456789',
                'is_active' => '1',
            ])
            ->assertRedirect(route('students.create'))
            ->assertSessionHasErrors('program');

        $this->assertDatabaseMissing('students', ['student_number' => '2026199']);
    }

    public function test_legacy_program_name_is_not_accepted_for_student_updates(): void
    {
        $student = Student::create([
            'student_number' => '2026188',
            'name' => 'Mahasiswa Lama',
            'department' => 'Teknik Informatika',
            'program' => 'Teknik Informatika',
            'class_name' => 'Pagi 1-A',
            'email' => 'mahasiswa.lama@example.test',
            'phone' => '08123456780',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->from(route('students.edit', $student))
            ->put(route('students.update', $student), [
                'student_number' => $student->student_number,
                'name' => $student->name,
                'department' => 'Teknik Informatika',
                'program' => 'Teknik Informatika',
                'class_name' => 'Pagi 1-A',
                'email' => $student->email,
                'phone' => $student->phone,
                'is_active' => '1',
            ])
            ->assertRedirect(route('students.edit', $student))
            ->assertSessionHasErrors('program');
    }

    public function test_student_live_search_returns_filtered_json(): void
    {
        Student::create([
            'student_number' => '2026100',
            'name' => 'Nadia Putri',
            'department' => 'Teknik Informatika',
            'program' => 'Teknik Informatika',
            'class_name' => 'Pagi 1-A',
            'is_active' => true,
        ]);
        Student::create([
            'student_number' => '2026101',
            'name' => 'Budi Santoso',
            'department' => 'Manajemen Bisnis',
            'program' => 'Akuntansi',
            'class_name' => 'Pagi 2-B',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Jurusan / program / kelas')
            ->assertSee('Semua jurusan')
            ->assertSee('Program studi')
            ->assertSee('Semua jurusan')
            ->assertSee('Semua program studi')
            ->assertSee('Semua kelas')
            ->assertSee('Reset');

        $response = $this->getJson(route('students.index', [
            'q' => 'Nadia',
            'department' => 'Teknik Informatika',
            'program' => 'Teknik Informatika',
            'class_name' => 'Pagi 1-A',
        ]));

        $response->assertOk()->assertJsonPath('total', 1);
        $this->assertStringContainsString('Nadia Putri', $response->json('rows'));
        $this->assertStringNotContainsString('Budi Santoso', $response->json('rows'));

        $this->getJson(route('students.index', [
            'department' => 'Teknik Informatika',
            'program' => 'Teknik Informatika',
            'class_name' => 'Pagi 2-B',
        ]))
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_admin_can_save_attendance_for_many_students_in_one_request(): void
    {
        $admin = User::factory()->create(['attendance_start' => '07:30']);
        $students = collect(['2026010', '2026011'])->map(fn ($number) => Student::create([
            'student_number' => $number,
            'name' => 'Mahasiswa '.$number,
            'program' => 'Teknik Informatika',
            'is_active' => true,
        ]));
        $untickedStudent = Student::create([
            'student_number' => '2026012',
            'name' => 'Tidak Dipilih',
            'program' => 'Teknik Informatika',
            'is_active' => true,
        ]);
        Attendance::create([
            'student_id' => $students[0]->id,
            'attendance_date' => '2026-09-27',
            'status' => 'absent',
            'check_out' => '16:00',
            'source' => 'generated',
        ]);

        $this->actingAs($admin)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('Absensi massal')
            ->assertSee('Riwayat absensi per tanggal')
            ->assertDontSee('Catat absensi')
            ->assertDontSee('ID fingerprint')
            ->assertSee('Jam keluar')
            ->assertSee('Pilih semua');

        $reportUrl = route('attendance.index', [
            'month' => '2026-09',
            'bulk_date' => '2026-09-27',
            'bulk_check_in' => '07:45',
            'bulk_check_out' => '16:45',
        ]);

        $this->actingAs($admin)
            ->post(route('attendance.bulk'), [
                'attendance_date' => '2026-09-27',
                'check_in' => '07:45',
                'check_out' => '16:45',
                'student_ids' => $students->pluck('id')->all(),
            ])
            ->assertRedirect($reportUrl);

        $this->actingAs($admin)
            ->get($reportUrl)
            ->assertOk()
            ->assertSee('value="2026-09-27"', false)
            ->assertSee('value="07:45"', false)
            ->assertSee('value="16:45"', false);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $students[0]->id,
            'attendance_date' => '2026-09-27',
            'status' => 'late',
            'check_in' => '07:45',
            'check_out' => '16:45',
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $students[1]->id,
            'attendance_date' => '2026-09-27',
            'status' => 'late',
        ]);
        $this->assertDatabaseMissing('attendances', [
            'student_id' => $untickedStudent->id,
            'attendance_date' => '2026-09-27',
        ]);
    }

    public function test_bulk_attendance_keeps_existing_check_out_when_left_blank(): void
    {
        $admin = User::factory()->create();
        $student = Student::create([
            'student_number' => '2026120',
            'name' => 'Mahasiswa Pulang',
            'program' => 'D3 Teknik Informatika',
            'is_active' => true,
        ]);
        Attendance::create([
            'student_id' => $student->id,
            'attendance_date' => '2026-09-27',
            'status' => 'present',
            'check_out' => '16:00',
            'source' => 'manual',
        ]);

        $this->actingAs($admin)
            ->post(route('attendance.bulk'), [
                'attendance_date' => '2026-09-27',
                'check_in' => '07:20',
                'student_ids' => [$student->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'attendance_date' => '2026-09-27',
            'check_out' => '16:00',
        ]);
    }

    public function test_fingerprint_scan_records_check_in_then_check_out_and_logs_both(): void
    {
        $admin = User::factory()->create(['attendance_start' => '07:30']);
        $student = Student::create([
            'student_number' => '2026130',
            'name' => 'Mahasiswa Fingerprint',
            'department' => 'Teknik Informatika',
            'program' => 'D3 Teknik Informatika',
            'class_name' => 'Pagi 1-A',
            'email' => 'fingerprint@example.test',
            'phone' => '08123456781',
            'fingerprint_id' => 'FP-AUTO-01',
            'is_active' => true,
        ]);
        $checkInTime = CarbonImmutable::parse('2026-09-27 08:05:00');

        $this->actingAs($admin)
            ->get(route('fingerprint.index'))
            ->assertOk()
            ->assertSee('ID fingerprint')
            ->assertSee('Log fingerprint terbaru');

        $this->travelTo($checkInTime);
        $this->actingAs($admin)
            ->post(route('fingerprint.scan'), ['fingerprint_id' => 'FP-AUTO-01'])
            ->assertRedirect(route('fingerprint.index'));

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'attendance_date' => '2026-09-27',
            'status' => 'late',
            'check_in' => '08:05:00',
            'source' => 'fingerprint',
        ]);

        $this->travelTo($checkInTime->addHours(8));
        $this->actingAs($admin)
            ->post(route('fingerprint.scan'), ['fingerprint_id' => 'FP-AUTO-01'])
            ->assertRedirect(route('fingerprint.index'));

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'attendance_date' => '2026-09-27',
            'check_out' => '16:05:00',
        ]);
        $this->assertDatabaseHas('fingerprint_logs', [
            'student_id' => $student->id,
            'event_type' => 'attendance_check_in',
            'result' => 'success',
        ]);
        $this->assertDatabaseHas('fingerprint_logs', [
            'student_id' => $student->id,
            'event_type' => 'attendance_check_out',
            'result' => 'success',
        ]);

        $this->travelBack();
    }

    public function test_attendance_history_is_grouped_by_calendar_date(): void
    {
        $admin = User::factory()->create();
        $student = Student::create([
            'student_number' => '2026131',
            'name' => 'Mahasiswa Riwayat',
            'department' => 'Teknik Informatika',
            'program' => 'D3 Teknik Informatika',
            'class_name' => 'Pagi 1-A',
            'email' => 'riwayat@example.test',
            'phone' => '08123456782',
            'is_active' => true,
        ]);

        foreach (['2026-09-26', '2026-09-27'] as $date) {
            Attendance::create([
                'student_id' => $student->id,
                'attendance_date' => $date,
                'status' => 'present',
                'source' => 'manual',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('attendance.index', ['month' => '2026-09']))
            ->assertOk()
            ->assertSee('Riwayat absensi per tanggal')
            ->assertSee('26 September 2026')
            ->assertSee('27 September 2026');
    }

    public function test_fingerprint_registration_is_recorded_in_fingerprint_logs(): void
    {
        $admin = User::factory()->create();
        $student = Student::create([
            'student_number' => '2026001',
            'name' => 'Nadia Putri',
            'program' => 'Teknik Informatika',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('students.fingerprint', $student), ['fingerprint_id' => 'FP-001'])
            ->assertRedirect();

        $this->assertDatabaseHas('fingerprint_logs', [
            'student_id' => $student->id,
            'fingerprint_id' => 'FP-001',
            'event_type' => 'registered',
            'result' => 'success',
        ]);
    }

    public function test_attendance_settings_are_saved_on_the_admin_user(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'campus_name' => 'Kampus Contoh',
                'attendance_start' => '07:15',
                'attendance_end' => '16:30',
            ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'campus_name' => 'Kampus Contoh',
            'attendance_start' => '07:15',
            'attendance_end' => '16:30',
        ]);
    }
}
