<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::query()->where('role', 'admin')->firstOrFail();
    }

    public function test_admin_can_view_master_data_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/master')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/MasterData/Index')
                ->has('academicYears')
                ->has('levels')
                ->has('classrooms')
                ->has('dorms')
                ->has('locations')
                ->has('teachers')
            );
    }

    public function test_admin_can_create_and_activate_academic_year(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/master/academic-years', [
                'name' => '2026/2027',
                'start_date' => '2026-07-01',
                'end_date' => '2027-06-30',
                'is_active' => true,
            ])
            ->assertRedirect();

        $year = AcademicYear::query()->where('name', '2026/2027')->firstOrFail();

        $this->assertTrue($year->is_active);
        $this->assertSame(1, AcademicYear::query()->where('is_active', true)->count());
        $this->assertSame('2026/2027', $this->admin->school->fresh()->setting('academic_year'));
    }

    public function test_admin_can_create_master_records_and_classroom_labels_are_synced(): void
    {
        $level = Level::query()->where('code', 'SMP')->firstOrFail();
        $year = AcademicYear::query()->where('name', '2025/2026')->firstOrFail();

        $this->actingAs($this->admin)
            ->post('/admin/master/classrooms', [
                'name' => 'IX Z',
                'level_id' => $level->id,
                'academic_year_id' => $year->id,
                'homeroom_teacher_id' => null,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('classrooms', [
            'school_id' => $this->admin->school_id,
            'name' => 'IX Z',
            'level' => 'SMP',
            'academic_year' => '2025/2026',
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/master/dorms', [
                'name' => 'Asrama Anggrek',
                'gender' => 'P',
                'capacity' => 40,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('dorms', [
            'school_id' => $this->admin->school_id,
            'name' => 'Asrama Anggrek',
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/master/locations', [
                'name' => 'Gerbang Belakang',
                'sort_order' => 9,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('incident_locations', [
            'school_id' => $this->admin->school_id,
            'name' => 'Gerbang Belakang',
        ]);
    }

    public function test_admin_can_update_branding_and_upload_logo(): void
    {
        Storage::fake('branding');

        $logo = UploadedFile::fake()->createWithContent(
            'logo.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
        );

        $this->actingAs($this->admin)
            ->patch('/admin/settings', [
                'name' => 'Sekolah Rakyat Nusantara',
                'npsn' => '12345678',
                'level' => 'SMP',
                'address' => 'Jl. Pendidikan',
                'phone' => '021123456',
                'small_group_threshold' => 5,
                'missing_student_escalation_minutes' => 120,
                'app_name' => 'SIGAP Nusantara',
                'tagline' => 'Anak aman, tumbuh, dan berprestasi',
                'primary_color' => '#123456',
                'logo' => $logo,
                'remove_logo' => false,
            ])
            ->assertRedirect();

        $school = $this->admin->school->fresh();

        $this->assertSame('SIGAP Nusantara', $school->setting('app_name'));
        $this->assertSame('#123456', $school->setting('primary_color'));
        $this->assertTrue(Storage::disk('branding')->exists($school->setting('logo_path')));

        $this->actingAs($this->admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/Index')
                ->where('school.app_name', 'SIGAP Nusantara')
                ->where('school.primary_color', '#123456')
                ->has('levels')
                ->has('developer')
            );
    }

    public function test_demo_accounts_are_hidden_unless_enabled(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('branding.demo.enabled', false)
                ->where('branding.demo.accounts', [])
            );

        config(['sigap.demo.enabled' => true]);

        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('branding.demo.enabled', true)
                ->has('branding.demo.accounts', 6)
            );
    }

    public function test_incident_uses_master_location(): void
    {
        $location = IncidentLocation::query()->where('school_id', $this->admin->school_id)->firstOrFail();
        $student = Student::query()->where('school_id', $this->admin->school_id)->firstOrFail();
        $category = IncidentCategory::query()->where('is_positive', false)->firstOrFail();

        $this->actingAs($this->admin)
            ->post('/insiden', [
                'category_id' => $category->id,
                'severity' => 1,
                'occurred_at' => now()->format('Y-m-d H:i:s'),
                'location_id' => $location->id,
                'description' => 'Siswa terlihat membutuhkan pendampingan di lokasi ini.',
                'needs_medical' => false,
                'participants' => [
                    ['student_id' => $student->id, 'role' => 'saksi', 'notes' => ''],
                ],
            ])
            ->assertRedirect();

        $incident = Incident::query()->latest('id')->firstOrFail();

        $this->assertSame($location->id, $incident->incident_location_id);
        $this->assertSame($location->name, $incident->location);
    }

    public function test_non_admin_cannot_manage_master_data(): void
    {
        $teacher = User::query()->where('role', 'guru')->firstOrFail();
        $location = IncidentLocation::query()->where('school_id', $this->admin->school_id)->firstOrFail();

        $this->actingAs($teacher)
            ->patch("/admin/master/locations/{$location->id}", [
                'name' => 'Diubah guru',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_master_data_is_school_scoped(): void
    {
        $otherSchool = School::query()->create([
            'name' => 'Sekolah Lain',
            'level' => 'SMP',
        ]);
        $location = IncidentLocation::query()->create([
            'school_id' => $otherSchool->id,
            'name' => 'Lokasi Sekolah Lain',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->patch("/admin/master/locations/{$location->id}", [
                'name' => 'Tidak boleh berubah',
                'is_active' => true,
            ])
            ->assertNotFound();
    }
}
