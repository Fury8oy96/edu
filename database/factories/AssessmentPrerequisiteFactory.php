<?php

namespace Database\Factories;

use App\Models\AssessmentPrerequisite;
use App\Models\Assessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssessmentPrerequisite>
 */
class AssessmentPrerequisiteFactory extends Factory
{
    protected $model = AssessmentPrerequisite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['quiz_completion', 'minimum_progress', 'lesson_completion']);
        
        return [
            'assessment_id' => Assessment::factory(),
            'prerequisite_type' => $type,
            'prerequisite_data' => $this->getPrerequisiteData($type),
        ];
    }

    /**
     * Get prerequisite data based on type.
     */
    protected function getPrerequisiteData(string $type): array
    {
        return match($type) {
            'quiz_completion' => ['require_all_quizzes' => true],
            'minimum_progress' => ['minimum_percentage' => fake()->numberBetween(50, 90)],
            'lesson_completion' => ['lesson_ids' => [1, 2, 3]],
            default => [],
        };
    }

    /**
     * Indicate that the prerequisite is quiz completion.
     */
    public function quizCompletion(): static
    {
        return $this->state(fn (array $attributes) => [
            'prerequisite_type' => 'quiz_completion',
            'prerequisite_data' => ['require_all_quizzes' => true],
        ]);
    }

    /**
     * Indicate that the prerequisite is minimum progress.
     */
    public function minimumProgress(int $percentage = 75): static
    {
        return $this->state(fn (array $attributes) => [
            'prerequisite_type' => 'minimum_progress',
            'prerequisite_data' => ['minimum_percentage' => $percentage],
        ]);
    }

    /**
     * Indicate that the prerequisite is lesson completion.
     */
    public function lessonCompletion(array $lessonIds = [1, 2, 3]): static
    {
        return $this->state(fn (array $attributes) => [
            'prerequisite_type' => 'lesson_completion',
            'prerequisite_data' => ['lesson_ids' => $lessonIds],
        ]);
    }
}
