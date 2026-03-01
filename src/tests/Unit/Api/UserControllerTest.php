<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::factory()->create(['name' => 'admin']);
        Role::factory()->create(['name' => 'student']);

        // Create an admin user to authenticate with on protected routes
        $this->admin = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $this->admin->roles()->attach($adminRole); // adjust if your relationship differs
    }

    /** @test */
    public function it_can_register_a_user_and_create_student_if_role_is_student()
    {
        $studentRole = Role::where('name', 'student')->first();

        $response = $this->actingAs($this->admin)   // <-- authenticate
            ->postJson('/api/admin/register', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'role_id' => $studentRole->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['email' => 'john@example.com']);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseHas('students', ['user_id' => $user->id]);
    }

    /** @test */
    public function it_can_update_a_user_and_role()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'admin')->first();

        $response = $this->actingAs($this->admin)   // <-- authenticate
            ->putJson("/api/admin/users/{$user->id}", [
                'name' => 'Updated Name',
                'role_id' => $role->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name']);
        $this->assertEquals($role->id, $user->fresh()->roles->first()->id);
    }

    /** @test */
    public function it_can_delete_a_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)   // <-- authenticate
            ->deleteJson("/api/admin/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'User Deleted successfully']);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function it_can_send_forgot_password_link()
    {
        $user = User::factory()->create();

        Notification::fake(); // <-- correct: intercepts the ResetPassword notification

        $response = $this->postJson('/api/auth/password/forgot', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Reset link sent']);

        // Optionally assert the notification was actually sent
        Notification::assertSentTo($user, ResetPassword::class);
    }

    /** @test */
    public function it_can_reset_password()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/password/reset', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Password reset successfully']);
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }
}