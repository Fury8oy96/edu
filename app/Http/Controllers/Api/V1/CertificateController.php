<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Http\Resources\CertificateDetailResource;
use App\Services\CertificateService;
use App\Services\PdfGenerator;
use App\Exceptions\CertificateNotFoundException;
use App\Exceptions\UnauthorizedCertificateAccessException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CertificateController extends Controller
{
    public function __construct(
        private CertificateService $certificateService,
        private PdfGenerator $pdfGenerator
    ) {}

    /**
     * List all certificates for authenticated student
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user();
        
        $certificates = $this->certificateService->getStudentCertificates($student->id);
        
        return response()->json([
            'success' => true,
            'data' => CertificateResource::collection($certificates),
        ]);
    }

    /**
     * Get certificate details for authenticated student
     * 
     * @param Request $request
     * @param string $certificateId
     * @return JsonResponse
     */
    public function show(Request $request, string $certificateId): JsonResponse
    {
        try {
            $student = $request->user();
            
            $certificate = $this->certificateService->getStudentCertificate(
                $student->id,
                $certificateId
            );
            
            return response()->json([
                'success' => true,
                'data' => new CertificateDetailResource($certificate),
            ]);
        } catch (CertificateNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found',
            ], 404);
        } catch (UnauthorizedCertificateAccessException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to certificate',
            ], 403);
        }
    }

    /**
     * Download certificate PDF for authenticated student
     * 
     * @param Request $request
     * @param string $certificateId
     * @return Response
     */
    public function download(Request $request, string $certificateId): Response
    {
        try {
            $student = $request->user();
            
            $certificate = $this->certificateService->getStudentCertificate(
                $student->id,
                $certificateId
            );
            
            return $this->pdfGenerator->downloadPdf($certificate);
        } catch (CertificateNotFoundException $e) {
            abort(404, 'Certificate not found');
        } catch (UnauthorizedCertificateAccessException $e) {
            abort(403, 'Unauthorized access to certificate');
        }
    }
}
