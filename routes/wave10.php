<?php

use App\Http\Controllers\HR\HrOperationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('hr/operations')->name('hr.operations.')->group(function () {
    Route::get('/',[HrOperationsController::class,'index'])->middleware('permission:hr.employee.read')->name('index');
    Route::post('/positions',[HrOperationsController::class,'storePosition'])->middleware('permission:hr.employee.manage')->name('positions.store');
    Route::post('/employees/{employee}/position',[HrOperationsController::class,'assignPosition'])->middleware('permission:hr.employee.manage')->name('employees.position');
    Route::post('/attendance',[HrOperationsController::class,'storeAttendance'])->middleware('permission:hr.employee.manage')->name('attendance.store');
    Route::post('/leave',[HrOperationsController::class,'storeLeave'])->middleware('permission:hr.employee.manage')->name('leave.store');
    Route::post('/leave/{leave}/decide',[HrOperationsController::class,'decideLeave'])->middleware('permission:workflow.approval.decide')->name('leave.decide');
    Route::post('/payroll',[HrOperationsController::class,'storePayroll'])->middleware('permission:hr.employee.manage')->name('payroll.store');
    Route::post('/payroll/{payroll}/post',[HrOperationsController::class,'postPayroll'])->middleware('permission:finance.journal.post')->name('payroll.post');
});
