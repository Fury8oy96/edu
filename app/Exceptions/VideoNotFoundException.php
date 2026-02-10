<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when a video cannot be found.
 */
class VideoNotFoundException extends VideoException
{
    public function __construct(?int $videoId = null)
    {
        $message = $videoId 
            ? "Video not found: {$videoId}"
            : "Video not found";
            
        parent::__construct($message, 404);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Video not found'
        ], 404);
    }
}
