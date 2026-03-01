<?php

namespace Tests\Feature\Api;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $studentUser;
    protected Student $student;
    protected Subject $subject;
    protected Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        Role::factory()->create(['name' => 'admin']);
        $studentRole = Role::factory()->create(['name' => 'student']);
        $teacherRole = Role::factory()->create(['name' => 'teacher']);

        // Teacher
        $this->teacher = User::factory()->create();
        $this->teacher->roles()->attach($teacherRole);

        // Student user with student profile
        $this->studentUser = User::factory()->create();
        $this->studentUser->roles()->attach($studentRole);
        $this->student = Student::factory()->create([
            'user_id' => $this->studentUser->id,
        ]);

        // Subject and enrollment
        $this->subject = Subject::factory()->create(['units' => 3]);
        $this->enrollment = Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'school_year' => '2024-2025',
            'semester' => '1st',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store — Save grades for an enrollment
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_save_grades_for_an_enrollment()
    {
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/teacher/enrollments/{$this->enrollment->id}/grades", [
                'quarter_1' => 80,
                'quarter_2' => 85,
                'quarter_3' => 90,
                'quarter_4' => 95,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Grades saved successfully.']);

        $this->assertDatabaseHas('grades', [
            'enrollment_id' => $this->enrollment->id,
            'final_grade' => 87.50, // average of 80,85,90,95
            'remarks' => 'Passed',
        ]);
    }

    /** @test */
    public function it_can_save_partial_grades()
    {
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/teacher/enrollments/{$this->enrollment->id}/grades", [
                'quarter_1' => 70,
                'quarter_2' => 65,
                // quarter_3 and quarter_4 omitted
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('grades', [
            'enrollment_id' => $this->enrollment->id,
            'final_grade' => 67.50, // average of only 70 and 65
            'remarks' => 'Failed',
        ]);
    }

    /** @test */
    public function it_can_update_existing_grades()
    {
        // Create initial grade
        Grade::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'quarter_1' => 70,
            'final_grade' => 70,
            'remarks' => 'Failed',
        ]);

        // Update with passing grades
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/teacher/enrollments/{$this->enrollment->id}/grades", [
                'quarter_1' => 90,
                'quarter_2' => 90,
                'quarter_3' => 90,
                'quarter_4' => 90,
            ]);

        $response->assertStatus(200);

        // Should still be one grade record, not two
        $this->assertCount(1, Grade::where('enrollment_id', $this->enrollment->id)->get());

        $this->assertDatabaseHas('grades', [
            'enrollment_id' => $this->enrollment->id,
            'final_grade' => 90.00,
            'remarks' => 'Passed',
        ]);
    }

    /** @test */
    public function it_rejects_grades_above_100()
    {
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/teacher/enrollments/{$this->enrollment->id}/grades", [
                'quarter_1' => 101,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_grades_below_0()
    {
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/teacher/enrollments/{$this->enrollment->id}/grades", [
                'quarter_1' => -1,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_enrollment()
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/teacher/enrollments/9999/grades', [
                'quarter_1' => 80,
            ]);

        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | History — Academic history grouped by school year and semester
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_return_academic_history_for_a_student()
    {
        Grade::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'final_grade' => 88,
            'remarks' => 'Passed',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/student/academic-history');

        $response->assertStatus(200)
            ->assertJsonStructure(['academic_history']);
    }

    /** @test */
    public function it_returns_empty_history_when_student_has_no_enrollments()
    {
        // Fresh student with no enrollments
        $studentRole = Role::where('name', 'student')->first();
        $newUser = User::factory()->create();
        $newUser->roles()->attach($studentRole);
        Student::factory()->create(['user_id' => $newUser->id]);

        $response = $this->actingAs($newUser)
            ->getJson('/api/student/academic-history');

        $response->assertStatus(200)
            ->assertJson(['academic_history' => []]);
    }

    /*
    |--------------------------------------------------------------------------
    | GPA
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_calculate_gpa_for_a_student()
    {
        Grade::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'final_grade' => 90.00,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/student/gpa');

        $response->assertStatus(200)
            ->assertJsonStructure(['gpa']);

        // subject has 3 units, only one subject so gpa = final_grade
        $this->assertEquals(90.00, $response->json('gpa'));
    }

    /** @test */
    public function it_returns_gpa_of_zero_when_no_graded_enrollments()
    {
        // Enrollment exists but no grade record
        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/student/gpa');

        $response->assertStatus(200)
            ->assertJson(['gpa' => 0]);
    }

    /** @test */
    public function it_calculates_weighted_gpa_across_multiple_subjects()
    {
        // Subject 1: 3 units, grade 90
        // Already set up in setUp: $this->enrollment / $this->subject (3 units)
        Grade::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'final_grade' => 90.00,
        ]);

        // Subject 2: 2 units, grade 80
        $subject2 = Subject::factory()->create(['units' => 2]);
        $enrollment2 = Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'subject_id' => $subject2->id,
            'school_year' => '2024-2025',
            'semester' => '1st',
        ]);
        Grade::factory()->create([
            'enrollment_id' => $enrollment2->id,
            'final_grade' => 80.00,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/student/gpa');

        // Weighted: (90*3 + 80*2) / (3+2) = (270+160)/5 = 430/5 = 86
        $response->assertStatus(200)
            ->assertJson(['gpa' => 86.00]);
    }

    /*
    |--------------------------------------------------------------------------
    | Grades — Flat list per subject
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_return_grades_list_for_a_student()
    {
        Grade::factory()->create([
            'enrollment_id' => $this->enrollment->id,
            'quarter_1' => 80,
            'quarter_2' => 85,
            'quarter_3' => null,
            'quarter_4' => null,
            'final_grade' => 82.5,
            'remarks' => 'Passed',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/student/grades');

        $response->assertStatus(200)
            ->assertJsonStructure(['grades'])
            ->assertJsonFragment([
                'subject' => $this->subject->name,
                'final_grade' => 82.50,
                'remarks' => 'Passed',
            ]);
    }

    /** @test */
    public function it_returns_null_grade_fields_when_no_grade_record_exists()
    {
        // Enrollment exists but no grade saved yet
        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/student/grades');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'final_grade' => null,
                'remarks' => null,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_prevents_unauthenticated_access_to_grade_store()
    {
        $this->postJson("/api/teacher/enrollments/{$this->enrollment->id}/grades")
            ->assertStatus(401);
    }

    /** @test */
    public function it_prevents_non_teacher_from_saving_grades()
    {
        $this->actingAs($this->studentUser)
            ->postJson("/api/teacher/enrollments/{$this->enrollment->id}/grades", [
                'quarter_1' => 80,
            ])
            ->assertStatus(403);
    }

    /** @test */
    public function it_prevents_unauthenticated_access_to_student_grade_routes()
    {
        $this->getJson('/api/student/academic-history')->assertStatus(401);
        $this->getJson('/api/student/gpa')->assertStatus(401);
        $this->getJson('/api/student/grades')->assertStatus(401);
    }

    /** @test */
    public function it_prevents_non_student_from_accessing_student_grade_routes()
    {
        $this->actingAs($this->teacher)
            ->getJson('/api/student/grades')
            ->assertStatus(403);
    }
}