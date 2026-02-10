<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminCertificateAnalyticsController extends Controller
{
    public function __construct(
        private CertificateService $certificateService
    ) {}

    /**
     * Get certificate analytics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'include_revoked' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;
        $includeRevoked = $request->boolean('include_revoked', false);

        $analytics = $this->certificateService->getAnalytics(
            $startDate,
            $endDate,
            $includeRevoked
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }
}
