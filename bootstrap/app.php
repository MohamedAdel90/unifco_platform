<?php

use App\Http\Middleware\{AuthenticateApiToken,AuthenticateJwt,BrandingPresentation,EnsureUserSessionValid,LegacyFormCompatibility,PublicAssetQrInsecureFallback,PublicAssetQrPresentation,PublicRequestAttachmentsPresentation,PublicRequestCompactDesign,PublicRequestHeaderMatch,PublicServiceLinks,RequirePermission};
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/public.php',
            __DIR__.'/../routes/public-asset-qr.php',
            __DIR__.'/../routes/services.php',
            __DIR__.'/../routes/brand.php',
            __DIR__.'/../routes/field.php',
            __DIR__.'/../routes/reporting.php',
            __DIR__.'/../routes/parts.php',
            __DIR__.'/../routes/wave9.php',
            __DIR__.'/../routes/wave10.php',
            __DIR__.'/../routes/wave11.php',
            __DIR__.'/../routes/wave12.php',
            __DIR__.'/../routes/wave13.php',
            __DIR__.'/../routes/wave14.php',
            __DIR__.'/../routes/wave15.php',
            __DIR__.'/../routes/wave16.php',
            __DIR__.'/../routes/wave17.php',
            __DIR__.'/../routes/wave18.php',
            __DIR__.'/../routes/navigation.php',
        ],
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['permission'=>RequirePermission::class,'api.token'=>AuthenticateApiToken::class,'jwt'=>AuthenticateJwt::class]);
        // Response mutators unwind in reverse order, so the insecure QR fallback is
        // registered before the QR presentation middleware and can see its injected UI.
        $middleware->web(append: [EnsureUserSessionValid::class,LegacyFormCompatibility::class,BrandingPresentation::class,PublicRequestHeaderMatch::class,PublicRequestAttachmentsPresentation::class,PublicAssetQrInsecureFallback::class,PublicAssetQrPresentation::class,PublicRequestCompactDesign::class,PublicServiceLinks::class]);
        $middleware->validateCsrfTokens(except: ['login']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    })->create();
