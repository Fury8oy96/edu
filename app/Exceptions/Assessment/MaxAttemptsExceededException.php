<?php

namespace App\Exceptions\Assessment;

class MaxAttemptsExceededException extends AssessmentException
{
    protected $statusCode = 403;
    protected $errorCode = 'MAX_ATTEMPTS_EXCEEDED';
    protected $message = 'Maximum attempts exceeded';
}
