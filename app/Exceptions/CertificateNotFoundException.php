<?php

namespace App\Exceptions;

/**
 * Exception thrown when a certificate cannot be found.
 */
class CertificateNotFoundException extends CertificateException
{
    public function __construct(string $certificateId)
    {
        parent::__construct(
            "Certificate not found: {$certificateId}",
            404
        );
    }
}
