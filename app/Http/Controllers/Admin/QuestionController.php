<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;

class QuestionController extends Controller
{
    protected QuizService $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    /**
     * Add question to quiz
     * 
     * POST /admin/quizzes/{quizId}/questions
     */
    public function store(int $quizId, StoreQuestionRequest $request): RedirectResponse
    {
        try {
            $this->quizService->addQuestion($quizId, $request->validated());

            return back()->with('success', 'Question added successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update question
     * 
     * PUT /admin/questions/{id}
     */
    public function update(int $id, StoreQuestionRequest $request): RedirectResponse
    {
        try {
            $this->quizService->updateQuestion($id, $request->validated());

            return back()->with('success', 'Question updated successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Delete question
     * 
     * DELETE /admin/questions/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->quizService->deleteQuestion($id);

            return back()->with('success', 'Question deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
