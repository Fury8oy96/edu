<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when an operation requires an ongoing event but the event is not currently ongoing.
 */
class EventNotOngoingException extends EventException
{
    public function __construct()
    {
        parent::__construct(
            "Event is not currently ongoing",
            400
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Event is not currently ongoing'
        ], 400);
    }
}
