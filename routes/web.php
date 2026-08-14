<?php

use App\Http\Controllers\Admin\{AuditController,PermissionController};
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CRM\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\JournalController;
use App\Http\Controllers\HR\EmployeeController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\Procurement\{GoodsReceiptController,PurchaseOrderController};
use App\Http\Controllers\Projects\ProjectController;
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

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/audit',[AuditController::class,'index'])->middleware('permission:audit.read')->name('audit.index');
        Route::get('/permissions',[PermissionController::class,'index'])->middleware('permission:security.permission.manage')->name('permissions.index');
        Route::post('/permissions',[PermissionController::class,'store'])->middleware('permission:security.permission.manage')->name('permissions.store');
        Route::delete('/permissions/{id}',[PermissionController::class,'destroy'])->middleware('permission:security.permission.manage')->name('permissions.destroy');
    });
});
