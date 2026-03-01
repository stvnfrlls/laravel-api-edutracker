<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentUser;
    protected Student $student;
    protected Role $studentRole;

    protected function setUp(): void
    {
        parent::setUp();

        Role::factory()->create(['name' => 'admin']);
        $this->studentRole = Role::factory()->create(['name' => 'student']);

        $this->studentUser = User::factory()->create();
        $this->studentUser->roles()->attach($this->studentRole);

        $this->student = Student::factory()->create([
            'user_id' => $this->studentUser->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Index — My subjects
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_return_subjects_for_authenticated_student()
    {
        $subject = Subject::factory()->create();
        $this->student->subjects()->attach($subject->id, [
            'school_year' => '2024-2025',
            'semester' => '1st',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/student/my-subjects');

        $response->assertStatus(200)
            ->assertJsonFragment(['student_number' => $this->student->student_number])
            ->assertJsonFragment(['id' => $subject->id]);
    }

    /** @test */
    public function it_returns_empty_subjects_when_student_has_no_enrollments()
    {
        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/student/my-subjects');

        $response->assertStatus(200)
            ->assertJsonFragment(['student_number' => $this->student->student_number])
            ->assertJsonCount(0, 'subjects');
    }

    /** @test */
    public function it_returns_multiple_subjects_for_student()
    {
        $subjects = Subject::factory()->count(3)->create();

        foreach ($subjects as $subject) {
            $this->student->subjects()->attach($subject->id, [
                'school_year' => '2024-2025',
                'semester' => '1st',
            ]);
        }

        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/student/my-subjects');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'subjects');
    }

    /** @test */
    public function it_returns_404_when_student_has_no_profile()
    {
        // User with student role but no Student record
        $userWithoutProfile = User::factory()->create();
        $userWithoutProfile->roles()->attach($this->studentRole);

        $response = $this->actingAs($userWithoutProfile)
            ->getJson('/api/student/my-subjects');

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Student profile not found']);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_prevents_unauthenticated_access()
    {
        $this->getJson('/api/student/my-subjects')
            ->assertStatus(401);
    }

    /** @test */
    public function it_prevents_non_student_from_accessing_my_subjects()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $this->actingAs($admin)
            ->getJson('/api/student/my-subjects')
            ->assertStatus(403);
    }
}