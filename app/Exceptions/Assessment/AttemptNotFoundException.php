<?php

namespace App\Exceptions\Assessment;

class AttemptNotFoundException extends AssessmentException
{
    protected $statusCode = 404;
    protected $errorCode = 'ATTEMPT_NOT_FOUND';
    protected $message = 'Assessment attempt not found';
}
