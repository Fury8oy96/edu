<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Students;
use App\Models\Courses;
use App\Exceptions\CertificateAlreadyExistsException;
use App\Exceptions\CertificateNotFoundException;
use App\Exceptions\InsufficientScoreException;
use App\Exceptions\UnauthorizedCertificateAccessException;
use App\Exceptions\InvalidCertificateDataException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CertificateService
{
    public function __construct(
        private GradeCalculator $gradeCalculator,
        private CertificateIdGenerator $idGenerator
    ) {}

    /**
     * Generate certificate for a student who completed a course
     * 
     * @param int $studentId
     * @param int $courseId
     * @param string $issuedBy 'system' or 'admin'
     * @param int|null $adminId Required if issuedBy is 'admin'
     * @return Certificate
     * @throws CertificateAlreadyExistsException
     * @throws InsufficientScoreException
     * @throws InvalidCertificateDataException
     */
    public function generateCertificate(
        int $studentId,
        int $courseId,
        string $issuedBy = 'system',
        ?int $adminId = null
    ): Certificate {
        return DB::transaction(function () use ($studentId, $courseId, $issuedBy, $adminId) {
            // Validate student and course exist
            $student = Students::findOrFail($studentId);
            $course = Courses::with('instructor')->findOrFail($courseId);

            // Validate required data exists (Requirement 9.7)
            if (!$student->name || !$student->email) {
                throw new InvalidCertificateDataException('Student name and email are required');
            }

            if (!$course->title) {
                throw new InvalidCertificateDataException('Course title is required');
            }

            // Check for duplicate certificate (Requirement 1.12)
            $existingCertificate = Certificate::where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->first();

            if ($existingCertificate) {
                throw new CertificateAlreadyExistsException($studentId, $courseId);
            }

            // Calculate grade using GradeCalculator (Requirements 1.3-1.8)
            $gradeData = $this->gradeCalculator->calculateGrade($studentId, $courseId);

            // Check minimum score requirement (Requirement 8.6)
            if ($gradeData['grade'] === null) {
                throw new InsufficientScoreException($gradeData['average_score']);
            }

            // Generate unique certificate ID (Requirement 1.2)
            $certificateId = $this->idGenerator->generate();

            // Denormalize student and course data (Requirements 2.1-2.4)
            $studentName = $student->name;
            $studentEmail = $student->email;
            $courseTitle = $course->title;
            $instructorName = $course->instructor ? $course->instructor->name : 'Unknown';
            $courseDuration = $course->duration_hours ? "{$course->duration_hours} hours" : null;

            // Generate verification URL (Requirement 2.7)
            $verificationUrl = url("/api/verify/{$certificateId}");

            // Create certificate record (Requirements 2.1-2.9)
            $certificate = Certificate::create([
                'certificate_id' => $certificateId,
                'student_id' => $studentId,
                'course_id' => $courseId,
                'student_name' => $studentName,
                'student_email' => $studentEmail,
                'course_title' => $courseTitle,
                'instructor_name' => $instructorName,
                'course_duration' => $courseDuration,
                'completion_date' => now(),
                'grade' => $gradeData['grade'],
                'average_score' => $gradeData['average_score'],
                'assessment_scores' => $gradeData['scores'],
                'verification_url' => $verificationUrl,
                'issued_by' => $issuedBy,
                'issued_by_admin_id' => $adminId,
                'status' => 'active',
            ]);

            // Dispatch CertificateGenerated event (Requirement 1.11)
            event(new \App\Events\CertificateGenerated($certificate));

            Log::info("Certificate generated successfully", [
                'certificate_id' => $certificateId,
                'student_id' => $studentId,
                'course_id' => $courseId,
                'grade' => $gradeData['grade'],
            ]);

            return $certificate;
        });
    }

    /**
     * Get all certificates for a student
     * 
     * @param int $studentId
     * @return Collection
     */
    public function getStudentCertificates(int $studentId): Collection
    {
        // Use eager loading for performance (Requirement 10.1)
        return Certificate::where('student_id', $studentId)
            ->with(['course', 'student'])
            ->orderBy('completion_date', 'desc')
            ->get();
    }

    /**
     * Get certificate by ID for a specific student
     * 
     * @param int $studentId
     * @param string $certificateId
     * @return Certificate
     * @throws CertificateNotFoundException
     * @throws UnauthorizedCertificateAccessException
     */
    public function getStudentCertificate(int $studentId, string $certificateId): Certificate
    {
        $certificate = Certificate::where('certificate_id', $certificateId)
            ->with(['course', 'student'])
            ->first();

        // Check if certificate exists (Requirement 9.5)
        if (!$certificate) {
            throw new CertificateNotFoundException($certificateId);
        }

        // Check authorization (Requirements 3.3, 3.5)
        if ($certificate->student_id !== $studentId) {
            throw new UnauthorizedCertificateAccessException($certificateId);
        }

        return $certificate;
    }

    /**
     * Verify certificate by public certificate ID
     * 
     * @param string $certificateId
     * @return array
     * @throws CertificateNotFoundException
     */
    public function verifyCertificate(string $certificateId): array
    {
        $certificate = Certificate::where('certificate_id', $certificateId)->first();

        // Check if certificate exists (Requirement 4.3)
        if (!$certificate) {
            throw new CertificateNotFoundException($certificateId);
        }

        // Return limited data for public verification (Requirements 4.2, 4.5)
        return [
            'certificate_id' => $certificate->certificate_id,
            'student_name' => $certificate->student_name,
            'course_title' => $certificate->course_title,
            'completion_date' => $certificate->completion_date->format('Y-m-d'),
            'grade' => $certificate->grade,
            'status' => $certificate->status,
            'verified' => true,
        ];
    }

    /**
     * Revoke a certificate
     * 
     * @param string $certificateId
     * @param int $adminId
     * @param string|null $reason
     * @return Certificate
     * @throws CertificateNotFoundException
     */
    public function revokeCertificate(
        string $certificateId,
        int $adminId,
        ?string $reason = null
    ): Certificate {
        return DB::transaction(function () use ($certificateId, $adminId, $reason) {
            $certificate = Certificate::where('certificate_id', $certificateId)
                ->lockForUpdate()
                ->first();

            if (!$certificate) {
                throw new CertificateNotFoundException($certificateId);
            }

            // Update status and revocation metadata (Requirements 2.10, 5.7)
            $certificate->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revoked_by_admin_id' => $adminId,
                'revocation_reason' => $reason,
            ]);

            Log::info("Certificate revoked", [
                'certificate_id' => $certificateId,
                'admin_id' => $adminId,
                'reason' => $reason,
            ]);

            return $certificate->fresh();
        });
    }

    /**
     * Get certificate analytics
     * 
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param bool $includeRevoked
     * @return array
     */
    public function getAnalytics(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        bool $includeRevoked = false
    ): array {
        // Build base query
        $query = Certificate::query();

        // Apply date range filter (Requirements 6.5, 6.6)
        if ($startDate || $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        // Exclude revoked certificates by default (Requirement 6.6)
        if (!$includeRevoked) {
            $query->active();
        }

        // Get all certificates for analytics
        $certificates = $query->get();

        // Calculate total count (Requirement 6.1)
        $totalCount = $certificates->count();

        // Group by course (Requirement 6.2)
        $byCourse = $certificates->groupBy('course_id')->map(function ($group) {
            return [
                'course_id' => $group->first()->course_id,
                'course_title' => $group->first()->course_title,
                'count' => $group->count(),
            ];
        })->values();

        // Group by grade (Requirement 6.3)
        $byGrade = $certificates->groupBy('grade')->map(function ($group, $grade) {
            return [
                'grade' => $grade,
                'count' => $group->count(),
            ];
        })->values();

        // Group by time period - daily (Requirement 6.4)
        $byDay = $certificates->groupBy(function ($cert) {
            return $cert->completion_date->format('Y-m-d');
        })->map(function ($group, $date) {
            return [
                'date' => $date,
                'count' => $group->count(),
            ];
        })->values();

        // Group by time period - weekly
        $byWeek = $certificates->groupBy(function ($cert) {
            return $cert->completion_date->format('Y-W');
        })->map(function ($group, $week) {
            return [
                'week' => $week,
                'count' => $group->count(),
            ];
        })->values();

        // Group by time period - monthly
        $byMonth = $certificates->groupBy(function ($cert) {
            return $cert->completion_date->format('Y-m');
        })->map(function ($group, $month) {
            return [
                'month' => $month,
                'count' => $group->count(),
            ];
        })->values();

        return [
            'total_count' => $totalCount,
            'by_course' => $byCourse,
            'by_grade' => $byGrade,
            'by_day' => $byDay,
            'by_week' => $byWeek,
            'by_month' => $byMonth,
            'date_range' => [
                'start' => $startDate?->format('Y-m-d'),
                'end' => $endDate?->format('Y-m-d'),
            ],
            'includes_revoked' => $includeRevoked,
        ];
    }
}
