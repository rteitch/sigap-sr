<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Dorm;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentPhotoAndIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('branding');
    }

    public function test_admin_can_create_student_with_photo_and_birth_details(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $classroom = Classroom::query()->where('school_id', $admin->school_id)->firstOrFail();
        $dorm = Dorm::query()->where('school_id', $admin->school_id)->firstOrFail();

        $photo = UploadedFile::fake()->image('santri.jpg', 300, 300);

        $response = $this->actingAs($admin)->post('/siswa', [
            'code' => 'SR-2026-9090',
            'full_name' => 'Ahmad Santri Teladan',
            'nickname' => 'Ahmad',
            'gender' => 'L',
            'birth_date' => '2012-05-14',
            'birth_place' => 'Surabaya',
            'entry_date' => '2026-07-15',
            'status' => 'aktif',
            'classroom_id' => $classroom->id,
            'dorm_id' => $dorm->id,
            'address' => 'Jl. Asrama No. 12',
            'photo' => $photo,
        ]);

        $response->assertRedirect();

        $student = Student::query()->where('code', 'SR-2026-9090')->firstOrFail();
        $this->assertNotNull($student->identity);
        $this->assertSame('Ahmad Santri Teladan', $student->identity->full_name);
        $this->assertSame('Surabaya', $student->identity->birth_place);
        $this->assertSame('2012-05-14', $student->birth_date->toDateString());
        $this->assertNotNull($student->identity->photo_path);

        Storage::disk('branding')->assertExists($student->identity->photo_path);
        $this->assertNotNull($student->photo_url);
        $this->assertIsInt($student->age);
    }

    public function test_admin_can_remove_existing_student_photo(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $student = Student::query()->where('school_id', $admin->school_id)->firstOrFail();

        // Seed photo first
        $photo = UploadedFile::fake()->image('original.png', 200, 200);
        $storedPath = $photo->store('avatars', 'branding');
        $student->identity->update(['photo_path' => $storedPath]);

        Storage::disk('branding')->assertExists($storedPath);

        // Update student with remove_photo = true
        $response = $this->actingAs($admin)->put("/siswa/{$student->id}", [
            'full_name' => $student->identity->full_name,
            'nickname' => $student->identity->nickname,
            'gender' => $student->gender,
            'status' => $student->status,
            'remove_photo' => true,
        ]);

        $response->assertRedirect();

        $student->identity->refresh();
        $this->assertNull($student->identity->photo_path);
        Storage::disk('branding')->assertMissing($storedPath);
    }

    public function test_student_index_and_show_return_photo_and_age_data(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $student = Student::query()->where('school_id', $admin->school_id)->firstOrFail();
        $student->update(['birth_date' => '2011-08-20']);

        $this->actingAs($admin)
            ->get('/siswa')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Index')
                ->has('students.data.0.age')
                ->has('students.data.0.photo_url')
            );

        $this->actingAs($admin)
            ->get("/siswa/{$student->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Show')
                ->has('student.photo_url')
                ->has('student.birth_date')
                ->has('student.age')
            );
    }
}
