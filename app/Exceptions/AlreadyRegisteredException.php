<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when a student attempts to register for an event they are already registered for.
 */
class AlreadyRegisteredException extends EventException
{
    public function __construct()
    {
        parent::__construct(
            "You are already registered for this event",
            409
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'You are already registered for this event'
        ], 409);
    }
}
