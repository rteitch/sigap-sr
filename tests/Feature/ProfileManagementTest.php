<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_from_profile_page(): void
    {
        $this->get('/profil')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $user = User::query()->where('role', 'guru')->firstOrFail();

        $this->actingAs($user)
            ->get('/profil')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->has('user')
                ->where('user.id', $user->id)
                ->where('user.name', $user->name)
                ->where('user.email', $user->email)
            );
    }

    public function test_user_can_update_profile_name_and_phone(): void
    {
        $user = User::query()->where('role', 'guru')->firstOrFail();

        $this->actingAs($user)
            ->patch('/profil', [
                'name' => 'Guru Teladan, S.Pd',
                'phone' => '081234567890',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Guru Teladan, S.Pd', $user->name);
        $this->assertSame('081234567890', $user->phone);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.profile_updated',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_change_password_with_valid_current_password(): void
    {
        $user = User::query()->where('role', 'guru')->firstOrFail();

        $this->actingAs($user)
            ->put('/profil/password', [
                'current_password' => 'password',
                'password' => 'NewSecretPassword123!',
                'password_confirmation' => 'NewSecretPassword123!',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecretPassword123!', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.password_changed',
            'user_id' => $user->id,
        ]);
    }

    public function test_password_change_fails_with_invalid_current_password(): void
    {
        $user = User::query()->where('role', 'guru')->firstOrFail();

        $this->actingAs($user)
            ->put('/profil/password', [
                'current_password' => 'wrong-current-password',
                'password' => 'NewSecretPassword123!',
                'password_confirmation' => 'NewSecretPassword123!',
            ])
            ->assertSessionHasErrors('current_password');

        $user->refresh();
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
