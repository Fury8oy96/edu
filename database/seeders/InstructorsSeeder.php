<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Instructors;
class InstructorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Instructors::create([
            'name' => fake()->name(),
            'email' => fake()->email(),
            'bio' => fake()->sentence(),
            'avatar' => fake()->imageUrl(),
            'skills' => fake()->sentence(),
            'experience' => fake()->sentence(),
            'education' => fake()->sentence(),
            'certifications' => fake()->sentence(),
            'facebook' => fake()->url(),
            'twitter' => fake()->url(),
            'instagram' => fake()->url(),
            'linkedin' => fake()->url(),
            'youtube' => fake()->url(),
            'website' => fake()->url(),
            'github' => fake()->url(),
            'course_id' => 1,
            'lesson_id' => 1,
        ]);
    }
}
