<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when an operation requires an upcoming event but the event is not in upcoming state.
 */
class EventNotUpcomingException extends EventException
{
    public function __construct()
    {
        parent::__construct(
            "Event is not in upcoming state",
            400
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Event is not in upcoming state'
        ], 400);
    }
}
