<?php

namespace App\Exceptions\Assessment;

class InvalidGradingDataException extends AssessmentException
{
    protected $statusCode = 422;
    protected $errorCode = 'INVALID_GRADING_DATA';
}
