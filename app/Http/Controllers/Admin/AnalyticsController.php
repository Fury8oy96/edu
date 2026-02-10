<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Show quiz analytics
     * 
     * GET /admin/quizzes/{quizId}/analytics
     */
    public function show(int $quizId): View
    {
        $statistics = $this->analyticsService->getQuizStatistics($quizId);
        $studentResults = $this->analyticsService->getStudentResults($quizId);
        $quiz = \App\Models\Quiz::with('lesson')->findOrFail($quizId);

        return view('admin.analytics.quiz', compact('quiz', 'statistics', 'studentResults'));
    }

    /**
     * Show detailed attempt view
     * 
     * GET /admin/quiz-attempts/{attemptId}
     */
    public function attempt(int $attemptId): View
    {
        $attemptDetails = $this->analyticsService->getAttemptDetails($attemptId);

        return view('admin.analytics.attempt', compact('attemptDetails'));
    }
}
