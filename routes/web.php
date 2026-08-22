<?php

use App\Http\Controllers\Admin\{ApiTokenController,AuditController,PermissionController};
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CRM\CustomerController;
use App\Http\Controllers\DashboardController;
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
use App\Http\Controllers\Workflow\ApprovalController;
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

    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/employees',[EmployeeController::class,'index'])->middleware('permission:hr.employee.read')->name('employees.index');
        Route::get('/employees/create',[EmployeeController::class,'create'])->middleware('permission:hr.employee.manage')->name('employees.create');
        Route::post('/employees',[EmployeeController::class,'store'])->middleware('permission:hr.employee.manage')->name('employees.store');
        Route::get('/employees/{employee}/edit',[EmployeeController::class,'edit'])->middleware('permission:hr.employee.manage')->name('employees.edit');
        Route::put('/employees/{employee}',[EmployeeController::class,'update'])->middleware('permission:hr.employee.manage')->name('employees.update');
        Route::post('/employees/{employee}/deactivate',[EmployeeController::class,'deactivate'])->middleware('permission:hr.employee.manage')->name('employees.deactivate');
    });

    Route::prefix('crm')->name('crm.')->group(function () {
        Route::get('/customers',[CustomerController::class,'index'])->middleware('permission:crm.customer.read')->name('customers.index');
        Route::get('/customers/create',[CustomerController::class,'create'])->middleware('permission:crm.customer.manage')->name('customers.create');
        Route::post('/customers',[CustomerController::class,'store'])->middleware('permission:crm.customer.manage')->name('customers.store');
        Route::get('/customers/{customer}/edit',[CustomerController::class,'edit'])->middleware('permission:crm.customer.manage')->name('customers.edit');
        Route::put('/customers/{customer}',[CustomerController::class,'update'])->middleware('permission:crm.customer.manage')->name('customers.update');
        Route::post('/customers/{customer}/block',[CustomerController::class,'block'])->middleware('permission:crm.customer.manage')->name('customers.block');
    });

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/',[ProjectController::class,'index'])->middleware('permission:projects.project.read')->name('projects.index');
        Route::get('/create',[ProjectController::class,'create'])->middleware('permission:projects.project.manage')->name('projects.create');
        Route::post('/',[ProjectController::class,'store'])->middleware('permission:projects.project.manage')->name('projects.store');
        Route::get('/{project}/edit',[ProjectController::class,'edit'])->middleware('permission:projects.project.manage')->name('projects.edit');
        Route::put('/{project}',[ProjectController::class,'update'])->middleware('permission:projects.project.manage')->name('projects.update');
        Route::post('/{project}/activate',[ProjectController::class,'activate'])->middleware('permission:projects.project.manage')->name('projects.activate');
    });

    Route::prefix('manufacturing')->name('manufacturing.')->group(function () {
        Route::get('/production-orders',[ProductionOrderController::class,'index'])->middleware('permission:manufacturing.production.read')->name('production-orders.index');
        Route::get('/production-orders/create',[ProductionOrderController::class,'create'])->middleware('permission:manufacturing.production.manage')->name('production-orders.create');
        Route::post('/production-orders',[ProductionOrderController::class,'store'])->middleware('permission:manufacturing.production.manage')->name('production-orders.store');
        Route::get('/production-orders/{productionOrder}/edit',[ProductionOrderController::class,'edit'])->middleware('permission:manufacturing.production.manage')->name('production-orders.edit');
        Route::put('/production-orders/{productionOrder}',[ProductionOrderController::class,'update'])->middleware('permission:manufacturing.production.manage')->name('production-orders.update');
        Route::post('/production-orders/{productionOrder}/release',[ProductionOrderController::class,'release'])->middleware('permission:manufacturing.production.manage')->name('production-orders.release');
        Route::post('/production-orders/{productionOrder}/complete',[ProductionOrderController::class,'complete'])->middleware('permission:manufacturing.production.manage')->name('production-orders.complete');
    });

    Route::prefix('eam')->name('eam.')->group(function () {
        Route::get('/assets',[AssetController::class,'index'])->middleware('permission:eam.asset.read')->name('assets.index');
        Route::get('/assets/create',[AssetController::class,'create'])->middleware('permission:eam.asset.manage')->name('assets.create');
        Route::post('/assets',[AssetController::class,'store'])->middleware('permission:eam.asset.manage')->name('assets.store');
        Route::get('/assets/{asset}/edit',[AssetController::class,'edit'])->middleware('permission:eam.asset.manage')->name('assets.edit');
        Route::put('/assets/{asset}',[AssetController::class,'update'])->middleware('permission:eam.asset.manage')->name('assets.update');
        Route::post('/assets/{asset}/capitalize',[AssetController::class,'capitalize'])->middleware('permission:eam.asset.capitalize')->name('assets.capitalize');
    });

    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/work-orders',[WorkOrderController::class,'index'])->middleware('permission:maintenance.work_order.read')->name('work-orders.index');
        Route::get('/work-orders/create',[WorkOrderController::class,'create'])->middleware('permission:maintenance.work_order.manage')->name('work-orders.create');
        Route::post('/work-orders',[WorkOrderController::class,'store'])->middleware('permission:maintenance.work_order.manage')->name('work-orders.store');
        Route::get('/work-orders/{workOrder}/edit',[WorkOrderController::class,'edit'])->middleware('permission:maintenance.work_order.manage')->name('work-orders.edit');
        Route::put('/work-orders/{workOrder}',[WorkOrderController::class,'update'])->middleware('permission:maintenance.work_order.manage')->name('work-orders.update');
        Route::post('/work-orders/{workOrder}/complete',[WorkOrderController::class,'complete'])->middleware('permission:maintenance.work_order.manage')->name('work-orders.complete');
    });

    Route::prefix('platform')->name('platform.')->group(function () {
        Route::get('/documents',[DocumentController::class,'index'])->middleware('permission:documents.read')->name('documents.index');
        Route::post('/documents',[DocumentController::class,'store'])->middleware('permission:documents.manage')->name('documents.store');
        Route::get('/documents/{document}/download',[DocumentController::class,'download'])->middleware('permission:documents.read')->name('documents.download');
        Route::get('/notifications',[NotificationController::class,'index'])->name('notifications.index');
        Route::post('/notifications/read-all',[NotificationController::class,'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read',[NotificationController::class,'read'])->name('notifications.read');
    });

    Route::get('/reports/executive',[ExecutiveReportController::class,'index'])->middleware('permission:reporting.executive.read')->name('reporting.executive');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/audit',[AuditController::class,'index'])->middleware('permission:audit.read')->name('audit.index');
        Route::get('/permissions',[PermissionController::class,'index'])->middleware('permission:security.permission.manage')->name('permissions.index');
        Route::post('/permissions',[PermissionController::class,'store'])->middleware('permission:security.permission.manage')->name('permissions.store');
        Route::delete('/permissions/{id}',[PermissionController::class,'destroy'])->middleware('permission:security.permission.manage')->name('permissions.destroy');
        Route::get('/api-tokens',[ApiTokenController::class,'index'])->middleware('permission:security.permission.manage')->name('api-tokens.index');
        Route::post('/api-tokens',[ApiTokenController::class,'store'])->middleware('permission:security.permission.manage')->name('api-tokens.store');
        Route::delete('/api-tokens/{apiToken}',[ApiTokenController::class,'destroy'])->middleware('permission:security.permission.manage')->name('api-tokens.destroy');
    });
});