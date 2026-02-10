<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class LessonNotFoundException extends Exception
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
            'message' => 'Lesson not found'
        ], 404);
    }
}
