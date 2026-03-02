<?php

namespace Tests\Feature\Feature\Api;

use App\Models\Program;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::factory()->create(['name' => 'admin']);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);
    }

    /** @test */
    public function admin_can_create_program()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/programs', [
                'name' => 'BSIT'
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('programs', [
            'name' => 'BSIT'
        ]);
    }

    /** @test */
    public function admin_can_attach_subject_to_program()
    {
        $program = Program::factory()->create();
        $subject = Subject::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/programs/{$program->id}/curriculum", [
                'subject_id' => $subject->id,
                'year_level' => 1,
                'semester' => '1st'
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('curriculum', [
            'program_id' => $program->id,
            'subject_id' => $subject->id,
            'year_level' => 1,
            'semester' => '1st'
        ]);
    }
}
