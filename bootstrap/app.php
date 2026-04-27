<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function(){
            // Single-tenant routing
            Route::middleware('web')->group(__DIR__.'/../routes/web.php');

            // API routes (stateless)
            Route::middleware('api')
                ->prefix('api')
                ->group(__DIR__.'/../routes/api.php');
        },
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Removed tenancy-specific 'universal' group. Keep only app-specific aliases.
        // Alias for bridge integration auth (Bearer token)
        $middleware->alias([
            'bridge.auth' => \App\Http\Middleware\AccessBridgeAuth::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'livewire/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
