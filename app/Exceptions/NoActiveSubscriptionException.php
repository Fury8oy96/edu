<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class NoActiveSubscriptionException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Active subscription required to enroll in paid courses',
            'errors' => [
                'subscription' => ['You need an active subscription to access this paid course']
            ],
        ], 403);
    }
}
