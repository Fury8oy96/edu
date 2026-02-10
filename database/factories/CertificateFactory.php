<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Courses;
use App\Models\Students;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->year();
        $sequence = str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT);
        $certificateId = "CERT-{$year}-{$sequence}";
        
        $studentName = fake()->name();
        $studentEmail = fake()->safeEmail();
        $courseTitle = fake()->sentence(4);
        $instructorName = fake()->name();
        
        return [
            'certificate_id' => $certificateId,
            'student_id' => Students::factory(),
            'course_id' => Courses::factory(),
            'student_name' => $studentName,
            'student_email' => $studentEmail,
            'course_title' => $courseTitle,
            'instructor_name' => $instructorName,
            'course_duration' => fake()->randomElement(['4 weeks', '8 weeks', '12 weeks', '16 weeks']),
            'completion_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'grade' => fake()->randomElement(['Excellent', 'Very Good', 'Good', 'Pass', 'Completed']),
            'average_score' => fake()->optional(0.8)->randomFloat(2, 60, 100), // 80% chance of having a score
            'assessment_scores' => fake()->optional(0.8)->passthrough([
                ['assessment' => 'Quiz 1', 'score' => fake()->randomFloat(2, 60, 100)],
                ['assessment' => 'Final Exam', 'score' => fake()->randomFloat(2, 60, 100)],
            ]),
            'verification_url' => "https://lms.example.com/verify/{$certificateId}",
            'pdf_path' => fake()->optional(0.7)->passthrough("certificates/{$certificateId}.pdf"),
            'issued_by' => 'system',
            'issued_by_admin_id' => null,
            'status' => 'active',
            'revoked_at' => null,
            'revoked_by_admin_id' => null,
            'revocation_reason' => null,
        ];
    }

    /**
     * Indicate that the certificate was manually issued by an admin.
     */
    public function issuedByAdmin(?int $adminId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'issued_by' => 'admin',
            'issued_by_admin_id' => $adminId ?? \App\Models\User::factory(),
        ]);
    }

    /**
     * Indicate that the certificate is revoked.
     */
    public function revoked(?int $adminId = null, ?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'revoked',
            'revoked_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'revoked_by_admin_id' => $adminId ?? \App\Models\User::factory(),
            'revocation_reason' => $reason ?? fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the certificate has a specific grade.
     */
    public function withGrade(string $grade, ?float $averageScore = null): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => $grade,
            'average_score' => $averageScore,
        ]);
    }

    /**
     * Indicate that the certificate has no assessments (Completed grade).
     */
    public function withoutAssessments(): static
    {
        return $this->state(fn (array $attributes) => [
            'grade' => 'Completed',
            'average_score' => null,
            'assessment_scores' => null,
        ]);
    }

    /**
     * Indicate that the certificate has specific assessment scores.
     */
    public function withAssessmentScores(array $scores): static
    {
        $average = count($scores) > 0 ? array_sum(array_column($scores, 'score')) / count($scores) : null;
        
        return $this->state(fn (array $attributes) => [
            'assessment_scores' => $scores,
            'average_score' => $average ? round($average, 2) : null,
        ]);
    }

    /**
     * Indicate that the certificate has no PDF generated yet.
     */
    public function withoutPdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'pdf_path' => null,
        ]);
    }

    /**
     * Indicate that the certificate was completed on a specific date.
     */
    public function completedOn(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_date' => $date,
        ]);
    }
}
