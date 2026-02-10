<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizResource;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;

class QuizController extends Controller
{
    protected QuizService $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    /**
     * Get quiz details for enrolled student
     * 
     * GET /api/v1/quizzes/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $student = auth()->user();
            $quiz = $this->quizService->getQuizForStudent($id, $student);

            return response()->json([
                'data' => new QuizResource($quiz)
            ], 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
