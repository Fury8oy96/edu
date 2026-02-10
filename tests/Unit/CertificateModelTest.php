<?php

use App\Models\Certificate;
use App\Models\Courses;
use App\Models\Students;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('certificate model has correct fillable attributes', function () {
    $fillable = [
        'certificate_id',
        'student_id',
        'course_id',
        'student_name',
        'student_email',
        'course_title',
        'instructor_name',
        'course_duration',
        'completion_date',
        'grade',
        'average_score',
        'assessment_scores',
        'verification_url',
        'pdf_path',
        'issued_by',
        'issued_by_admin_id',
        'status',
        'revoked_at',
        'revoked_by_admin_id',
        'revocation_reason',
    ];

    $certificate = new Certificate();
    expect($certificate->getFillable())->toBe($fillable);
});

test('certificate model has correct casts', function () {
    $certificate = new Certificate();
    $casts = $certificate->getCasts();

    expect($casts)->toHaveKey('completion_date', 'datetime');
    expect($casts)->toHaveKey('assessment_scores', 'array');
    expect($casts)->toHaveKey('revoked_at', 'datetime');
    expect($casts)->toHaveKey('average_score', 'decimal:2');
});

test('certificate belongs to student', function () {
    $certificate = new Certificate();
    $relation = $certificate->student();

    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($relation->getRelated())->toBeInstanceOf(Students::class);
});

test('certificate belongs to course', function () {
    $certificate = new Certificate();
    $relation = $certificate->course();

    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($relation->getRelated())->toBeInstanceOf(Courses::class);
});

test('certificate belongs to issued by admin', function () {
    $certificate = new Certificate();
    $relation = $certificate->issuedByAdmin();

    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($relation->getRelated())->toBeInstanceOf(User::class);
});

test('certificate belongs to revoked by admin', function () {
    $certificate = new Certificate();
    $relation = $certificate->revokedByAdmin();

    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($relation->getRelated())->toBeInstanceOf(User::class);
});

test('active scope filters only active certificates', function () {
    // Create test data
    $student = Students::factory()->create();
    $course1 = Courses::factory()->create();
    $course2 = Courses::factory()->create();

    Certificate::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course1->id,
        'status' => 'active',
    ]);

    Certificate::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course2->id,
        'certificate_id' => 'CERT-2024-00002',
        'status' => 'revoked',
    ]);

    $activeCertificates = Certificate::active()->get();

    expect($activeCertificates)->toHaveCount(1);
    expect($activeCertificates->first()->status)->toBe('active');
});

test('revoked scope filters only revoked certificates', function () {
    // Create test data
    $student = Students::factory()->create();
    $course1 = Courses::factory()->create();
    $course2 = Courses::factory()->create();

    Certificate::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course1->id,
        'status' => 'active',
    ]);

    Certificate::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course2->id,
        'certificate_id' => 'CERT-2024-00002',
        'status' => 'revoked',
    ]);

    $revokedCertificates = Certificate::revoked()->get();

    expect($revokedCertificates)->toHaveCount(1);
    expect($revokedCertificates->first()->status)->toBe('revoked');
});

test('byGrade scope filters certificates by specific grade', function () {
    // Create test data
    $student = Students::factory()->create();
    $course1 = Courses::factory()->create();
    $course2 = Courses::factory()->create();

    Certificate::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course1->id,
        'grade' => 'Excellent',
    ]);

    Certificate::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course2->id,
        'certificate_id' => 'CERT-2024-00002',
        'grade' => 'Good',
    ]);

    $excellentCertificates = Certificate::byGrade('Excellent')->get();

    expect($excellentCertificates)->toHaveCount(1);
    expect($excellentCertificates->first()->grade)->toBe('Excellent');
});

test('byDateRange scope filters certificates by date range', function () {
    // Create test data
    $student = Students::factory()->create();
    $course1 = Courses::factory()->create();
    $course2 = Courses::factory()->create();
    $course3 = Courses::factory()->create();

    Certificate::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course1->id,
        'completion_date' => '2024-01-15',
    ]);

    Certificate::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course2->id,
        'certificate_id' => 'CERT-2024-00002',
        'completion_date' => '2024-02-20',
    ]);

    Certificate::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course3->id,
        'certificate_id' => 'CERT-2024-00003',
        'completion_date' => '2024-03-10',
    ]);

    // Test with both start and end dates
    $certificates = Certificate::byDateRange('2024-01-01', '2024-02-28')->get();
    expect($certificates)->toHaveCount(2);

    // Test with only start date
    $certificates = Certificate::byDateRange('2024-02-01', null)->get();
    expect($certificates)->toHaveCount(2);

    // Test with only end date
    $certificates = Certificate::byDateRange(null, '2024-02-28')->get();
    expect($certificates)->toHaveCount(2);
});

test('isRevoked accessor returns true for revoked certificates', function () {
    $certificate = new Certificate(['status' => 'revoked']);
    expect($certificate->is_revoked)->toBeTrue();
});

test('isRevoked accessor returns false for active certificates', function () {
    $certificate = new Certificate(['status' => 'active']);
    expect($certificate->is_revoked)->toBeFalse();
});

test('isActive accessor returns true for active certificates', function () {
    $certificate = new Certificate(['status' => 'active']);
    expect($certificate->is_active)->toBeTrue();
});

test('isActive accessor returns false for revoked certificates', function () {
    $certificate = new Certificate(['status' => 'revoked']);
    expect($certificate->is_active)->toBeFalse();
});
