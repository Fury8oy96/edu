<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is authenticated and email is verified
        if ($user && is_null($user->email_verified_at)) {
            return response()->json([
                'message' => 'Email verification required.'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
