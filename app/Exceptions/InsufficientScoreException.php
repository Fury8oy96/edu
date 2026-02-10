<?php

namespace App\Exceptions;

/**
 * Exception thrown when a student's average score is below
 * the minimum requirement for certificate generation.
 */
class InsufficientScoreException extends CertificateException
{
    public function __construct(float $averageScore, float $minimumScore = 60.0)
    {
        parent::__construct(
            "Student does not meet minimum score requirement. Average: {$averageScore}%, Required: {$minimumScore}%",
            422
        );
    }
}
