<?php

use App\Http\Controllers\Api\V1\StudentAuthController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\BlogPostController;
use App\Http\Controllers\Api\V1\Admin\AdminPaymentController;
use App\Http\Controllers\StudentProfileController;
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
    
    // Public blog post routes
    Route::get('/blog-posts', [BlogPostController::class, 'index']);
    Route::get('/blog-posts/{id}', [BlogPostController::class, 'show'])->where('id', '[0-9]+');
    
    // Public blog comment routes
    Route::get('/blog-posts/{blogPostId}/comments', [\App\Http\Controllers\Api\V1\BlogCommentController::class, 'index']);
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
    
    // Protected blog post routes
    Route::middleware('verified')->group(function () {
        // Student profile routes
        Route::get('/student/profile', [StudentProfileController::class, 'show']);
        Route::put('/student/profile', [StudentProfileController::class, 'update']);
        Route::post('/student/profile/avatar', [StudentProfileController::class, 'uploadAvatar']);
        Route::delete('/student/profile/avatar', [StudentProfileController::class, 'removeAvatar']);
        Route::get('/student/profile/progress', [StudentProfileController::class, 'progress']);
        Route::get('/student/profile/statistics', [StudentProfileController::class, 'statistics']);
        
        Route::get('/blog-posts/my-posts', [BlogPostController::class, 'myPosts']);
        Route::post('/blog-posts', [BlogPostController::class, 'store']);
        Route::put('/blog-posts/{id}', [BlogPostController::class, 'update']);
        Route::delete('/blog-posts/{id}', [BlogPostController::class, 'destroy']);
        Route::post('/blog-posts/{id}/publish', [BlogPostController::class, 'publish']);
        Route::post('/blog-posts/{id}/unpublish', [BlogPostController::class, 'unpublish']);
        
        // Protected blog comment routes
        Route::post('/blog-posts/{blogPostId}/comments', [\App\Http\Controllers\Api\V1\BlogCommentController::class, 'store']);
        Route::delete('/blog-comments/{id}', [\App\Http\Controllers\Api\V1\BlogCommentController::class, 'destroy']);
        
        // Protected blog reaction routes
        Route::post('/blog-posts/{blogPostId}/reactions', [\App\Http\Controllers\Api\V1\BlogReactionController::class, 'toggle']);
    });
});

// Protected admin payment routes
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/payments/pending', [AdminPaymentController::class, 'pending']);
    Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approve']);
    Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'reject']);
    Route::get('/payments', [AdminPaymentController::class, 'history']);
});
