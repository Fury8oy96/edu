<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class UnverifiedStudentException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request): JsonResponse
    {
        return response()->json([
            'message' => 'Email verification required to enroll in courses'
        ], 403);
    }
}
