<?php

use App\Http\Controllers\HR\PerformanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('hr/performance')->name('hr.performance.')->group(function () {
    Route::get('/',[PerformanceController::class,'index'])->middleware('permission:hr.employee.read')->name('index');
    Route::post('/cycles',[PerformanceController::class,'storeCycle'])->middleware('permission:hr.employee.manage')->name('cycles.store');
    Route::get('/employees/{employee}',[PerformanceController::class,'employee'])->middleware('permission:hr.employee.read')->name('employees.show');
    Route::post('/employees/{employee}/goals',[PerformanceController::class,'storeGoal'])->middleware('permission:hr.employee.manage')->name('goals.store');
    Route::post('/employees/{employee}/goals/{goal}',[PerformanceController::class,'updateGoal'])->middleware('permission:hr.employee.manage')->name('goals.update');
    Route::post('/employees/{employee}/reviews',[PerformanceController::class,'storeReview'])->middleware('permission:hr.employee.manage')->name('reviews.store');
    Route::post('/reviews/{review}/submit',[PerformanceController::class,'submitReview'])->middleware('permission:hr.employee.manage')->name('reviews.submit');
    Route::post('/reviews/{review}/decide',[PerformanceController::class,'decideReview'])->middleware('permission:workflow.approval.decide')->name('reviews.decide');
    Route::post('/employees/{employee}/probation',[PerformanceController::class,'storeProbation'])->middleware('permission:hr.employee.manage')->name('probation.store');
    Route::post('/employees/{employee}/development',[PerformanceController::class,'storeDevelopment'])->middleware('permission:hr.employee.manage')->name('development.store');
    Route::post('/employees/{employee}/skills',[PerformanceController::class,'storeSkill'])->middleware('permission:hr.employee.manage')->name('skills.store');
    Route::post('/employees/{employee}/career-actions',[PerformanceController::class,'storeCareerAction'])->middleware('permission:hr.employee.manage')->name('career.store');
    Route::post('/career-actions/{action}/decide',[PerformanceController::class,'decideCareerAction'])->middleware('permission:workflow.approval.decide')->name('career.decide');
    Route::post('/career-actions/{action}/apply',[PerformanceController::class,'applyCareerAction'])->middleware('permission:hr.employee.manage')->name('career.apply');
});
