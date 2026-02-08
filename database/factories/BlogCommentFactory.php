<?php

namespace Database\Factories;

use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\Students;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogComment>
 */
class BlogCommentFactory extends Factory
{
    protected $model = BlogComment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'blog_post_id' => BlogPost::factory(),
            'student_id' => Students::factory(),
            'content' => fake()->paragraph(),
        ];
    }
}
