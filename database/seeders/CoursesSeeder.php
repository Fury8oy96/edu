<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Courses;
use Illuminate\Support\Facades\DB;

class CoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Courses::create([
            'title' => fake()->sentence(),
            'description' => fake()->sentence(),
            'image' => fake()->imageUrl(),
            'price' => fake()->randomFloat(2, 0, 100),
            'status' => fake()->randomElement(['active', 'inactive']),
            'language' => fake()->languageCode(),
            'level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'category' => fake()->randomElement(['programming', 'design', 'business', 'marketing', 'music', 'art', 'writing', 'language', 'science', 'math', 'social sciences', 'health', 'personal development', 'other']),
            'subcategory' => fake()->randomElement(['programming', 'design', 'business', 'marketing', 'music', 'art', 'writing', 'language', 'science', 'math', 'social sciences', 'health', 'personal development', 'other']),
            'tags' => fake()->sentence(),
            'keywords' => fake()->sentence(),
            'requirements' => fake()->sentence(),
            'outcomes' => fake()->sentence(),
            'target_audience' => fake()->sentence(),
        ]);
    }
}
