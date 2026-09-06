<?php

use App\Http\Controllers\CurrentCustomerMaintenanceRequestController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Middleware\PublicCurrentMaintenanceFormEnhancements;
use App\Http\Middleware\PublicMaintenanceSparePartsBridge;
use App\Http\Middleware\PublicSparePartsPresetExperience;
use App\Http\Middleware\PublicSparePartsRequestCompatibility;
use App\Http\Middleware\PublicSparePartsRequestPresentation;
use Illuminate\Support\Facades\Route;

// Public Maintenance portal (canonical routes).
Route::get('/maintenance', [CurrentCustomerMaintenanceRequestController::class, 'create'])
    ->middleware([PublicCurrentMaintenanceFormEnhancements::class, PublicMaintenanceSparePartsBridge::class])
    ->name('public.current-maintenance');
Route::get('/maintenance/spare-parts', [PublicSiteController::class, 'quote'])
    ->middleware([
        PublicSparePartsRequestCompatibility::class,
        PublicSparePartsPresetExperience::class,
        PublicSparePartsRequestPresentation::class,
    ])
    ->name('public.current-maintenance.spare-parts');
Route::get('/maintenance/customer', [CurrentCustomerMaintenanceRequestController::class, 'customer'])
    ->middleware('throttle:30,1')
    ->name('public.current-maintenance.customer');
Route::get('/maintenance/assets', [CurrentCustomerMaintenanceRequestController::class, 'assets'])
    ->middleware('throttle:60,1')
    ->name('public.current-maintenance.assets');

// Backward-compatible aliases. Keep legacy links working while separating the
// public Maintenance portal from the authenticated Client Portal (/customer).
Route::get('/request-service/current-maintenance', fn () => redirect()->route('public.current-maintenance', [], 301));
Route::get('/request-service/current-maintenance/customer', fn () => redirect()->route('public.current-maintenance.customer', request()->query(), 301));
Route::get('/request-service/current-maintenance/assets', fn () => redirect()->route('public.current-maintenance.assets', request()->query(), 301));
