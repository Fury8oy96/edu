<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitPaymentRequest;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PaymentHistoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private SubscriptionService $subscriptionService
    ) {}

    public function plans(): JsonResponse
    {
        $plans = $this->subscriptionService->getAvailablePlans();
        return response()->json([
            'success' => true,
            'data' => SubscriptionPlanResource::collection($plans),
        ]);
    }

    public function submitPayment(SubmitPaymentRequest $request): JsonResponse
    {
        $student = $request->user();
        $validated = $request->validated();

        $payment = $this->paymentService->submitPayment(
            $student,
            $validated['transaction_id'],
            $validated['amount'],
            $validated['subscription_plan_id']
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment submitted successfully',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    public function paymentStatus(Request $request): JsonResponse
    {
        $student = $request->user();
        $payments = $this->paymentService->getPaymentStatus($student);

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
        ]);
    }

    public function paymentHistory(Request $request): JsonResponse
    {
        $student = $request->user();
        $payments = $this->paymentService->getPaymentHistory($student);

        return response()->json([
            'success' => true,
            'data' => PaymentHistoryResource::collection($payments),
        ]);
    }

    public function subscriptionStatus(Request $request): JsonResponse
    {
        $student = $request->user();
        $subscription = $this->subscriptionService->getActiveSubscription($student);

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'message' => 'No active subscription',
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $subscription,
        ]);
    }
}
