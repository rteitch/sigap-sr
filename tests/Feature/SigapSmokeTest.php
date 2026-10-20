<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Dorm;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SigapSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_user_can_login_and_inactive_account_is_rejected(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->post('/logout');
        $this->assertGuest();

        $admin->update(['is_active' => false]);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_open_every_main_page(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $student = Student::query()->firstOrFail();
        $incident = Incident::query()->where('confidentiality', '!=', 'sangat_rahasia')->firstOrFail();

        $this->actingAs($admin);

        $this->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Dashboard')->has('stats'));
        $this->get('/siswa')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Students/Index')->has('students'));
        $this->get('/siswa/create')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Students/Form'));
        $this->get("/siswa/{$student->id}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Students/Show'));
        $this->get("/siswa/{$student->id}/edit")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Students/Form'));
        $this->get('/siswa/import')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Students/Import'));
        $this->get('/presensi')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Attendance/Index'));
        $this->get('/asrama')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Dorm/Index'));
        $this->get('/insiden')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Incidents/Index'));
        $this->get('/insiden/buat')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Incidents/Create'));
        $this->get("/insiden/{$incident->id}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Incidents/Show'));
        $this->get('/laporan')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Reports/Index'));
        $this->get('/notifikasi')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Notifications/Index'));
        $this->get('/admin/users')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Users/Index'));
        $this->get('/admin/users/create')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Users/Form'));
        $this->get("/admin/users/{$admin->id}/edit")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Users/Form'));
        $this->get('/admin/categories')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Categories/Index'));
        $this->get('/admin/settings')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Settings/Index'));
        $this->get('/admin/audit')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Audit/Index'));
    }

    public function test_dorm_wali_can_open_their_pages_and_cannot_manage_users(): void
    {
        $wali = User::query()->where('email', 'waliasrama2@sigap.test')->firstOrFail();
        $dorm = Dorm::query()->where('wali_id', $wali->id)->firstOrFail();
        $student = Student::query()->where('dorm_id', $dorm->id)->firstOrFail();

        $this->actingAs($wali)
            ->get('/dashboard')->assertOk();
        $this->get('/presensi?type=apel_malam')->assertOk();
        $this->get('/asrama')->assertOk();
        $this->get('/insiden')->assertOk();
        $this->get("/siswa/{$student->id}")->assertOk();
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_staff_cannot_open_students_outside_their_scope(): void
    {
        $guru = User::query()->where('role', 'guru')->firstOrFail();
        $student = Student::query()->firstOrFail();

        $this->actingAs($guru)
            ->get("/siswa/{$student->id}")
            ->assertForbidden();

        $wali = User::query()->where('email', 'waliasrama2@sigap.test')->firstOrFail();
        $dorm = Dorm::query()->where('wali_id', $wali->id)->firstOrFail();
        $outsider = Student::query()->where('dorm_id', '!=', $dorm->id)->firstOrFail();

        $this->actingAs($wali)
            ->get("/siswa/{$outsider->id}")
            ->assertForbidden();
    }

    public function test_staff_cannot_access_unassigned_dorm_data(): void
    {
        $dorm = Dorm::query()->where('name', 'Melati')->firstOrFail();
        $student = Student::query()->where('dorm_id', $dorm->id)->firstOrFail();
        $wali = User::query()->where('email', 'waliasrama2@sigap.test')->firstOrFail();
        $guru = User::query()->where('role', 'guru')->firstOrFail();
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        foreach ([$wali, $guru] as $user) {
            $this->actingAs($user)
                ->get("/asrama?dorm_id={$dorm->id}")
                ->assertForbidden();
            $this->get("/presensi?type=apel_malam&dorm_id={$dorm->id}")
                ->assertForbidden();
            $this->post('/presensi', [
                'date' => now()->toDateString(),
                'type' => 'apel_malam',
                'dorm_id' => $dorm->id,
                'records' => [
                    ['student_id' => $student->id, 'status' => 'hadir', 'notes' => null],
                ],
            ])->assertForbidden();
            $this->post('/asrama/logbook', [
                'dorm_id' => $dorm->id,
                'log_date' => now()->toDateString(),
                'shift' => 'malam',
                'checkpoints' => [],
            ])->assertForbidden();
        }

        $this->actingAs($admin)
            ->get("/asrama?dorm_id={$dorm->id}")
            ->assertOk();
    }

    public function test_kepala_sekolah_can_open_confidential_case(): void
    {
        $kepsek = User::query()->where('role', 'kepala_sekolah')->firstOrFail();
        $incident = Incident::query()->where('confidentiality', 'sangat_rahasia')->firstOrFail();

        $this->actingAs($kepsek)
            ->get("/insiden/{$incident->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Incidents/Show'));
    }

    public function test_guru_can_report_an_incident(): void
    {
        $guru = User::query()->where('role', 'guru')->firstOrFail();
        $category = IncidentCategory::query()->where('name', 'Pelanggaran ringan')->firstOrFail();
        $student = Student::query()->firstOrFail();

        $response = $this->actingAs($guru)->post('/insiden', [
            'category_id' => $category->id,
            'severity' => 2,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'location' => 'Kelas',
            'description' => 'Siswa datang terlambat dan mengganggu kegiatan belajar di kelas.',
            'initial_action' => 'Siswa diajak bicara di luar kelas dan diingatkan kesepakatan.',
            'needs_medical' => false,
            'participants' => [
                ['student_id' => $student->id, 'role' => 'pelaku', 'notes' => null],
            ],
        ]);

        $incident = Incident::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('incidents.show', $incident));

        $this->assertSame('baru', $incident->status);
        $this->assertSame($guru->id, $incident->reported_by);
        $this->assertDatabaseHas('incident_students', [
            'incident_id' => $incident->id,
            'student_id' => $student->id,
            'role' => 'pelaku',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'incident.created']);
    }

    public function test_very_confidential_report_is_redirected_away_from_reporter(): void
    {
        $guru = User::query()->where('role', 'guru')->firstOrFail();
        $category = IncidentCategory::query()->where('name', 'Kekerasan seksual')->firstOrFail();
        $student = Student::query()->firstOrFail();

        $response = $this->actingAs($guru)->post('/insiden', [
            'category_id' => $category->id,
            'severity' => 4,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'location' => 'Asrama',
            'description' => 'Laporan uji untuk memastikan kasus sangat rahasia dialihkan ke jalur terbatas.',
            'initial_action' => 'Pendampingan awal dan koordinasi dengan TPPK.',
            'needs_medical' => true,
            'participants' => [
                ['student_id' => $student->id, 'role' => 'korban', 'notes' => null],
            ],
        ]);

        $response->assertRedirect(route('incidents.index'));
        $response->assertSessionHas('info');

        $incident = Incident::query()->latest('id')->firstOrFail();
        $this->assertSame('sangat_rahasia', $incident->confidentiality);
        $this->assertFalse($incident->canBeViewedBy($guru));
    }

    public function test_absent_dorm_attendance_creates_missing_student_incident(): void
    {
        $wali = User::query()->where('email', 'waliasrama2@sigap.test')->firstOrFail();
        $dorm = Dorm::query()->where('wali_id', $wali->id)->firstOrFail();
        $student = Student::query()->where('dorm_id', $dorm->id)->where('status', 'aktif')->firstOrFail();
        $date = now()->subDays(40)->toDateString();
        $category = IncidentCategory::query()->where('name', 'like', 'Kabur%')->firstOrFail();

        $this->actingAs($wali)->post('/presensi', [
            'date' => $date,
            'type' => 'apel_malam',
            'dorm_id' => $dorm->id,
            'records' => [
                ['student_id' => $student->id, 'status' => 'alpa', 'notes' => 'Tidak ada di kamar saat apel malam.'],
            ],
        ])->assertRedirect();

        $this->assertTrue(
            AttendanceRecord::query()
                ->where('student_id', $student->id)
                ->whereDate('date', $date)
                ->where('type', 'apel_malam')
                ->where('status', 'alpa')
                ->exists()
        );

        $incident = Incident::query()
            ->where('category_id', $category->id)
            ->whereDate('occurred_at', $date)
            ->whereHas('students', fn ($query) => $query->where('students.id', $student->id))
            ->first();

        $this->assertNotNull($incident);
        $this->assertSame(3, $incident->severity);
        $this->assertSame('baru', $incident->status);
    }

    public function test_today_attendance_and_dorm_log_are_read_back(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $dorm = Dorm::query()->firstOrFail();
        $today = now()->toDateString();

        $this->assertGreaterThan(
            0,
            AttendanceRecord::query()->where('date', $today)->where('type', 'apel_malam')->count()
        );

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('stats.dorm_total', fn ($value) => $value > 0));

        $this->post('/asrama/logbook', [
            'dorm_id' => $dorm->id,
            'log_date' => $today,
            'shift' => 'malam',
            'checkpoints' => ['Gerbang asrama'],
            'notes' => 'Uji logbook hari ini.',
        ])->assertRedirect();

        $this->get("/asrama?dorm_id={$dorm->id}&date={$today}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where(
                'logs',
                fn ($logs) => $logs->contains('notes', 'Uji logbook hari ini.')
            ));
    }

    public function test_student_edit_form_preserves_sensitive_fields(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $student = Student::query()->firstOrFail();
        $student->update([
            'special_needs' => 'Alergi kacang dan perlu pendampingan belajar.',
            'socioeconomic_desil' => 2,
            'consent_given_at' => now(),
            'consent_scope' => 'Pemrosesan data pengasuhan dan evaluasi.',
            'consent_document' => 'Formulir persetujuan nomor 12.',
        ]);

        $this->actingAs($admin)
            ->get("/siswa/{$student->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Form')
                ->where('student.special_needs', 'Alergi kacang dan perlu pendampingan belajar.')
                ->where('student.socioeconomic_desil', 2)
                ->where('student.consent_scope', 'Pemrosesan data pengasuhan dan evaluasi.')
                ->where('student.consent_document', 'Formulir persetujuan nomor 12.')
            );
    }

    public function test_student_import_preview_and_confirm_flow(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['kode', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'kelas', 'asrama', 'status'],
            ['SR-2026-9999', 'Siswa Impor Uji', 'P', '2012-01-01', '7A', 'Melati', 'aktif'],
        ]);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'sigap-import-');
        $path = $temporaryPath.'.xlsx';
        @unlink($temporaryPath);
        (new Xlsx($spreadsheet))->save($path);

        $this->actingAs($admin)
            ->post('/siswa/import/pratinjau', [
                'file' => new UploadedFile($path, 'impor-siswa.xlsx', null, null, true),
            ])
            ->assertRedirect(route('students.import'));

        $this->get('/siswa/import')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Students/Import')->where('preview.valid_count', 1)
        );

        $this->post('/siswa/import/konfirmasi')->assertRedirect(route('students.index'));

        $student = Student::query()->where('code', 'SR-2026-9999')->firstOrFail();
        $this->assertSame('Siswa Impor Uji', $student->identity?->full_name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.imported']);

        @unlink($path);
    }
}
