<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when an upload session has expired.
 */
class ExpiredSessionException extends VideoException
{
    public function __construct(?string $sessionId = null)
    {
        $message = $sessionId 
            ? "Upload session has expired: {$sessionId}"
            : "Upload session has expired";
            
        parent::__construct($message, 410);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Upload session has expired'
        ], 410);
    }
}
