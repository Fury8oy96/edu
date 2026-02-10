<?php

namespace App\Services;

use App\Models\QuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get quiz statistics
     * 
     * @param int $quizId
     * @return array
     */
    public function getQuizStatistics(int $quizId): array
    {
        $attempts = QuizAttempt::where('quiz_id', $quizId)
            ->whereNotNull('submitted_at')
            ->get();

        $totalAttempts = $attempts->count();
        $averageScore = $totalAttempts > 0 ? $attempts->avg('score_percentage') : 0;
        $passedCount = $attempts->where('passed', true)->count();
        $passRate = $totalAttempts > 0 ? ($passedCount / $totalAttempts) * 100 : 0;

        return [
            'total_attempts' => $totalAttempts,
            'average_score' => round($averageScore, 2),
            'pass_rate' => round($passRate, 2),
            'passed_count' => $passedCount,
            'failed_count' => $totalAttempts - $passedCount,
        ];
    }

    /**
     * Get student performance for a quiz
     * 
     * @param int $quizId
     * @return Collection
     */
    public function getStudentResults(int $quizId): Collection
    {
        $results = QuizAttempt::select(
                'student_id',
                DB::raw('MAX(score_percentage) as best_score'),
                DB::raw('COUNT(*) as attempt_count'),
                DB::raw('MAX(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as has_passed')
            )
            ->where('quiz_id', $quizId)
            ->whereNotNull('submitted_at')
            ->groupBy('student_id')
            ->with('student')
            ->get();

        return $results->map(function ($result) {
            return [
                'student_id' => $result->student_id,
                'student_name' => $result->student->name ?? 'Unknown',
                'student_email' => $result->student->email ?? 'Unknown',
                'best_score' => round($result->best_score, 2),
                'attempt_count' => $result->attempt_count,
                'has_passed' => (bool) $result->has_passed,
            ];
        });
    }

    /**
     * Get detailed attempt breakdown
     * 
     * @param int $attemptId
     * @return array
     */
    public function getAttemptDetails(int $attemptId): array
    {
        $attempt = QuizAttempt::with([
            'quiz',
            'student',
            'answers.question'
        ])->find($attemptId);

        if (!$attempt) {
            return [];
        }

        $answers = $attempt->answers->map(function ($answer) {
            return [
                'question_id' => $answer->question_id,
                'question_text' => $answer->question->question_text,
                'question_type' => $answer->question->question_type,
                'question_points' => $answer->question->points,
                'student_answer' => $answer->student_answer,
                'points_awarded' => $answer->points_awarded,
                'is_correct' => $answer->is_correct,
                'correct_answer' => $answer->question->getCorrectAnswer(),
            ];
        });

        return [
            'attempt_id' => $attempt->id,
            'quiz_title' => $attempt->quiz->title,
            'student_name' => $attempt->student->name ?? 'Unknown',
            'student_email' => $attempt->student->email ?? 'Unknown',
            'started_at' => $attempt->started_at,
            'submitted_at' => $attempt->submitted_at,
            'time_taken_minutes' => $attempt->time_taken_minutes,
            'score' => $attempt->score,
            'score_percentage' => $attempt->score_percentage,
            'passed' => $attempt->passed,
            'requires_grading' => $attempt->requires_grading,
            'answers' => $answers,
        ];
    }
}
