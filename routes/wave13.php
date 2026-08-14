<?php

use App\Http\Controllers\Maintenance\MaintenanceEamOperationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('maintenance/operations')->name('maintenance.operations.')->group(function () {
    Route::get('/',[MaintenanceEamOperationsController::class,'index'])->middleware('permission:maintenance.work_order.read')->name('index');
    Route::post('/plans',[MaintenanceEamOperationsController::class,'storePlan'])->middleware('permission:maintenance.work_order.manage')->name('plans.store');
    Route::post('/plans/generate',[MaintenanceEamOperationsController::class,'generate'])->middleware('permission:maintenance.work_order.manage')->name('plans.generate');
    Route::post('/assets/{asset}/meter',[MaintenanceEamOperationsController::class,'meter'])->middleware('permission:eam.asset.manage')->name('assets.meter');
    Route::post('/assets/{asset}/transfer',[MaintenanceEamOperationsController::class,'transfer'])->middleware('permission:eam.asset.manage')->name('assets.transfer');
    Route::post('/assets/{asset}/depreciate',[MaintenanceEamOperationsController::class,'depreciate'])->middleware('permission:eam.asset.capitalize')->name('assets.depreciate');
    Route::post('/assets/{asset}/dispose',[MaintenanceEamOperationsController::class,'dispose'])->middleware('permission:eam.asset.manage')->name('assets.dispose');
    Route::post('/work-orders/{workOrder}/start',[MaintenanceEamOperationsController::class,'start'])->middleware('permission:maintenance.work_order.manage')->name('work-orders.start');
    Route::post('/work-orders/{workOrder}/labor',[MaintenanceEamOperationsController::class,'labor'])->middleware('permission:maintenance.work_order.manage')->name('work-orders.labor');
    Route::post('/work-orders/{workOrder}/material',[MaintenanceEamOperationsController::class,'material'])->middleware('permission:maintenance.work_order.manage')->name('work-orders.material');
    Route::post('/work-orders/{workOrder}/complete',[MaintenanceEamOperationsController::class,'complete'])->middleware('permission:maintenance.work_order.manage')->name('work-orders.complete');
});
