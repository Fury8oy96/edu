<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class AttemptAlreadySubmittedException extends Exception
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
            'message' => 'This quiz attempt has already been submitted'
        ], 400);
    }
}
