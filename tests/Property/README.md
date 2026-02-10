# Property-Based Tests for Assessment System

This directory contains property-based tests that verify universal correctness properties of the Assessment System, as defined in the design document.

## Overview

Property-based tests verify that certain properties hold true across all valid inputs, rather than testing specific examples. These tests use randomization and repetition to catch edge cases that might be missed by traditional unit tests.

## Test Files

### TransactionPropertiesTest.php

**Property 48: Transaction Atomicity**

Tests that verify database transaction atomicity for multi-step operations. If any step in a transaction fails, all changes must be rolled back.

**Test Cases:**
1. **Assessment creation with questions rolls back on failure** - Verifies that when creating an assessment with invalid questions, both the assessment and all questions are rolled back.

2. **Assessment creation with invalid course rolls back completely** - Verifies that attempting to create an assessment with a non-existent course rolls back all changes.

3. **Attempt submission with grading rolls back on time limit failure** - Verifies that when a student submits after the time limit, no answers are saved (transaction rollback).

4. **Attempt submission rolls back on incomplete answers** - Verifies that when a student submits without answering all questions, no answers are saved.

5. **Manual grading with score recalculation rolls back on invalid score** - Verifies that when grading with an invalid score (exceeds max points), neither the answer nor the attempt score is updated.

6. **Manual grading transaction includes score recalculation** - Verifies that manual grading and score recalculation happen in the same transaction.

7. **Database transaction rollback on constraint violation** - Verifies that database constraint violations trigger complete transaction rollback.

**Validates:** Requirements 10.1, 10.2, 10.3, 10.4

### ConcurrencyPropertiesTest.php

**Property 49: Concurrent Modification Safety**

Tests that verify the system handles concurrent modifications correctly using database locking to prevent data corruption.

**Test Cases:**
1. **Concurrent assessment updates do not corrupt data** - Verifies that sequential updates with locking maintain data consistency.

2. **Concurrent question additions maintain correct order** - Verifies that adding multiple questions maintains unique, sequential order values.

3. **Concurrent attempt submissions maintain data integrity** - Verifies that creating multiple attempts maintains unique attempt numbers.

4. **Concurrent question reordering maintains consistency** - Verifies that reordering questions with locking produces consistent results.

5. **Concurrent prerequisite additions do not create duplicates** - Verifies that adding prerequisites with locking prevents duplicates.

6. **Concurrent grading operations maintain score consistency** - Verifies that grading multiple answers with locking produces correct final scores.

7. **Concurrent assessment deletions do not cause orphaned records** - Verifies that deletion with locking properly cascades to all related records.

**Validates:** Requirements 10.5

## Running the Tests

### Run all property tests:
```bash
php artisan test tests/Property/TransactionPropertiesTest.php
php artisan test tests/Property/ConcurrencyPropertiesTest.php
```

### Run with verbose output:
```bash
php artisan test tests/Property/TransactionPropertiesTest.php --verbose
```

## Test Configuration

- **Repetitions:** Each test runs 10 times to catch intermittent issues
- **Database:** Uses RefreshDatabase trait to ensure clean state between tests
- **Isolation:** Each test is isolated with database transactions

## Key Testing Patterns

### Transaction Rollback Testing
```php
$initialCount = Model::count();

try {
    // Attempt operation that should fail
    $service->operation($invalidData);
    expect(false)->toBeTrue('Expected exception');
} catch (Exception $e) {
    // Expected exception
}

// Verify rollback
expect(Model::count())->toBe($initialCount);
```

### Concurrency Testing
```php
DB::transaction(function () use ($service, $id) {
    // Lock record to prevent race conditions
    Model::lockForUpdate()->find($id);
    
    // Perform operation
    $service->operation($id, $data);
});

// Verify consistency
expect($model->field)->toBe($expectedValue);
```

### Decimal Comparison
```php
// Handle Laravel's decimal casting (returns string)
expect((float)$model->score)->toBe(8.0);
```

## Notes

- These tests simulate concurrent operations sequentially with database locking, as true parallel execution is difficult in PHP test environments
- The tests verify that the locking mechanisms prevent race conditions and data corruption
- All tests use factories to generate realistic test data
- Tests handle Laravel's decimal type casting (which returns strings) by casting to float for comparisons

## Coverage

These property tests cover:
- ✅ Assessment creation transactions
- ✅ Attempt submission transactions  
- ✅ Manual grading transactions
- ✅ Concurrent assessment updates
- ✅ Concurrent question management
- ✅ Concurrent attempt creation
- ✅ Concurrent grading operations
- ✅ Cascade deletion integrity

## Future Enhancements

Potential additions for more comprehensive testing:
- True parallel execution using multiple processes
- Load testing with high concurrency
- Deadlock detection and recovery
- Performance benchmarking under concurrent load


### ValidationAndErrorPropertiesTest.php

**Property 55: Field-Specific Validation Errors**

Tests that verify validation errors include field-specific messages identifying which fields are invalid and why.

**Test Cases:**
1. **CreateAssessmentRequest returns field-specific validation errors for missing required fields** - Verifies that missing required fields return specific error messages.
2. **CreateAssessmentRequest returns field-specific validation errors for invalid field values** - Verifies that invalid values return specific error messages.
3. **CreateQuestionRequest returns field-specific validation errors for invalid question type** - Verifies that invalid question_type values return specific error messages.
4. **CreateQuestionRequest returns field-specific validation errors for multiple choice without options** - Verifies that multiple_choice questions without options return specific error messages.
5. **CreateQuestionRequest returns field-specific validation errors for invalid points** - Verifies that zero or negative points return specific error messages.
6. **SubmitAssessmentRequest returns field-specific validation errors for missing answers** - Verifies that missing answers array returns specific error messages.
7. **SubmitAssessmentRequest returns field-specific validation errors for invalid answer structure** - Verifies that invalid answer structure returns specific error messages for each field.
8. **GradeAnswerRequest returns field-specific validation errors for missing points** - Verifies that missing points_earned returns specific error messages.
9. **GradeAnswerRequest returns field-specific validation errors for negative points** - Verifies that negative points_earned returns specific error messages.
10. **Validation errors include multiple field-specific messages when multiple fields are invalid** - Verifies that multiple invalid fields return multiple field-specific error messages.

**Validates:** Requirements 12.1

**Property 56: HTTP Status Code Correctness**

Tests that verify the system returns appropriate HTTP status codes for different error conditions.

**Test Cases:**
1. **NotEnrolledException returns 403 Forbidden status code** - Verifies 403 status code for enrollment errors.
2. **AssessmentNotFoundException returns 404 Not Found status code** - Verifies 404 status code for not found errors.
3. **TimeLimitExceededException returns 422 Unprocessable Entity status code** - Verifies 422 status code for validation errors.
4. **All 403 exceptions return correct status code** - Verifies all authorization exceptions return 403.
5. **All 404 exceptions return correct status code** - Verifies all not found exceptions return 404.
6. **All 422 exceptions return correct status code** - Verifies all validation exceptions return 422.
7. **Base AssessmentException returns 500 Internal Server Error status code** - Verifies 500 status code for server errors.

**Validates:** Requirements 12.2, 12.3, 12.4, 12.5, 12.6, 14.6, 14.7

**Property 51: Error Response Structure**

Tests that verify error responses include error code, message, and relevant context.

**Test Cases:**
1. **Exception handler returns error response with code and message** - Verifies that exceptions include error code and message.
2. **Exception handler returns error response with context for PrerequisitesNotMetException** - Verifies that PrerequisitesNotMetException includes unmet_prerequisites context.
3. **All exceptions include error code and message** - Verifies that all assessment exceptions include error code, message, and status code.

**Validates:** Requirements 11.2

### Validation Testing Pattern
```php
$request = new CreateAssessmentRequest();
$validator = Validator::make($invalidData, $request->rules(), $request->messages());

expect($validator->fails())->toBeTrue();

$errors = $validator->errors();
expect($errors->has('field_name'))->toBeTrue();
expect($errors->first('field_name'))->toContain('expected text');
```

### Exception Testing Pattern
```php
$exception = new NotEnrolledException();

expect($exception->getStatusCode())->toBe(403);
expect($exception->getErrorCode())->toBe('NOT_ENROLLED');
expect($exception->getMessage())->toBe('Not enrolled in course');
```

## Updated Coverage

These property tests now cover:
- ✅ Assessment creation transactions
- ✅ Attempt submission transactions  
- ✅ Manual grading transactions
- ✅ Concurrent assessment updates
- ✅ Concurrent question management
- ✅ Concurrent attempt creation
- ✅ Concurrent grading operations
- ✅ Cascade deletion integrity
- ✅ Field-specific validation errors
- ✅ HTTP status code correctness
- ✅ Error response structure
