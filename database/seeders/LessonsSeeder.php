<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Lessons;

class LessonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Lessons::create([
            'title' => fake()->sentence(),
            'description' => fake()->sentence(),
            'video_url' => fake()->url(),
            'duration' => fake()->time(),
            'outcomes' => fake()->sentence(),
            'keywords' => fake()->sentence(),
            'requirements' => fake()->sentence(),
            'tags' => fake()->sentence(),
            'module_id' => 1,
        ]);
    }
}
