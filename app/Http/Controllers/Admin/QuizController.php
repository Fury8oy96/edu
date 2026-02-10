<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuizRequest;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuizController extends Controller
{
    protected QuizService $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    /**
     * List all quizzes
     * 
     * GET /admin/quizzes
     */
    public function index(): View
    {
        $quizzes = \App\Models\Quiz::with('lesson.module.course')
            ->paginate(15);

        return view('admin.quizzes.index', compact('quizzes'));
    }

    /**
     * Show quiz creation form
     * 
     * GET /admin/quizzes/create
     */
    public function create(): View
    {
        $lessons = \App\Models\Lessons::with('module.course')->get();
        
        return view('admin.quizzes.create', compact('lessons'));
    }

    /**
     * Store new quiz
     * 
     * POST /admin/quizzes
     */
    public function store(StoreQuizRequest $request): RedirectResponse
    {
        try {
            $quiz = $this->quizService->createQuiz($request->validated());

            return redirect()
                ->route('admin.quizzes.edit', $quiz->id)
                ->with('success', 'Quiz created successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show quiz edit form
     * 
     * GET /admin/quizzes/{id}/edit
     */
    public function edit(int $id): View
    {
        $quiz = \App\Models\Quiz::with(['questions' => function ($query) {
            $query->orderBy('order', 'asc');
        }, 'lesson'])->findOrFail($id);

        $lessons = \App\Models\Lessons::with('module.course')->get();

        return view('admin.quizzes.edit', compact('quiz', 'lessons'));
    }

    /**
     * Update quiz
     * 
     * PUT /admin/quizzes/{id}
     */
    public function update(int $id, StoreQuizRequest $request): RedirectResponse
    {
        try {
            $this->quizService->updateQuiz($id, $request->validated());

            return back()->with('success', 'Quiz updated successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Delete quiz
     * 
     * DELETE /admin/quizzes/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->quizService->deleteQuiz($id);

            return redirect()
                ->route('admin.quizzes.index')
                ->with('success', 'Quiz deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
