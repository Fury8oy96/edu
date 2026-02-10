<?php

namespace App\Exceptions\Assessment;

class InvalidQuestionTypeException extends AssessmentException
{
    protected $statusCode = 422;
    protected $errorCode = 'INVALID_QUESTION_TYPE';
}
