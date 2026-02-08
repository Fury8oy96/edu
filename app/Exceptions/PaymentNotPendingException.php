<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PaymentNotPendingException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Payment is not in pending status',
            'errors' => [
                'payment' => ['Only pending payments can be approved or rejected']
            ],
        ], 409);
    }
}
