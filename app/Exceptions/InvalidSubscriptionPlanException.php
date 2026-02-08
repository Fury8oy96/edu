<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidSubscriptionPlanException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Invalid or inactive subscription plan',
            'errors' => [
                'subscription_plan_id' => ['The selected subscription plan is invalid or inactive']
            ],
        ], 422);
    }
}
