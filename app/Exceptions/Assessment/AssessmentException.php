<?php

namespace App\Exceptions\Assessment;

use Exception;

class AssessmentException extends Exception
{
    protected $statusCode = 500;
    protected $errorCode = 'ASSESSMENT_ERROR';
    
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
