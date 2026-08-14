<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\JournalController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\Procurement\{GoodsReceiptController,PurchaseOrderController};
use App\Http\Controllers\Workflow\ApprovalController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login',[AuthController::class,'create'])->name('login');
    Route::post('/login',[AuthController::class,'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout',[AuthController::class,'destroy'])->name('logout');
    Route::get('/',DashboardController::class)->name('dashboard');
    Route::get('/modules/{module}',[ModuleController::class,'index'])->name('modules.index');

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/journals',[JournalController::class,'index'])->middleware('permission:finance.journal.read')->name('journals.index');
        Route::get('/journals/create',[JournalController::class,'create'])->middleware('permission:finance.journal.create')->name('journals.create');
        Route::post('/journals',[JournalController::class,'store'])->middleware('permission:finance.journal.create')->name('journals.store');
        Route::post('/journals/{journal}/post',[JournalController::class,'post'])->middleware('permission:finance.journal.post')->name('journals.post');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/stock',[StockController::class,'index'])->middleware('permission:inventory.stock.read')->name('stock.index');
        Route::post('/stock/move',[StockController::class,'move'])->middleware('permission:inventory.stock.move')->name('stock.move');
    });

    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('/purchase-orders',[PurchaseOrderController::class,'index'])->middleware('permission:procurement.po.read')->name('purchase-orders.index');
        Route::post('/purchase-orders/{purchaseOrder}/approve',[PurchaseOrderController::class,'approve'])->middleware('permission:procurement.po.approve')->name('purchase-orders.approve');
        Route::get('/purchase-orders/{purchaseOrder}/receive',[GoodsReceiptController::class,'create'])->middleware('permission:inventory.stock.move')->name('goods-receipts.create');
        Route::post('/purchase-orders/{purchaseOrder}/receive',[GoodsReceiptController::class,'store'])->middleware('permission:inventory.stock.move')->name('goods-receipts.store');
    });

    Route::prefix('workflow')->name('workflow.')->group(function () {
        Route::get('/approvals',[ApprovalController::class,'index'])->middleware('permission:workflow.approval.read')->name('approvals.index');
        Route::post('/approvals/{approval}/decide',[ApprovalController::class,'decide'])->middleware('permission:workflow.approval.decide')->name('approvals.decide');
    });
});
