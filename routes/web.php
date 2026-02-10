<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\GradingController;
use App\Http\Controllers\Admin\AnalyticsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstructorsController;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    
});

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::resource('instructors', InstructorsController::class);
    
    // Admin Quiz Management Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Quiz CRUD routes
        Route::resource('quizzes', QuizController::class);
        
        // Question routes
        Route::post('quizzes/{quizId}/questions', [QuestionController::class, 'store'])->name('quizzes.questions.store');
        Route::put('questions/{id}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('questions/{id}', [QuestionController::class, 'destroy'])->name('questions.destroy');
        
        // Grading routes
        Route::get('grading/pending', [GradingController::class, 'pending'])->name('grading.pending');
        Route::post('grading/answers/{id}', [GradingController::class, 'grade'])->name('grading.grade');
        
        // Analytics routes
        Route::get('quizzes/{quizId}/analytics', [AnalyticsController::class, 'show'])->name('quizzes.analytics');
        Route::get('quiz-attempts/{attemptId}', [AnalyticsController::class, 'attempt'])->name('quiz-attempts.show');
        
        // Assessment CRUD routes
        Route::resource('assessments', \App\Http\Controllers\Admin\AssessmentController::class);
        
        // Assessment Question routes
        Route::post('assessments/{assessmentId}/questions', [\App\Http\Controllers\Admin\AssessmentQuestionController::class, 'store'])->name('assessments.questions.store');
        Route::put('assessment-questions/{id}', [\App\Http\Controllers\Admin\AssessmentQuestionController::class, 'update'])->name('assessment-questions.update');
        Route::delete('assessment-questions/{id}', [\App\Http\Controllers\Admin\AssessmentQuestionController::class, 'destroy'])->name('assessment-questions.destroy');
        Route::post('assessments/{assessmentId}/questions/reorder', [\App\Http\Controllers\Admin\AssessmentQuestionController::class, 'reorder'])->name('assessments.questions.reorder');
        
        // Assessment Prerequisite routes
        Route::post('assessments/{assessmentId}/prerequisites', [\App\Http\Controllers\Admin\AssessmentPrerequisiteController::class, 'store'])->name('assessments.prerequisites.store');
        Route::delete('assessment-prerequisites/{id}', [\App\Http\Controllers\Admin\AssessmentPrerequisiteController::class, 'destroy'])->name('assessment-prerequisites.destroy');
        
        // Assessment Grading routes
        Route::get('assessment-grading/pending', [\App\Http\Controllers\Admin\AssessmentGradingController::class, 'index'])->name('assessment-grading.pending');
        Route::get('assessment-grading/attempts/{attemptId}', [\App\Http\Controllers\Admin\AssessmentGradingController::class, 'show'])->name('assessment-grading.show');
        Route::post('assessment-grading/answers/{answerId}', [\App\Http\Controllers\Admin\AssessmentGradingController::class, 'update'])->name('assessment-grading.update');
        
        // Assessment Analytics routes
        Route::get('assessments/{assessmentId}/analytics', [\App\Http\Controllers\Admin\AssessmentAnalyticsController::class, 'show'])->name('assessments.analytics');
        Route::get('assessments/{assessmentId}/analytics/export', [\App\Http\Controllers\Admin\AssessmentAnalyticsController::class, 'export'])->name('assessments.analytics.export');
    });
});
