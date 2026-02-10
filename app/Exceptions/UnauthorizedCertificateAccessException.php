<?php

namespace App\Exceptions;

/**
 * Exception thrown when a user attempts to access a certificate
 * that does not belong to them.
 */
class UnauthorizedCertificateAccessException extends CertificateException
{
    public function __construct(string $certificateId)
    {
        parent::__construct(
            "Unauthorized access to certificate: {$certificateId}",
            403
        );
    }
}
