<?php

use App\Models\Certificate;
use Illuminate\Support\Facades\View;

describe('Certificate PDF Template', function () {
    it('renders certificate template with all required fields', function () {
        // Create a certificate with all required data
        $certificate = new Certificate([
            'certificate_id' => 'CERT-2024-00123',
            'student_name' => 'John Doe',
            'course_title' => 'Advanced Laravel Development',
            'instructor_name' => 'Jane Smith',
            'completion_date' => now(),
            'grade' => 'Excellent',
            'verification_url' => 'https://example.com/verify/CERT-2024-00123',
        ]);

        // Render the template
        $html = View::make('certificates.certificate', ['certificate' => $certificate])->render();

        // Assert all required fields are present in the rendered HTML
        expect($html)->toContain('John Doe')
            ->and($html)->toContain('Advanced Laravel Development')
            ->and($html)->toContain('Jane Smith')
            ->and($html)->toContain('CERT-2024-00123')
            ->and($html)->toContain('https://example.com/verify/CERT-2024-00123')
            ->and($html)->toContain('Excellent')
            ->and($html)->toContain('Certificate')
            ->and($html)->toContain('of Completion');
    });

    it('renders certificate template without grade section when grade is Completed', function () {
        // Create a certificate with "Completed" grade (no assessments)
        $certificate = new Certificate([
            'certificate_id' => 'CERT-2024-00124',
            'student_name' => 'Jane Smith',
            'course_title' => 'Introduction to Programming',
            'instructor_name' => 'John Instructor',
            'completion_date' => now(),
            'grade' => 'Completed',
            'verification_url' => 'https://example.com/verify/CERT-2024-00124',
        ]);

        // Render the template
        $html = View::make('certificates.certificate', ['certificate' => $certificate])->render();

        // Assert grade section is not displayed for "Completed" grade
        expect($html)->toContain('Jane Smith')
            ->and($html)->toContain('Introduction to Programming')
            ->and($html)->not->toContain('Performance Grade')
            ->and($html)->not->toContain('Completed'); // Grade value should not be shown
    });

    it('formats completion date correctly', function () {
        $completionDate = \Carbon\Carbon::create(2024, 3, 15);
        
        $certificate = new Certificate([
            'certificate_id' => 'CERT-2024-00125',
            'student_name' => 'Test Student',
            'course_title' => 'Test Course',
            'instructor_name' => 'Test Instructor',
            'completion_date' => $completionDate,
            'grade' => 'Good',
            'verification_url' => 'https://example.com/verify/CERT-2024-00125',
        ]);

        $html = View::make('certificates.certificate', ['certificate' => $certificate])->render();

        // Assert date is formatted as "Month Day, Year"
        expect($html)->toContain('March 15, 2024');
    });

    it('includes all styling for print-friendly output', function () {
        $certificate = new Certificate([
            'certificate_id' => 'CERT-2024-00126',
            'student_name' => 'Print Test',
            'course_title' => 'Print Course',
            'instructor_name' => 'Print Instructor',
            'completion_date' => now(),
            'grade' => 'Pass',
            'verification_url' => 'https://example.com/verify/CERT-2024-00126',
        ]);

        $html = View::make('certificates.certificate', ['certificate' => $certificate])->render();

        // Assert print-specific styles are present
        expect($html)->toContain('@media print')
            ->and($html)->toContain('print-color-adjust: exact')
            ->and($html)->toContain('@page');
    });

    it('includes professional layout elements', function () {
        $certificate = new Certificate([
            'certificate_id' => 'CERT-2024-00127',
            'student_name' => 'Layout Test',
            'course_title' => 'Layout Course',
            'instructor_name' => 'Layout Instructor',
            'completion_date' => now(),
            'grade' => 'Very Good',
            'verification_url' => 'https://example.com/verify/CERT-2024-00127',
        ]);

        $html = View::make('certificates.certificate', ['certificate' => $certificate])->render();

        // Assert professional design elements are present
        expect($html)->toContain('certificate-container')
            ->and($html)->toContain('certificate-border')
            ->and($html)->toContain('decorative-element')
            ->and($html)->toContain('signature-section')
            ->and($html)->toContain('certificate-metadata');
    });
});
