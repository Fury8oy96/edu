<?php

use App\Models\Certificate;
use App\Models\Courses;
use App\Models\Students;
use App\Services\PdfGenerator;
use App\Exceptions\PdfGenerationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->pdfGenerator = new PdfGenerator();
    
    // Fake the certificates storage disk
    Storage::fake('certificates');
});

// Test generatePdf() creates PDF from certificate data

test('generatePdf creates PDF and stores it in storage', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-00001',
        'student_name' => 'John Doe',
        'course_title' => 'Laravel Development',
        'instructor_name' => 'Jane Smith',
        'grade' => 'Excellent',
        'completion_date' => now(),
        'verification_url' => 'https://example.com/verify/CERT-2024-00001',
    ]);
    
    $path = $this->pdfGenerator->generatePdf($certificate);
    
    expect($path)->toBe('CERT-2024-00001.pdf');
    Storage::disk('certificates')->assertExists($path);
});

test('generatePdf returns correct filename format', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-12345',
    ]);
    
    $path = $this->pdfGenerator->generatePdf($certificate);
    
    expect($path)->toBe('CERT-2024-12345.pdf');
});

test('generatePdf throws exception when storage fails', function () {
    // Create a certificate
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-00001',
    ]);
    
    // Mock Storage to return false (storage failure)
    Storage::shouldReceive('disk')
        ->with('certificates')
        ->andReturnSelf();
    
    Storage::shouldReceive('put')
        ->andReturn(false);
    
    expect(fn() => $this->pdfGenerator->generatePdf($certificate))
        ->toThrow(PdfGenerationException::class);
});

test('generatePdf wraps PDF library exceptions', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-00001',
    ]);
    
    // Mock Pdf facade to throw an exception
    Pdf::shouldReceive('loadView')
        ->andThrow(new \Exception('PDF library error'));
    
    expect(fn() => $this->pdfGenerator->generatePdf($certificate))
        ->toThrow(PdfGenerationException::class);
});

// Test getPdfPath() retrieves stored PDF path

test('getPdfPath returns pdf_path from certificate', function () {
    $certificate = Certificate::factory()->create([
        'pdf_path' => 'CERT-2024-00001.pdf',
    ]);
    
    $path = $this->pdfGenerator->getPdfPath($certificate);
    
    expect($path)->toBe('CERT-2024-00001.pdf');
});

test('getPdfPath returns null when no PDF exists', function () {
    $certificate = Certificate::factory()->create([
        'pdf_path' => null,
    ]);
    
    $path = $this->pdfGenerator->getPdfPath($certificate);
    
    expect($path)->toBeNull();
});

// Test downloadPdf() streams PDF response

test('downloadPdf throws exception when no PDF path exists', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-00001',
        'pdf_path' => null,
    ]);
    
    expect(fn() => $this->pdfGenerator->downloadPdf($certificate))
        ->toThrow(PdfGenerationException::class, 'No PDF file exists');
});

test('downloadPdf throws exception when PDF file not found in storage', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-00001',
        'pdf_path' => 'CERT-2024-00001.pdf',
    ]);
    
    // Don't create the file in storage
    
    expect(fn() => $this->pdfGenerator->downloadPdf($certificate))
        ->toThrow(PdfGenerationException::class, 'PDF file not found in storage');
});

test('downloadPdf returns streamed response when PDF exists', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-00001',
        'student_name' => 'John Doe',
        'course_title' => 'Laravel Development',
        'pdf_path' => 'CERT-2024-00001.pdf',
    ]);
    
    // Create a fake PDF file in storage
    Storage::disk('certificates')->put('CERT-2024-00001.pdf', 'fake pdf content');
    
    $response = $this->pdfGenerator->downloadPdf($certificate);
    
    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
});

test('downloadPdf generates user-friendly filename', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-00001',
        'student_name' => 'John Doe',
        'course_title' => 'Advanced Laravel Development',
        'pdf_path' => 'CERT-2024-00001.pdf',
    ]);
    
    // Create a fake PDF file in storage
    Storage::disk('certificates')->put('CERT-2024-00001.pdf', 'fake pdf content');
    
    $response = $this->pdfGenerator->downloadPdf($certificate);
    
    // Check the Content-Disposition header contains a user-friendly filename
    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toContain('Certificate_');
    expect($disposition)->toContain('.pdf');
});

// Test error handling for PDF generation

test('generatePdf includes certificate ID in exception message', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-99999',
    ]);
    
    // Mock Storage to fail
    Storage::shouldReceive('disk')
        ->with('certificates')
        ->andReturnSelf();
    
    Storage::shouldReceive('put')
        ->andReturn(false);
    
    try {
        $this->pdfGenerator->generatePdf($certificate);
        expect(false)->toBeTrue(); // Should not reach here
    } catch (PdfGenerationException $e) {
        expect($e->getMessage())->toContain('CERT-2024-99999');
    }
});

// Test PDF generation with different certificate data

test('generatePdf handles certificate with Completed grade', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-00002',
        'grade' => 'Completed',
        'average_score' => null,
    ]);
    
    $path = $this->pdfGenerator->generatePdf($certificate);
    
    expect($path)->toBe('CERT-2024-00002.pdf');
    Storage::disk('certificates')->assertExists($path);
});

test('generatePdf handles certificate with all grade types', function () {
    $grades = ['Excellent', 'Very Good', 'Good', 'Pass', 'Completed'];
    
    foreach ($grades as $index => $grade) {
        $certificateId = sprintf('CERT-2024-%05d', $index + 1);
        
        $certificate = Certificate::factory()->create([
            'certificate_id' => $certificateId,
            'grade' => $grade,
        ]);
        
        $path = $this->pdfGenerator->generatePdf($certificate);
        
        expect($path)->toBe("{$certificateId}.pdf");
        Storage::disk('certificates')->assertExists($path);
    }
});

test('generatePdf handles special characters in student name', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-00003',
        'student_name' => "O'Brien-Smith, José María",
        'course_title' => 'Test Course',
    ]);
    
    $path = $this->pdfGenerator->generatePdf($certificate);
    
    expect($path)->toBe('CERT-2024-00003.pdf');
    Storage::disk('certificates')->assertExists($path);
});

test('generatePdf handles long course titles', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-00004',
        'course_title' => 'Advanced Full-Stack Web Development with Laravel, Vue.js, and Modern DevOps Practices',
    ]);
    
    $path = $this->pdfGenerator->generatePdf($certificate);
    
    expect($path)->toBe('CERT-2024-00004.pdf');
    Storage::disk('certificates')->assertExists($path);
});

// Test that PDF path storage is consistent

test('generatePdf returns same path format for multiple certificates', function () {
    $certificate1 = Certificate::factory()->create(['certificate_id' => 'CERT-2024-00001']);
    $certificate2 = Certificate::factory()->create(['certificate_id' => 'CERT-2024-00002']);
    
    $path1 = $this->pdfGenerator->generatePdf($certificate1);
    $path2 = $this->pdfGenerator->generatePdf($certificate2);
    
    expect($path1)->toMatch('/^CERT-\d{4}-\d{5}\.pdf$/');
    expect($path2)->toMatch('/^CERT-\d{4}-\d{5}\.pdf$/');
});

// Test downloadPdf filename sanitization

test('downloadPdf sanitizes special characters in filename', function () {
    $certificate = Certificate::factory()->create([
        'certificate_id' => 'CERT-2024-00001',
        'student_name' => 'John/Doe<Test>',
        'course_title' => 'Course:Title*With?Special|Chars',
        'pdf_path' => 'CERT-2024-00001.pdf',
    ]);
    
    // Create a fake PDF file in storage
    Storage::disk('certificates')->put('CERT-2024-00001.pdf', 'fake pdf content');
    
    $response = $this->pdfGenerator->downloadPdf($certificate);
    
    // Filename should have special characters replaced with underscores
    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->not->toContain('/');
    expect($disposition)->not->toContain('<');
    expect($disposition)->not->toContain('>');
    expect($disposition)->not->toContain(':');
    expect($disposition)->not->toContain('*');
    expect($disposition)->not->toContain('?');
    expect($disposition)->not->toContain('|');
});
