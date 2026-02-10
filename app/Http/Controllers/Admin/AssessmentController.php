<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Models\Assessment;
use App\Services\AssessmentService;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    protected AssessmentService $assessmentService;
    
    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }
    
    /**
     * Display a listing of assessments
     */
    public function index()
    {
        $assessments = Assessment::with('course')->paginate(15);
        return view('admin.assessments.index', compact('assessments'));
    }
    
    /**
     * Show the form for creating a new assessment
     */
    public function create()
    {
        $courses = \App\Models\Courses::all();
        return view('admin.assessments.create', compact('courses'));
    }
    
    /**
     * Store a newly created assessment
     */
    public function store(CreateAssessmentRequest $request)
    {
        try {
            $assessment = $this->assessmentService->createAssessment($request->validated());
            return redirect()->route('admin.assessments.edit', $assessment->id)
                ->with('success', 'Assessment created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Show the form for editing an assessment
     */
    public function edit(Assessment $assessment)
    {
        $assessment->load(['questions', 'prerequisites', 'course']);
        $courses = \App\Models\Courses::all();
        return view('admin.assessments.edit', compact('assessment', 'courses'));
    }
    
    /**
     * Update the specified assessment
     */
    public function update(UpdateAssessmentRequest $request, Assessment $assessment)
    {
        try {
            $this->assessmentService->updateAssessment($assessment->id, $request->validated());
            return back()->with('success', 'Assessment updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Remove the specified assessment
     */
    public function destroy(Assessment $assessment)
    {
        try {
            $this->assessmentService->deleteAssessment($assessment->id);
            return redirect()->route('admin.assessments.index')
                ->with('success', 'Assessment deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
