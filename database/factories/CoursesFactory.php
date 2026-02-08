<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Courses>
 */
class CoursesFactory extends Factory
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
            'image' => fake()->imageUrl(640, 480, 'education'),
            'price' => fake()->randomFloat(2, 0, 999.99),
            'is_paid' => fake()->boolean(),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'language' => fake()->randomElement(['English', 'Spanish', 'French', 'German']),
            'level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'category' => fake()->randomElement(['Programming', 'Design', 'Business', 'Marketing']),
            'subcategory' => fake()->word(),
            'tags' => [fake()->word(), fake()->word(), fake()->word()],
            'keywords' => [fake()->word(), fake()->word()],
            'requirements' => fake()->paragraph(),
            'outcomes' => fake()->paragraph(),
            'target_audience' => fake()->paragraph(),
            'instructor_id' => \App\Models\Instructors::factory(),
            'published_at' => fake()->optional()->dateTime(),
            'duration_hours' => fake()->numberBetween(1, 100),
            'enrollment_count' => fake()->numberBetween(0, 1000),
        ];
    }
}
