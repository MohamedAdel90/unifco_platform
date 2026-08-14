<?php

use App\Http\Controllers\CRM\CrmOperationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('crm')->name('crm.')->group(function () {
    Route::get('/operations',[CrmOperationsController::class,'index'])->middleware('permission:crm.customer.read')->name('operations.index');
    Route::post('/operations/leads',[CrmOperationsController::class,'storeLead'])->middleware('permission:crm.customer.manage')->name('operations.leads.store');
    Route::post('/operations/opportunities',[CrmOperationsController::class,'storeOpportunity'])->middleware('permission:crm.customer.manage')->name('operations.opportunities.store');
    Route::post('/operations/quotations',[CrmOperationsController::class,'storeQuotation'])->middleware('permission:crm.customer.manage')->name('operations.quotations.store');
    Route::post('/operations/opportunities/{opportunity}/win',[CrmOperationsController::class,'win'])->middleware('permission:projects.project.manage')->name('operations.opportunities.win');
});

Route::middleware('auth')->prefix('projects')->name('projects.')->group(function () {
    Route::post('/operations/tasks',[CrmOperationsController::class,'storeTask'])->middleware('permission:projects.project.manage')->name('operations.tasks.store');
    Route::post('/operations/{project}/resources',[CrmOperationsController::class,'assignResource'])->middleware('permission:projects.project.manage')->name('operations.resources.store');
    Route::post('/operations/{project}/timesheets',[CrmOperationsController::class,'postTimesheet'])->middleware('permission:projects.project.manage')->name('operations.timesheets.store');
});
