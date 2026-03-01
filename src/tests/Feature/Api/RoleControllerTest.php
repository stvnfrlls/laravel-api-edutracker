<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $targetUser;
    protected Role $adminRole;
    protected Role $studentRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->studentRole = Role::factory()->create(['name' => 'student']);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);

        $this->targetUser = User::factory()->create();
    }

    /*
    |--------------------------------------------------------------------------
    | Assign Role
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_assign_a_role_to_a_user()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$this->targetUser->id}/roles", [
                'role' => 'student', // <-- name string, not role_id
            ]);

        $response->assertStatus(200);

        $this->assertTrue(
            $this->targetUser->fresh()->roles->contains($this->studentRole)
        );
    }

    /** @test */
    public function it_cannot_assign_a_nonexistent_role()
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$this->targetUser->id}/roles", [
                'role' => 'nonexistent', // <-- name string
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_assign_a_duplicate_role()
    {
        $this->targetUser->roles()->attach($this->studentRole);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$this->targetUser->id}/roles", [
                'role' => 'student', // <-- name string
            ]);

        $response->assertStatus(409); // controller explicitly returns 409 for duplicates
        $this->assertCount(1, $this->targetUser->fresh()->roles);
    }

    /*
    |--------------------------------------------------------------------------
    | Revoke Role
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_revoke_a_role_from_a_user()
    {
        $this->targetUser->roles()->attach($this->studentRole);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/users/{$this->targetUser->id}/roles/{$this->studentRole->id}");

        $response->assertStatus(200);

        $this->assertFalse(
            $this->targetUser->fresh()->roles->contains($this->studentRole)
        );
    }

    /** @test */
    public function it_cannot_revoke_a_role_the_user_does_not_have()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/users/{$this->targetUser->id}/roles/{$this->studentRole->id}");

        $response->assertStatus(404); // or 422 depending on your implementation
    }

    /*
    |--------------------------------------------------------------------------
    | List Roles
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_list_roles_of_a_user()
    {
        $this->targetUser->roles()->attach($this->studentRole);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->targetUser->id}/roles");

        $response->assertStatus(200)
            ->assertJson(['roles' => ['student']]); // matches {"roles": ["student"]}
    }

    /** @test */
    public function it_returns_empty_array_when_user_has_no_roles()
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->targetUser->id}/roles");

        $response->assertStatus(200)
            ->assertJson(['roles' => []]); // matches {"roles": []}
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_prevents_admin_from_revoking_their_own_admin_role()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/users/{$this->admin->id}/roles/{$this->adminRole->id}");

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'You cannot remove your own admin role.']);
    }

    /** @test */
    public function it_prevents_non_admin_from_managing_roles()
    {
        $student = User::factory()->create();
        $student->roles()->attach($this->studentRole);

        $this->actingAs($student)
            ->postJson("/api/admin/users/{$this->targetUser->id}/roles", [
                'role_id' => $this->adminRole->id,
            ])
            ->assertStatus(403);
    }
}