<?php

namespace App\Exceptions\Assessment;

class PrerequisitesNotMetException extends AssessmentException
{
    protected $statusCode = 403;
    protected $errorCode = 'PREREQUISITES_NOT_MET';
    protected $unmetPrerequisites = [];
    
    public function __construct(array $unmetPrerequisites)
    {
        $this->message = 'Prerequisites not met';
        $this->unmetPrerequisites = $unmetPrerequisites;
        parent::__construct($this->message);
    }
    
    public function getUnmetPrerequisites(): array
    {
        return $this->unmetPrerequisites;
    }
}
