<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Http\Resources\CertificateDetailResource;
use App\Services\CertificateService;
use App\Exceptions\CertificateNotFoundException;
use App\Exceptions\CertificateAlreadyExistsException;
use App\Exceptions\InsufficientScoreException;
use App\Exceptions\InvalidCertificateDataException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminCertificateController extends Controller
{
    public function __construct(
        private CertificateService $certificateService
    ) {}

    /**
     * List all certificates with optional filters
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'nullable|integer|exists:students,id',
            'course_id' => 'nullable|integer|exists:courses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:active,revoked',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = \App\Models\Certificate::with(['student', 'course']);

        // Apply filters
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->has('start_date') || $request->has('end_date')) {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : null;
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : null;
            $query->byDateRange($startDate, $endDate);
        }

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'revoked') {
                $query->revoked();
            }
        }

        $certificates = $query->orderBy('completion_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => CertificateResource::collection($certificates),
            'meta' => [
                'total' => $certificates->count(),
                'filters' => $request->only(['student_id', 'course_id', 'start_date', 'end_date', 'status']),
            ],
        ]);
    }

    /**
     * Get complete certificate details
     * 
     * @param string $certificateId
     * @return JsonResponse
     */
    public function show(string $certificateId): JsonResponse
    {
        try {
            $certificate = \App\Models\Certificate::where('certificate_id', $certificateId)
                ->with(['student', 'course', 'issuedByAdmin', 'revokedByAdmin'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => new CertificateDetailResource($certificate),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found',
            ], 404);
        }
    }

    /**
     * Manually generate certificate
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer|exists:students,id',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $admin = $request->user();
            
            $certificate = $this->certificateService->generateCertificate(
                $request->student_id,
                $request->course_id,
                'admin',
                $admin->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Certificate generated successfully',
                'data' => new CertificateDetailResource($certificate),
            ], 201);
        } catch (CertificateAlreadyExistsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate already exists for this student and course',
            ], 409);
        } catch (InsufficientScoreException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Student has not met the minimum score requirement',
                'data' => [
                    'average_score' => $e->getMessage(),
                ],
            ], 422);
        } catch (InvalidCertificateDataException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Revoke a certificate
     * 
     * @param Request $request
     * @param string $certificateId
     * @return JsonResponse
     */
    public function revoke(Request $request, string $certificateId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $admin = $request->user();
            
            $certificate = $this->certificateService->revokeCertificate(
                $certificateId,
                $admin->id,
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Certificate revoked successfully',
                'data' => new CertificateDetailResource($certificate),
            ]);
        } catch (CertificateNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found',
            ], 404);
        }
    }
}
