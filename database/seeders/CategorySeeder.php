<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Web Development',
            'Mobile Development',
            'Data Science',
            'DevOps',
            'UI/UX Design',
            'Career Advice',
            'Learning Tips',
            'Project Showcase',
        ];

        foreach ($categories as $categoryName) {
            Category::firstOrCreate(
                ['name' => $categoryName],
                [
                    'slug' => Str::slug($categoryName),
                    'description' => null,
                ]
            );
        }
    }
}
