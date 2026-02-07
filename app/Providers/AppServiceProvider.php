<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure rate limiters for OTP operations
        $this->configureRateLimiting();
    }
    
    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Rate limiter for OTP generation (register, login, resend)
        // 3 attempts per 5 minutes per email address
        RateLimiter::for('otp-generation', function (Request $request) {
            return Limit::perMinutes(5, 3)->by($request->input('email') ?: $request->ip());
        });
        
        // Rate limiter for OTP verification
        // 5 attempts per 5 minutes per email address
        RateLimiter::for('otp-verification', function (Request $request) {
            return Limit::perMinutes(5, 5)->by($request->input('email') ?: $request->ip());
        });
    }
}
