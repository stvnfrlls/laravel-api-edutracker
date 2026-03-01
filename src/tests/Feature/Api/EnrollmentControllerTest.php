<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $studentUser;
    protected Student $student;
    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::factory()->create(['name' => 'admin']);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->studentUser = User::factory()->create();
        $this->student = Student::factory()->create([
            'user_id' => $this->studentUser->id,
        ]);

        $this->subject = Subject::factory()->create();
    }

    // Helper to avoid repeating pivot data everywhere
    private function pivotData(): array
    {
        return [
            'school_year' => '2024-2025',
            'semester' => '1st',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Store — Enroll student in subject
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_enroll_a_student_in_a_subject()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/enrollments', [
                'user_id' => $this->studentUser->id,
                'subject_id' => $this->subject->id,
                'school_year' => '2024-2025',
                'semester' => '1st',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Student successfully enrolled'])
            ->assertJsonFragment(['subject_id' => $this->subject->id]);

        $this->assertTrue(
            $this->student->subjects()->where('subject_id', $this->subject->id)->exists()
        );
    }

    /** @test */
    public function it_prevents_duplicate_enrollment()
    {
        $this->student->subjects()->attach($this->subject->id, $this->pivotData());

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/enrollments', [
                'user_id' => $this->studentUser->id,
                'subject_id' => $this->subject->id,
                'school_year' => '2024-2025',
                'semester' => '1st',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Student already enrolled in this subject']);

        $this->assertCount(1, $this->student->subjects()->get());
    }

    /** @test */
    public function it_allows_enrollment_in_same_subject_different_semester()
    {
        // First enrollment
        $this->student->subjects()->attach($this->subject->id, [
            'school_year' => '2024-2025',
            'semester' => '1st',
        ]);

        // Same subject, different semester — should be allowed per unique constraint
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/enrollments', [
                'user_id' => $this->studentUser->id,
                'subject_id' => $this->subject->id,
                'school_year' => '2024-2025',
                'semester' => '2nd',
            ]);

        $response->assertStatus(201);
        $this->assertCount(2, $this->student->subjects()->get());
    }

    /** @test */
    public function it_rejects_enrollment_for_nonexistent_student()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/enrollments', [
                'user_id' => 9999,
                'subject_id' => $this->subject->id,
                'school_year' => '2024-2025',
                'semester' => '1st',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_enrollment_for_nonexistent_subject()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/enrollments', [
                'user_id' => $this->studentUser->id,
                'subject_id' => 9999,
                'school_year' => '2024-2025',
                'semester' => '1st',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_enrollment_missing_school_year()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/enrollments', [
                'user_id' => $this->studentUser->id,
                'subject_id' => $this->subject->id,
                'semester' => '1st',
                // school_year missing
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_enrollment_missing_semester()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/enrollments', [
                'user_id' => $this->studentUser->id,
                'subject_id' => $this->subject->id,
                'school_year' => '2024-2025',
                // semester missing
            ]);

        $response->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Show — View subjects of a student
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_show_subjects_of_a_student()
    {
        $this->student->subjects()->attach($this->subject->id, $this->pivotData());

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/enrollments/{$this->studentUser->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['student_id' => $this->student->id])
            ->assertJsonPath('subjects.0.id', $this->subject->id);
    }

    /** @test */
    public function it_returns_empty_subjects_when_student_has_no_enrollments()
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/enrollments/{$this->studentUser->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['student_id' => $this->student->id])
            ->assertJsonCount(0, 'subjects');
    }

    /** @test */
    public function it_returns_404_when_showing_enrollments_for_nonexistent_student()
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/enrollments/9999");

        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | Update — Update pivot status
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_update_enrollment_status()
    {
        $this->student->subjects()->attach($this->subject->id, $this->pivotData());

        $response = $this->actingAs($this->admin)
            ->putJson('/api/admin/enrollments', [
                'user_id' => $this->studentUser->id,
                'subject_id' => $this->subject->id,
                'status' => 'enrolled',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Enrollment updated']);
    }

    /** @test */
    public function it_rejects_update_without_status()
    {
        $response = $this->actingAs($this->admin)
            ->putJson('/api/admin/enrollments', [
                'user_id' => $this->studentUser->id,
                'subject_id' => $this->subject->id,
                // status missing
            ]);

        $response->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy — Unenroll student from subject
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_unenroll_a_student_from_a_subject()
    {
        $this->student->subjects()->attach($this->subject->id, $this->pivotData());

        $response = $this->actingAs($this->admin)
            ->deleteJson('/api/admin/enrollments', [
                'user_id' => $this->studentUser->id,
                'subject_id' => $this->subject->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Student unenrolled successfully']);

        $this->assertFalse(
            $this->student->subjects()->where('subject_id', $this->subject->id)->exists()
        );
    }

    /** @test */
    public function it_rejects_unenroll_for_nonexistent_student()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson('/api/admin/enrollments', [
                'user_id' => 9999,
                'subject_id' => $this->subject->id,
            ]);

        $response->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_prevents_unauthenticated_access()
    {
        $this->postJson('/api/admin/enrollments')->assertStatus(401);
        $this->getJson("/api/admin/enrollments/{$this->studentUser->id}")->assertStatus(401);
        $this->putJson('/api/admin/enrollments')->assertStatus(401);
        $this->deleteJson('/api/admin/enrollments')->assertStatus(401);
    }

    /** @test */
    public function it_prevents_non_admin_from_managing_enrollments()
    {
        $studentRole = Role::factory()->create(['name' => 'student']);
        $nonAdmin = User::factory()->create();
        $nonAdmin->roles()->attach($studentRole);

        $this->actingAs($nonAdmin)
            ->postJson('/api/admin/enrollments', [
                'user_id' => $this->studentUser->id,
                'subject_id' => $this->subject->id,
                'school_year' => '2024-2025',
                'semester' => '1st',
            ])
            ->assertStatus(403);
    }
}