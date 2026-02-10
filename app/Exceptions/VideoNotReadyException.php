<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when attempting to use a video that is still processing.
 */
class VideoNotReadyException extends VideoException
{
    protected ?string $status;
    protected ?int $progress;

    public function __construct(?string $status = null, ?int $progress = null)
    {
        $this->status = $status;
        $this->progress = $progress;
        
        $message = "Video is still processing and cannot be attached to lesson";
            
        parent::__construct($message, 409);
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function render(): JsonResponse
    {
        $response = [
            'message' => 'Video is still processing and cannot be attached to lesson'
        ];

        if ($this->status !== null) {
            $response['status'] = $this->status;
        }

        if ($this->progress !== null) {
            $response['progress'] = $this->progress;
        }

        return response()->json($response, 409);
    }
}
