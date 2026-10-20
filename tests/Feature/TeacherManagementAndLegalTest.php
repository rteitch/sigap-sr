<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeacherManagementAndLegalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_privacy_policy_page_is_publicly_accessible(): void
    {
        $this->get('/kebijakan-privasi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Legal/Privacy')
                ->has('updatedAt')
            );
    }

    public function test_terms_of_service_page_is_publicly_accessible(): void
    {
        $this->get('/persyaratan-layanan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Legal/Terms')
                ->has('updatedAt')
            );
    }

    public function test_authenticated_user_can_access_legal_pages(): void
    {
        $user = User::query()->where('role', 'guru')->firstOrFail();

        $this->actingAs($user)->get('/kebijakan-privasi')->assertOk();
        $this->actingAs($user)->get('/persyaratan-layanan')->assertOk();
    }

    public function test_admin_users_index_returns_teacher_and_dorm_assignments(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $waliKelas = User::query()->where('role', 'wali_kelas')->firstOrFail();

        // Assign to a classroom
        $classroom = Classroom::query()->where('school_id', $admin->school_id)->firstOrFail();
        $classroom->update(['homeroom_teacher_id' => $waliKelas->id]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data')
                ->has('users.data.0.homeroom_classes')
                ->has('users.data.0.dorm_assignments')
            );
    }
}
