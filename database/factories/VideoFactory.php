<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->word() . '-' . fake()->word() . '.mp4';
        
        return [
            'original_filename' => $filename,
            'display_name' => fake()->sentence(3),
            'file_size' => fake()->numberBetween(1000000, 2000000000), // 1MB to 2GB
            'duration' => fake()->randomFloat(2, 10, 3600), // 10 seconds to 1 hour
            'resolution' => fake()->randomElement(['1280x720', '1920x1080', '3840x2160']),
            'codec' => fake()->randomElement(['h264', 'h265', 'vp9']),
            'format' => fake()->randomElement(['mp4', 'webm', 'mkv']),
            'original_path' => 'videos/' . fake()->numberBetween(1, 100) . '/' . fake()->numberBetween(1, 100) . '/' . fake()->numberBetween(1, 1000) . '/original.mp4',
            'thumbnail_path' => 'videos/' . fake()->numberBetween(1, 100) . '/' . fake()->numberBetween(1, 100) . '/' . fake()->numberBetween(1, 1000) . '/thumbnail.jpg',
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'processing_progress' => fake()->numberBetween(0, 100),
            'error_message' => null,
            'uploaded_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the video is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processing_progress' => 0,
        ]);
    }

    /**
     * Indicate that the video is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'processing_progress' => fake()->numberBetween(1, 99),
        ]);
    }

    /**
     * Indicate that the video is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processing_progress' => 100,
        ]);
    }

    /**
     * Indicate that the video has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->sentence(),
        ]);
    }
}
