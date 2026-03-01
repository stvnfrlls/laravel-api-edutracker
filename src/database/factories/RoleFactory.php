<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->numberBetween(1, 4),
            'name' => $this->faker->unique()->randomElement([
                'user',
                'admin',
                'teacher',
                'student',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create all default roles with fixed IDs
     */
    public function defaultRoles(): array
    {
        return [
            ['id' => 1, 'name' => 'user', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ];
    }
}
