<?php

use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\FieldServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::prefix('field')->name('field.')->group(function () {
        Route::get('/operations',[FieldServiceController::class,'operations'])->name('operations');
        Route::post('/assignments',[FieldServiceController::class,'assign'])->name('assign');
        Route::post('/inspection-templates',[FieldServiceController::class,'storeTemplate'])->name('templates.store');
        Route::get('/technician',[FieldServiceController::class,'technician'])->name('technician');
        Route::post('/technician/assignments/{assignment}/status',[FieldServiceController::class,'technicianStatus'])->name('technician.status');
        Route::post('/inspections',[FieldServiceController::class,'inspect'])->name('inspections.store');
        Route::get('/assistant',[FieldServiceController::class,'assistant'])->name('assistant');
        Route::post('/assistant',[FieldServiceController::class,'askAssistant'])->name('assistant.ask');
    });
    Route::get('/customer/profile',[CustomerProfileController::class,'edit'])->name('customer.profile.edit');
    Route::put('/customer/profile',[CustomerProfileController::class,'update'])->name('customer.profile.update');
});
