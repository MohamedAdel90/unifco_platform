<?php

use App\Http\Controllers\CRM\CustomerPortalAdminController;
use App\Http\Controllers\CRM\CustomerPortalServiceAdminController;
use App\Http\Controllers\CustomerInboxController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\CustomerPortalOperationsController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\CustomerWorkAcceptanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EAM\{AssetController,AssetMeterController};
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('public.home');
Route::get('/request-quote', [PublicSiteController::class, 'quote'])->name('public.quote');
Route::get('/request-service', [PublicSiteController::class, 'quote'])->name('public.request-service');
Route::get('/emergency-maintenance', [PublicSiteController::class, 'emergency'])->name('public.emergency');
Route::post('/service-requests', [PublicSiteController::class, 'store'])->middleware('throttle:10,1')->name('public.request.store');
Route::get('/request-received/{reference}', [PublicSiteController::class, 'received'])->name('public.request.received');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('permission:eam.asset.read')->group(function () {
        Route::get('/eam/assets/{asset}', [AssetController::class,'show'])->name('eam.assets.show');
        Route::get('/eam/assets/{asset}/meters', [AssetMeterController::class,'show'])->name('eam.assets.meters.show');
        Route::get('/eam/assets/{asset}/documents/{document}', [AssetController::class,'downloadDocument'])->name('eam.assets.documents.download');
    });
    Route::middleware('permission:eam.asset.manage')->group(function () {
        Route::post('/eam/assets/{asset}/status', [AssetController::class,'updateStatus'])->name('eam.assets.status');
        Route::post('/eam/assets/{asset}/contracts', [AssetController::class,'assignContract'])->name('eam.assets.contracts.store');
        Route::post('/eam/assets/{asset}/specifications', [AssetController::class,'storeSpecification'])->name('eam.assets.specifications.store');
        Route::post('/eam/assets/{asset}/template-specifications', [AssetController::class,'storeTemplateSpecifications'])->name('eam.assets.specifications.template.store');
        Route::post('/eam/assets/{asset}/documents', [AssetController::class,'storeDocument'])->name('eam.assets.documents.store');
        Route::post('/eam/assets/{asset}/meter-readings', [AssetMeterController::class,'store'])->name('eam.assets.meter-readings.store');
        Route::post('/eam/assets/{asset}/maintenance-plans', [AssetController::class,'storeMaintenancePlan'])->name('eam.assets.maintenance-plans.store');
        Route::post('/eam/assets/{asset}/maintenance-plans/{plan}/tasks', [AssetController::class,'storeMaintenanceTask'])->name('eam.assets.maintenance-plans.tasks.store');
    });

    Route::get('/customer', CustomerPortalController::class)->name('customer.portal');
    Route::get('/customer/inbox', [CustomerInboxController::class,'customerIndex'])->name('customer.inbox');
    Route::post('/customer/inbox', [CustomerInboxController::class,'customerStart'])->name('customer.inbox.start');
    Route::post('/customer/inbox/{conversation}/reply', [CustomerInboxController::class,'customerReply'])->name('customer.inbox.reply');
    Route::get('/customer/profile', [CustomerProfileController::class,'edit'])->name('customer.profile.edit');
    Route::put('/customer/profile', [CustomerProfileController::class,'update'])->name('customer.profile.update');
    Route::post('/customer/profile/logo', [CustomerProfileController::class,'updateLogo'])->name('customer.profile.logo');
    Route::put('/customer/profile/password', [CustomerProfileController::class,'updatePassword'])->name('customer.profile.password');
    Route::get('/customer/{section}', CustomerPortalController::class)
        ->whereIn('section', ['dashboard','contracts','assets','work-orders','maintenance','invoices','reports','sla','documents','notifications'])
        ->name('customer.section');
    Route::post('/customer/service-requests', [CustomerPortalOperationsController::class,'requestService'])->name('customer.requests.store');
    Route::post('/customer/quotations/{quotation}/decision', [CustomerPortalOperationsController::class,'decideQuotation'])->name('customer.quotations.decision');
    Route::get('/customer/invoices/{invoice}/pdf', [CustomerPortalOperationsController::class,'invoicePdf'])->name('customer.invoices.pdf');
    Route::get('/customer/contracts/{contract}/pdf', [CustomerPortalOperationsController::class,'contractPdf'])->name('customer.contracts.pdf');
    Route::get('/customer/visit-reports/{report}/pdf', [CustomerPortalOperationsController::class,'visitPdf'])->name('customer.visits.pdf');
    Route::get('/customer/attachments/{id}', [CustomerPortalOperationsController::class,'attachment'])->name('customer.attachments.download');
    Route::get('/customer/work-acceptance', [CustomerWorkAcceptanceController::class,'index'])->name('customer.work-acceptance.index');
    Route::post('/customer/work-orders/{workOrder}/acceptance', [CustomerWorkAcceptanceController::class,'decide'])->name('customer.work-acceptance.decide');

    Route::middleware('permission:crm.customer.manage')->group(function () {
        Route::get('/crm/customer-inbox', [CustomerInboxController::class,'adminIndex'])->name('crm.customer-inbox.index');
        Route::post('/crm/customer-inbox/{conversation}/reply', [CustomerInboxController::class,'adminReply'])->name('crm.customer-inbox.reply');
        Route::get('/crm/customers/{customer}/portal', [CustomerPortalAdminController::class, 'show'])->name('crm.customers.portal');
        Route::post('/crm/customers/{customer}/portal/contacts', [CustomerPortalAdminController::class, 'storeContact'])->name('crm.customers.portal.contacts.store');
        Route::post('/crm/customers/{customer}/portal/sites', [CustomerPortalAdminController::class, 'storeSite'])->name('crm.customers.portal.sites.store');
        Route::post('/crm/customers/{customer}/portal/activate', [CustomerPortalAdminController::class, 'activate'])->name('crm.customers.portal.activate');
        Route::post('/crm/customers/{customer}/portal/users', [CustomerPortalAdminController::class, 'provisionUser'])->name('crm.customers.portal.users.store');
        Route::post('/crm/customers/{customer}/portal/contracts', [CustomerPortalAdminController::class, 'storeContract'])->name('crm.customers.portal.contracts.store');
        Route::post('/crm/customers/{customer}/portal/assets', [CustomerPortalAdminController::class, 'assignAsset'])->name('crm.customers.portal.assets.store');
        Route::post('/crm/customers/{customer}/portal/invoices', [CustomerPortalAdminController::class, 'linkInvoice'])->name('crm.customers.portal.invoices.store');
        Route::post('/crm/service-requests/{serviceRequest}/status', [CustomerPortalServiceAdminController::class,'updateRequest'])->name('crm.service-requests.status');
        Route::post('/crm/customer-visits', [CustomerPortalServiceAdminController::class,'storeVisit'])->name('crm.customer-visits.store');
        Route::post('/crm/customer-maintenance-attachments', [CustomerPortalServiceAdminController::class,'storeAttachment'])->name('crm.customer-attachments.store');
        Route::get('/admin/public-requests', [PublicSiteController::class, 'adminIndex'])->name('admin.public-requests.index');
    });
});
