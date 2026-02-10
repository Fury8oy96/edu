<?php

namespace App\Listeners;

use App\Events\CertificateGenerated;
use App\Jobs\GenerateCertificatePdfJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class QueuePdfGeneration implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CertificateGenerated $event): void
    {
        // Dispatch the PDF generation job
        GenerateCertificatePdfJob::dispatch($event->certificate->id);
    }
}
