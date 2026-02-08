<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminPaymentHistoryRequest;
use App\Services\AdminPaymentService;
use App\Http\Resources\AdminPaymentResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function __construct(
        private AdminPaymentService $adminPaymentService
    ) {}

    public function pending(): JsonResponse
    {
        $payments = $this->adminPaymentService->getPendingPayments();

        return response()->json([
            'success' => true,
            'data' => AdminPaymentResource::collection($payments),
        ]);
    }

    public function approve(Payment $payment, Request $request): JsonResponse
    {
        $admin = $request->user();
        $approvedPayment = $this->adminPaymentService->approvePayment($payment, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Payment approved successfully',
            'data' => new AdminPaymentResource($approvedPayment),
        ]);
    }

    public function reject(Payment $payment, Request $request): JsonResponse
    {
        $admin = $request->user();
        $rejectedPayment = $this->adminPaymentService->rejectPayment($payment, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Payment rejected successfully',
            'data' => new AdminPaymentResource($rejectedPayment),
        ]);
    }

    public function history(AdminPaymentHistoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $payments = $this->adminPaymentService->getPaymentHistory(
            $validated['status'] ?? null,
            $validated['student_id'] ?? null,
            $validated['per_page'] ?? 15
        );

        return response()->json([
            'success' => true,
            'data' => AdminPaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }
}
