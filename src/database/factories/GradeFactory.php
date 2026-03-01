<?php

namespace Database\Factories;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grade>
 */
class GradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quarters = [
            'quarter_1' => $this->faker->randomFloat(2, 60, 100),
            'quarter_2' => $this->faker->randomFloat(2, 60, 100),
            'quarter_3' => $this->faker->randomFloat(2, 60, 100),
            'quarter_4' => $this->faker->randomFloat(2, 60, 100),
        ];

        $final = round(collect($quarters)->avg(), 2);

        return [
            'enrollment_id' => Enrollment::factory(),
            ...$quarters,
            'final_grade' => $final,
            'remarks' => $final >= 75 ? 'Passed' : 'Failed',
        ];
    }
}
