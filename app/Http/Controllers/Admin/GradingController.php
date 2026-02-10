<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeAnswerRequest;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GradingController extends Controller
{
    protected GradingService $gradingService;

    public function __construct(GradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Show pending grading queue
     * 
     * GET /admin/grading/pending
     */
    public function pending(): View
    {
        $attempts = $this->gradingService->getPendingGradingQueue();

        return view('admin.grading.pending', compact('attempts'));
    }

    /**
     * Grade a short answer
     * 
     * POST /admin/grading/answers/{id}
     */
    public function grade(int $id, GradeAnswerRequest $request): RedirectResponse
    {
        try {
            $points = $request->validated()['points_awarded'];
            $this->gradingService->gradeShortAnswer($id, $points);

            return back()->with('success', 'Answer graded successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
