<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Modules;

class ModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Modules::create([
            'title' => fake()->sentence(),
            'description' => fake()->sentence(),
            'course_id' => 1,
            'duration' => fake()->time(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'keywords' => fake()->sentence(),
            'requirements' => fake()->sentence(),
            'outcomes' => fake()->sentence(),
            'tags' => fake()->sentence(),
        ]);
    }
}
