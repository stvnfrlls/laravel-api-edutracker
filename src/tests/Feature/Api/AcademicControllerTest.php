<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\User;
use App\Models\SchoolYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::factory()->create(['name' => 'admin']);
        $studentRole = Role::factory()->create(['name' => 'student']);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->student = User::factory()->create();
        $this->student->roles()->attach($studentRole);
    }

    /** @test */
    public function admin_can_create_school_year()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/academic/school-years', [
                'name' => '2025-2026'
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('school_years', [
            'name' => '2025-2026'
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_school_year()
    {
        $response = $this->actingAs($this->student)
            ->postJson('/api/admin/academic/school-years', [
                'name' => '2025-2026'
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_school_year()
    {
        $response = $this->postJson('/api/admin/academic/school-years', [
            'name' => '2025-2026'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function school_year_name_must_be_unique()
    {
        SchoolYear::create(['name' => '2025-2026']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/academic/school-years', [
                'name' => '2025-2026'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function only_one_school_year_can_be_active()
    {
        SchoolYear::create([
            'name' => '2024-2025',
            'is_active' => true
        ]);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/academic/school-years', [
                'name' => '2025-2026',
                'is_active' => true
            ]);

        $this->assertDatabaseHas('school_years', [
            'name' => '2024-2025',
            'is_active' => false
        ]);

        $this->assertDatabaseHas('school_years', [
            'name' => '2025-2026',
            'is_active' => true
        ]);
    }
}