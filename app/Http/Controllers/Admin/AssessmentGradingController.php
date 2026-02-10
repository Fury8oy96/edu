<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeAnswerRequest;
use App\Services\AssessmentGradingService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AssessmentGradingController extends Controller
{
    protected AssessmentGradingService $gradingService;

    public function __construct(AssessmentGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Show pending grading queue
     * 
     * GET /admin/assessment-grading/pending
     */
    public function index(): View
    {
        $attempts = $this->gradingService->getPendingGrading();

        return view('admin.assessment-grading.pending', compact('attempts'));
    }

    /**
     * Show attempt details for grading
     * 
     * GET /admin/assessment-grading/attempts/{attemptId}
     */
    public function show(int $attemptId): View
    {
        $attempt = $this->gradingService->getAttemptForGrading($attemptId);

        return view('admin.assessment-grading.show', compact('attempt'));
    }

    /**
     * Grade a short answer or essay question
     * 
     * POST /admin/assessment-grading/answers/{answerId}
     */
    public function update(int $answerId, GradeAnswerRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            
            $this->gradingService->gradeAnswer(
                $answerId,
                $validated['points_earned'],
                $validated['grader_feedback'] ?? null
            );

            return back()->with('success', 'Answer graded successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
