<?php

namespace App\Services;

use App\Exceptions\AttemptAlreadySubmittedException;
use App\Exceptions\AttemptNotFoundException;
use App\Exceptions\DeadlineExceededException;
use App\Exceptions\MaxAttemptsExceededException;
use App\Exceptions\QuizNotFoundException;
use App\Exceptions\UnauthorizedAttemptAccessException;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\Students;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuizAttemptService
{
    protected QuizService $quizService;
    protected GradingService $gradingService;

    public function __construct(QuizService $quizService, GradingService $gradingService)
    {
        $this->quizService = $quizService;
        $this->gradingService = $gradingService;
    }

    /**
     * Start a new quiz attempt
     * 
     * @param int $quizId
     * @param Students $student
     * @return QuizAttempt
     * @throws QuizNotFoundException
     * @throws MaxAttemptsExceededException
     */
    public function startAttempt(int $quizId, Students $student): QuizAttempt
    {
        // Validate quiz exists and student is enrolled
        $quiz = $this->quizService->getQuizForStudent($quizId, $student);

        // Check remaining attempts
        $remainingAttempts = $this->getRemainingAttempts($quizId, $student);
        if ($remainingAttempts <= 0) {
            throw new MaxAttemptsExceededException('Maximum quiz attempts exceeded');
        }

        // Create quiz attempt
        $startedAt = now();
        $deadline = $startedAt->copy()->addMinutes($quiz->time_limit_minutes);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quizId,
            'student_id' => $student->id,
            'started_at' => $startedAt,
            'deadline' => $deadline,
        ]);

        // Load questions
        $attempt->load(['quiz.questions']);

        // Apply randomization if enabled
        if ($quiz->randomize_questions) {
            // Use deterministic seed based on attempt ID for consistency
            $questions = $attempt->quiz->questions->shuffle(function () use ($attempt) {
                mt_srand($attempt->id);
                return mt_rand();
            });
            $attempt->quiz->setRelation('questions', $questions);
        } else {
            // Order by the order field
            $questions = $attempt->quiz->questions->sortBy('order');
            $attempt->quiz->setRelation('questions', $questions);
        }

        return $attempt;
    }

    /**
     * Submit answers for a quiz attempt
     * 
     * @param int $attemptId
     * @param array $answers
     * @param Students $student
     * @return QuizAttempt
     * @throws AttemptNotFoundException
     * @throws AttemptAlreadySubmittedException
     * @throws DeadlineExceededException
     * @throws UnauthorizedAttemptAccessException
     */
    public function submitAnswers(int $attemptId, array $answers, Students $student): QuizAttempt
    {
        $attempt = QuizAttempt::with('quiz.questions')->find($attemptId);

        if (!$attempt) {
            throw new AttemptNotFoundException('Quiz attempt not found');
        }

        // Validate attempt belongs to student
        if ($attempt->student_id !== $student->id) {
            throw new UnauthorizedAttemptAccessException('You do not have permission to access this quiz attempt');
        }

        // Check attempt not already submitted
        if ($attempt->isSubmitted()) {
            throw new AttemptAlreadySubmittedException('This quiz attempt has already been submitted');
        }

        // Check deadline
        if ($attempt->isExpired()) {
            throw new DeadlineExceededException('Quiz deadline has passed');
        }

        // Use database transaction to store all answers
        DB::transaction(function () use ($attempt, $answers) {
            $submittedAt = now();
            
            // Store each answer
            foreach ($answers as $answerData) {
                QuizAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $answerData['question_id'],
                    'student_answer' => $answerData['answer'] ?? null,
                ]);
            }

            // Calculate time taken
            $timeTaken = $attempt->started_at->diffInMinutes($submittedAt);

            // Update attempt
            $attempt->update([
                'submitted_at' => $submittedAt,
                'time_taken_minutes' => $timeTaken,
            ]);

            // Grade the attempt
            $this->gradingService->gradeAttempt($attempt);
        });

        // Reload attempt with answers
        return $attempt->fresh(['answers.question', 'quiz']);
    }

    /**
     * Get attempt details with answers
     * 
     * @param int $attemptId
     * @param Students $student
     * @return QuizAttempt
     * @throws AttemptNotFoundException
     * @throws UnauthorizedAttemptAccessException
     */
    public function getAttempt(int $attemptId, Students $student): QuizAttempt
    {
        $attempt = QuizAttempt::with(['answers.question', 'quiz'])->find($attemptId);

        if (!$attempt) {
            throw new AttemptNotFoundException('Quiz attempt not found');
        }

        // Validate attempt belongs to student
        if ($attempt->student_id !== $student->id) {
            throw new UnauthorizedAttemptAccessException('You do not have permission to access this quiz attempt');
        }

        return $attempt;
    }

    /**
     * Get all attempts for a student and quiz
     * 
     * @param int $quizId
     * @param Students $student
     * @return Collection
     */
    public function getStudentAttempts(int $quizId, Students $student): Collection
    {
        return QuizAttempt::where('quiz_id', $quizId)
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->orderBy('submitted_at', 'desc')
            ->get();
    }

    /**
     * Auto-submit attempt when deadline is reached
     * 
     * @param int $attemptId
     * @return QuizAttempt
     * @throws AttemptNotFoundException
     */
    public function autoSubmitExpiredAttempt(int $attemptId): QuizAttempt
    {
        $attempt = QuizAttempt::with('quiz.questions')->find($attemptId);

        if (!$attempt) {
            throw new AttemptNotFoundException('Quiz attempt not found');
        }

        // If already submitted, return as is
        if ($attempt->isSubmitted()) {
            return $attempt;
        }

        // Get existing answers
        $existingAnswers = QuizAnswer::where('quiz_attempt_id', $attemptId)->pluck('question_id')->toArray();

        // Use database transaction
        DB::transaction(function () use ($attempt, $existingAnswers) {
            $submittedAt = now();

            // Create empty answers for unanswered questions
            foreach ($attempt->quiz->questions as $question) {
                if (!in_array($question->id, $existingAnswers)) {
                    QuizAnswer::create([
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'student_answer' => null,
                    ]);
                }
            }

            // Calculate time taken
            $timeTaken = $attempt->started_at->diffInMinutes($submittedAt);

            // Update attempt
            $attempt->update([
                'submitted_at' => $submittedAt,
                'time_taken_minutes' => $timeTaken,
            ]);

            // Grade the attempt
            $this->gradingService->gradeAttempt($attempt);
        });

        return $attempt->fresh(['answers.question', 'quiz']);
    }

    /**
     * Calculate remaining attempts for a student
     * 
     * @param int $quizId
     * @param Students $student
     * @return int
     */
    public function getRemainingAttempts(int $quizId, Students $student): int
    {
        $quiz = Quiz::find($quizId);
        if (!$quiz) {
            return 0;
        }

        $attemptCount = QuizAttempt::where('quiz_id', $quizId)
            ->where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->count();

        return max(0, $quiz->max_attempts - $attemptCount);
    }
}
