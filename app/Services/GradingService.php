<?php

namespace App\Services;

use App\Exceptions\InvalidPointsException;
use App\Exceptions\QuizAnswerNotFoundException;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradingService
{
    /**
     * Grade a submitted quiz attempt
     * Auto-grades objective questions, marks subjective for manual grading
     * 
     * @param QuizAttempt $attempt
     * @return QuizAttempt
     */
    public function gradeAttempt(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->load(['answers.question', 'quiz']);
        
        $totalScore = 0;
        $totalPoints = 0;
        $requiresGrading = false;

        foreach ($attempt->answers as $answer) {
            $question = $answer->question;
            $totalPoints += $question->points;

            if ($question->isAutoGradable()) {
                // Auto-grade multiple_choice and true_false questions
                $isCorrect = $this->checkAnswer($question, $answer->student_answer);
                $pointsAwarded = $isCorrect ? $question->points : 0;

                $answer->update([
                    'is_correct' => $isCorrect,
                    'points_awarded' => $pointsAwarded,
                ]);

                $totalScore += $pointsAwarded;
            } else {
                // Mark short_answer for manual grading
                $requiresGrading = true;
                $answer->update([
                    'is_correct' => null,
                    'points_awarded' => null,
                ]);
            }
        }

        // Calculate score percentage
        $scorePercentage = $totalPoints > 0 ? ($totalScore / $totalPoints) * 100 : 0;
        $passed = $scorePercentage >= $attempt->quiz->passing_score_percentage;

        // Update attempt
        $attempt->update([
            'score' => $totalScore,
            'score_percentage' => $scorePercentage,
            'passed' => $passed,
            'requires_grading' => $requiresGrading,
        ]);

        return $attempt->fresh();
    }

    /**
     * Manually grade a short answer question
     * 
     * @param int $answerId
     * @param float $points
     * @return QuizAnswer
     * @throws QuizAnswerNotFoundException
     * @throws InvalidPointsException
     */
    public function gradeShortAnswer(int $answerId, float $points): QuizAnswer
    {
        $answer = QuizAnswer::with('question')->find($answerId);

        if (!$answer) {
            throw new QuizAnswerNotFoundException('Quiz answer not found');
        }

        // Validate points
        if ($points < 0 || $points > $answer->question->points) {
            throw new InvalidPointsException('Points awarded must be between 0 and ' . $answer->question->points);
        }

        // Update answer
        $answer->update([
            'points_awarded' => $points,
            'is_correct' => $points > 0,
        ]);

        // Recalculate attempt score
        $this->recalculateScore($answer->attempt);

        return $answer->fresh();
    }

    /**
     * Get all attempts requiring manual grading
     * 
     * @return Collection
     */
    public function getPendingGradingQueue(): Collection
    {
        return QuizAttempt::with(['quiz', 'student', 'answers.question'])
            ->where('requires_grading', true)
            ->orderBy('submitted_at', 'asc')
            ->get();
    }

    /**
     * Check if attempt is fully graded
     * 
     * @param QuizAttempt $attempt
     * @return bool
     */
    public function isFullyGraded(QuizAttempt $attempt): bool
    {
        $attempt->load('answers');
        
        foreach ($attempt->answers as $answer) {
            if (is_null($answer->points_awarded)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recalculate attempt score after manual grading
     * 
     * @param QuizAttempt $attempt
     * @return QuizAttempt
     */
    public function recalculateScore(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->load(['answers.question', 'quiz']);

        $totalScore = 0;
        $totalPoints = 0;

        foreach ($attempt->answers as $answer) {
            $totalPoints += $answer->question->points;
            $totalScore += $answer->points_awarded ?? 0;
        }

        // Calculate score percentage
        $scorePercentage = $totalPoints > 0 ? ($totalScore / $totalPoints) * 100 : 0;
        $passed = $scorePercentage >= $attempt->quiz->passing_score_percentage;

        // Check if fully graded
        $requiresGrading = !$this->isFullyGraded($attempt);

        // Update attempt
        $attempt->update([
            'score' => $totalScore,
            'score_percentage' => $scorePercentage,
            'passed' => $passed,
            'requires_grading' => $requiresGrading,
        ]);

        return $attempt->fresh();
    }

    /**
     * Check if an answer is correct
     * 
     * @param \App\Models\Question $question
     * @param mixed $studentAnswer
     * @return bool
     */
    protected function checkAnswer($question, $studentAnswer): bool
    {
        $correctAnswer = $question->getCorrectAnswer();

        if ($question->question_type === 'true_false') {
            // Convert string to boolean if needed
            if (is_string($studentAnswer)) {
                $studentAnswer = filter_var($studentAnswer, FILTER_VALIDATE_BOOLEAN);
            }
            return $studentAnswer === $correctAnswer;
        }

        if ($question->question_type === 'multiple_choice') {
            // Compare text answers (case-insensitive)
            return strcasecmp(trim($studentAnswer), trim($correctAnswer)) === 0;
        }

        return false;
    }
}
