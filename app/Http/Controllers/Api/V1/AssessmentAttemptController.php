<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAssessmentRequest;
use App\Http\Resources\AssessmentAttemptResource;
use App\Models\AssessmentAttempt;
use App\Services\AssessmentAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentAttemptController extends Controller
{
    protected AssessmentAttemptService $attemptService;
    
    public function __construct(AssessmentAttemptService $attemptService)
    {
        $this->attemptService = $attemptService;
    }
    
    /**
     * Submit assessment answers
     * 
     * @param AssessmentAttempt $attempt
     * @param SubmitAssessmentRequest $request
     * @return AssessmentAttemptResource|JsonResponse
     */
    public function submit(AssessmentAttempt $attempt, SubmitAssessmentRequest $request)
    {
        try {
            // Verify ownership
            if ($attempt->student_id !== $request->user()->id) {
                return response()->json([
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Unauthorized access to attempt',
                    ]
                ], 403);
            }
            
            $attempt = $this->attemptService->submitAttempt($attempt->id, $request->validated()['answers']);
            
            return new AssessmentAttemptResource($attempt);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => method_exists($e, 'getErrorCode') ? $e->getErrorCode() : 'ERROR',
                    'message' => $e->getMessage(),
                ]
            ], method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    }
    
    /**
     * Get remaining time for an attempt
     * 
     * @param AssessmentAttempt $attempt
     * @param Request $request
     * @return JsonResponse
     */
    public function remainingTime(AssessmentAttempt $attempt, Request $request): JsonResponse
    {
        // Verify ownership
        if ($attempt->student_id !== $request->user()->id) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Unauthorized access to attempt',
                ]
            ], 403);
        }
        
        $remainingSeconds = $this->attemptService->getRemainingTime($attempt->id);
        
        return response()->json([
            'remaining_seconds' => $remainingSeconds,
        ]);
    }
    
    /**
     * Get attempt details
     * 
     * @param AssessmentAttempt $attempt
     * @param Request $request
     * @return AssessmentAttemptResource|JsonResponse
     */
    public function show(AssessmentAttempt $attempt, Request $request)
    {
        try {
            $attempt = $this->attemptService->getAttemptDetails($attempt->id, $request->user()->id);
            
            return new AssessmentAttemptResource($attempt);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => method_exists($e, 'getErrorCode') ? $e->getErrorCode() : 'ERROR',
                    'message' => $e->getMessage(),
                ]
            ], method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    }
}
