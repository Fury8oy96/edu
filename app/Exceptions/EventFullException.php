<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when an event has reached maximum capacity.
 */
class EventFullException extends EventException
{
    public function __construct()
    {
        parent::__construct(
            "Event has reached maximum capacity",
            409
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Event has reached maximum capacity'
        ], 409);
    }
}
