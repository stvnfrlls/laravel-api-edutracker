<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'BSIT',
                'BSCS',
                'BSECE',
                'BSBA'
            ]),
        ];
    }
}
