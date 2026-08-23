<?php
use App\Http\Controllers\HR\HrComplianceController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','permission:hr.employee.read'])->prefix('hr/compliance')->name('hr.compliance.')->group(function(){
 Route::get('/',[HrComplianceController::class,'index'])->name('index');
 Route::post('/profile',[HrComplianceController::class,'updateProfile'])->middleware('permission:hr.employee.manage')->name('profile.update');
 Route::post('/scan',[HrComplianceController::class,'scan'])->middleware('permission:hr.employee.manage')->name('scan');
 Route::post('/employees/{employee}/gosi',[HrComplianceController::class,'updateEmployeeRegistration'])->middleware('permission:hr.employee.manage')->name('employees.gosi');
 Route::post('/contracts/{contract}/qiwa',[HrComplianceController::class,'updateQiwa'])->middleware('permission:hr.employee.manage')->name('contracts.qiwa');
 Route::post('/cases/{case}',[HrComplianceController::class,'updateCase'])->middleware('permission:hr.employee.manage')->name('cases.update');
});
