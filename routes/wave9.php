<?php

use App\Http\Controllers\Inventory\InventoryOperationsController;
use App\Http\Controllers\Procurement\ProcurementOperationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::prefix('procurement/operations')->name('procurement.operations.')->group(function () {
        Route::get('/',[ProcurementOperationsController::class,'index'])->middleware('permission:procurement.po.read')->name('index');
        Route::post('/suppliers',[ProcurementOperationsController::class,'storeSupplier'])->middleware('permission:procurement.po.read')->name('suppliers.store');
        Route::post('/requisitions',[ProcurementOperationsController::class,'storeRequisition'])->middleware('permission:procurement.po.read')->name('requisitions.store');
        Route::post('/requisitions/{requisition}/approve',[ProcurementOperationsController::class,'approve'])->middleware('permission:procurement.po.approve')->name('requisitions.approve');
        Route::post('/requisitions/{requisition}/convert',[ProcurementOperationsController::class,'convert'])->middleware('permission:procurement.po.read')->name('requisitions.convert');
        Route::post('/purchase-orders/{purchaseOrder}/match-invoice',[ProcurementOperationsController::class,'matchInvoice'])->middleware('permission:procurement.po.read')->name('purchase-orders.match');
    });

    Route::prefix('inventory/operations')->name('inventory.operations.')->group(function () {
        Route::get('/',[InventoryOperationsController::class,'index'])->middleware('permission:inventory.stock.read')->name('index');
        Route::post('/warehouses',[InventoryOperationsController::class,'storeWarehouse'])->middleware('permission:inventory.stock.move')->name('warehouses.store');
        Route::post('/transfers',[InventoryOperationsController::class,'transfer'])->middleware('permission:inventory.stock.move')->name('transfers.store');
        Route::post('/counts',[InventoryOperationsController::class,'count'])->middleware('permission:inventory.stock.move')->name('counts.store');
    });
});
