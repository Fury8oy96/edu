<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when attempting to delete an event with attendance records.
 */
class EventHasAttendanceException extends EventException
{
    public function __construct(int $eventId)
    {
        parent::__construct(
            "Cannot delete event {$eventId} with attendance records",
            400
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Cannot delete event with attendance records'
        ], 400);
    }
}
