<?php

use Illuminate\Support\Facades\Route;

Route::get('/en', function () {
    return redirect()->route('public.home', ['lang' => 'en']);
})->name('public.home.en');

/*
 | Canonical public service-request entry point.
 | This route is intentionally registered after routes/public.php and
 | routes/current-customer-maintenance.php so /request-service always resolves
 | to the unified public.request form we are actively developing.
 */
Route::get('/request-service', function () {
    return response(view('public.request', ['type' => 'ROUTINE_MAINTENANCE'])->render())
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0')
        ->header('X-UNIFCO-Request-Service', 'unified-public-request');
});
