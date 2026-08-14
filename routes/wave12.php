<?php

use App\Http\Controllers\Manufacturing\ManufacturingOperationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('manufacturing/operations')->name('manufacturing.operations.')->group(function () {
    Route::get('/',[ManufacturingOperationsController::class,'index'])->middleware('permission:manufacturing.production.read')->name('index');
    Route::post('/work-centers',[ManufacturingOperationsController::class,'storeWorkCenter'])->middleware('permission:manufacturing.production.manage')->name('work-centers.store');
    Route::post('/boms',[ManufacturingOperationsController::class,'storeBom'])->middleware('permission:manufacturing.production.manage')->name('boms.store');
    Route::post('/routings',[ManufacturingOperationsController::class,'storeRouting'])->middleware('permission:manufacturing.production.manage')->name('routings.store');
    Route::post('/orders/{productionOrder}/release',[ManufacturingOperationsController::class,'release'])->middleware('permission:manufacturing.production.manage')->name('orders.release');
    Route::post('/orders/{productionOrder}/issue-materials',[ManufacturingOperationsController::class,'issue'])->middleware('permission:manufacturing.production.manage')->name('orders.issue');
    Route::post('/orders/{productionOrder}/confirm',[ManufacturingOperationsController::class,'confirm'])->middleware('permission:manufacturing.production.manage')->name('orders.confirm');
    Route::post('/orders/{productionOrder}/inspect',[ManufacturingOperationsController::class,'inspect'])->middleware('permission:manufacturing.production.manage')->name('orders.inspect');
    Route::post('/orders/{productionOrder}/complete',[ManufacturingOperationsController::class,'complete'])->middleware('permission:manufacturing.production.manage')->name('orders.complete');
});
