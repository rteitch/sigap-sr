<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_case_handler_can_add_custom_task_to_incident(): void
    {
        $kepsek = User::query()->where('role', 'kepala_sekolah')->firstOrFail();
        $incident = Incident::query()->where('school_id', $kepsek->school_id)->firstOrFail();

        $response = $this->actingAs($kepsek)
            ->post("/insiden/{$incident->id}/tugas", [
                'label' => 'Koordinasi dengan tim psikolog luar sekolah',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('incident_tasks', [
            'incident_id' => $incident->id,
            'label' => 'Koordinasi dengan tim psikolog luar sekolah',
            'is_done' => false,
        ]);
    }

    public function test_task_label_requires_minimum_length(): void
    {
        $kepsek = User::query()->where('role', 'kepala_sekolah')->firstOrFail();
        $incident = Incident::query()->where('school_id', $kepsek->school_id)->firstOrFail();

        $this->actingAs($kepsek)
            ->post("/insiden/{$incident->id}/tugas", [
                'label' => 'ab',
            ])
            ->assertSessionHasErrors('label');
    }

    public function test_unauthorized_user_cannot_add_task_to_incident(): void
    {
        $guru = User::query()->where('role', 'guru')->firstOrFail();
        $incident = Incident::query()
            ->where('school_id', $guru->school_id)
            ->where('assigned_to', '!=', $guru->id)
            ->firstOrFail();

        $this->actingAs($guru)
            ->post("/insiden/{$incident->id}/tugas", [
                'label' => 'Langkah tidak berwenang',
            ])
            ->assertForbidden();
    }
}
