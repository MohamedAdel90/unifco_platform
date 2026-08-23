<?php

use App\Http\Controllers\HR\SelfServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('hr')->name('hr.')->group(function () {
    Route::get('/self-service',[SelfServiceController::class,'employeePortal'])->name('self-service.index');
    Route::post('/self-service/leave',[SelfServiceController::class,'requestLeave'])->name('self-service.leave.store');
    Route::post('/self-service/missions',[SelfServiceController::class,'requestMission'])->name('self-service.missions.store');
    Route::post('/self-service/goals/{goal}',[SelfServiceController::class,'updateGoal'])->name('self-service.goals.update');

    Route::get('/manager-self-service',[SelfServiceController::class,'managerPortal'])->name('manager-self-service.index');
    Route::post('/manager-self-service/leave/{leave}/decide',[SelfServiceController::class,'decideTeamLeave'])->name('manager-self-service.leave.decide');
    Route::post('/manager-self-service/missions/{mission}/decide',[SelfServiceController::class,'decideTeamMission'])->name('manager-self-service.missions.decide');
});
