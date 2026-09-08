<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/api.php'));

            Route::middleware('api')
                ->prefix('webhooks')
                ->group(base_path('routes/webhooks.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\SecureHeaders::class);

        $middleware->web(append: [
            \App\Http\Middleware\SanitizeInput::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\LogAuditTrail::class,
        ]);

        $middleware->alias([
            'verify.internal' => \App\Http\Middleware\VerifyInternalWebhook::class,
            'lgpd.consent' => \App\Http\Middleware\CheckLgpdConsent::class,
            'audit' => \App\Http\Middleware\LogAuditTrail::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
