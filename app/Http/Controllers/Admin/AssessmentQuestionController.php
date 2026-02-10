<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateQuestionRequest;
use App\Services\AssessmentService;
use Illuminate\Http\Request;

class AssessmentQuestionController extends Controller
{
    protected AssessmentService $assessmentService;
    
    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }
    
    public function store(CreateQuestionRequest $request, int $assessmentId)
    {
        try {
            $this->assessmentService->addQuestion($assessmentId, $request->validated());
            return back()->with('success', 'Question added successfully');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function update(Request $request, int $id)
    {
        try {
            $this->assessmentService->updateQuestion($id, $request->all());
            return back()->with('success', 'Question updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function destroy(int $id)
    {
        try {
            $this->assessmentService->deleteQuestion($id);
            return back()->with('success', 'Question deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function reorder(Request $request, int $assessmentId)
    {
        try {
            $this->assessmentService->reorderQuestions($assessmentId, $request->input('orders', []));
            return back()->with('success', 'Questions reordered successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
