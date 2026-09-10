<?php

use Illuminate\Support\Facades\Route;

/*
 | Canonical public service-request entry point.
 | Keep /request-service on the unified public.request experience instead of
 | the legacy/current-customer-only maintenance view.
 */
Route::get('/request-service', function () {
    return response(view('public.request', ['type' => 'ROUTINE_MAINTENANCE'])->render())
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0')
        ->header('X-UNIFCO-Request-Service', 'unified-public-request');
});
