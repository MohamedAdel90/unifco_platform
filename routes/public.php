<?php

use App\Http\Controllers\CRM\CustomerPortalAdminController;
use App\Http\Controllers\CRM\CustomerPortalServiceAdminController;
use App\Http\Controllers\CustomerAssetReadController;
use App\Http\Controllers\CustomerInboxController;
use App\Http\Controllers\CustomerPortalAccessAdminController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\CustomerPortalOperationsController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\CustomerWorkAcceptanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EAM\AssetController;
use App\Http\Controllers\EAM\AssetHealthDashboardController;
use App\Http\Controllers\EAM\AssetMeterController;
use App\Http\Controllers\EAM\AssetReliabilityController;
use App\Http\Controllers\EAM\AssetSparePartController;
use App\Http\Controllers\Maintenance\WorkOrderController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('public.home');
Route::get('/about', fn () => redirect()->route('public.home'))->name('public.about');
Route::get('/industries', fn () => redirect()->route('public.home'))->name('public.industries');
Route::get('/services', fn () => redirect()->route('public.home'))->name('public.services');
Route::get('/request-quote', [PublicSiteController::class, 'quote'])->name('public.quote');
Route::get('/request-service', [PublicSiteController::class, 'quote'])->name('public.request-service');
Route::get('/emergency-maintenance', [PublicSiteController::class, 'emergency'])->name('public.emergency');
Route::post('/service-requests', [PublicSiteController::class, 'store'])->middleware('throttle:10,1')->name('public.request.store');
Route::get('/request-received/{reference}', [PublicSiteController::class, 'received'])->name('public.request.received');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('permission:eam.asset.read')->group(function () {
        Route::get('/eam/health', [AssetHealthDashboardController::class, 'index'])->name('eam.health.index');
        Route::get('/eam/assets/{asset}', [AssetController::class, 'show'])->name('eam.assets.show');
        Route::get('/eam/assets/{asset}/meters', [AssetMeterController::class, 'show'])->name('eam.assets.meters.show');
        Route::get('/eam/assets/{asset}/spare-parts', [AssetSparePartController::class, 'show'])->name('eam.assets.spare-parts.show');
        Route::get('/eam/assets/{asset}/reliability', [AssetReliabilityController::class, 'show'])->name('eam.assets.reliability.show');
        Route::get('/eam/assets/{asset}/documents/{document}', [AssetController::class, 'downloadDocument'])->name('eam.assets.documents.download');
    });
    Route::middleware('permission:eam.asset.manage')->group(function () {
        Route::post('/eam/assets/{asset}/status', [AssetController::class, 'updateStatus'])->name('eam.assets.status');
        Route::post('/eam/assets/{asset}/contracts', [AssetController::class, 'assignContract'])->name('eam.assets.contracts.store');
        Route::post('/eam/assets/{asset}/specifications', [AssetController::class, 'storeSpecification'])->name('eam.assets.specifications.store');
        Route::post('/eam/assets/{asset}/template-specifications', [AssetController::class, 'storeTemplateSpecifications'])->name('eam.assets.specifications.template.store');
        Route::post('/eam/assets/{asset}/documents', [AssetController::class, 'storeDocument'])->name('eam.assets.documents.store');
        Route::post('/eam/assets/{asset}/meter-readings', [AssetMeterController::class, 'store'])->name('eam.assets.meter-readings.store');
        Route::post('/eam/assets/{asset}/spare-parts', [AssetSparePartController::class, 'store'])->name('eam.assets.spare-parts.store');
        Route::delete('/eam/assets/{asset}/spare-parts/{part}', [AssetSparePartController::class, 'destroy'])->name('eam.assets.spare-parts.destroy');
        Route::post('/eam/assets/{asset}/maintenance-plans', [AssetController::class, 'storeMaintenancePlan'])->name('eam.assets.maintenance-plans.store');
        Route::post('/eam/assets/{asset}/maintenance-plans/{plan}/tasks', [AssetController::class, 'storeMaintenanceTask'])->name('eam.assets.maintenance-plans.tasks.store');
    });

    Route::middleware('permission:maintenance.work_order.read')->group(function () {
        Route::get('/maintenance/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('maintenance.work-orders.show');
        Route::get('/maintenance/work-orders/{workOrder}/attachments/{attachment}', [WorkOrderController::class, 'downloadAttachment'])->name('maintenance.work-orders.attachments.download');
    });
    Route::middleware('permission:maintenance.work_order.manage')->group(function () {
        Route::post('/maintenance/work-orders/{workOrder}/start', [WorkOrderController::class, 'start'])->name('maintenance.work-orders.start');
        Route::post('/maintenance/work-orders/{workOrder}/checklist', [WorkOrderController::class, 'saveChecklist'])->name('maintenance.work-orders.checklist');
        Route::post('/maintenance/work-orders/{workOrder}/attachments', [WorkOrderController::class, 'uploadAttachment'])->name('maintenance.work-orders.attachments.store');
        Route::post('/maintenance/work-orders/{workOrder}/materials', [WorkOrderController::class, 'issueMaterial'])->name('maintenance.work-orders.materials.store');
        Route::post('/maintenance/work-orders/{workOrder}/failures', [WorkOrderController::class, 'recordFailure'])->name('maintenance.work-orders.failures.store');
    });

    Route::get('/customer', CustomerPortalController::class)->name('customer.portal');
    Route::get('/customer/asset-health', [AssetHealthDashboardController::class, 'customer'])->name('customer.asset-health');
    Route::get('/customer/assets/{asset}', [CustomerAssetReadController::class, 'asset'])->name('customer.asset.show');
    Route::get('/customer/work-orders/{workOrder}', [CustomerAssetReadController::class, 'workOrder'])->name('customer.work-orders.show');
    Route::get('/customer/work-orders/{workOrder}/attachments/{attachment}', [CustomerAssetReadController::class, 'attachment'])->name('customer.work-orders.attachments.download');
    Route::get('/customer/inbox', [CustomerInboxController::class, 'customerIndex'])->name('customer.inbox');
    Route::post('/customer/inbox', [CustomerInboxController::class, 'customerStart'])->name('customer.inbox.start');
    Route::post('/customer/inbox/{conversation}/reply', [CustomerInboxController::class, 'customerReply'])->name('customer.inbox.reply');
    Route::get('/customer/profile', [CustomerProfileController::class, 'edit'])->name('customer.profile.edit');
    Route::put('/customer/profile', [CustomerProfileController::class, 'update'])->name('customer.profile.update');
    Route::post('/customer/profile/logo', [CustomerProfileController::class, 'updateLogo'])->name('customer.profile.logo');
    Route::put('/customer/profile/password', [CustomerProfileController::class, 'updatePassword'])->name('customer.profile.password');

    Route::get('/customer/users-access', [CustomerPortalAccessAdminController::class, 'index'])->name('customer.access.index');
    Route::post('/customer/users-access', [CustomerPortalAccessAdminController::class, 'store'])->name('customer.access.store');
    Route::put('/customer/users-access/{user}', [CustomerPortalAccessAdminController::class, 'update'])->name('customer.access.update');
    Route::post('/customer/users-access/{user}/reset-password', [CustomerPortalAccessAdminController::class, 'resetPassword'])->name('customer.access.reset-password');

    Route::get('/customer/{section}', CustomerPortalController::class)
        ->whereIn('section', ['dashboard', 'requests', 'quotations', 'timeline', 'contracts', 'assets', 'work-orders', 'maintenance', 'invoices', 'reports', 'sla', 'documents', 'notifications'])
        ->name('customer.section');
    Route::post('/customer/service-requests', [CustomerPortalOperationsController::class, 'requestService'])->name('customer.requests.store');
    Route::post('/customer/quotations/{quotation}/decision', [CustomerPortalOperationsController::class, 'decideQuotation'])->name('customer.quotations.decision');
    Route::get('/customer/invoices/{invoice}/pdf', [CustomerPortalOperationsController::class, 'invoicePdf'])->name('customer.invoices.pdf');
    Route::get('/customer/contracts/{contract}/pdf', [CustomerPortalOperationsController::class, 'contractPdf'])->name('customer.contracts.pdf');
    Route::get('/customer/visit-reports/{report}/pdf', [CustomerPortalOperationsController::class, 'visitPdf'])->name('customer.visits.pdf');
    Route::get('/customer/attachments/{id}', [CustomerPortalOperationsController::class, 'attachment'])->name('customer.attachments.download');
    Route::get('/customer/work-acceptance', [CustomerWorkAcceptanceController::class, 'index'])->name('customer.work-acceptance.index');
    Route::post('/customer/work-orders/{workOrder}/acceptance', [CustomerWorkAcceptanceController::class, 'decide'])->name('customer.work-acceptance.decide');

    Route::middleware('permission:crm.customer.manage')->group(function () {
        Route::get('/crm/customer-inbox', [CustomerInboxController::class, 'adminIndex'])->name('crm.customer-inbox.index');
        Route::post('/crm/customer-inbox/{conversation}/reply', [CustomerInboxController::class, 'adminReply'])->name('crm.customer-inbox.reply');
        Route::get('/crm/customers/{customer}/portal', [CustomerPortalAdminController::class, 'show'])->name('crm.customers.portal');
        Route::post('/crm/customers/{customer}/portal/contacts', [CustomerPortalAdminController::class, 'storeContact'])->name('crm.customers.portal.contacts.store');
        Route::post('/crm/customers/{customer}/portal/sites', [CustomerPortalAdminController::class, 'storeSite'])->name('crm.customers.portal.sites.store');
        Route::post('/crm/customers/{customer}/portal/activate', [CustomerPortalAdminController::class, 'activate'])->name('crm.customers.portal.activate');
        Route::post('/crm/customers/{customer}/portal/users', [CustomerPortalAdminController::class, 'provisionUser'])->name('crm.customers.portal.users.store');
        Route::post('/crm/customers/{customer}/portal/contracts', [CustomerPortalAdminController::class, 'storeContract'])->name('crm.customers.portal.contracts.store');
        Route::post('/crm/customers/{customer}/portal/assets', [CustomerPortalAdminController::class, 'assignAsset'])->name('crm.customers.portal.assets.store');
        Route::post('/crm/customers/{customer}/portal/invoices', [CustomerPortalAdminController::class, 'linkInvoice'])->name('crm.customers.portal.invoices.store');
        Route::post('/crm/service-requests/{serviceRequest}/status', [CustomerPortalServiceAdminController::class, 'updateRequest'])->name('crm.service-requests.status');
        Route::post('/crm/customer-visits', [CustomerPortalServiceAdminController::class, 'storeVisit'])->name('crm.customer-visits.store');
        Route::post('/crm/customer-maintenance-attachments', [CustomerPortalServiceAdminController::class, 'storeAttachment'])->name('crm.customer-attachments.store');
        Route::get('/admin/public-requests', [PublicSiteController::class, 'adminIndex'])->name('admin.public-requests.index');
    });
});
