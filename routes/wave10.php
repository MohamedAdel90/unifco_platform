<?php

use App\Http\Controllers\HR\{AttendanceController,BusinessTripController,EmployeeController,FinalSettlementController,HrDashboardController,HrOperationsController,LeaveController,PayrollController,RecruitmentController};
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

    Route::get('/payroll',[PayrollController::class,'index'])->middleware('permission:hr.employee.read')->name('payroll.index');
    Route::post('/payroll/policies',[PayrollController::class,'storePolicy'])->middleware('permission:hr.employee.manage')->name('payroll.policies.store');
    Route::post('/payroll/runs',[PayrollController::class,'createRun'])->middleware('permission:hr.employee.manage')->name('payroll.runs.store');
    Route::get('/payroll/{payroll}',[PayrollController::class,'show'])->middleware('permission:hr.employee.read')->name('payroll.show');
    Route::post('/payroll/{payroll}/recalculate',[PayrollController::class,'recalculate'])->middleware('permission:hr.employee.manage')->name('payroll.recalculate');
    Route::post('/payroll/{payroll}/post',[PayrollController::class,'post'])->middleware('permission:finance.journal.post')->name('payroll.post');

    Route::get('/missions',[BusinessTripController::class,'index'])->middleware('permission:hr.employee.read')->name('missions.index');
    Route::post('/missions',[BusinessTripController::class,'store'])->middleware('permission:hr.employee.manage')->name('missions.store');
    Route::get('/missions/{mission}',[BusinessTripController::class,'show'])->middleware('permission:hr.employee.read')->name('missions.show');
    Route::post('/missions/{mission}/decide',[BusinessTripController::class,'decide'])->middleware('permission:workflow.approval.decide')->name('missions.decide');
    Route::post('/missions/{mission}/advance',[BusinessTripController::class,'advance'])->middleware('permission:hr.employee.manage')->name('missions.advance');
    Route::post('/missions/{mission}/expenses',[BusinessTripController::class,'storeExpense'])->middleware('permission:hr.employee.manage')->name('missions.expenses.store');
    Route::post('/missions/{mission}/expenses/{expense}/decide',[BusinessTripController::class,'decideExpense'])->middleware('permission:workflow.approval.decide')->name('missions.expenses.decide');
    Route::post('/missions/{mission}/complete',[BusinessTripController::class,'complete'])->middleware('permission:hr.employee.manage')->name('missions.complete');
    Route::post('/missions/{mission}/settle',[BusinessTripController::class,'settle'])->middleware('permission:hr.employee.manage')->name('missions.settle');

    Route::get('/settlements',[FinalSettlementController::class,'index'])->middleware('permission:hr.employee.read')->name('settlements.index');
    Route::post('/settlements',[FinalSettlementController::class,'store'])->middleware('permission:hr.employee.manage')->name('settlements.store');
    Route::get('/settlements/{settlement}',[FinalSettlementController::class,'show'])->middleware('permission:hr.employee.read')->name('settlements.show');
    Route::post('/settlements/{settlement}/recalculate',[FinalSettlementController::class,'recalculate'])->middleware('permission:hr.employee.manage')->name('settlements.recalculate');
    Route::post('/settlements/{settlement}/submit',[FinalSettlementController::class,'submit'])->middleware('permission:hr.employee.manage')->name('settlements.submit');
    Route::post('/settlements/{settlement}/decide',[FinalSettlementController::class,'decide'])->middleware('permission:workflow.approval.decide')->name('settlements.decide');
    Route::post('/settlements/{settlement}/paid',[FinalSettlementController::class,'markPaid'])->middleware('permission:finance.journal.post')->name('settlements.paid');

    Route::get('/recruitment',[RecruitmentController::class,'index'])->middleware('permission:hr.employee.read')->name('recruitment.index');
    Route::post('/recruitment/requisitions',[RecruitmentController::class,'storeRequisition'])->middleware('permission:hr.employee.manage')->name('recruitment.requisitions.store');
    Route::post('/recruitment/requisitions/{requisition}/decide',[RecruitmentController::class,'decideRequisition'])->middleware('permission:workflow.approval.decide')->name('recruitment.requisitions.decide');
    Route::post('/recruitment/requisitions/{requisition}/vacancies',[RecruitmentController::class,'openVacancy'])->middleware('permission:hr.employee.manage')->name('recruitment.vacancies.store');
    Route::post('/recruitment/candidates',[RecruitmentController::class,'storeCandidate'])->middleware('permission:hr.employee.manage')->name('recruitment.candidates.store');
    Route::get('/recruitment/candidates/{candidate}',[RecruitmentController::class,'showCandidate'])->middleware('permission:hr.employee.read')->name('recruitment.candidates.show');
    Route::post('/recruitment/candidates/{candidate}/stage',[RecruitmentController::class,'updateStage'])->middleware('permission:hr.employee.manage')->name('recruitment.candidates.stage');
    Route::post('/recruitment/candidates/{candidate}/interviews',[RecruitmentController::class,'scheduleInterview'])->middleware('permission:hr.employee.manage')->name('recruitment.interviews.store');
    Route::post('/recruitment/candidates/{candidate}/interviews/{interview}/decide',[RecruitmentController::class,'decideInterview'])->middleware('permission:hr.employee.manage')->name('recruitment.interviews.decide');
    Route::post('/recruitment/candidates/{candidate}/offer',[RecruitmentController::class,'createOffer'])->middleware('permission:hr.employee.manage')->name('recruitment.offers.store');
    Route::post('/recruitment/candidates/{candidate}/offer/decide',[RecruitmentController::class,'decideOffer'])->middleware('permission:hr.employee.manage')->name('recruitment.offers.decide');
    Route::post('/recruitment/candidates/{candidate}/hire',[RecruitmentController::class,'hire'])->middleware('permission:hr.employee.manage')->name('recruitment.hire');
    Route::post('/recruitment/onboarding/{task}/complete',[RecruitmentController::class,'completeOnboardingTask'])->middleware('permission:hr.employee.manage')->name('recruitment.onboarding.complete');
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
