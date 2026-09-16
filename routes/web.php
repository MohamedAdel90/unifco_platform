<?php

use App\Http\Controllers\Admin\{ApiTokenController,AuditController,PermissionController};
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CRM\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperationsManagerDashboardController;
use App\Http\Controllers\EAM\AssetController;
use App\Http\Controllers\Finance\{FinanceCoreController,JournalController};
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\Inventory\{InventoryTransferOrderController,StockController,WarehouseFieldInventoryController};
use App\Http\Controllers\Maintenance\WorkOrderController;
use App\Http\Controllers\Manufacturing\ProductionOrderController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\Platform\{DocumentController,NotificationController};
use App\Http\Controllers\Procurement\{GoodsReceiptController,PurchaseOrderController};
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Reporting\ExecutiveReportController;
use App\Http\Controllers\Workflow\{ApprovalController,WorkflowWorkspaceController};
use Illuminate\Support\Facades\Route;

Route::get('/health/live',[HealthController::class,'live']);
Route::get('/health/ready',[HealthController::class,'ready']);

Route::middleware('guest')->group(function () {
    Route::get('/login',[AuthController::class,'create'])->name('login');
    Route::post('/login',[AuthController::class,'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout',[AuthController::class,'destroy'])->name('logout');
    Route::get('/',DashboardController::class)->name('dashboard');
    Route::get('/operations',OperationsManagerDashboardController::class)->name('operations-manager.dashboard');
    Route::get('/modules/{module}',[ModuleController::class,'index'])->name('modules.index');

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/core',[FinanceCoreController::class,'index'])->middleware('permission:finance.journal.read')->name('core.index');
        Route::post('/core/accounts',[FinanceCoreController::class,'storeAccount'])->middleware('permission:finance.journal.create')->name('core.accounts.store');
        Route::post('/core/periods',[FinanceCoreController::class,'storePeriod'])->middleware('permission:finance.journal.create')->name('core.periods.store');
        Route::post('/core/periods/{period}/close',[FinanceCoreController::class,'closePeriod'])->middleware('permission:finance.journal.post')->name('core.periods.close');
        Route::post('/core/periods/{period}/reopen',[FinanceCoreController::class,'reopenPeriod'])->middleware('permission:finance.journal.post')->name('core.periods.reopen');
        Route::post('/core/documents',[FinanceCoreController::class,'storeDocument'])->middleware('permission:finance.journal.create')->name('core.documents.store');
        Route::post('/core/documents/{document}/post',[FinanceCoreController::class,'postDocument'])->middleware('permission:finance.journal.post')->name('core.documents.post');
        Route::post('/core/documents/{document}/pay',[FinanceCoreController::class,'payDocument'])->middleware('permission:finance.journal.post')->name('core.documents.pay');
        Route::get('/journals',[JournalController::class,'index'])->middleware('permission:finance.journal.read')->name('journals.index');
        Route::get('/journals/create',[JournalController::class,'create'])->middleware('permission:finance.journal.create')->name('journals.create');
        Route::post('/journals',[JournalController::class,'store'])->middleware('permission:finance.journal.create')->name('journals.store');
        Route::post('/journals/{journal}/post',[JournalController::class,'post'])->middleware('permission:finance.journal.post')->name('journals.post');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/stock',[StockController::class,'index'])->middleware('permission:inventory.stock.read')->name('stock.index');
        Route::post('/stock/move',[StockController::class,'move'])->middleware('permission:inventory.stock.move')->name('stock.move');
        Route::get('/warehouse-control',[WarehouseFieldInventoryController::class,'index'])->middleware('permission:inventory.warehouse.read')->name('warehouse.index');
        Route::post('/warehouses',[WarehouseFieldInventoryController::class,'storeWarehouse'])->middleware('permission:inventory.warehouse.manage')->name('warehouses.store');
        Route::post('/warehouses/{warehouse}/bins',[WarehouseFieldInventoryController::class,'storeBin'])->middleware('permission:inventory.warehouse.manage')->name('warehouses.bins.store');
        Route::post('/transfers',[InventoryTransferOrderController::class,'store'])->middleware('permission:inventory.transfer.request')->name('transfers.store');
        Route::post('/transfers/{transfer}/issue',[InventoryTransferOrderController::class,'issue'])->middleware('permission:inventory.transfer.issue')->name('transfers.issue');
        Route::post('/transfers/{transfer}/receive',[InventoryTransferOrderController::class,'receive'])->middleware('permission:inventory.transfer.receive')->name('transfers.receive');
    });

    // Remaining application routes are loaded from the existing route files below.
    require __DIR__.'/web_modules.php';
});
