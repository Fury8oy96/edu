<?php

use App\Models\Certificate;
use App\Models\Students;
use App\Models\Courses;
use App\Models\Instructors;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Services\CertificateService;
use App\Services\GradeCalculator;
use App\Services\CertificateIdGenerator;
use App\Exceptions\CertificateAlreadyExistsException;
use App\Exceptions\CertificateNotFoundException;
use App\Exceptions\InsufficientScoreException;
use App\Exceptions\UnauthorizedCertificateAccessException;
use App\Exceptions\InvalidCertificateDataException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeCalculator = new GradeCalculator();
    $this->idGenerator = new CertificateIdGenerator();
    $this->certificateService = new CertificateService(
        $this->gradeCalculator,
        $this->idGenerator
    );
});

// Test generateCertificate() creates certificate with correct data

test('generateCertificate creates certificate with correct data', function () {
    $instructor = Instructors::factory()->create(['name' => 'John Instructor']);
    $student = Students::factory()->create([
        'name' => 'Jane Student',
        'email' => 'jane@example.com',
    ]);
    $course = Courses::factory()->create([
        'title' => 'Laravel Mastery',
        'instructor_id' => $instructor->id,
        'duration_hours' => 40,
    ]);
    $assessment = Assessment::factory()->create(['course_id' => $course->id]);
    
    // Create assessment attempt with passing score
    AssessmentAttempt::factory()->create([
        'assessment_id' => $assessment->id,
        'student_id' => $student->id,
        'status' => 'completed',
        'percentage' => 85.0,
    ]);
    
    $certificate = $this->certificateService->generateCertificate($student->id, $course->id);
    
    expect($certificate)->toBeInstanceOf(Certificate::class)
        ->and($certificate->student_name)->toBe('Jane Student')
        ->and($certificate->course_title)->toBe('Laravel Mastery')
        ->and($certificate->instructor_name)->toBe('John Instructor')
        ->and($certificate->course_duration)->toBe('40 hours')
        ->and($certificate->grade)->toBe('Very Good')
        ->and($certificate->status)->toBe('active')
        ->and($certificate->certificate_id)->toStartWith('CERT-');
});