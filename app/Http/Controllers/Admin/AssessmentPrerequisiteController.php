<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AssessmentService;
use Illuminate\Http\Request;

class AssessmentPrerequisiteController extends Controller
{
    protected AssessmentService $assessmentService;
    
    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }
    
    /**
     * Add a prerequisite to an assessment
     * 
     * POST /admin/assessments/{assessmentId}/prerequisites
     */
    public function store(Request $request, int $assessmentId)
    {
        try {
            $validated = $request->validate([
                'prerequisite_type' => 'required|in:quiz_completion,minimum_progress,lesson_completion',
                'prerequisite_data' => 'required|array',
            ]);
            
            $this->assessmentService->addPrerequisite($assessmentId, $validated);
            
            return back()->with('success', 'Prerequisite added successfully');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Remove a prerequisite from an assessment
     * 
     * DELETE /admin/assessment-prerequisites/{id}
     */
    public function destroy(int $id)
    {
        try {
            $this->assessmentService->removePrerequisite($id);
            
            return back()->with('success', 'Prerequisite removed successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
