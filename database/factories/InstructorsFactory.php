<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Instructors>
 */
class InstructorsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'bio' => fake()->paragraph(),
            'avatar' => fake()->imageUrl(200, 200, 'people'),
            'skills' => [fake()->word(), fake()->word(), fake()->word()],
            'experience' => fake()->numberBetween(1, 20) . ' years',
            'education' => fake()->sentence(),
            'certifications' => [fake()->sentence(), fake()->sentence()],
            'facebook' => fake()->optional()->url(),
            'twitter' => fake()->optional()->url(),
            'instagram' => fake()->optional()->url(),
            'linkedin' => fake()->optional()->url(),
            'youtube' => fake()->optional()->url(),
            'website' => fake()->optional()->url(),
            'github' => fake()->optional()->url(),
        ];
    }
}
