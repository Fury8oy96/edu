<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Modules>
 */
class ModulesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'course_id' => \App\Models\Courses::factory(),
            'duration' => fake()->numberBetween(30, 180) . ' minutes',
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'keywords' => fake()->words(3, true),
            'requirements' => fake()->sentence(),
            'outcomes' => fake()->sentence(),
            'tags' => json_encode(fake()->words(3)),
        ];
    }
}
