<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Mohamed Abbas',
            'email' => 'midoo3000@gmail.com',
            'password' => 'password',
        ]);

        $this->call([
            CoursesSeeder::class,
            ModulesSeeder::class,
            LessonsSeeder::class,
            InstructorsSeeder::class,
            StudentsSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
