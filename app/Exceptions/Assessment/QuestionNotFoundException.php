<?php

namespace App\Exceptions\Assessment;

class QuestionNotFoundException extends AssessmentException
{
    protected $statusCode = 404;
    protected $errorCode = 'QUESTION_NOT_FOUND';
    protected $message = 'Question not found';
}
