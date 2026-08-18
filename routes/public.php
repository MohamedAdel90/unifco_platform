<?php

use App\Http\Controllers\CRM\CustomerPortalAdminController;
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

    Route::middleware('permission:crm.customer.manage')->group(function () {
        Route::get('/crm/customers/{customer}/portal', [CustomerPortalAdminController::class, 'show'])->name('crm.customers.portal');
        Route::post('/crm/customers/{customer}/portal/users', [CustomerPortalAdminController::class, 'provisionUser'])->name('crm.customers.portal.users.store');
        Route::post('/crm/customers/{customer}/portal/contracts', [CustomerPortalAdminController::class, 'storeContract'])->name('crm.customers.portal.contracts.store');
        Route::post('/crm/customers/{customer}/portal/assets', [CustomerPortalAdminController::class, 'assignAsset'])->name('crm.customers.portal.assets.store');
        Route::post('/crm/customers/{customer}/portal/invoices', [CustomerPortalAdminController::class, 'linkInvoice'])->name('crm.customers.portal.invoices.store');
        Route::get('/admin/public-requests', [PublicSiteController::class, 'adminIndex'])->name('admin.public-requests.index');
    });
});
