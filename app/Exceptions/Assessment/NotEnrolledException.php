<?php

namespace App\Exceptions\Assessment;

class NotEnrolledException extends AssessmentException
{
    protected $statusCode = 403;
    protected $errorCode = 'NOT_ENROLLED';
    protected $message = 'Not enrolled in course';
}
