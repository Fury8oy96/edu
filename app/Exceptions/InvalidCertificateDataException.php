<?php

namespace App\Exceptions;

/**
 * Exception thrown when certificate data is invalid or incomplete.
 */
class InvalidCertificateDataException extends CertificateException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }
}
