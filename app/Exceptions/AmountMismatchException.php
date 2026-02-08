<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class AmountMismatchException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Payment amount does not match subscription plan price',
            'errors' => [
                'amount' => ['The payment amount must match the subscription plan price']
            ],
        ], 422);
    }
}
