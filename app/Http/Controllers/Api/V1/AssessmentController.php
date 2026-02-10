<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssessmentResource;
use App\Http\Resources\AssessmentAttemptResource;
use App\Models\Assessment;
use App\Services\AssessmentAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    protected AssessmentAttemptService $attemptService;
    
    public function __construct(AssessmentAttemptService $attemptService)
    {
        $this->attemptService = $attemptService;
    }
    
    /**
     * Get assessment details
     * 
     * @param Assessment $assessment
     * @param Request $request
     * @return AssessmentResource|JsonResponse
     */
    public function show(Assessment $assessment, Request $request)
    {
        try {
            // Check access
            $this->attemptService->checkAccess($assessment->id, $request->user()->id);
            
            // Load questions
            $assessment->load('questions');
            
            return new AssessmentResource($assessment);
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
     * Start a new assessment attempt
     * 
     * @param Assessment $assessment
     * @param Request $request
     * @return AssessmentAttemptResource|JsonResponse
     */
    public function start(Assessment $assessment, Request $request)
    {
        try {
            $attempt = $this->attemptService->startAttempt($assessment->id, $request->user()->id);
            
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
     * Get student's attempt history for an assessment
     * 
     * @param Assessment $assessment
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function history(Assessment $assessment, Request $request)
    {
        // Get per_page parameter (default 15, max 100)
        $perPage = $request->has('per_page') 
            ? min((int) $request->input('per_page'), 100) 
            : 15;
        
        $attempts = $this->attemptService->getAttemptHistory(
            $assessment->id, 
            $request->user()->id,
            $perPage
        );
        
        return AssessmentAttemptResource::collection($attempts);
    }
}
