<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Services\PdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateCertificatePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $certificateId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PdfGenerator $pdfGenerator): void
    {
        try {
            // Find the certificate
            $certificate = Certificate::findOrFail($this->certificateId);

            Log::info("Generating PDF for certificate", [
                'certificate_id' => $certificate->certificate_id,
                'job_id' => $this->job->getJobId(),
            ]);

            // Generate PDF
            $pdfPath = $pdfGenerator->generatePdf($certificate);

            // Update certificate with PDF path
            $certificate->update(['pdf_path' => $pdfPath]);

            Log::info("PDF generated successfully", [
                'certificate_id' => $certificate->certificate_id,
                'pdf_path' => $pdfPath,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to generate certificate PDF", [
                'certificate_id' => $this->certificateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Certificate PDF generation failed permanently", [
            'certificate_id' => $this->certificateId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
