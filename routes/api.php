<?php

use App\Http\Controllers\Api\V1\StudentAuthController;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::prefix('v1/student')->group(function () {
    Route::post('/register', [StudentAuthController::class, 'register'])
        ->middleware('throttle:otp-generation');
    
    Route::post('/verify-otp', [StudentAuthController::class, 'verifyOtp'])
        ->middleware('throttle:otp-verification');
    
    Route::post('/login', [StudentAuthController::class, 'login'])
        ->middleware('throttle:otp-generation');
    
    Route::post('/resend-otp', [StudentAuthController::class, 'resendOtp'])
        ->middleware('throttle:otp-generation');
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->prefix('v1/student')->group(function () {
    Route::post('/logout', [StudentAuthController::class, 'logout']);
});
