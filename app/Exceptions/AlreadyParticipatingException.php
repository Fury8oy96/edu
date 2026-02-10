<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when a student attempts to join an event they are already participating in.
 */
class AlreadyParticipatingException extends EventException
{
    public function __construct()
    {
        parent::__construct(
            "You are already participating in this event",
            409
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'You are already participating in this event'
        ], 409);
    }
}
