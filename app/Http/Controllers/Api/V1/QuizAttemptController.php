<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAnswersRequest;
use App\Http\Resources\QuizAttemptResource;
use App\Services\QuizAttemptService;
use Illuminate\Http\JsonResponse;

class QuizAttemptController extends Controller
{
    protected QuizAttemptService $quizAttemptService;

    public function __construct(QuizAttemptService $quizAttemptService)
    {
        $this->quizAttemptService = $quizAttemptService;
    }

    /**
     * Start a new quiz attempt
     * 
     * POST /api/v1/quizzes/{quizId}/attempts
     */
    public function start(int $quizId): JsonResponse
    {
        try {
            $student = auth()->user();
            $attempt = $this->quizAttemptService->startAttempt($quizId, $student);

            return response()->json([
                'data' => new QuizAttemptResource($attempt)
            ], 201);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Submit answers for an attempt
     * 
     * POST /api/v1/quiz-attempts/{attemptId}/submit
     */
    public function submit(int $attemptId, SubmitAnswersRequest $request): JsonResponse
    {
        try {
            $student = auth()->user();
            $answers = $request->validated()['answers'];
            
            $attempt = $this->quizAttemptService->submitAnswers($attemptId, $answers, $student);

            return response()->json([
                'data' => new QuizAttemptResource($attempt)
            ], 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get attempt details and results
     * 
     * GET /api/v1/quiz-attempts/{attemptId}
     */
    public function show(int $attemptId): JsonResponse
    {
        try {
            $student = auth()->user();
            $attempt = $this->quizAttemptService->getAttempt($attemptId, $student);

            return response()->json([
                'data' => new QuizAttemptResource($attempt)
            ], 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get all attempts for a quiz
     * 
     * GET /api/v1/quizzes/{quizId}/attempts
     */
    public function index(int $quizId): JsonResponse
    {
        try {
            $student = auth()->user();
            $attempts = $this->quizAttemptService->getStudentAttempts($quizId, $student);
            $remainingAttempts = $this->quizAttemptService->getRemainingAttempts($quizId, $student);

            return response()->json([
                'data' => QuizAttemptResource::collection($attempts),
                'meta' => [
                    'remaining_attempts' => $remainingAttempts
                ]
            ], 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
