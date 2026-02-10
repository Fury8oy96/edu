<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when an event cannot be found.
 */
class EventNotFoundException extends EventException
{
    public function __construct(?int $eventId = null)
    {
        $message = $eventId 
            ? "Event not found: {$eventId}"
            : "Event not found";
            
        parent::__construct($message, 404);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Event not found'
        ], 404);
    }
}
