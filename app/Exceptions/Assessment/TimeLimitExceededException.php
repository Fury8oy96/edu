<?php

namespace App\Exceptions\Assessment;

class TimeLimitExceededException extends AssessmentException
{
    protected $statusCode = 422;
    protected $errorCode = 'TIME_LIMIT_EXCEEDED';
    protected $message = 'Assessment time limit exceeded';
}
