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

    public function test_student_form_contains_the_requested_academic_options(): void
    {
        Student::create([
            'student_number' => '2026008',
            'name' => 'Mahasiswa Modal',
            'department' => 'Teknik Informatika',
            'program' => 'Informatika',
            'class_name' => 'Pagi Web A',
            'is_active' => true,
        ]);

        $this->assertSame([
            'Pagi Web A',
            'Pagi Mobile B',
            'Pagi IoT C',
            'Pagi AI D',
            'Pagi Web E',
        ], config('academic.class_options'));

        $this->actingAs(User::factory()->create())
            ->get(route('fingerprint.index'))
            ->assertOk()
            ->assertSee('Teknik Informatika')
            ->assertSee('Informatika')
            ->assertSee('Pagi Web A')
            ->assertSee('Pagi Mobile B')
            ->assertSee('Pagi IoT C')
            ->assertSee('Pagi AI D')
            ->assertSee('Pagi Web E')
            ->assertDontSee('Teknik Elektro')
            ->assertDontSee('Pagi 1-A');

        $this->get(route('students.index'))
            ->assertOk()
            ->assertDontSee('Tambah mahasiswa')
            ->assertSee('data-edit-student', false)
            ->assertSee('data-delete-student', false);
        $this->get('/students/create')->assertStatus(405);
    }

    public function test_unverified_admin_can_save_a_student_and_return_to_the_list(): void
    {
        $admin = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($admin)
            ->post(route('students.store'), [
                'student_number' => '2026009',
                'name' => 'Mahasiswa Uji',
                'department' => 'Teknik Informatika',
                'program' => 'Informatika',
                'class_name' => 'Pagi Web A',
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
            ->from(route('fingerprint.index'))
            ->post(route('students.store'), [
                'student_number' => '2026099',
                'name' => 'Data Belum Lengkap',
                'program' => 'Informatika',
                'is_active' => '1',
            ])
            ->assertRedirect(route('fingerprint.index'))
            ->assertSessionHasErrors(['class_name', 'email', 'phone']);

        $this->assertDatabaseMissing('students', ['student_number' => '2026099']);
    }

    public function test_student_program_must_belong_to_the_selected_department(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('fingerprint.index'))
            ->post(route('students.store'), [
                'student_number' => '2026199',
                'name' => 'Jurusan Tidak Cocok',
                'department' => 'Teknik Informatika',
                'program' => 'D3 Teknik Informatika',
                'class_name' => 'Pagi Web A',
                'email' => 'jurusan.test@example.test',
                'phone' => '08123456789',
                'is_active' => '1',
            ])
            ->assertRedirect(route('fingerprint.index'))
            ->assertSessionHasErrors('program');

        $this->assertDatabaseMissing('students', ['student_number' => '2026199']);
    }

    public function test_legacy_program_name_is_not_accepted_for_student_updates(): void
    {
        $student = Student::create([
            'student_number' => '2026188',
            'name' => 'Mahasiswa Lama',
            'department' => 'Teknik Informatika',
            'program' => 'D3 Teknik Informatika',
            'class_name' => 'Pagi Web A',
            'email' => 'mahasiswa.lama@example.test',
            'phone' => '08123456780',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->from(route('students.index'))
            ->put(route('students.update', $student), [
                'student_number' => $student->student_number,
                'name' => $student->name,
                'department' => 'Teknik Informatika',
                'program' => 'D3 Teknik Informatika',
                'class_name' => 'Pagi Web A',
                'email' => $student->email,
                'phone' => $student->phone,
                'is_active' => '1',
            ])
            ->assertRedirect(route('students.index'))
            ->assertSessionHasErrors('program');
    }

    public function test_student_live_search_returns_filtered_json(): void
    {
        Student::create([
            'student_number' => '2026100',
            'name' => 'Nadia Putri',
            'department' => 'Teknik Informatika',
            'program' => 'Informatika',
            'class_name' => 'Pagi Web A',
            'is_active' => true,
        ]);
        Student::create([
            'student_number' => '2026101',
            'name' => 'Budi Santoso',
            'department' => 'Manajemen Bisnis',
            'program' => 'Akuntansi',
            'class_name' => 'Pagi Mobile B',
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
            ->assertSee('Reset')
            ->assertDontSee('Tambah mahasiswa')
            ->assertSee('data-edit-student', false)
            ->assertSee('data-delete-student', false);

        $response = $this->getJson(route('students.index', [
            'q' => 'Nadia',
            'department' => 'Teknik Informatika',
            'program' => 'Informatika',
            'class_name' => 'Pagi Web A',
        ]));

        $response->assertOk()->assertJsonPath('total', 1);
        $this->assertStringContainsString('Nadia Putri', $response->json('rows'));
        $this->assertStringNotContainsString('Budi Santoso', $response->json('rows'));

        $this->getJson(route('students.index', [
            'department' => 'Teknik Informatika',
            'program' => 'Informatika',
            'class_name' => 'Pagi Mobile B',
        ]))
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_attendance_history_is_selected_by_year_month_and_day_then_grouped_by_class(): void
    {
        $admin = User::factory()->create();
        $webStudent = Student::create([
            'student_number' => '2026010',
            'name' => 'Mahasiswa Kelas Web',
            'program' => 'Informatika',
            'class_name' => 'Pagi Web A',
            'is_active' => true,
        ]);
        $mobileStudent = Student::create([
            'student_number' => '2026011',
            'name' => 'Mahasiswa Kelas Mobile',
            'program' => 'Informatika',
            'class_name' => 'Pagi Mobile B',
            'is_active' => true,
        ]);
        foreach ([$webStudent, $mobileStudent] as $student) {
            Attendance::create([
                'student_id' => $student->id,
                'attendance_date' => '2026-09-27',
                'status' => 'present',
                'source' => 'fingerprint',
            ]);
        }
        Attendance::create([
            'student_id' => $webStudent->id,
            'attendance_date' => '2026-09-26',
            'status' => 'present',
            'source' => 'fingerprint',
        ]);

        $this->actingAs($admin)
            ->get(route('attendance.index', [
                'year' => 2026,
                'month_number' => 9,
                'day' => 27,
            ]))
            ->assertOk()
            ->assertSee('Tahun')
            ->assertSee('Bulan')
            ->assertSee('Hari / tanggal')
            ->assertSee('Riwayat absensi per kelas')
            ->assertSee('Mahasiswa Kelas Web')
            ->assertSee('Mahasiswa Kelas Mobile')
            ->assertSee('27 September 2026')
            ->assertDontSee('26 September 2026')
            ->assertDontSee('Absensi massal');

        $this->get(route('attendance.index', [
            'year' => 2026,
            'month_number' => 9,
            'day' => 31,
        ]))->assertSessionHasErrors('day');
    }

    public function test_fingerprint_scan_records_check_in_then_check_out_and_logs_both(): void
    {
        $admin = User::factory()->create(['attendance_start' => '07:30']);
        $student = Student::create([
            'student_number' => '2026130',
            'name' => 'Mahasiswa Fingerprint',
            'department' => 'Teknik Informatika',
            'program' => 'Informatika',
            'class_name' => 'Pagi Web A',
            'email' => 'fingerprint@example.test',
            'phone' => '08123456781',
            'fingerprint_id' => 'FP-AUTO-01',
            'is_active' => true,
        ]);
        $checkInTime = CarbonImmutable::parse('2026-09-27 08:05:00');

        $this->actingAs($admin)
            ->get(route('fingerprint.index'))
            ->assertOk()
            ->assertSee('Tambah mahasiswa')
            ->assertSee('/api/device/fingerprint/scan')
            ->assertSee('Riwayat perangkat')
            ->assertDontSee('Catat scan')
            ->assertDontSee('Masukkan ID fingerprint');

        config(['services.fingerprint.device_token' => 'test-device-token']);
        $this->travelTo($checkInTime);
        $this->withToken('test-device-token')
            ->postJson(route('device.fingerprint.scan'), [
                'fingerprint_id' => 'FP-AUTO-01',
                'type' => 'check_in',
                'device_id' => 'ESP32-WEB-A',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Check-in berhasil');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'attendance_date' => '2026-09-27',
            'status' => 'late',
            'check_in' => '08:05:00',
            'source' => 'fingerprint',
        ]);

        $this->travelTo($checkInTime->addHours(8));
        $this->withToken('test-device-token')
            ->postJson(route('device.fingerprint.scan'), [
                'fingerprint_id' => 'FP-AUTO-01',
                'type' => 'check_out',
                'device_id' => 'ESP32-WEB-A',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Check-out berhasil');

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
        $this->assertDatabaseHas('fingerprint_logs', [
            'student_id' => $student->id,
            'device_id' => 'ESP32-WEB-A',
            'event_type' => 'attendance_check_in',
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
            'program' => 'Informatika',
            'class_name' => 'Pagi Web A',
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
            ->assertSee('Riwayat absensi per hari')
            ->assertSee('27 September 2026');
    }

    public function test_device_enrollment_is_logged_and_bound_to_student(): void
    {
        $student = Student::create([
            'student_number' => '2026001',
            'name' => 'Nadia Putri',
            'department' => 'Teknik Informatika',
            'program' => 'Informatika',
            'class_name' => 'Pagi Web A',
            'is_active' => true,
        ]);

        config(['services.fingerprint.device_token' => 'test-device-token']);
        $this->withToken('test-device-token')
            ->postJson(route('device.fingerprint.enroll'), [
                'student_number' => $student->student_number,
                'fingerprint_id' => 'FP-001',
                'device_id' => 'ESP32-WEB-A',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Fingerprint berhasil didaftarkan');

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'fingerprint_id' => 'FP-001',
        ]);

        $this->assertDatabaseHas('fingerprint_logs', [
            'student_id' => $student->id,
            'fingerprint_id' => 'FP-001',
            'event_type' => 'registered',
            'result' => 'success',
            'device_id' => 'ESP32-WEB-A',
        ]);
    }

    public function test_device_api_requires_a_configured_valid_token(): void
    {
        config(['services.fingerprint.device_token' => null]);
        $this->postJson(route('device.fingerprint.scan'), [
            'fingerprint_id' => 'FP-001',
            'type' => 'check_in',
        ])->assertServiceUnavailable();

        config(['services.fingerprint.device_token' => 'test-device-token']);
        $this->postJson(route('device.fingerprint.scan'), [
            'fingerprint_id' => 'FP-001',
            'type' => 'check_in',
        ])->assertUnauthorized();
    }

    public function test_student_crud_actions_are_modal_and_add_is_only_on_fingerprint_page(): void
    {
        $admin = User::factory()->create();
        $student = Student::create([
            'student_number' => '2026200',
            'name' => 'Mahasiswa Modal',
            'department' => 'Teknik Informatika',
            'program' => 'Informatika',
            'class_name' => 'Pagi Web A',
            'email' => 'modal@example.test',
            'phone' => '08123456780',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('students.index'))
            ->assertOk()
            ->assertDontSee('Tambah mahasiswa')
            ->assertSee('data-edit-student', false)
            ->assertSee('data-delete-student', false)
            ->assertSee('id="student-edit-modal"', false)
            ->assertSee('id="student-delete-modal"', false);

        $this->get(route('fingerprint.index'))
            ->assertOk()
            ->assertSee('data-open-student-modal', false)
            ->assertSee('id="student-create-modal"', false)
            ->assertSee('name="origin" value="fingerprint"', false);

        $this->post(route('students.store'), [
            'origin' => 'fingerprint',
            'student_number' => '2026201',
            'name' => 'Mahasiswa Baru dari Fingerprint',
            'department' => 'Teknik Informatika',
            'program' => 'Informatika',
            'class_name' => 'Pagi IoT C',
            'email' => 'baru@example.test',
            'phone' => '08123456789',
            'is_active' => '1',
        ])->assertRedirect(route('fingerprint.index'));

        $this->put(route('students.update', $student), [
            'student_id' => (string) $student->id,
            'student_number' => $student->student_number,
            'name' => 'Nama Diperbarui',
            'department' => 'Teknik Informatika',
            'program' => 'Informatika',
            'class_name' => 'Pagi Mobile B',
            'email' => $student->email,
            'phone' => $student->phone,
            'is_active' => '1',
        ])->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Nama Diperbarui',
            'class_name' => 'Pagi Mobile B',
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
