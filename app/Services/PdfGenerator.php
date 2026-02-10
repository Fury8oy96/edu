<?php

namespace App\Services;

use App\Models\Certificate;
use App\Exceptions\PdfGenerationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfGenerator
{
    /**
     * The storage disk to use for certificate PDFs.
     */
    private const STORAGE_DISK = 'certificates';

    /**
     * Generate PDF for certificate.
     * 
     * Creates a PDF from the certificate data using the certificate template,
     * stores it in Laravel Storage, and returns the storage path.
     * 
     * @param Certificate $certificate The certificate to generate PDF for
     * @return string The storage path to the generated PDF
     * @throws PdfGenerationException If PDF generation or storage fails
     */
    public function generatePdf(Certificate $certificate): string
    {
        try {
            // Generate PDF from certificate template
            $pdf = Pdf::loadView('certificates.certificate', [
                'certificate' => $certificate
            ])->setPaper('a4', 'landscape');

            // Generate filename from certificate ID
            $filename = $this->generateFilename($certificate);

            // Get PDF content
            $pdfContent = $pdf->output();

            // Store PDF in certificates disk
            $stored = Storage::disk(self::STORAGE_DISK)->put($filename, $pdfContent);

            if (!$stored) {
                throw new PdfGenerationException(
                    "Failed to store PDF for certificate {$certificate->certificate_id}"
                );
            }

            // Return the storage path
            return $filename;

        } catch (PdfGenerationException $e) {
            // Re-throw our custom exception
            throw $e;
        } catch (\Exception $e) {
            // Wrap any other exceptions in our custom exception
            throw new PdfGenerationException(
                "PDF generation failed for certificate {$certificate->certificate_id}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Get PDF file path for certificate.
     * 
     * Returns the storage path to the certificate's PDF file if it exists.
     * 
     * @param Certificate $certificate The certificate to get PDF path for
     * @return string|null The storage path or null if no PDF exists
     */
    public function getPdfPath(Certificate $certificate): ?string
    {
        // Return the pdf_path from the certificate record
        return $certificate->pdf_path;
    }

    /**
     * Stream PDF download response.
     * 
     * Creates a streamed response that downloads the certificate PDF.
     * 
     * @param Certificate $certificate The certificate to download PDF for
     * @return StreamedResponse The streamed download response
     * @throws PdfGenerationException If PDF file doesn't exist
     */
    public function downloadPdf(Certificate $certificate): StreamedResponse
    {
        $pdfPath = $this->getPdfPath($certificate);

        if (!$pdfPath) {
            throw new PdfGenerationException(
                "No PDF file exists for certificate {$certificate->certificate_id}"
            );
        }

        // Check if file exists in storage
        if (!Storage::disk(self::STORAGE_DISK)->exists($pdfPath)) {
            throw new PdfGenerationException(
                "PDF file not found in storage for certificate {$certificate->certificate_id}"
            );
        }

        // Generate download filename
        $downloadFilename = $this->generateDownloadFilename($certificate);

        // Stream the file as a download
        return Storage::disk(self::STORAGE_DISK)->download($pdfPath, $downloadFilename);
    }

    /**
     * Generate filename for storing the PDF.
     * 
     * @param Certificate $certificate
     * @return string
     */
    private function generateFilename(Certificate $certificate): string
    {
        return "{$certificate->certificate_id}.pdf";
    }

    /**
     * Generate user-friendly filename for downloading.
     * 
     * @param Certificate $certificate
     * @return string
     */
    private function generateDownloadFilename(Certificate $certificate): string
    {
        // Create a clean filename from student name and course title
        $studentName = preg_replace('/[^A-Za-z0-9\-]/', '_', $certificate->student_name);
        $courseTitle = preg_replace('/[^A-Za-z0-9\-]/', '_', $certificate->course_title);
        
        return "Certificate_{$studentName}_{$courseTitle}.pdf";
    }
}
