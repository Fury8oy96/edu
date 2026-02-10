<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when an upload session cannot be found or is invalid.
 */
class InvalidSessionException extends VideoException
{
    public function __construct(?string $sessionId = null)
    {
        $message = $sessionId 
            ? "Upload session not found: {$sessionId}"
            : "Upload session not found";
            
        parent::__construct($message, 404);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Upload session not found'
        ], 404);
    }
}
