<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Dorm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentFormInterconnectivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_student_create_form_provides_classrooms_with_homeroom_teacher(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/siswa/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Form')
                ->has('classrooms')
                ->where('classrooms.0.id', fn ($id) => is_numeric($id))
                ->has('classrooms.0.name')
                ->has('classrooms.0.level')
                ->has('classrooms.0.homeroom_teacher')
            );
    }

    public function test_student_create_form_provides_dorms_with_wali_name(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/siswa/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Form')
                ->has('dorms')
                ->where('dorms.0.id', fn ($id) => is_numeric($id))
                ->has('dorms.0.name')
                ->has('dorms.0.gender')
                ->has('dorms.0.wali_name')
            );
    }

    public function test_student_store_persists_classroom_and_dorm_correctly(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $classroom = Classroom::query()->where('school_id', $admin->school_id)->firstOrFail();
        $dorm = Dorm::query()->where('school_id', $admin->school_id)->where('gender', 'L')->firstOrFail();

        $response = $this->actingAs($admin)->post('/siswa', [
            'code' => 'SR-TEST-0099',
            'full_name' => 'Ahmad Santoso Test',
            'nickname' => 'Ahmad',
            'gender' => 'L',
            'birth_date' => '2012-05-10',
            'birth_place' => 'Surabaya',
            'entry_date' => '2026-07-15',
            'status' => 'aktif',
            'classroom_id' => $classroom->id,
            'dorm_id' => $dorm->id,
            'address' => 'Jl. Pemuda No. 10',
            'consent_given_at' => '2026-07-15',
            'consent_scope' => 'Pendidikan dan pengasuhan',
            'contacts' => [
                [
                    'name' => 'Bapak Santoso',
                    'relation' => 'Ayah',
                    'phone' => '081234567891',
                    'is_primary' => true,
                    'can_pickup' => true,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('students', [
            'code' => 'SR-TEST-0099',
            'classroom_id' => $classroom->id,
            'dorm_id' => $dorm->id,
            'gender' => 'L',
        ]);

        $this->assertDatabaseHas('student_identities', [
            'full_name' => 'Ahmad Santoso Test',
        ]);
    }
}
