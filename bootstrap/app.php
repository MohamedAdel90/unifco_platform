<?php

use App\Http\Middleware\{AuthenticateApiToken,AuthenticateJwt,RequirePermission};
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/public.php',
            __DIR__.'/../routes/brand.php',
            __DIR__.'/../routes/field.php',
            __DIR__.'/../routes/reporting.php',
            __DIR__.'/../routes/parts.php',
            __DIR__.'/../routes/wave9.php',
            __DIR__.'/../routes/wave10.php',
            __DIR__.'/../routes/wave11.php',
            __DIR__.'/../routes/wave12.php',
            __DIR__.'/../routes/wave13.php',
            __DIR__.'/../routes/navigation.php',
        ],
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['permission'=>RequirePermission::class,'api.token'=>AuthenticateApiToken::class,'jwt'=>AuthenticateJwt::class]);
        // Temporary narrow exception for the login POST only. The production server is currently
        // serving over plain HTTP on an IP address and is returning 419 before credentials are checked.
        // All authenticated and business forms remain CSRF protected.
        $middleware->validateCsrfTokens(except: ['login']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API and web exception rendering use Laravel defaults; production logging remains authoritative.
    })->create();
