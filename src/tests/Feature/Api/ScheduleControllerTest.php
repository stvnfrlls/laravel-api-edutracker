<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::factory()->create(['name' => 'admin']);
        Role::factory()->create(['name' => 'student']);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->subject = Subject::factory()->create();
    }

    /*
    |--------------------------------------------------------------------------
    | Store — Create schedule
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_create_a_schedule()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/schedules', [
                'subject_id' => $this->subject->id,
                'day' => 'Monday',
                'start_time' => '08:00',
                'end_time' => '10:00',
                'room' => 'Room 101',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['day' => 'Monday'])
            ->assertJsonFragment(['room' => 'Room 101']);

        $this->assertDatabaseHas('schedules', [
            'subject_id' => $this->subject->id,
            'day' => 'Monday',
        ]);
    }

    /** @test */
    public function it_can_create_a_schedule_without_optional_fields()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/schedules', [
                'subject_id' => $this->subject->id,
                'day' => 'Tuesday',
                'start_time' => '09:00',
                'end_time' => '11:00',
                // room and instructor_id omitted — both nullable
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('schedules', [
            'subject_id' => $this->subject->id,
            'room' => null,
            'instructor_id' => null,
        ]);
    }

    /** @test */
    public function it_can_create_a_schedule_with_an_instructor()
    {
        $instructor = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/schedules', [
                'subject_id' => $this->subject->id,
                'day' => 'Wednesday',
                'start_time' => '13:00',
                'end_time' => '15:00',
                'instructor_id' => $instructor->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['instructor_id' => $instructor->id]);
    }

    /** @test */
    public function it_rejects_schedule_with_end_time_before_start_time()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/schedules', [
                'subject_id' => $this->subject->id,
                'day' => 'Monday',
                'start_time' => '10:00',
                'end_time' => '08:00', // before start
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_schedule_with_invalid_time_format()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/schedules', [
                'subject_id' => $this->subject->id,
                'day' => 'Monday',
                'start_time' => '8am',   // wrong format
                'end_time' => '10am',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_rejects_schedule_for_nonexistent_subject()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/schedules', [
                'subject_id' => 9999,
                'day' => 'Monday',
                'start_time' => '08:00',
                'end_time' => '10:00',
            ]);

        $response->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Show — Get schedules by subject
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_show_schedules_for_a_subject()
    {
        Schedule::factory()->count(3)->create([
            'subject_id' => $this->subject->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/schedules/subject/{$this->subject->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /** @test */
    public function it_returns_empty_array_when_subject_has_no_schedules()
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/schedules/subject/{$this->subject->id}");

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    /*
    |--------------------------------------------------------------------------
    | Update — Edit schedule
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_update_a_schedule()
    {
        $schedule = Schedule::factory()->create([
            'subject_id' => $this->subject->id,
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/schedules/{$schedule->id}", [
                'day' => 'Friday',
                'room' => 'Room 202',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['day' => 'Friday'])
            ->assertJsonFragment(['room' => 'Room 202']);

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'day' => 'Friday',
        ]);
    }

    /** @test */
    public function it_rejects_update_with_invalid_time_format()
    {
        $schedule = Schedule::factory()->create(['subject_id' => $this->subject->id]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/schedules/{$schedule->id}", [
                'start_time' => 'not-a-time',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_schedule()
    {
        $response = $this->actingAs($this->admin)
            ->putJson('/api/admin/schedules/9999', [
                'day' => 'Monday',
            ]);

        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy — Delete schedule
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_delete_a_schedule()
    {
        $schedule = Schedule::factory()->create(['subject_id' => $this->subject->id]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/schedules/{$schedule->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Schedule deleted']);

        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_schedule()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson('/api/admin/schedules/9999');

        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | My Schedule — Student view
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_return_schedule_for_authenticated_student()
    {
        $studentRole = Role::where('name', 'student')->first();
        $studentUser = User::factory()->create();
        $studentUser->roles()->attach($studentRole);

        $student = Student::factory()->create(['user_id' => $studentUser->id]);

        // Enroll student in subject with pivot data
        $student->subjects()->attach($this->subject->id, [
            'school_year' => '2024-2025',
            'semester' => '1st',
        ]);

        // Create a schedule for that subject
        $schedule = Schedule::factory()->create(['subject_id' => $this->subject->id]);

        $response = $this->actingAs($studentUser)
            ->getJson('/api/student/my-schedule');

        $response->assertStatus(200)
            ->assertJsonFragment(['subject_code' => $this->subject->code])
            ->assertJsonFragment(['day' => $schedule->day]);
    }

    /** @test */
    public function it_returns_404_when_student_user_has_no_student_profile()
    {
        $studentRole = Role::where('name', 'student')->first();
        $studentUser = User::factory()->create();
        $studentUser->roles()->attach($studentRole);
        // No Student record created for this user

        $response = $this->actingAs($studentUser)
            ->getJson('/api/student/my-schedule');

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Student profile not found']);
    }

    /** @test */
    public function it_returns_empty_schedule_when_student_has_no_subjects()
    {
        $studentRole = Role::where('name', 'student')->first();
        $studentUser = User::factory()->create();
        $studentUser->roles()->attach($studentRole);
        Student::factory()->create(['user_id' => $studentUser->id]);
        // No subjects attached

        $response = $this->actingAs($studentUser)
            ->getJson('/api/student/my-schedule');

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_prevents_unauthenticated_access_to_admin_routes()
    {
        $this->postJson('/api/admin/schedules')->assertStatus(401);
        $this->getJson("/api/admin/schedules/subject/{$this->subject->id}")->assertStatus(401);
        $this->putJson('/api/admin/schedules/1')->assertStatus(401);
        $this->deleteJson('/api/admin/schedules/1')->assertStatus(401);
    }

    /** @test */
    public function it_prevents_non_admin_from_managing_schedules()
    {
        $studentRole = Role::where('name', 'student')->first();
        $nonAdmin = User::factory()->create();
        $nonAdmin->roles()->attach($studentRole);

        $this->actingAs($nonAdmin)
            ->postJson('/api/admin/schedules', [
                'subject_id' => $this->subject->id,
                'day' => 'Monday',
                'start_time' => '08:00',
                'end_time' => '10:00',
            ])
            ->assertStatus(403);
    }

    /** @test */
    public function it_prevents_unauthenticated_access_to_my_schedule()
    {
        $this->getJson('/api/student/my-schedule')->assertStatus(401);
    }
}