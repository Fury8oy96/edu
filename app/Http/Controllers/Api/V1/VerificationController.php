<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VerificationResource;
use App\Services\CertificateService;
use App\Exceptions\CertificateNotFoundException;
use Illuminate\Http\JsonResponse;

class VerificationController extends Controller
{
    public function __construct(
        private CertificateService $certificateService
    ) {}

    /**
     * Verify certificate by public certificate ID
     * No authentication required
     * 
     * @param string $certificateId
     * @return JsonResponse
     */
    public function verify(string $certificateId): JsonResponse
    {
        try {
            $verificationData = $this->certificateService->verifyCertificate($certificateId);
            
            return response()->json([
                'success' => true,
                'data' => new VerificationResource($verificationData),
            ]);
        } catch (CertificateNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found',
                'data' => [
                    'certificate_id' => $certificateId,
                    'verified' => false,
                ],
            ], 404);
        }
    }
}
