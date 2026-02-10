<?php

namespace Database\Factories;

use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssessmentAnswer>
 */
class AssessmentAnswerFactory extends Factory
{
    protected $model = AssessmentAnswer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attempt_id' => AssessmentAttempt::factory(),
            'question_id' => AssessmentQuestion::factory(),
            'answer' => ['text' => fake()->sentence()],
            'is_correct' => null,
            'points_earned' => null,
            'grading_status' => 'pending_review',
            'grader_feedback' => null,
            'graded_by' => null,
            'graded_at' => null,
        ];
    }

    /**
     * Indicate that the answer is auto-graded and correct.
     */
    public function autoGradedCorrect(): static
    {
        return $this->state(function (array $attributes) {
            // Assuming the question has points
            $points = 5; // Default points
            
            return [
                'answer' => ['selected_option_id' => 'a'],
                'is_correct' => true,
                'points_earned' => $points,
                'grading_status' => 'auto_graded',
            ];
        });
    }

    /**
     * Indicate that the answer is auto-graded and incorrect.
     */
    public function autoGradedIncorrect(): static
    {
        return $this->state(fn (array $attributes) => [
            'answer' => ['selected_option_id' => 'b'],
            'is_correct' => false,
            'points_earned' => 0,
            'grading_status' => 'auto_graded',
        ]);
    }

    /**
     * Indicate that the answer is manually graded.
     */
    public function manuallyGraded(float $points): static
    {
        return $this->state(fn (array $attributes) => [
            'answer' => ['text' => fake()->paragraph()],
            'is_correct' => null,
            'points_earned' => $points,
            'grading_status' => 'manually_graded',
            'grader_feedback' => fake()->sentence(),
            'graded_by' => 1, // Assuming admin ID 1
            'graded_at' => now(),
        ]);
    }

    /**
     * Indicate that the answer is pending review.
     */
    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'answer' => ['text' => fake()->paragraph()],
            'grading_status' => 'pending_review',
        ]);
    }
}
