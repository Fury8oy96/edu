<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
        
        // Register admin middleware alias
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle Assessment exceptions
        $exceptions->renderable(function (\App\Exceptions\Assessment\AssessmentException $e, $request) {
            $response = [
                'error' => [
                    'code' => $e->getErrorCode(),
                    'message' => $e->getMessage(),
                ]
            ];
            
            // Add additional context for specific exceptions
            if ($e instanceof \App\Exceptions\Assessment\PrerequisitesNotMetException) {
                $response['error']['unmet_prerequisites'] = $e->getUnmetPrerequisites();
            }
            
            return response()->json($response, $e->getStatusCode());
        });
    })->create();
