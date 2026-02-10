<?php

namespace App\Services;

use App\Exceptions\Assessment\AnswerNotFoundException;
use App\Exceptions\Assessment\InvalidGradingDataException;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentGradingService
{
    /**
     * Auto-grade multiple choice and true/false questions
     * 
     * @param AssessmentAttempt $attempt
     * @return void
     */
    public function autoGradeAnswers(AssessmentAttempt $attempt): void
    {
        $attempt->load('answers.question');
        
        foreach ($attempt->answers as $answer) {
            $question = $answer->question;
            
            // Only auto-grade multiple_choice and true_false
            if (!in_array($question->question_type, ['multiple_choice', 'true_false'])) {
                continue;
            }
            
            $isCorrect = false;
            $pointsEarned = 0;
            
            if ($question->question_type === 'multiple_choice') {
                // Check if selected option matches correct answer
                $studentAnswer = $answer->answer;
                $correctAnswer = $question->correct_answer;
                
                if (isset($studentAnswer['selected_option_id']) && isset($correctAnswer['correct_option_id'])) {
                    $isCorrect = $studentAnswer['selected_option_id'] === $correctAnswer['correct_option_id'];
                }
            } elseif ($question->question_type === 'true_false') {
                // Check if boolean matches
                $studentAnswer = $answer->answer;
                $correctAnswer = $question->correct_answer;
                
                if (isset($studentAnswer['value']) && isset($correctAnswer['correct_value'])) {
                    $isCorrect = $studentAnswer['value'] === $correctAnswer['correct_value'];
                }
            }
            
            if ($isCorrect) {
                $pointsEarned = $question->points;
            }
            
            $answer->update([
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'grading_status' => 'auto_graded',
            ]);
        }
    }
    
    /**
     * Manually grade a short answer or essay question
     * 
     * @param int $answerId
     * @param float $points
     * @param string|null $feedback
     * @return AssessmentAnswer
     * @throws AnswerNotFoundException
     * @throws ValidationException
     */
    public function gradeAnswer(int $answerId, float $points, ?string $feedback = null): AssessmentAnswer
    {
        return DB::transaction(function () use ($answerId, $points, $feedback) {
            $answer = AssessmentAnswer::with('question')->find($answerId);
            
            if (!$answer) {
                throw new AnswerNotFoundException('Answer not found');
            }
            
            // Validate points are within range
            if ($points < 0 || $points > $answer->question->points) {
                throw ValidationException::withMessages([
                    'points_earned' => ["Points must be between 0 and {$answer->question->points}"]
                ]);
            }
            
            // Update answer
            $answer->update([
                'points_earned' => $points,
                'grading_status' => 'manually_graded',
                'grader_feedback' => $feedback,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
            ]);
            
            // Recalculate attempt score
            $this->recalculateScore($answer->attempt_id);
            
            return $answer->fresh();
        });
    }
    
    /**
     * Recalculate attempt score after manual grading
     * 
     * @param int $attemptId
     * @return AssessmentAttempt
     */
    public function recalculateScore(int $attemptId): AssessmentAttempt
    {
        $attempt = AssessmentAttempt::with(['answers', 'assessment'])->findOrFail($attemptId);
        
        // Calculate total score
        $totalScore = $attempt->answers->sum('points_earned') ?? 0;
        $maxScore = $attempt->max_score;
        
        // Calculate percentage
        $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;
        
        // Determine if passed
        $passed = $percentage >= $attempt->assessment->passing_score;
        
        // Check if all questions are graded
        $pendingCount = $attempt->answers()->where('grading_status', 'pending_review')->count();
        $status = $pendingCount > 0 ? 'grading_pending' : 'completed';
        
        // Update attempt
        $attempt->update([
            'score' => $totalScore,
            'percentage' => $percentage,
            'passed' => $passed,
            'status' => $status,
        ]);
        
        return $attempt->fresh();
    }
    
    /**
     * Get all attempts pending manual grading
     * 
     * @param int|null $assessmentId
     * @return Collection
     */
    public function getPendingGrading(?int $assessmentId = null): Collection
    {
        $query = AssessmentAttempt::whereHas('answers', function ($q) {
            $q->where('grading_status', 'pending_review');
        })->with(['assessment', 'student', 'answers' => function ($q) {
            $q->where('grading_status', 'pending_review')->with('question');
        }]);
        
        if ($assessmentId) {
            $query->where('assessment_id', $assessmentId);
        }
        
        return $query->orderBy('completion_time', 'asc')->get();
    }
    
    /**
     * Get attempt details for grading
     * 
     * @param int $attemptId
     * @return AssessmentAttempt
     */
    public function getAttemptForGrading(int $attemptId): AssessmentAttempt
    {
        return AssessmentAttempt::with([
            'assessment',
            'student',
            'answers.question'
        ])->findOrFail($attemptId);
    }
}
