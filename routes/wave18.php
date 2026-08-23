<?php
use App\Http\Controllers\HR\HrAnalyticsController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','permission:hr.employee.read'])->prefix('hr/analytics')->name('hr.analytics.')->group(function(){
    Route::get('/',[HrAnalyticsController::class,'index'])->name('index');
    Route::post('/workforce-plan',[HrAnalyticsController::class,'storePlan'])->middleware('permission:hr.employee.manage')->name('plans.store');
});
