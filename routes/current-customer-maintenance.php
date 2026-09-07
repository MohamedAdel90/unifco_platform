<?php

use App\Http\Controllers\CurrentCustomerMaintenanceRequestController;
use App\Http\Middleware\PublicCurrentMaintenanceFormEnhancements;
use Illuminate\Support\Facades\Route;

Route::get('/request-service/current-maintenance', [CurrentCustomerMaintenanceRequestController::class, 'create'])
    ->middleware(PublicCurrentMaintenanceFormEnhancements::class)
    ->name('public.current-maintenance');
Route::get('/request-service/current-maintenance/customer', [CurrentCustomerMaintenanceRequestController::class, 'customer'])->middleware('throttle:30,1')->name('public.current-maintenance.customer');
Route::get('/request-service/current-maintenance/assets', [CurrentCustomerMaintenanceRequestController::class, 'assets'])->middleware('throttle:60,1')->name('public.current-maintenance.assets');
