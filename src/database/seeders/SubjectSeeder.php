<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            [
                'code' => 'CS101',
                'name' => 'Introduction to Programming',
                'units' => 3,
            ],
            [
                'code' => 'CS102',
                'name' => 'Data Structures and Algorithms',
                'units' => 3,
            ],
            [
                'code' => 'MATH101',
                'name' => 'College Algebra',
                'units' => 3,
            ],
            [
                'code' => 'ENG101',
                'name' => 'Academic Writing',
                'units' => 3,
            ],
            [
                'code' => 'IT201',
                'name' => 'Database Systems',
                'units' => 4,
            ],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                ['code' => $subject['code']],
                $subject
            );
        }
    }
}
