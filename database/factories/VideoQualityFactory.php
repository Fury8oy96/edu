<?php

namespace Database\Factories;

use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VideoQuality>
 */
class VideoQualityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quality = fake()->randomElement(['360p', '480p', '720p', '1080p']);
        
        return [
            'video_id' => Video::factory(),
            'quality' => $quality,
            'file_path' => 'videos/' . fake()->numberBetween(1, 100) . '/' . fake()->numberBetween(1, 100) . '/' . fake()->numberBetween(1, 1000) . '/' . $quality . '.mp4',
            'file_size' => fake()->numberBetween(500000, 1000000000), // 500KB to 1GB
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'processing_progress' => fake()->numberBetween(0, 100),
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the quality is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processing_progress' => 0,
        ]);
    }

    /**
     * Indicate that the quality is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'processing_progress' => fake()->numberBetween(1, 99),
        ]);
    }

    /**
     * Indicate that the quality is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processing_progress' => 100,
        ]);
    }

    /**
     * Indicate that the quality has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->sentence(),
        ]);
    }
}
