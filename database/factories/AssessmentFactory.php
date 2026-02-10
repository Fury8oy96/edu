<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Courses;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assessment>
 */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Courses::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'time_limit' => fake()->numberBetween(30, 180), // 30 to 180 minutes
            'passing_score' => fake()->randomFloat(2, 60, 90), // 60% to 90%
            'max_attempts' => fake()->optional(0.7)->numberBetween(1, 5), // 70% chance of having a limit
            'start_date' => null,
            'end_date' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the assessment has an availability window.
     */
    public function withAvailabilityWindow(?string $startDate = null, ?string $endDate = null): static
    {
        $start = $startDate ? $startDate : now()->subDays(7);
        $end = $endDate ? $endDate : now()->addDays(30);
        
        return $this->state(fn (array $attributes) => [
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    /**
     * Indicate that the assessment is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the assessment has unlimited attempts.
     */
    public function unlimitedAttempts(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_attempts' => null,
        ]);
    }

    /**
     * Indicate that the assessment has questions.
     */
    public function withQuestions(int $count = 5): static
    {
        return $this->has(
            \App\Models\AssessmentQuestion::factory()->count($count),
            'questions'
        );
    }

    /**
     * Indicate that the assessment has prerequisites.
     */
    public function withPrerequisites(array $types = ['quiz_completion']): static
    {
        return $this->has(
            \App\Models\AssessmentPrerequisite::factory()
                ->count(count($types))
                ->sequence(...array_map(fn($type) => ['prerequisite_type' => $type], $types)),
            'prerequisites'
        );
    }
}
