<?php

namespace Database\Factories;

use App\Models\AssessmentQuestion;
use App\Models\Assessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssessmentQuestion>
 */
class AssessmentQuestionFactory extends Factory
{
    protected $model = AssessmentQuestion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'question_type' => fake()->randomElement(['multiple_choice', 'true_false', 'short_answer', 'essay']),
            'question_text' => fake()->sentence() . '?',
            'points' => fake()->randomFloat(2, 1, 10),
            'order' => 1,
            'options' => null,
            'correct_answer' => null,
            'grading_rubric' => null,
        ];
    }

    /**
     * Indicate that the question is multiple choice.
     */
    public function multipleChoice(): static
    {
        return $this->state(function (array $attributes) {
            $options = [
                ['id' => 'a', 'text' => fake()->sentence()],
                ['id' => 'b', 'text' => fake()->sentence()],
                ['id' => 'c', 'text' => fake()->sentence()],
                ['id' => 'd', 'text' => fake()->sentence()],
            ];
            
            $correctOptionId = fake()->randomElement(['a', 'b', 'c', 'd']);
            
            return [
                'question_type' => 'multiple_choice',
                'options' => $options,
                'correct_answer' => ['correct_option_id' => $correctOptionId],
            ];
        });
    }

    /**
     * Indicate that the question is true/false.
     */
    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'true_false',
            'options' => null,
            'correct_answer' => ['correct_value' => fake()->boolean()],
        ]);
    }

    /**
     * Indicate that the question is short answer.
     */
    public function shortAnswer(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'short_answer',
            'options' => null,
            'correct_answer' => ['expected_answer' => fake()->sentence()],
            'grading_rubric' => fake()->paragraph(),
        ]);
    }

    /**
     * Indicate that the question is essay.
     */
    public function essay(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'essay',
            'options' => null,
            'correct_answer' => null,
            'grading_rubric' => fake()->paragraph(),
            'points' => fake()->randomFloat(2, 5, 20), // Essays typically worth more points
        ]);
    }

    /**
     * Set the order of the question.
     */
    public function order(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order' => $order,
        ]);
    }
}
