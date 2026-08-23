<?php

use App\Http\Controllers\HR\{AttendanceController,EmployeeController,HrDashboardController,HrOperationsController,LeaveController};
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard',[HrDashboardController::class,'index'])->middleware('permission:hr.employee.read')->name('dashboard');
    Route::get('/employees/{employee}',[EmployeeController::class,'show'])->middleware('permission:hr.employee.read')->name('employees.show');
    Route::post('/employees/{employee}/activate',[EmployeeController::class,'activate'])->middleware('permission:hr.employee.manage')->name('employees.activate');
    Route::post('/employees/{employee}/contracts',[EmployeeController::class,'storeContract'])->middleware('permission:hr.employee.manage')->name('employees.contracts.store');
    Route::post('/employees/{employee}/documents',[EmployeeController::class,'storeDocument'])->middleware('permission:hr.employee.manage')->name('employees.documents.store');

    Route::get('/attendance',[AttendanceController::class,'index'])->middleware('permission:hr.employee.read')->name('attendance.index');
    Route::post('/attendance',[AttendanceController::class,'store'])->middleware('permission:hr.employee.manage')->name('attendance.store');
    Route::post('/attendance/schedules',[AttendanceController::class,'storeSchedule'])->middleware('permission:hr.employee.manage')->name('attendance.schedules.store');
    Route::post('/attendance/employees/{employee}/schedule',[AttendanceController::class,'assignSchedule'])->middleware('permission:hr.employee.manage')->name('attendance.employees.schedule');

    Route::get('/leave',[LeaveController::class,'index'])->middleware('permission:hr.employee.read')->name('leave.index');
    Route::post('/leave',[LeaveController::class,'store'])->middleware('permission:hr.employee.manage')->name('leave.store');
    Route::post('/leave/policies',[LeaveController::class,'storePolicy'])->middleware('permission:hr.employee.manage')->name('leave.policies.store');
    Route::post('/leave/balances',[LeaveController::class,'seedBalance'])->middleware('permission:hr.employee.manage')->name('leave.balances.store');
    Route::post('/leave/balances/{balance}/accrue',[LeaveController::class,'accrue'])->middleware('permission:hr.employee.manage')->name('leave.balances.accrue');
    Route::post('/leave/{leave}/decide',[LeaveController::class,'decide'])->middleware('permission:workflow.approval.decide')->name('leave.decide');
});

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
