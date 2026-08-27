<?php

use App\Http\Controllers\CRM\CustomerAcquisitionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('crm/acquisition')->name('crm.acquisition.')->group(function(){
    Route::get('/',[CustomerAcquisitionController::class,'index'])->name('index');
    Route::post('/leads',[CustomerAcquisitionController::class,'store'])->name('store');
    Route::post('/leads/{lead}/stage',[CustomerAcquisitionController::class,'stage'])->name('stage');
    Route::post('/leads/{lead}/follow-up',[CustomerAcquisitionController::class,'followUp'])->name('follow-up');
    Route::post('/leads/{lead}/request-conversion',[CustomerAcquisitionController::class,'requestConversion'])->name('request-conversion');
    Route::post('/leads/{lead}/review-conversion',[CustomerAcquisitionController::class,'reviewConversion'])->name('review-conversion');
    Route::post('/leads/{lead}/convert',[CustomerAcquisitionController::class,'convert'])->name('convert');
    Route::post('/customers/{customer}/review-onboarding',[CustomerAcquisitionController::class,'reviewOnboarding'])->name('review-onboarding');
});
