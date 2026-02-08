<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lessons>
 */
class LessonsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'video_url' => fake()->url(),
            'duration' => fake()->numberBetween(10, 60) . ' minutes',
            'module_id' => \App\Models\Modules::factory(),
            'outcomes' => fake()->sentence(),
            'keywords' => fake()->words(3, true),
            'requirements' => fake()->sentence(),
            'tags' => json_encode(fake()->words(3)),
        ];
    }
}
