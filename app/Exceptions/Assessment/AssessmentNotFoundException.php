<?php

namespace App\Exceptions\Assessment;

class AssessmentNotFoundException extends AssessmentException
{
    protected $statusCode = 404;
    protected $errorCode = 'ASSESSMENT_NOT_FOUND';
    protected $message = 'Assessment not found';
}
