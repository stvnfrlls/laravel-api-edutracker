<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all subjects
        $subjects = Subject::all();

        // Optional: get instructors (users with role 'instructor')
        $instructors = User::whereHas('roles', function ($q) {
            $q->where('name', 'teacher');
        })->pluck('id');

        // Days array
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        foreach ($subjects as $subject) {
            // Assign 2 schedules per subject randomly
            for ($i = 0; $i < 2; $i++) {
                Schedule::create([
                    'subject_id' => $subject->id,
                    'day' => $days[array_rand($days)],
                    'start_time' => sprintf('%02d:00', rand(8, 15)), // 8AM to 3PM
                    'end_time' => sprintf('%02d:00', rand(9, 17)),   // 9AM to 5PM
                    'room' => 'Room ' . rand(100, 499),
                    'instructor_id' => $instructors->isNotEmpty() ? $instructors->random() : null,
                ]);
            }
        }
    }
}
