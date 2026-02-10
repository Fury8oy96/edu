<?php

namespace App\Exceptions\Assessment;

class AssessmentAlreadySubmittedException extends AssessmentException
{
    protected $statusCode = 422;
    protected $errorCode = 'ALREADY_SUBMITTED';
    protected $message = 'Assessment already submitted';
}
