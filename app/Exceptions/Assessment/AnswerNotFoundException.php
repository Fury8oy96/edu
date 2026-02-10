<?php

namespace App\Exceptions\Assessment;

class AnswerNotFoundException extends AssessmentException
{
    protected $statusCode = 404;
    protected $errorCode = 'ANSWER_NOT_FOUND';
    protected $message = 'Answer not found';
}
