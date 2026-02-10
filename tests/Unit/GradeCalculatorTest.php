<?php

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Courses;
use App\Models\Students;
use App\Services\GradeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeCalculator = new GradeCalculator();
});

// Test mapScoreToGrade() with threshold logic

test('mapScoreToGrade returns Excellent for score 90 and above', function () {
    expect($this->gradeCalculator->mapScoreToGrade(90.0))->toBe('Excellent');
    expect($this->gradeCalculator->mapScoreToGrade(95.5))->toBe('Excellent');
    expect($this->gradeCalculator->mapScoreToGrade(100.0))->toBe('Excellent');
});

test('mapScoreToGrade returns Very Good for score 80-89', function () {
    expect($this->gradeCalculator->mapScoreToGrade(80.0))->toBe('Very Good');
    expect($this->gradeCalculator->mapScoreToGrade(85.5))->toBe('Very Good');
    expect($this->gradeCalculator->mapScoreToGrade(89.99))->toBe('Very Good');
});

test('mapScoreToGrade returns Good for score 70-79', function () {
    expect($this->gradeCalculator->mapScoreToGrade(70.0))->toBe('Good');
    expect($this->gradeCalculator->mapScoreToGrade(75.5))->toBe('Good');
    expect($this->gradeCalculator->mapScoreToGrade(79.99))->toBe('Good');
});

test('mapScoreToGrade returns Pass for score 60-69', function () {
    expect($this->gradeCalculator->mapScoreToGrade(60.0))->toBe('Pass');
    expect($this->gradeCalculator->mapScoreToGrade(65.5))->toBe('Pass');
    expect($this->gradeCalculator->mapScoreToGrade(69.99))->toBe('Pass');
});

// Test getAssessmentScores() retrieves completed assessments

test('getAssessmentScores retrieves completed assessment scores', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    // Create completed attempts with different attempt numbers
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'percentage' => 85.5,
    ]);
    
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 2,
        'status' => 'completed',
        'percentage' => 92.0,
    ]);
    
    $scores = $this->gradeCalculator->getAssessmentScores($student->id, $course->id);
    
    expect($scores)->toHaveCount(2);
    expect($scores)->toContain(85.5, 92.0);
});

test('getAssessmentScores only includes completed assessments', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    // Create completed attempt
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'percentage' => 85.5,
    ]);
    
    // Create in-progress attempt (should be excluded)
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 2,
        'status' => 'in_progress',
        'percentage' => null,
    ]);
    
    // Create timed-out attempt (should be excluded)
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 3,
        'status' => 'timed_out',
        'percentage' => 50.0,
    ]);
    
    $scores = $this->gradeCalculator->getAssessmentScores($student->id, $course->id);
    
    expect($scores)->toHaveCount(1);
    expect($scores)->toContain(85.5);
});

test('getAssessmentScores excludes attempts with null percentage', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    // Create completed attempt with percentage
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'percentage' => 85.5,
    ]);
    
    // Create completed attempt without percentage (should be excluded)
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 2,
        'status' => 'completed',
        'percentage' => null,
    ]);
    
    $scores = $this->gradeCalculator->getAssessmentScores($student->id, $course->id);
    
    expect($scores)->toHaveCount(1);
    expect($scores)->toContain(85.5);
});

test('getAssessmentScores returns empty array when no assessments exist', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    
    $scores = $this->gradeCalculator->getAssessmentScores($student->id, $course->id);
    
    expect($scores)->toBeArray();
    expect($scores)->toBeEmpty();
});

test('getAssessmentScores only returns scores for specific student and course', function () {
    $student1 = Students::factory()->create();
    $student2 = Students::factory()->create();
    $course1 = Courses::factory()->create();
    $course2 = Courses::factory()->create();
    $assessment1 = Assessment::factory()->create(['course_id' => $course1->id]);
    $assessment2 = Assessment::factory()->create(['course_id' => $course2->id]);
    
    // Create attempts for student1 in course1
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment1->id,
        'student_id' => $student1->id,
        'status' => 'completed',
        'percentage' => 85.0,
    ]);
    
    // Create attempts for student2 in course1 (should be excluded)
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment1->id,
        'student_id' => $student2->id,
        'status' => 'completed',
        'percentage' => 90.0,
    ]);
    
    // Create attempts for student1 in course2 (should be excluded)
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment2->id,
        'student_id' => $student1->id,
        'status' => 'completed',
        'percentage' => 95.0,
    ]);
    
    $scores = $this->gradeCalculator->getAssessmentScores($student1->id, $course1->id);
    
    expect($scores)->toHaveCount(1);
    expect($scores)->toContain(85.0);
    expect($scores)->not->toContain(90.0, 95.0);
});

// Test calculateGrade() computes average and maps to grade

test('calculateGrade computes average and maps to correct grade', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    // Create attempts with scores that average to 87.5 (Very Good)
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'percentage' => 85.0,
    ]);
    
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 2,
        'status' => 'completed',
        'percentage' => 90.0,
    ]);
    
    $result = $this->gradeCalculator->calculateGrade($student->id, $course->id);
    
    expect($result)->toBeArray();
    expect($result['grade'])->toBe('Very Good');
    expect($result['average_score'])->toBe(87.5);
    expect($result['scores'])->toHaveCount(2);
    expect($result['scores'])->toContain(85.0, 90.0);
});

test('calculateGrade rounds average to 2 decimal places', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    // Create attempts with scores that result in repeating decimal
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'percentage' => 85.33,
    ]);
    
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 2,
        'status' => 'completed',
        'percentage' => 90.67,
    ]);
    
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 3,
        'status' => 'completed',
        'percentage' => 92.00,
    ]);
    
    $result = $this->gradeCalculator->calculateGrade($student->id, $course->id);
    
    // Average should be (85.33 + 90.67 + 92.00) / 3 = 89.333... rounded to 89.33
    expect($result['average_score'])->toBe(89.33);
    expect($result['grade'])->toBe('Very Good');
});

// Test edge case: no assessments returns "Completed"

test('calculateGrade returns Completed when no assessments exist', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    
    $result = $this->gradeCalculator->calculateGrade($student->id, $course->id);
    
    expect($result)->toBeArray();
    expect($result['grade'])->toBe('Completed');
    expect($result['average_score'])->toBeNull();
    expect($result['scores'])->toBeEmpty();
});

// Test edge case: score below 60% returns null (no certificate)

test('calculateGrade returns null grade when average is below 60 percent', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    // Create attempts with scores below 60%
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 1,
        'status' => 'completed',
        'percentage' => 55.0,
    ]);
    
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'attempt_number' => 2,
        'status' => 'completed',
        'percentage' => 58.0,
    ]);
    
    $result = $this->gradeCalculator->calculateGrade($student->id, $course->id);
    
    // Average is 56.5, which is below 60%
    expect($result)->toBeArray();
    expect($result['grade'])->toBeNull();
    expect($result['average_score'])->toBe(56.5);
    expect($result['scores'])->toHaveCount(2);
});

test('calculateGrade returns Pass grade when average is exactly 60 percent', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    // Create attempts that average to exactly 60%
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'status' => 'completed',
        'percentage' => 60.0,
    ]);
    
    $result = $this->gradeCalculator->calculateGrade($student->id, $course->id);
    
    expect($result)->toBeArray();
    expect($result['grade'])->toBe('Pass');
    expect($result['average_score'])->toBe(60.0);
});

test('calculateGrade returns Excellent grade when average is exactly 90 percent', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    // Create attempts that average to exactly 90%
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'status' => 'completed',
        'percentage' => 90.0,
    ]);
    
    $result = $this->gradeCalculator->calculateGrade($student->id, $course->id);
    
    expect($result)->toBeArray();
    expect($result['grade'])->toBe('Excellent');
    expect($result['average_score'])->toBe(90.0);
});

test('calculateGrade handles single assessment score', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'status' => 'completed',
        'percentage' => 75.0,
    ]);
    
    $result = $this->gradeCalculator->calculateGrade($student->id, $course->id);
    
    expect($result)->toBeArray();
    expect($result['grade'])->toBe('Good');
    expect($result['average_score'])->toBe(75.0);
    expect($result['scores'])->toHaveCount(1);
});

test('calculateGrade handles multiple assessments in same course', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    $assessment1 = Assessment::factory()->create(['course_id' => $course->id]);
    $assessment2 = Assessment::factory()->create(['course_id' => $course->id]);
    $assessment3 = Assessment::factory()->create(['course_id' => $course->id]);
    
    // Create attempts for different assessments
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment1->id,
        'student_id' => $student->id,
        'status' => 'completed',
        'percentage' => 80.0,
    ]);
    
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment2->id,
        'student_id' => $student->id,
        'status' => 'completed',
        'percentage' => 90.0,
    ]);
    
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment3->id,
        'student_id' => $student->id,
        'status' => 'completed',
        'percentage' => 85.0,
    ]);
    
    $result = $this->gradeCalculator->calculateGrade($student->id, $course->id);
    
    // Average should be (80 + 90 + 85) / 3 = 85.0
    expect($result)->toBeArray();
    expect($result['grade'])->toBe('Very Good');
    expect($result['average_score'])->toBe(85.0);
    expect($result['scores'])->toHaveCount(3);
});

test('calculateGrade returns correct structure with all required fields', function () {
    $student = Students::factory()->create();
    $course = Courses::factory()->create();
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'status' => 'completed',
        'percentage' => 85.0,
    ]);
    
    $result = $this->gradeCalculator->calculateGrade($student->id, $course->id);
    
    expect($result)->toBeArray();
    expect($result)->toHaveKeys(['grade', 'average_score', 'scores']);
    expect($result['grade'])->toBeString();
    expect($result['average_score'])->toBeFloat();
    expect($result['scores'])->toBeArray();
});
