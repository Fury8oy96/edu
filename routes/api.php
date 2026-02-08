<?php

use App\Http\Controllers\Api\V1\StudentAuthController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\Admin\AdminPaymentController;
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

// Public course routes (no authentication required)
Route::prefix('v1')->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    
    // Public subscription plan route
    Route::get('/subscription-plans', [SubscriptionController::class, 'plans']);
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->prefix('v1/student')->group(function () {
    Route::post('/logout', [StudentAuthController::class, 'logout']);
});

// Protected course routes (authentication required)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll']);
    Route::get('/student/courses', [CourseController::class, 'myEnrollments']);
    Route::delete('/courses/{course}/unenroll', [CourseController::class, 'unenroll']);
    
    // Protected student payment routes
    Route::post('/payments/submit', [SubscriptionController::class, 'submitPayment']);
    Route::get('/payments/status', [SubscriptionController::class, 'paymentStatus']);
    Route::get('/payments/history', [SubscriptionController::class, 'paymentHistory']);
    Route::get('/subscription/status', [SubscriptionController::class, 'subscriptionStatus']);
});

// Protected admin payment routes
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/payments/pending', [AdminPaymentController::class, 'pending']);
    Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approve']);
    Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'reject']);
    Route::get('/payments', [AdminPaymentController::class, 'history']);
});
