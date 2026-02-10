<?php

namespace App\Exceptions\Assessment;

class AssessmentNotAvailableException extends AssessmentException
{
    protected $statusCode = 403;
    protected $errorCode = 'ASSESSMENT_NOT_AVAILABLE';
    protected $message = 'Assessment not available at this time';
}
