<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when a student attempts to unregister from an event they are not registered for.
 */
class NotRegisteredException extends EventException
{
    public function __construct()
    {
        parent::__construct(
            "You are not registered for this event",
            404
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'You are not registered for this event'
        ], 404);
    }
}
