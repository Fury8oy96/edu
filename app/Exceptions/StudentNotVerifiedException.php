<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when an unverified student attempts to register for an event.
 */
class StudentNotVerifiedException extends EventException
{
    public function __construct()
    {
        parent::__construct(
            "Email verification required to register for events",
            403
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Email verification required to register for events'
        ], 403);
    }
}
