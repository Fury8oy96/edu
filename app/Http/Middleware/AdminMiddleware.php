<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Admin;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
