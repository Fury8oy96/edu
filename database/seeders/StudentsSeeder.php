<?php

namespace Database\Seeders;

use App\Models\Students;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Students::create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'avatar' => fake()->imageUrl(),
            'bio' => fake()->sentence(),
            'skills' => fake()->sentence(),
            'experience' => fake()->sentence(),
            'education' => fake()->sentence(),
            'certifications' => fake()->sentence(),
        ]);
    }
}
