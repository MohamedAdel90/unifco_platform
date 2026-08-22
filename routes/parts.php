<?php

use App\Http\Controllers\Inventory\{WorkOrderPartConsumptionController,WorkOrderPartRequestController};
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/maintenance/work-orders/{workOrder}/part-requests',[WorkOrderPartRequestController::class,'store'])
        ->middleware('permission:parts.request.create')->name('maintenance.work-orders.part-requests.store');

    Route::post('/inventory/part-requests/{partRequest}/approve',[WorkOrderPartRequestController::class,'approve'])
        ->middleware('permission:parts.request.approve')->name('inventory.part-requests.approve');
    Route::post('/inventory/part-requests/{partRequest}/reject',[WorkOrderPartRequestController::class,'reject'])
        ->middleware('permission:parts.request.approve')->name('inventory.part-requests.reject');
    Route::post('/inventory/part-requests/{partRequest}/pick',[WorkOrderPartRequestController::class,'pick'])
        ->middleware('permission:parts.request.pick')->name('inventory.part-requests.pick');
    Route::post('/inventory/part-requests/{partRequest}/issue',[WorkOrderPartRequestController::class,'issue'])
        ->middleware('permission:parts.request.issue')->name('inventory.part-requests.issue');
    Route::post('/inventory/part-requests/{partRequest}/receive',[WorkOrderPartRequestController::class,'receive'])
        ->middleware('permission:parts.request.receive')->name('inventory.part-requests.receive');

    Route::post('/inventory/part-requests/{partRequest}/lines/{line}/consume',[WorkOrderPartConsumptionController::class,'consume'])
        ->middleware('permission:parts.consume')->name('inventory.part-requests.consume');
    Route::post('/inventory/part-requests/{partRequest}/lines/{line}/return',[WorkOrderPartConsumptionController::class,'returnUnused'])
        ->middleware('permission:parts.return')->name('inventory.part-requests.return');
});
