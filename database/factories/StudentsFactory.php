<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Students>
 */
class StudentsFactory extends Factory
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
            'password' => bcrypt('password'), // Default password for testing
            'profession' => fake()->jobTitle(),
            'avatar' => null,
            'bio' => fake()->sentence(),
            'skills' => null,
            'experience' => fake()->paragraph(),
            'education' => fake()->sentence(),
            'certifications' => null,
            'status' => 'active',
        ];
    }
}
