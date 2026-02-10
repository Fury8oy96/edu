<?php

namespace App\Services;

use App\Exceptions\Assessment\AssessmentNotAvailableException;
use App\Exceptions\Assessment\AttemptNotFoundException;
use App\Exceptions\Assessment\MaxAttemptsExceededException;
use App\Exceptions\Assessment\NotEnrolledException;
use App\Exceptions\Assessment\PrerequisitesNotMetException;
use App\Exceptions\Assessment\TimeLimitExceededException;
use App\Exceptions\Assessment\AssessmentAlreadySubmittedException;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Students;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentAttemptService
{
    protected PrerequisiteCheckService $prerequisiteService;
    protected AssessmentGradingService $gradingService;
    
    public function __construct(
        PrerequisiteCheckService $prerequisiteService,
        AssessmentGradingService $gradingService
    ) {
        $this->prerequisiteService = $prerequisiteService;
        $this->gradingService = $gradingService;
    }
    
    /**
     * Check if student can access assessment
     * 
     * @param int $assessmentId
     * @param int $studentId
     * @return bool
     * @throws NotEnrolledException
     * @throws PrerequisitesNotMetException
     * @throws MaxAttemptsExceededException
     * @throws AssessmentNotAvailableException
     */
    public function checkAccess(int $assessmentId, int $studentId): bool
    {
        $assessment = Assessment::findOrFail($assessmentId);
        $student = Students::findOrFail($studentId);
        
        // Check enrollment
        $isEnrolled = DB::table('course_student')
            ->where('course_id', $assessment->course_id)
            ->where('student_id', $studentId)
            ->exists();
            
        if (!$isEnrolled) {
            throw new NotEnrolledException('Not enrolled in course');
        }
        
        // Check email verification
        if (!$student->email_verified_at) {
            throw new NotEnrolledException('Email must be verified');
        }
        
        // Check availability window
        $now = Carbon::now();
        if ($assessment->start_date && $now->lt($assessment->start_date)) {
            throw new AssessmentNotAvailableException('Assessment not yet available');
        }
        if ($assessment->end_date && $now->gt($assessment->end_date)) {
            throw new AssessmentNotAvailableException('Assessment is no longer available');
        }
        
        // Check prerequisites
        $prerequisiteCheck = $this->prerequisiteService->checkPrerequisites($assessmentId, $studentId);
        if (!$prerequisiteCheck['met']) {
            throw new PrerequisitesNotMetException($prerequisiteCheck['unmet']);
        }
        
        // Check attempt limits
        if ($assessment->max_attempts !== null) {
            $attemptsRemaining = $assessment->calculateAttemptsRemaining($student);
            if ($attemptsRemaining <= 0) {
                throw new MaxAttemptsExceededException('Maximum attempts exceeded');
            }
        }
        
        return true;
    }
    
    /**
     * Start a new assessment attempt
     * 
     * @param int $assessmentId
     * @param int $studentId
     * @return AssessmentAttempt
     */
    public function startAttempt(int $assessmentId, int $studentId): AssessmentAttempt
    {
        // Check access first
        $this->checkAccess($assessmentId, $studentId);
        
        $assessment = Assessment::with('questions')->findOrFail($assessmentId);
        
        // Calculate attempt number
        $attemptNumber = AssessmentAttempt::where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->max('attempt_number') ?? 0;
        $attemptNumber++;
        
        // Calculate max score
        $maxScore = $assessment->questions->sum('points');
        
        // Create attempt
        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessmentId,
            'student_id' => $studentId,
            'attempt_number' => $attemptNumber,
            'status' => 'in_progress',
            'start_time' => Carbon::now(),
            'max_score' => $maxScore,
        ]);
        
        return $attempt->load('assessment.questions');
    }
    
    /**
     * Submit assessment answers
     * 
     * @param int $attemptId
     * @param array $answers
     * @return AssessmentAttempt
     * @throws AttemptNotFoundException
     * @throws TimeLimitExceededException
     * @throws ValidationException
     */
    public function submitAttempt(int $attemptId, array $answers): AssessmentAttempt
    {
        return DB::transaction(function () use ($attemptId, $answers) {
            $attempt = AssessmentAttempt::with('assessment.questions')->findOrFail($attemptId);
            
            // Check if already submitted
            if ($attempt->isCompleted()) {
                throw new AssessmentAlreadySubmittedException('Assessment already submitted');
            }
            
            // Check time limit
            $now = Carbon::now();
            $timeLimitMinutes = $attempt->assessment->time_limit;
            $deadline = $attempt->start_time->copy()->addMinutes($timeLimitMinutes);
            
            if ($now->gt($deadline)) {
                // Mark as timed out
                $attempt->update([
                    'status' => 'timed_out',
                    'completion_time' => $now,
                    'time_taken' => $now->diffInSeconds($attempt->start_time),
                ]);
                throw new TimeLimitExceededException('Assessment time limit exceeded');
            }
            
            // Validate answer completeness
            $questionIds = $attempt->assessment->questions->pluck('id')->toArray();
            $answeredQuestionIds = collect($answers)->pluck('question_id')->toArray();
            
            if (count(array_diff($questionIds, $answeredQuestionIds)) > 0) {
                throw ValidationException::withMessages([
                    'answers' => ['All questions must be answered']
                ]);
            }
            
            // Store answers
            foreach ($answers as $answerData) {
                $question = $attempt->assessment->questions->firstWhere('id', $answerData['question_id']);
                
                $attempt->answers()->create([
                    'question_id' => $answerData['question_id'],
                    'answer' => $answerData['answer'],
                    'grading_status' => in_array($question->question_type, ['multiple_choice', 'true_false']) 
                        ? 'auto_graded' 
                        : 'pending_review',
                ]);
            }
            
            // Auto-grade
            $this->gradingService->autoGradeAnswers($attempt);
            
            // Calculate completion time
            $completionTime = Carbon::now();
            $timeTaken = $completionTime->diffInSeconds($attempt->start_time);
            
            // Check if all questions are auto-graded
            $pendingCount = $attempt->answers()->where('grading_status', 'pending_review')->count();
            
            if ($pendingCount > 0) {
                // Has manual grading questions
                $attempt->update([
                    'status' => 'grading_pending',
                    'completion_time' => $completionTime,
                    'time_taken' => $timeTaken,
                ]);
            } else {
                // All auto-graded, calculate final score
                $this->gradingService->recalculateScore($attemptId);
            }
            
            return $attempt->fresh(['answers.question', 'assessment']);
        });
    }
    
    /**
     * Auto-submit attempt when time expires
     * 
     * @param int $attemptId
     * @return AssessmentAttempt
     */
    public function autoSubmitOnTimeout(int $attemptId): AssessmentAttempt
    {
        return DB::transaction(function () use ($attemptId) {
            $attempt = AssessmentAttempt::with('assessment.questions', 'answers')->findOrFail($attemptId);
            
            // Auto-grade any answered questions
            if ($attempt->answers->count() > 0) {
                $this->gradingService->autoGradeAnswers($attempt);
            }
            
            // Mark as timed out
            $now = Carbon::now();
            $attempt->update([
                'status' => 'timed_out',
                'completion_time' => $now,
                'time_taken' => $now->diffInSeconds($attempt->start_time),
            ]);
            
            // Calculate score if possible
            $pendingCount = $attempt->answers()->where('grading_status', 'pending_review')->count();
            if ($pendingCount === 0 && $attempt->answers->count() > 0) {
                $this->gradingService->recalculateScore($attemptId);
            }
            
            return $attempt->fresh();
        });
    }
    
    /**
     * Get student's attempt history for an assessment
     * 
     * @param int $assessmentId
     * @param int $studentId
     * @param int|null $perPage Number of items per page (null for all)
     * @return Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAttemptHistory(int $assessmentId, int $studentId, ?int $perPage = null)
    {
        $query = AssessmentAttempt::where('assessment_id', $assessmentId)
            ->where('student_id', $studentId)
            ->with('assessment.course')
            ->orderBy('created_at', 'desc');
        
        // Return paginated results if perPage is specified, otherwise return all
        return $perPage ? $query->paginate($perPage) : $query->get();
    }
    
    /**
     * Get attempt details with answers
     * 
     * @param int $attemptId
     * @param int $studentId
     * @return AssessmentAttempt
     * @throws AttemptNotFoundException
     */
    public function getAttemptDetails(int $attemptId, int $studentId): AssessmentAttempt
    {
        $attempt = AssessmentAttempt::with([
            'assessment.course',
            'answers.question'
        ])->find($attemptId);
        
        if (!$attempt) {
            throw new AttemptNotFoundException('Assessment attempt not found');
        }
        
        // Verify ownership
        if ($attempt->student_id !== $studentId) {
            throw new AttemptNotFoundException('Assessment attempt not found');
        }
        
        return $attempt;
    }
    
    /**
     * Calculate remaining time for an attempt
     * 
     * @param int $attemptId
     * @return int Remaining seconds (0 if expired)
     */
    public function getRemainingTime(int $attemptId): int
    {
        $attempt = AssessmentAttempt::with('assessment')->findOrFail($attemptId);
        
        if ($attempt->isCompleted()) {
            return 0;
        }
        
        $now = Carbon::now();
        $timeLimitMinutes = $attempt->assessment->time_limit;
        $deadline = $attempt->start_time->copy()->addMinutes($timeLimitMinutes);
        
        $remainingSeconds = $deadline->diffInSeconds($now, false);
        
        return max(0, $remainingSeconds);
    }
}
