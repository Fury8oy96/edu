<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class DuplicatePaymentException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'You already have a pending payment for this subscription plan',
            'errors' => [
                'subscription_plan_id' => ['A pending payment already exists for this plan']
            ],
        ], 409);
    }
}
