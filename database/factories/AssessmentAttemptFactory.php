<?php

namespace Database\Factories;

use App\Models\AssessmentAttempt;
use App\Models\Assessment;
use App\Models\Students;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssessmentAttempt>
 */
class AssessmentAttemptFactory extends Factory
{
    protected $model = AssessmentAttempt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('-1 month', 'now');
        $completionTime = (clone $startTime)->modify('+' . fake()->numberBetween(10, 120) . ' minutes');
        $timeTaken = ($completionTime->getTimestamp() - $startTime->getTimestamp());
        
        $maxScore = fake()->randomFloat(2, 50, 100);
        $score = fake()->randomFloat(2, 0, $maxScore);
        $percentage = ($score / $maxScore) * 100;
        $passed = $percentage >= 70; // Assuming 70% passing score
        
        return [
            'assessment_id' => Assessment::factory(),
            'student_id' => Students::factory(),
            'attempt_number' => 1,
            'status' => 'completed',
            'start_time' => $startTime,
            'completion_time' => $completionTime,
            'time_taken' => $timeTaken,
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'passed' => $passed,
        ];
    }

    /**
     * Indicate that the attempt is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'completion_time' => null,
            'time_taken' => null,
            'score' => null,
            'percentage' => null,
            'passed' => null,
        ]);
    }

    /**
     * Indicate that the attempt timed out.
     */
    public function timedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'timed_out',
        ]);
    }

    /**
     * Indicate that the attempt is pending grading.
     */
    public function gradingPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'grading_pending',
            'passed' => null,
        ]);
    }

    /**
     * Indicate that the attempt passed.
     */
    public function passed(): static
    {
        return $this->state(function (array $attributes) {
            $maxScore = $attributes['max_score'] ?? 100;
            $score = fake()->randomFloat(2, $maxScore * 0.7, $maxScore); // 70% to 100%
            $percentage = ($score / $maxScore) * 100;
            
            return [
                'score' => $score,
                'percentage' => $percentage,
                'passed' => true,
            ];
        });
    }

    /**
     * Indicate that the attempt failed.
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            $maxScore = $attributes['max_score'] ?? 100;
            $score = fake()->randomFloat(2, 0, $maxScore * 0.69); // 0% to 69%
            $percentage = ($score / $maxScore) * 100;
            
            return [
                'score' => $score,
                'percentage' => $percentage,
                'passed' => false,
            ];
        });
    }

    /**
     * Set the attempt number.
     */
    public function attemptNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'attempt_number' => $number,
        ]);
    }

    /**
     * Indicate that the attempt has answers.
     */
    public function withAnswers(int $count = 5): static
    {
        return $this->has(
            \App\Models\AssessmentAnswer::factory()->count($count),
            'answers'
        );
    }
}
