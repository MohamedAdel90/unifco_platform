<?php

use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('public.home');
Route::get('/request-quote', [PublicSiteController::class, 'quote'])->name('public.quote');
Route::get('/emergency-maintenance', [PublicSiteController::class, 'emergency'])->name('public.emergency');
Route::post('/service-requests', [PublicSiteController::class, 'store'])->middleware('throttle:10,1')->name('public.request.store');
Route::get('/request-received/{reference}', [PublicSiteController::class, 'received'])->name('public.request.received');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/customer', CustomerPortalController::class)->name('customer.portal');
    Route::get('/admin/public-requests', [PublicSiteController::class, 'adminIndex'])
        ->middleware('permission:crm.customer.manage')
        ->name('admin.public-requests.index');
});
