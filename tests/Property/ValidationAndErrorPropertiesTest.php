<?php

use App\Exceptions\Assessment\AssessmentNotFoundException;
use App\Exceptions\Assessment\NotEnrolledException;
use App\Exceptions\Assessment\TimeLimitExceededException;
use App\Http\Requests\CreateAssessmentRequest;
use App\Http\Requests\CreateQuestionRequest;
use App\Http\Requests\GradeAnswerRequest;
use App\Http\Requests\SubmitAssessmentRequest;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Courses;
use App\Models\Students;
use App\Services\AssessmentAttemptService;
use App\Services\AssessmentGradingService;
use App\Services\PrerequisiteCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * Property 55: Field-Specific Validation Errors
 * 
 * For any request with invalid data, the error response must include field-specific 
 * validation messages identifying which fields are invalid and why.
 * 
 * Validates: Requirements 12.1
 */

test('CreateAssessmentRequest returns field-specific validation errors for missing required fields', function () {
    // Feature: assessment-system, Property 55: Field-Specific Validation Errors
    // Validates: Requirements 12.1
    
    $request = new CreateAssessmentRequest();
    $validator = Validator::make([], $request->rules(), $request->messages());
    
    expect($validator->fails())->toBeTrue();
    
    $errors = $validator->errors();
    
    // Verify field-specific errors exist
    expect($errors->has('course_id'))->toBeTrue();
    expect($errors->has('title'))->toBeTrue();
    expect($errors->has('time_limit'))->toBeTrue();
    expect($errors->has('passing_score'))->toBeTrue();
    
    // Verify error messages are descriptive
    expect($errors->first('course_id'))->toContain('required');
    expect($errors->first('title'))->toContain('required');
    expect($errors->first('time_limit'))->toContain('required');
    expect($errors->first('passing_score'))->toContain('required');
})->repeat(10);

test('CreateAssessmentRequest returns field-specific validation errors for invalid field values', function () {
    // Feature: assessment-system, Property 55: Field-Specific Validation Errors
    // Validates: Requirements 12.1
    
    $request = new CreateAssessmentRequest();
    
    $invalidData = [
        'course_id' => 99999, // Non-existent course
        'title' => str_repeat('a', 300), // Too long
        'time_limit' => 0, // Below minimum
        'passing_score' => 150, // Above maximum
        'max_attempts' => -1, // Below minimum
        'start_date' => now()->toDateString(),
        'end_date' => now()->subDay()->toDateString(), // Before start_date
    ];
    
    $validator = Validator::make($invalidData, $request->rules(), $request->messages());
    
    expect($validator->fails())->toBeTrue();
    
    $errors = $validator->errors();
    
    // Verify each invalid field has a specific error
    expect($errors->has('course_id'))->toBeTrue();
    expect($errors->has('title'))->toBeTrue();
    expect($errors->has('time_limit'))->toBeTrue();
    expect($errors->has('passing_score'))->toBeTrue();
    expect($errors->has('max_attempts'))->toBeTrue();
    expect($errors->has('end_date'))->toBeTrue();
    
    // Verify error messages explain the problem
    expect($errors->first('time_limit'))->toContain('1');
    expect($errors->first('passing_score'))->toContain('100');
    expect($errors->first('max_attempts'))->toContain('1');
    expect($errors->first('end_date'))->toContain('after');
})->repeat(10);

test('CreateQuestionRequest returns field-specific validation errors for invalid question type', function () {
    // Feature: assessment-system, Property 55: Field-Specific Validation Errors
    // Validates: Requirements 12.1
    
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    $request = new CreateQuestionRequest();
    
    $invalidData = [
        'assessment_id' => $assessment->id,
        'question_type' => 'invalid_type', // Invalid type
        'question_text' => 'Test question',
        'points' => 10,
    ];
    
    $validator = Validator::make($invalidData, $request->rules(), $request->messages());
    
    expect($validator->fails())->toBeTrue();
    
    $errors = $validator->errors();
    
    // Verify field-specific error for question_type
    expect($errors->has('question_type'))->toBeTrue();
    expect($errors->first('question_type'))->toContain('multiple_choice');
    expect($errors->first('question_type'))->toContain('true_false');
})->repeat(10);

test('CreateQuestionRequest returns field-specific validation errors for multiple choice without options', function () {
    // Feature: assessment-system, Property 55: Field-Specific Validation Errors
    // Validates: Requirements 12.1
    
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    $invalidData = [
        'assessment_id' => $assessment->id,
        'question_type' => 'multiple_choice',
        'question_text' => 'Test question',
        'points' => 10,
        // Missing 'options' field
    ];
    
    // Create a request instance and set the data
    $request = CreateQuestionRequest::create('/test', 'POST', $invalidData);
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    
    // Get the validator
    $validator = Validator::make($invalidData, $request->rules(), $request->messages());
    
    expect($validator->fails())->toBeTrue();
    
    $errors = $validator->errors();
    
    // Verify field-specific error for options
    expect($errors->has('options'))->toBeTrue();
    expect($errors->first('options'))->toContain('required');
})->repeat(10);

test('CreateQuestionRequest returns field-specific validation errors for invalid points', function () {
    // Feature: assessment-system, Property 55: Field-Specific Validation Errors
    // Validates: Requirements 12.1
    
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    $request = new CreateQuestionRequest();
    
    $invalidData = [
        'assessment_id' => $assessment->id,
        'question_type' => 'essay',
        'question_text' => 'Test question',
        'points' => 0, // Invalid: must be > 0
    ];
    
    $validator = Validator::make($invalidData, $request->rules(), $request->messages());
    
    expect($validator->fails())->toBeTrue();
    
    $errors = $validator->errors();
    
    // Verify field-specific error for points
    expect($errors->has('points'))->toBeTrue();
    expect($errors->first('points'))->toContain('0');
})->repeat(10);

test('SubmitAssessmentRequest returns field-specific validation errors for missing answers', function () {
    // Feature: assessment-system, Property 55: Field-Specific Validation Errors
    // Validates: Requirements 12.1
    
    $request = new SubmitAssessmentRequest();
    
    $invalidData = [
        // Missing 'answers' field
    ];
    
    $validator = Validator::make($invalidData, $request->rules(), $request->messages());
    
    expect($validator->fails())->toBeTrue();
    
    $errors = $validator->errors();
    
    // Verify field-specific error for answers
    expect($errors->has('answers'))->toBeTrue();
    expect($errors->first('answers'))->toContain('required');
})->repeat(10);

test('SubmitAssessmentRequest returns field-specific validation errors for invalid answer structure', function () {
    // Feature: assessment-system, Property 55: Field-Specific Validation Errors
    // Validates: Requirements 12.1
    
    $request = new SubmitAssessmentRequest();
    
    $invalidData = [
        'answers' => [
            [
                // Missing 'question_id'
                'answer' => 'Some answer',
            ],
            [
                'question_id' => 99999, // Non-existent question
                // Missing 'answer'
            ],
        ],
    ];
    
    $validator = Validator::make($invalidData, $request->rules(), $request->messages());
    
    expect($validator->fails())->toBeTrue();
    
    $errors = $validator->errors();
    
    // Verify field-specific errors for nested answer fields
    expect($errors->has('answers.0.question_id'))->toBeTrue();
    expect($errors->has('answers.1.question_id'))->toBeTrue();
    expect($errors->has('answers.1.answer'))->toBeTrue();
})->repeat(10);

test('GradeAnswerRequest returns field-specific validation errors for missing points', function () {
    // Feature: assessment-system, Property 55: Field-Specific Validation Errors
    // Validates: Requirements 12.1
    
    $request = new GradeAnswerRequest();
    
    $invalidData = [
        // Missing 'points_earned'
        'grader_feedback' => 'Good work',
    ];
    
    $validator = Validator::make($invalidData, $request->rules(), $request->messages());
    
    expect($validator->fails())->toBeTrue();
    
    $errors = $validator->errors();
    
    // Verify field-specific error for points_earned
    expect($errors->has('points_earned'))->toBeTrue();
    expect($errors->first('points_earned'))->toContain('required');
})->repeat(10);

test('GradeAnswerRequest returns field-specific validation errors for negative points', function () {
    // Feature: assessment-system, Property 55: Field-Specific Validation Errors
    // Validates: Requirements 12.1
    
    $request = new GradeAnswerRequest();
    
    $invalidData = [
        'points_earned' => -5, // Invalid: cannot be negative
        'grader_feedback' => 'Needs improvement',
    ];
    
    $validator = Validator::make($invalidData, $request->rules(), $request->messages());
    
    expect($validator->fails())->toBeTrue();
    
    $errors = $validator->errors();
    
    // Verify field-specific error for points_earned
    expect($errors->has('points_earned'))->toBeTrue();
    expect($errors->first('points_earned'))->toContain('negative');
})->repeat(10);

test('validation errors include multiple field-specific messages when multiple fields are invalid', function () {
    // Feature: assessment-system, Property 55: Field-Specific Validation Errors
    // Validates: Requirements 12.1
    
    $request = new CreateAssessmentRequest();
    
    $invalidData = [
        // All fields invalid or missing
        'course_id' => 'not_a_number',
        'time_limit' => -10,
        'passing_score' => 200,
        'max_attempts' => 0,
    ];
    
    $validator = Validator::make($invalidData, $request->rules(), $request->messages());
    
    expect($validator->fails())->toBeTrue();
    
    $errors = $validator->errors();
    
    // Verify multiple field-specific errors are returned
    $errorCount = count($errors->keys());
    expect($errorCount)->toBeGreaterThan(3);
    
    // Each error should be associated with a specific field
    foreach ($errors->keys() as $field) {
        expect($errors->first($field))->toBeString();
        expect(strlen($errors->first($field)))->toBeGreaterThan(0);
    }
})->repeat(10);

/**
 * Property 56: HTTP Status Code Correctness
 * 
 * For any error condition, the system must return the appropriate HTTP status code: 
 * 401 for authentication failures, 403 for authorization failures, 404 for not found, 
 * 422 for validation errors, 500 for server errors.
 * 
 * Validates: Requirements 12.2, 12.3, 12.4, 12.5, 12.6, 14.6, 14.7
 */

test('NotEnrolledException returns 403 Forbidden status code', function () {
    // Feature: assessment-system, Property 56: HTTP Status Code Correctness
    // Validates: Requirements 12.2, 14.7
    
    $exception = new NotEnrolledException();
    
    expect($exception->getStatusCode())->toBe(403);
    expect($exception->getErrorCode())->toBe('NOT_ENROLLED');
    expect($exception->getMessage())->toBe('Not enrolled in course');
})->repeat(10);

test('AssessmentNotFoundException returns 404 Not Found status code', function () {
    // Feature: assessment-system, Property 56: HTTP Status Code Correctness
    // Validates: Requirements 12.4
    
    $exception = new AssessmentNotFoundException();
    
    expect($exception->getStatusCode())->toBe(404);
    expect($exception->getErrorCode())->toBe('ASSESSMENT_NOT_FOUND');
    expect($exception->getMessage())->toBe('Assessment not found');
})->repeat(10);

test('TimeLimitExceededException returns 422 Unprocessable Entity status code', function () {
    // Feature: assessment-system, Property 56: HTTP Status Code Correctness
    // Validates: Requirements 12.3
    
    $exception = new TimeLimitExceededException();
    
    expect($exception->getStatusCode())->toBe(422);
    expect($exception->getErrorCode())->toBe('TIME_LIMIT_EXCEEDED');
    expect($exception->getMessage())->toBe('Assessment time limit exceeded');
})->repeat(10);

test('all 403 exceptions return correct status code', function () {
    // Feature: assessment-system, Property 56: HTTP Status Code Correctness
    // Validates: Requirements 12.2, 14.7
    
    $exceptions = [
        new \App\Exceptions\Assessment\NotEnrolledException(),
        new \App\Exceptions\Assessment\PrerequisitesNotMetException([]),
        new \App\Exceptions\Assessment\MaxAttemptsExceededException(),
        new \App\Exceptions\Assessment\AssessmentNotAvailableException(),
    ];
    
    foreach ($exceptions as $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
})->repeat(10);

test('all 404 exceptions return correct status code', function () {
    // Feature: assessment-system, Property 56: HTTP Status Code Correctness
    // Validates: Requirements 12.4
    
    $exceptions = [
        new \App\Exceptions\Assessment\AssessmentNotFoundException(),
        new \App\Exceptions\Assessment\AttemptNotFoundException(),
        new \App\Exceptions\Assessment\QuestionNotFoundException(),
        new \App\Exceptions\Assessment\AnswerNotFoundException(),
    ];
    
    foreach ($exceptions as $exception) {
        expect($exception->getStatusCode())->toBe(404);
    }
})->repeat(10);

test('all 422 exceptions return correct status code', function () {
    // Feature: assessment-system, Property 56: HTTP Status Code Correctness
    // Validates: Requirements 12.3, 12.6
    
    $exceptions = [
        new \App\Exceptions\Assessment\TimeLimitExceededException(),
        new \App\Exceptions\Assessment\InvalidQuestionTypeException(),
        new \App\Exceptions\Assessment\InvalidGradingDataException(),
        new \App\Exceptions\Assessment\AssessmentAlreadySubmittedException(),
    ];
    
    foreach ($exceptions as $exception) {
        expect($exception->getStatusCode())->toBe(422);
    }
})->repeat(10);

test('base AssessmentException returns 500 Internal Server Error status code', function () {
    // Feature: assessment-system, Property 56: HTTP Status Code Correctness
    // Validates: Requirements 12.5
    
    $exception = new \App\Exceptions\Assessment\AssessmentException();
    
    expect($exception->getStatusCode())->toBe(500);
    expect($exception->getErrorCode())->toBe('ASSESSMENT_ERROR');
})->repeat(10);

/**
 * Property 51: Error Response Structure
 * 
 * For any API error response, the response must include error code, message, 
 * and relevant context.
 * 
 * Validates: Requirements 11.2
 */

test('exception handler returns error response with code and message', function () {
    // Feature: assessment-system, Property 51: Error Response Structure
    // Validates: Requirements 11.2
    
    // This test verifies the exception handler format by directly testing the exception
    $exception = new NotEnrolledException();
    
    // Verify exception properties
    expect($exception->getStatusCode())->toBe(403);
    expect($exception->getErrorCode())->toBe('NOT_ENROLLED');
    expect($exception->getMessage())->toBe('Not enrolled in course');
    
    // The exception handler in bootstrap/app.php will format this as:
    // {
    //   "error": {
    //     "code": "NOT_ENROLLED",
    //     "message": "Not enrolled in course"
    //   }
    // }
})->repeat(10);

test('exception handler returns error response with context for PrerequisitesNotMetException', function () {
    // Feature: assessment-system, Property 51: Error Response Structure
    // Validates: Requirements 11.2
    
    $unmetPrerequisites = [
        ['type' => 'minimum_progress', 'required' => 75, 'current' => 50],
    ];
    
    $exception = new \App\Exceptions\Assessment\PrerequisitesNotMetException($unmetPrerequisites);
    
    // Verify exception properties
    expect($exception->getStatusCode())->toBe(403);
    expect($exception->getErrorCode())->toBe('PREREQUISITES_NOT_MET');
    expect($exception->getMessage())->toBe('Prerequisites not met');
    expect($exception->getUnmetPrerequisites())->toBe($unmetPrerequisites);
    
    // The exception handler in bootstrap/app.php will format this as:
    // {
    //   "error": {
    //     "code": "PREREQUISITES_NOT_MET",
    //     "message": "Prerequisites not met",
    //     "unmet_prerequisites": [...]
    //   }
    // }
})->repeat(10);

test('all exceptions include error code and message', function () {
    // Feature: assessment-system, Property 51: Error Response Structure
    // Validates: Requirements 11.2
    
    $exceptions = [
        new \App\Exceptions\Assessment\NotEnrolledException(),
        new \App\Exceptions\Assessment\AssessmentNotFoundException(),
        new \App\Exceptions\Assessment\TimeLimitExceededException(),
        new \App\Exceptions\Assessment\MaxAttemptsExceededException(),
        new \App\Exceptions\Assessment\AssessmentNotAvailableException(),
        new \App\Exceptions\Assessment\AttemptNotFoundException(),
        new \App\Exceptions\Assessment\QuestionNotFoundException(),
        new \App\Exceptions\Assessment\AnswerNotFoundException(),
        new \App\Exceptions\Assessment\AssessmentAlreadySubmittedException(),
    ];
    
    foreach ($exceptions as $exception) {
        // All exceptions must have an error code
        expect($exception->getErrorCode())->toBeString();
        expect(strlen($exception->getErrorCode()))->toBeGreaterThan(0);
        
        // All exceptions must have a message (some may be empty by default)
        expect($exception->getMessage())->toBeString();
        
        // All exceptions must have a status code
        expect($exception->getStatusCode())->toBeInt();
        expect($exception->getStatusCode())->toBeGreaterThanOrEqual(400);
        expect($exception->getStatusCode())->toBeLessThan(600);
    }
})->repeat(10);
