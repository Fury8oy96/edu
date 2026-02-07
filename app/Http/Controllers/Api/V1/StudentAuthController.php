<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Services\AuthService;
use App\Exceptions\InvalidOtpException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\AlreadyVerifiedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAuthController extends Controller
{
    /**
     * Create a new StudentAuthController instance
     * 
     * @param AuthService $authService
     */
    public function __construct(
        private AuthService $authService
    ) {
        // Note: Rate limiting middleware 'throttle:otp-generation' should be applied in routes
    }
    
    /**
     * Register a new student and send OTP
     * 
     * POST /api/v1/student/register
     * 
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Call AuthService to handle registration logic
        $result = $this->authService->register($request->validated());
        
        // Return JSON response with 201 Created status
        return response()->json($result, 201);
    }
    
    /**
     * Verify OTP and activate student account
     * 
     * POST /api/v1/student/verify-otp
     * 
     * @param VerifyOtpRequest $request
     * @return JsonResponse
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            // Call AuthService to handle OTP verification logic
            $result = $this->authService->verifyOtp(
                $request->input('email'),
                $request->input('otp')
            );
            
            // Return JSON response with 200 OK status
            return response()->json($result, 200);
        } catch (InvalidOtpException $e) {
            // InvalidOtpException will be automatically rendered with 400 status
            // by its render() method, but we can also handle it explicitly here
            throw $e;
        }
    }
    
    /**
     * Login student (generate token if verified, send OTP if not)
     * 
     * POST /api/v1/student/login
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Call AuthService to handle login logic
            $result = $this->authService->login(
                $request->input('email'),
                $request->input('password')
            );
            
            // Return JSON response with 200 OK status
            // This handles both verified (with token) and unverified (with message) cases
            return response()->json($result, 200);
        } catch (InvalidCredentialsException $e) {
            // InvalidCredentialsException will be automatically rendered with 401 status
            // by its render() method, but we can also handle it explicitly here
            throw $e;
        }
    }

    /**
     * Resend OTP to unverified student
     *
     * POST /api/v1/student/resend-otp
     *
     * @param ResendOtpRequest $request
     * @return JsonResponse
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        try {
            // Call AuthService to handle OTP resend logic
            $result = $this->authService->resendOtp(
                $request->input('email')
            );

            // Return JSON response with 200 OK status
            return response()->json($result, 200);
        } catch (AlreadyVerifiedException $e) {
            // AlreadyVerifiedException will be automatically rendered with 400 status
            // by its render() method, but we can also handle it explicitly here
            throw $e;
        }
    }

    /**
     * Logout student by revoking current token
     *
     * POST /api/v1/student/logout
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Get authenticated student from request
        $student = $request->user();

        // Call AuthService to handle logout logic
        $result = $this->authService->logout($student);

        // Return JSON response with 200 OK status
        return response()->json($result, 200);
    }

}
