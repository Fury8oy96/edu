<?php

namespace App\Exceptions;

/**
 * Exception thrown when attempting to create a duplicate certificate
 * for the same student and course combination.
 */
class CertificateAlreadyExistsException extends CertificateException
{
    public function __construct(int $studentId, int $courseId)
    {
        parent::__construct(
            "Certificate already exists for student {$studentId} and course {$courseId}",
            409
        );
    }
}
