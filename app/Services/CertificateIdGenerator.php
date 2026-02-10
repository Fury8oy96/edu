<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Facades\DB;

class CertificateIdGenerator
{
    /**
     * Maximum number of attempts to generate a unique certificate ID.
     */
    private const MAX_ATTEMPTS = 10;

    /**
     * Generate a unique certificate ID in the format CERT-YYYY-XXXXX.
     * 
     * This method uses database transactions to ensure uniqueness and
     * implements collision detection with automatic regeneration.
     * 
     * @return string The generated unique certificate ID
     * @throws \RuntimeException If unable to generate unique ID after max attempts
     */
    public function generate(): string
    {
        return DB::transaction(function () {
            $attempts = 0;

            while ($attempts < self::MAX_ATTEMPTS) {
                $certificateId = $this->generateId();

                // Check for collision
                if (!$this->exists($certificateId)) {
                    return $certificateId;
                }

                // Collision detected, increment attempts and try again
                $attempts++;
            }

            // If we've exhausted all attempts, throw an exception
            throw new \RuntimeException(
                'Unable to generate unique certificate ID after ' . self::MAX_ATTEMPTS . ' attempts'
            );
        });
    }

    /**
     * Generate a certificate ID in the format CERT-YYYY-XXXXX.
     * 
     * @return string The generated certificate ID
     */
    private function generateId(): string
    {
        $year = date('Y');
        $sequence = str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        
        return "CERT-{$year}-{$sequence}";
    }

    /**
     * Check if a certificate ID already exists in the database.
     * 
     * @param string $certificateId The certificate ID to check
     * @return bool True if the ID exists, false otherwise
     */
    private function exists(string $certificateId): bool
    {
        return Certificate::where('certificate_id', $certificateId)->exists();
    }
}
