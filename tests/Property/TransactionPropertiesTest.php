<?php

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Courses;
use App\Models\Students;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentGradingService;
use App\Services\AssessmentService;
use App\Services\PrerequisiteCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * Property 48: Transaction Atomicity
 * 
 * For any multi-step operation (assessment creation with questions, attempt submission 
 * with grading, manual grading with recalculation), if any step fails, all changes 
 * must be rolled back.
 * 
 * Validates: Requirements 10.1, 10.2, 10.3, 10.4
 */

test('assessment creation with questions rolls back on failure', function () {
    // Feature: assessment-system, Property 48: Transaction Atomicity
    // Validates: Requirements 10.1, 10.4
    
    $service = app(AssessmentService::class);
    $course = Courses::factory()->create();
    
    // Count initial records
    $initialAssessmentCount = Assessment::count();
    $initialQuestionCount = AssessmentQuestion::count();
    
    // Attempt to create assessment with invalid question data
    // This should fail validation and rollback the entire transaction
    try {
        $service->createAssessment([
            'course_id' => $course->id,
            'title' => 'Test Assessment',
            'description' => 'Test Description',
            'time_limit' => 60,
            'passing_score' => 70,
            'max_attempts' => 3,
            'questions' => [
                [
                    'question_type' => 'multiple_choice',
                    'question_text' => 'Valid question?',
                    'points' => 10,
                    'options' => [
                        ['id' => 'a', 'text' => 'Option A'],
                        ['id' => 'b', 'text' => 'Option B'],
                    ],
                    'correct_answer' => ['correct_option_id' => 'a'],
                ],
                [
                    // Invalid: multiple_choice with only 1 option
                    'question_type' => 'multiple_choice',
                    'question_text' => 'Invalid question?',
                    'points' => 10,
                    'options' => [
                        ['id' => 'a', 'text' => 'Only one option'],
                    ],
                    'correct_answer' => ['correct_option_id' => 'a'],
                ],
            ],
        ]);
        
        // Should not reach here
        expect(false)->toBeTrue('Expected ValidationException to be thrown');
    } catch (ValidationException $e) {
        // Expected exception
        expect($e)->toBeInstanceOf(ValidationException::class);
    }
    
    // Verify rollback: no assessment or questions should be created
    expect(Assessment::count())->toBe($initialAssessmentCount);
    expect(AssessmentQuestion::count())->toBe($initialQuestionCount);
})->repeat(10);

test('assessment creation with invalid course rolls back completely', function () {
    // Feature: assessment-system, Property 48: Transaction Atomicity
    // Validates: Requirements 10.1, 10.4
    
    $service = app(AssessmentService::class);
    
    $initialAssessmentCount = Assessment::count();
    $initialQuestionCount = AssessmentQuestion::count();
    
    try {
        $service->createAssessment([
            'course_id' => 99999, // Non-existent course
            'title' => 'Test Assessment',
            'description' => 'Test Description',
            'time_limit' => 60,
            'passing_score' => 70,
            'max_attempts' => 3,
            'questions' => [
                [
                    'question_type' => 'true_false',
                    'question_text' => 'Is this true?',
                    'points' => 5,
                    'correct_answer' => true,
                ],
            ],
        ]);
        
        expect(false)->toBeTrue('Expected ValidationException to be thrown');
    } catch (ValidationException $e) {
        expect($e)->toBeInstanceOf(ValidationException::class);
    }
    
    // Verify complete rollback
    expect(Assessment::count())->toBe($initialAssessmentCount);
    expect(AssessmentQuestion::count())->toBe($initialQuestionCount);
})->repeat(10);

test('attempt submission with grading rolls back on time limit failure', function () {
    // Feature: assessment-system, Property 48: Transaction Atomicity
    // Validates: Requirements 10.2, 10.4
    
    $prerequisiteService = app(PrerequisiteCheckService::class);
    $gradingService = app(AssessmentGradingService::class);
    $service = new AssessmentAttemptService($prerequisiteService, $gradingService);
    
    // Create assessment with questions
    $course = Courses::factory()->create();
    $student = Students::factory()->create(['email_verified_at' => now()]);
    
    // Enroll student
    DB::table('course_student')->insert([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'time_limit' => 60, // 60 minutes
    ]);
    
    $question = AssessmentQuestion::factory()->multipleChoice()->create([
        'assessment_id' => $assessment->id,
    ]);
    
    // Start attempt
    $attempt = $service->startAttempt($assessment->id, $student->id);
    
    // Manually set start_time to past to simulate timeout
    $attempt->update(['start_time' => now()->subMinutes(61)]);
    
    $initialAnswerCount = DB::table('assessment_answers')->count();
    
    // Try to submit after timeout
    try {
        $service->submitAttempt($attempt->id, [
            [
                'question_id' => $question->id,
                'answer' => ['selected_option_id' => 'a'],
            ],
        ]);
        
        expect(false)->toBeTrue('Expected TimeLimitExceededException to be thrown');
    } catch (\App\Exceptions\Assessment\TimeLimitExceededException $e) {
        expect($e)->toBeInstanceOf(\App\Exceptions\Assessment\TimeLimitExceededException::class);
    }
    
    // Verify rollback: no answers should be saved
    expect(DB::table('assessment_answers')->count())->toBe($initialAnswerCount);
    
    // Note: The attempt status update happens within the transaction,
    // so it gets rolled back along with the answers when the exception is thrown.
    // However, the service updates the status before throwing the exception,
    // so in the actual implementation, the status IS updated to timed_out.
    // This test verifies that the transaction rollback works for the answers.
})->repeat(10);

test('attempt submission rolls back on incomplete answers', function () {
    // Feature: assessment-system, Property 48: Transaction Atomicity
    // Validates: Requirements 10.2, 10.4
    
    $prerequisiteService = app(PrerequisiteCheckService::class);
    $gradingService = app(AssessmentGradingService::class);
    $service = new AssessmentAttemptService($prerequisiteService, $gradingService);
    
    $course = Courses::factory()->create();
    $student = Students::factory()->create(['email_verified_at' => now()]);
    
    DB::table('course_student')->insert([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'time_limit' => 60,
    ]);
    
    $question1 = AssessmentQuestion::factory()->multipleChoice()->create([
        'assessment_id' => $assessment->id,
    ]);
    
    $question2 = AssessmentQuestion::factory()->trueFalse()->create([
        'assessment_id' => $assessment->id,
    ]);
    
    $attempt = $service->startAttempt($assessment->id, $student->id);
    
    $initialAnswerCount = DB::table('assessment_answers')->count();
    
    // Try to submit with only one answer (missing question2)
    try {
        $service->submitAttempt($attempt->id, [
            [
                'question_id' => $question1->id,
                'answer' => ['selected_option_id' => 'a'],
            ],
            // Missing question2 answer
        ]);
        
        expect(false)->toBeTrue('Expected ValidationException to be thrown');
    } catch (ValidationException $e) {
        expect($e)->toBeInstanceOf(ValidationException::class);
    }
    
    // Verify rollback: no answers should be saved
    expect(DB::table('assessment_answers')->count())->toBe($initialAnswerCount);
    
    // Verify attempt is still in progress
    $attempt->refresh();
    expect($attempt->status)->toBe('in_progress');
})->repeat(10);

test('manual grading with score recalculation rolls back on invalid score', function () {
    // Feature: assessment-system, Property 48: Transaction Atomicity
    // Validates: Requirements 10.3, 10.4
    
    $service = app(AssessmentGradingService::class);
    
    $course = Courses::factory()->create();
    $student = Students::factory()->create();
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'passing_score' => 70,
    ]);
    
    $question = AssessmentQuestion::factory()->essay()->create([
        'assessment_id' => $assessment->id,
        'points' => 10,
    ]);
    
    $attempt = AssessmentAttempt::factory()->gradingPending()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'max_score' => 10,
    ]);
    
    $answer = \App\Models\AssessmentAnswer::factory()->create([
        'attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'answer' => ['text' => 'Student essay response'],
        'grading_status' => 'pending_review',
        'points_earned' => null,
    ]);
    
    $originalPointsEarned = $answer->points_earned;
    $originalAttemptScore = $attempt->score;
    
    // Try to grade with invalid score (exceeds max points)
    try {
        $service->gradeAnswer($answer->id, 15, 'Good work'); // 15 > 10 max points
        
        expect(false)->toBeTrue('Expected ValidationException to be thrown');
    } catch (ValidationException $e) {
        expect($e)->toBeInstanceOf(ValidationException::class);
    }
    
    // Verify rollback: answer should not be updated
    $answer->refresh();
    expect($answer->points_earned)->toBe($originalPointsEarned);
    expect($answer->grading_status)->toBe('pending_review');
    
    // Verify attempt score was not recalculated
    $attempt->refresh();
    expect($attempt->score)->toBe($originalAttemptScore);
})->repeat(10);

test('manual grading transaction includes score recalculation', function () {
    // Feature: assessment-system, Property 48: Transaction Atomicity
    // Validates: Requirements 10.3
    
    $service = app(AssessmentGradingService::class);
    
    $course = Courses::factory()->create();
    $student = Students::factory()->create();
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'passing_score' => 70,
    ]);
    
    $question = AssessmentQuestion::factory()->essay()->create([
        'assessment_id' => $assessment->id,
        'points' => 10,
    ]);
    
    $attempt = AssessmentAttempt::factory()->gradingPending()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'max_score' => 10,
        'score' => null,
        'percentage' => null,
        'passed' => null,
    ]);
    
    $answer = \App\Models\AssessmentAnswer::factory()->create([
        'attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'answer' => ['text' => 'Student essay response'],
        'grading_status' => 'pending_review',
        'points_earned' => null,
    ]);
    
    // Grade the answer (valid score)
    $gradedAnswer = $service->gradeAnswer($answer->id, 8, 'Good work');
    
    // Verify answer was updated (handle decimal as string)
    expect((float)$gradedAnswer->points_earned)->toBe(8.0);
    expect($gradedAnswer->grading_status)->toBe('manually_graded');
    
    // Verify attempt score was recalculated in same transaction
    $attempt->refresh();
    expect((float)$attempt->score)->toBe(8.0);
    expect((float)$attempt->percentage)->toBe(80.0);
    expect($attempt->passed)->toBeTrue();
    expect($attempt->status)->toBe('completed');
})->repeat(10);

test('database transaction rollback on constraint violation', function () {
    // Feature: assessment-system, Property 48: Transaction Atomicity
    // Validates: Requirements 10.4
    
    $service = app(AssessmentService::class);
    
    $initialAssessmentCount = Assessment::count();
    $initialQuestionCount = AssessmentQuestion::count();
    
    // Create a course
    $course = Courses::factory()->create();
    
    // Try to create assessment with duplicate data that would violate constraints
    try {
        DB::transaction(function () use ($course) {
            // Create assessment
            $assessment = Assessment::create([
                'course_id' => $course->id,
                'title' => 'Test Assessment',
                'description' => 'Test Description',
                'time_limit' => 60,
                'passing_score' => 70,
                'max_attempts' => 3,
            ]);
            
            // Create question
            AssessmentQuestion::create([
                'assessment_id' => $assessment->id,
                'question_type' => 'multiple_choice',
                'question_text' => 'Test question?',
                'points' => 10,
                'order' => 1,
            ]);
            
            // Force a constraint violation by trying to create duplicate attempt
            $student = Students::factory()->create();
            AssessmentAttempt::create([
                'assessment_id' => $assessment->id,
                'student_id' => $student->id,
                'attempt_number' => 1,
                'status' => 'in_progress',
                'start_time' => now(),
                'max_score' => 10,
            ]);
            
            // Try to create duplicate (same assessment, student, attempt_number)
            AssessmentAttempt::create([
                'assessment_id' => $assessment->id,
                'student_id' => $student->id,
                'attempt_number' => 1, // Duplicate!
                'status' => 'in_progress',
                'start_time' => now(),
                'max_score' => 10,
            ]);
        });
        
        expect(false)->toBeTrue('Expected database exception to be thrown');
    } catch (\Exception $e) {
        // Expected exception (constraint violation)
        expect($e)->toBeInstanceOf(\Exception::class);
    }
    
    // Verify rollback: no new records should exist
    expect(Assessment::count())->toBe($initialAssessmentCount);
    expect(AssessmentQuestion::count())->toBe($initialQuestionCount);
})->repeat(10);
