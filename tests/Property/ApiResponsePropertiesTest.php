<?php

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\Students;
use App\Models\Courses;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Property 52: Pagination Metadata
 * 
 * For any paginated API response, the response must include current_page, 
 * total_pages, per_page, and total.
 * 
 * Validates: Requirements 11.3
 */

test('paginated assessment history includes all required pagination metadata', function () {
    // Feature: assessment-system, Property 52: Pagination Metadata
    // Validates: Requirements 11.3
    
    // Create a student
    $student = Student::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a course and enroll the student
    $course = Courses::factory()->create();
    Enrollment::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
    ]);
    
    // Create an assessment
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'is_active' => true,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);
    
    // Create questions for the assessment
    AssessmentQuestion::factory()->count(3)->create([
        'assessment_id' => $assessment->id,
        'question_type' => 'multiple_choice',
    ]);
    
    // Create multiple attempts (more than one page worth)
    $totalAttempts = 25;
    AssessmentAttempt::factory()->count($totalAttempts)->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'status' => 'completed',
    ]);
    
    // Test with different per_page values
    $perPageValues = [5, 10, 15, 20];
    
    foreach ($perPageValues as $perPage) {
        // Make API request with pagination
        $response = $this->actingAs($student, 'sanctum')
            ->getJson("/api/v1/assessments/{$assessment->id}/history?per_page={$perPage}");
        
        // Assert response is successful
        $response->assertStatus(200);
        
        // Assert pagination metadata exists
        $response->assertJsonStructure([
            'data',
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ],
        ]);
        
        // Assert pagination metadata values are correct
        $meta = $response->json('meta');
        
        expect($meta['current_page'])->toBe(1)
            ->and($meta['per_page'])->toBe($perPage)
            ->and($meta['total'])->toBe($totalAttempts)
            ->and($meta['last_page'])->toBe((int) ceil($totalAttempts / $perPage));
        
        // Assert data array has correct number of items (should be perPage or remaining items)
        $expectedItems = min($perPage, $totalAttempts);
        expect($response->json('data'))->toHaveCount($expectedItems);
    }
});

test('paginated assessment history respects page parameter', function () {
    // Feature: assessment-system, Property 52: Pagination Metadata
    // Validates: Requirements 11.3
    
    // Create a student
    $student = Student::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a course and enroll the student
    $course = Courses::factory()->create();
    Enrollment::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
    ]);
    
    // Create an assessment
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'is_active' => true,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);
    
    // Create questions for the assessment
    AssessmentQuestion::factory()->count(3)->create([
        'assessment_id' => $assessment->id,
        'question_type' => 'multiple_choice',
    ]);
    
    // Create multiple attempts
    $totalAttempts = 30;
    AssessmentAttempt::factory()->count($totalAttempts)->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'status' => 'completed',
    ]);
    
    $perPage = 10;
    $totalPages = (int) ceil($totalAttempts / $perPage);
    
    // Test each page
    for ($page = 1; $page <= $totalPages; $page++) {
        $response = $this->actingAs($student, 'sanctum')
            ->getJson("/api/v1/assessments/{$assessment->id}/history?per_page={$perPage}&page={$page}");
        
        $response->assertStatus(200);
        
        // Assert current_page matches requested page
        expect($response->json('meta.current_page'))->toBe($page);
        
        // Assert total and per_page remain consistent across pages
        expect($response->json('meta.total'))->toBe($totalAttempts)
            ->and($response->json('meta.per_page'))->toBe($perPage)
            ->and($response->json('meta.last_page'))->toBe($totalPages);
        
        // Assert correct number of items on each page
        if ($page < $totalPages) {
            // Full page
            expect($response->json('data'))->toHaveCount($perPage);
        } else {
            // Last page may have fewer items
            $remainingItems = $totalAttempts - (($page - 1) * $perPage);
            expect($response->json('data'))->toHaveCount($remainingItems);
        }
    }
});

test('paginated assessment history includes correct navigation links', function () {
    // Feature: assessment-system, Property 52: Pagination Metadata
    // Validates: Requirements 11.3
    
    // Create a student
    $student = Student::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a course and enroll the student
    $course = Courses::factory()->create();
    Enrollment::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
    ]);
    
    // Create an assessment
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'is_active' => true,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);
    
    // Create questions for the assessment
    AssessmentQuestion::factory()->count(3)->create([
        'assessment_id' => $assessment->id,
        'question_type' => 'multiple_choice',
    ]);
    
    // Create multiple attempts
    AssessmentAttempt::factory()->count(25)->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'status' => 'completed',
    ]);
    
    // Test first page - should have next link but no prev link
    $response = $this->actingAs($student, 'sanctum')
        ->getJson("/api/v1/assessments/{$assessment->id}/history?per_page=10&page=1");
    
    $response->assertStatus(200);
    expect($response->json('links.next'))->not->toBeNull()
        ->and($response->json('links.prev'))->toBeNull();
    
    // Test middle page - should have both next and prev links
    $response = $this->actingAs($student, 'sanctum')
        ->getJson("/api/v1/assessments/{$assessment->id}/history?per_page=10&page=2");
    
    $response->assertStatus(200);
    expect($response->json('links.next'))->not->toBeNull()
        ->and($response->json('links.prev'))->not->toBeNull();
    
    // Test last page - should have prev link but no next link
    $response = $this->actingAs($student, 'sanctum')
        ->getJson("/api/v1/assessments/{$assessment->id}/history?per_page=10&page=3");
    
    $response->assertStatus(200);
    expect($response->json('links.next'))->toBeNull()
        ->and($response->json('links.prev'))->not->toBeNull();
});

test('paginated assessment history with single page has correct metadata', function () {
    // Feature: assessment-system, Property 52: Pagination Metadata
    // Validates: Requirements 11.3
    
    // Create a student
    $student = Student::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a course and enroll the student
    $course = Courses::factory()->create();
    Enrollment::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
    ]);
    
    // Create an assessment
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'is_active' => true,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);
    
    // Create questions for the assessment
    AssessmentQuestion::factory()->count(3)->create([
        'assessment_id' => $assessment->id,
        'question_type' => 'multiple_choice',
    ]);
    
    // Create only a few attempts (less than one page)
    $totalAttempts = 5;
    AssessmentAttempt::factory()->count($totalAttempts)->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'status' => 'completed',
    ]);
    
    // Request with per_page larger than total items
    $response = $this->actingAs($student, 'sanctum')
        ->getJson("/api/v1/assessments/{$assessment->id}/history?per_page=15");
    
    $response->assertStatus(200);
    
    // Assert pagination metadata for single page
    expect($response->json('meta.current_page'))->toBe(1)
        ->and($response->json('meta.last_page'))->toBe(1)
        ->and($response->json('meta.total'))->toBe($totalAttempts)
        ->and($response->json('meta.per_page'))->toBe(15);
    
    // Assert all items are returned
    expect($response->json('data'))->toHaveCount($totalAttempts);
    
    // Assert no next/prev links for single page
    expect($response->json('links.next'))->toBeNull()
        ->and($response->json('links.prev'))->toBeNull();
});

test('paginated assessment history respects maximum per_page limit', function () {
    // Feature: assessment-system, Property 52: Pagination Metadata
    // Validates: Requirements 11.3
    
    // Create a student
    $student = Student::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a course and enroll the student
    $course = Courses::factory()->create();
    Enrollment::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
    ]);
    
    // Create an assessment
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'is_active' => true,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);
    
    // Create questions for the assessment
    AssessmentQuestion::factory()->count(3)->create([
        'assessment_id' => $assessment->id,
        'question_type' => 'multiple_choice',
    ]);
    
    // Create many attempts
    AssessmentAttempt::factory()->count(150)->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'status' => 'completed',
    ]);
    
    // Request with per_page > 100 (should be capped at 100)
    $response = $this->actingAs($student, 'sanctum')
        ->getJson("/api/v1/assessments/{$assessment->id}/history?per_page=200");
    
    $response->assertStatus(200);
    
    // Assert per_page is capped at 100
    expect($response->json('meta.per_page'))->toBe(100);
    
    // Assert data array has at most 100 items
    expect($response->json('data'))->toHaveCount(100);
});

test('paginated assessment history with empty results has correct metadata', function () {
    // Feature: assessment-system, Property 52: Pagination Metadata
    // Validates: Requirements 11.3
    
    // Create a student
    $student = Student::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a course and enroll the student
    $course = Courses::factory()->create();
    Enrollment::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
    ]);
    
    // Create an assessment
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'is_active' => true,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);
    
    // Create questions for the assessment
    AssessmentQuestion::factory()->count(3)->create([
        'assessment_id' => $assessment->id,
        'question_type' => 'multiple_choice',
    ]);
    
    // Don't create any attempts - empty result set
    
    // Request paginated history
    $response = $this->actingAs($student, 'sanctum')
        ->getJson("/api/v1/assessments/{$assessment->id}/history?per_page=15");
    
    $response->assertStatus(200);
    
    // Assert pagination metadata for empty results
    expect($response->json('meta.current_page'))->toBe(1)
        ->and($response->json('meta.last_page'))->toBe(1)
        ->and($response->json('meta.total'))->toBe(0)
        ->and($response->json('meta.per_page'))->toBe(15);
    
    // Assert empty data array
    expect($response->json('data'))->toBeArray()
        ->and($response->json('data'))->toHaveCount(0);
});
