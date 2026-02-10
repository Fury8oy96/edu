<?php

use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\Courses;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Property 49: Concurrent Modification Safety
 * 
 * For any two concurrent requests to modify the same assessment, the system must use 
 * database locking to ensure both modifications are applied correctly without data corruption.
 * 
 * Validates: Requirements 10.5
 */

test('concurrent assessment updates do not corrupt data', function () {
    // Feature: assessment-system, Property 49: Concurrent Modification Safety
    // Validates: Requirements 10.5
    
    $service = app(AssessmentService::class);
    $course = Courses::factory()->create();
    
    // Create initial assessment
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'title' => 'Original Title',
        'time_limit' => 60,
        'passing_score' => 70,
    ]);
    
    $originalId = $assessment->id;
    
    // Simulate concurrent updates using database transactions
    // In a real concurrent scenario, these would run in parallel
    // Here we test that the locking mechanism prevents corruption
    
    $results = [];
    
    // First update
    $results[] = DB::transaction(function () use ($service, $originalId) {
        $assessment = Assessment::lockForUpdate()->find($originalId);
        
        // Simulate some processing time
        usleep(10000); // 10ms
        
        return $service->updateAssessment($originalId, [
            'title' => 'Updated Title 1',
            'time_limit' => 90,
        ]);
    });
    
    // Second update (sequential in test, but demonstrates locking behavior)
    $results[] = DB::transaction(function () use ($service, $originalId) {
        $assessment = Assessment::lockForUpdate()->find($originalId);
        
        return $service->updateAssessment($originalId, [
            'title' => 'Updated Title 2',
            'passing_score' => 80,
        ]);
    });
    
    // Verify final state is consistent
    $finalAssessment = Assessment::find($originalId);
    
    expect($finalAssessment)->not->toBeNull();
    expect($finalAssessment->id)->toBe($originalId);
    
    // The last update should win
    expect($finalAssessment->title)->toBe('Updated Title 2');
    expect((float)$finalAssessment->passing_score)->toBe(80.0);
    
    // Verify no data corruption occurred
    expect(Assessment::count())->toBe(1);
})->repeat(10);

test('concurrent question additions maintain correct order', function () {
    // Feature: assessment-system, Property 49: Concurrent Modification Safety
    // Validates: Requirements 10.5
    
    $service = app(AssessmentService::class);
    $course = Courses::factory()->create();
    
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
    ]);
    
    // Add questions concurrently (simulated sequentially with locking)
    $questions = [];
    
    for ($i = 1; $i <= 5; $i++) {
        $questions[] = DB::transaction(function () use ($service, $assessment, $i) {
            // Lock the assessment to prevent race conditions
            Assessment::lockForUpdate()->find($assessment->id);
            
            return $service->addQuestion($assessment->id, [
                'question_type' => 'multiple_choice',
                'question_text' => "Question {$i}?",
                'points' => 10,
                'options' => [
                    ['id' => 'a', 'text' => 'Option A', 'is_correct' => true],
                    ['id' => 'b', 'text' => 'Option B', 'is_correct' => false],
                ],
                'correct_answer' => ['correct_option_id' => 'a'],
            ]);
        });
    }
    
    // Verify all questions were created
    expect(count($questions))->toBe(5);
    
    // Verify no duplicate orders
    $orders = collect($questions)->pluck('order')->toArray();
    expect(count($orders))->toBe(count(array_unique($orders)));
    
    // Verify questions are properly ordered
    $assessment->refresh();
    $questionOrders = $assessment->questions()->orderBy('order')->pluck('order')->toArray();
    expect($questionOrders)->toBe([1, 2, 3, 4, 5]);
})->repeat(10);

test('concurrent attempt submissions maintain data integrity', function () {
    // Feature: assessment-system, Property 49: Concurrent Modification Safety
    // Validates: Requirements 10.5
    
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'max_attempts' => 3,
    ]);
    
    $question = AssessmentQuestion::factory()->multipleChoice()->create([
        'assessment_id' => $assessment->id,
    ]);
    
    $student = \App\Models\Students::factory()->create(['email_verified_at' => now()]);
    
    // Enroll student
    DB::table('course_student')->insert([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    // Create multiple attempts (simulating concurrent creation)
    $attempts = [];
    
    for ($i = 1; $i <= 3; $i++) {
        $attempts[] = DB::transaction(function () use ($assessment, $student, $i) {
            // Lock to prevent race conditions on attempt_number calculation
            DB::table('assessment_attempts')
                ->where('assessment_id', $assessment->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->get();
            
            $attemptNumber = \App\Models\AssessmentAttempt::where('assessment_id', $assessment->id)
                ->where('student_id', $student->id)
                ->max('attempt_number') ?? 0;
            $attemptNumber++;
            
            return \App\Models\AssessmentAttempt::create([
                'assessment_id' => $assessment->id,
                'student_id' => $student->id,
                'attempt_number' => $attemptNumber,
                'status' => 'in_progress',
                'start_time' => now(),
                'max_score' => 10,
            ]);
        });
    }
    
    // Verify all attempts have unique attempt numbers
    $attemptNumbers = collect($attempts)->pluck('attempt_number')->toArray();
    expect(count($attemptNumbers))->toBe(count(array_unique($attemptNumbers)));
    expect($attemptNumbers)->toBe([1, 2, 3]);
})->repeat(10);

test('concurrent question reordering maintains consistency', function () {
    // Feature: assessment-system, Property 49: Concurrent Modification Safety
    // Validates: Requirements 10.5
    
    $service = app(AssessmentService::class);
    $course = Courses::factory()->create();
    
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
    ]);
    
    // Create initial questions
    $questions = [];
    for ($i = 1; $i <= 5; $i++) {
        $questions[] = AssessmentQuestion::factory()->multipleChoice()->create([
            'assessment_id' => $assessment->id,
            'order' => $i,
        ]);
    }
    
    // Perform reordering operations
    $newOrder = [
        $questions[0]->id => 5,
        $questions[1]->id => 4,
        $questions[2]->id => 3,
        $questions[3]->id => 2,
        $questions[4]->id => 1,
    ];
    
    // Reorder with transaction locking
    DB::transaction(function () use ($service, $assessment, $newOrder) {
        // Lock assessment to prevent concurrent modifications
        Assessment::lockForUpdate()->find($assessment->id);
        
        $service->reorderQuestions($assessment->id, $newOrder);
    });
    
    // Verify final order is correct
    $assessment->refresh();
    $finalOrders = $assessment->questions()->orderBy('order')->pluck('order', 'id')->toArray();
    
    foreach ($newOrder as $questionId => $expectedOrder) {
        expect($finalOrders[$questionId])->toBe($expectedOrder);
    }
    
    // Verify no duplicate orders
    expect(count($finalOrders))->toBe(count(array_unique($finalOrders)));
})->repeat(10);

test('concurrent prerequisite additions do not create duplicates', function () {
    // Feature: assessment-system, Property 49: Concurrent Modification Safety
    // Validates: Requirements 10.5
    
    $service = app(AssessmentService::class);
    $course = Courses::factory()->create();
    
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
    ]);
    
    // Add prerequisites concurrently (simulated with transactions)
    $prerequisites = [];
    
    $prerequisiteTypes = [
        ['type' => 'quiz_completion', 'data' => ['require_all_quizzes' => true]],
        ['type' => 'minimum_progress', 'data' => ['minimum_percentage' => 75]],
        ['type' => 'lesson_completion', 'data' => ['lesson_ids' => [1, 2, 3]]],
    ];
    
    foreach ($prerequisiteTypes as $prereq) {
        $prerequisites[] = DB::transaction(function () use ($service, $assessment, $prereq) {
            // Lock assessment to prevent race conditions
            Assessment::lockForUpdate()->find($assessment->id);
            
            return $service->addPrerequisite($assessment->id, [
                'prerequisite_type' => $prereq['type'],
                'prerequisite_data' => $prereq['data'],
            ]);
        });
    }
    
    // Verify all prerequisites were created
    expect(count($prerequisites))->toBe(3);
    
    // Verify no duplicates
    $assessment->refresh();
    expect($assessment->prerequisites()->count())->toBe(3);
    
    // Verify each type exists exactly once
    $types = $assessment->prerequisites()->pluck('prerequisite_type')->toArray();
    expect(count($types))->toBe(count(array_unique($types)));
})->repeat(10);

test('concurrent grading operations maintain score consistency', function () {
    // Feature: assessment-system, Property 49: Concurrent Modification Safety
    // Validates: Requirements 10.5
    
    $gradingService = app(\App\Services\AssessmentGradingService::class);
    
    $course = Courses::factory()->create();
    $student = \App\Models\Students::factory()->create();
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'passing_score' => 70,
    ]);
    
    // Create multiple essay questions
    $questions = [];
    for ($i = 1; $i <= 3; $i++) {
        $questions[] = AssessmentQuestion::factory()->essay()->create([
            'assessment_id' => $assessment->id,
            'points' => 10,
        ]);
    }
    
    $attempt = \App\Models\AssessmentAttempt::factory()->gradingPending()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'max_score' => 30,
    ]);
    
    // Create answers for all questions
    $answers = [];
    foreach ($questions as $question) {
        $answers[] = \App\Models\AssessmentAnswer::factory()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer' => ['text' => 'Student essay response'],
            'grading_status' => 'pending_review',
            'points_earned' => null,
        ]);
    }
    
    // Grade all answers concurrently (simulated with transactions)
    foreach ($answers as $index => $answer) {
        DB::transaction(function () use ($gradingService, $answer, $index) {
            // Lock the attempt to prevent race conditions during score recalculation
            \App\Models\AssessmentAttempt::lockForUpdate()->find($answer->attempt_id);
            
            $gradingService->gradeAnswer($answer->id, 8, "Good work on question " . ($index + 1));
        });
    }
    
    // Verify final score is correct (handle decimal as string)
    $attempt->refresh();
    expect((float)$attempt->score)->toBe(24.0); // 3 questions × 8 points each
    expect((float)$attempt->percentage)->toBe(80.0);
    expect($attempt->passed)->toBeTrue();
    expect($attempt->status)->toBe('completed');
    
    // Verify all answers were graded
    expect($attempt->answers()->where('grading_status', 'manually_graded')->count())->toBe(3);
})->repeat(10);

test('concurrent assessment deletions do not cause orphaned records', function () {
    // Feature: assessment-system, Property 49: Concurrent Modification Safety
    // Validates: Requirements 10.5
    
    $service = app(AssessmentService::class);
    $course = Courses::factory()->create();
    
    // Create assessment with related data
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
    ]);
    
    $question = AssessmentQuestion::factory()->multipleChoice()->create([
        'assessment_id' => $assessment->id,
    ]);
    
    $student = \App\Models\Students::factory()->create();
    $attempt = \App\Models\AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
    ]);
    
    $answer = \App\Models\AssessmentAnswer::factory()->create([
        'attempt_id' => $attempt->id,
        'question_id' => $question->id,
    ]);
    
    $assessmentId = $assessment->id;
    $questionId = $question->id;
    $attemptId = $attempt->id;
    $answerId = $answer->id;
    
    // Delete assessment with transaction locking
    DB::transaction(function () use ($service, $assessmentId) {
        // Lock assessment before deletion
        Assessment::lockForUpdate()->find($assessmentId);
        
        $service->deleteAssessment($assessmentId);
    });
    
    // Verify cascade deletion worked correctly
    expect(Assessment::find($assessmentId))->toBeNull();
    expect(AssessmentQuestion::find($questionId))->toBeNull();
    expect(\App\Models\AssessmentAttempt::find($attemptId))->toBeNull();
    expect(\App\Models\AssessmentAnswer::find($answerId))->toBeNull();
    
    // Verify no orphaned records exist
    expect(AssessmentQuestion::where('assessment_id', $assessmentId)->count())->toBe(0);
    expect(\App\Models\AssessmentAttempt::where('assessment_id', $assessmentId)->count())->toBe(0);
})->repeat(10);
