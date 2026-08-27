<?php

use App\Http\Controllers\Maintenance\{AssetMasterController,AssetMaintenanceIntelligenceController};
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('asset-master')->name('asset-master.')->group(function(){
    Route::get('/',[AssetMasterController::class,'index'])->name('index');
    Route::post('/',[AssetMasterController::class,'store'])->name('store');
    Route::post('/templates',[AssetMasterController::class,'storeTemplate'])->name('templates.store');
    Route::post('/locations',[AssetMasterController::class,'storeLocation'])->name('locations.store');
    Route::get('/{asset}',[AssetMasterController::class,'show'])->name('show');
    Route::put('/{asset}',[AssetMasterController::class,'update'])->name('update');
    Route::post('/{asset}/verify',[AssetMasterController::class,'verify'])->name('verify');
    Route::post('/{asset}/transition',[AssetMasterController::class,'transition'])->name('transition');
    Route::post('/{asset}/assign-location',[AssetMasterController::class,'assignLocation'])->name('assign-location');
    Route::post('/{asset}/commissioning',[AssetMasterController::class,'requestCommissioning'])->name('commissioning.request');
    Route::post('/{asset}/commissioning/{record}/review',[AssetMasterController::class,'reviewCommissioning'])->name('commissioning.review');
    Route::post('/{asset}/documents',[AssetMasterController::class,'document'])->name('documents.store');
    Route::get('/documents/{document}/download',[AssetMasterController::class,'download'])->name('documents.download');

    Route::get('/{asset}/intelligence',[AssetMaintenanceIntelligenceController::class,'show'])->name('intelligence.show');
    Route::post('/{asset}/intelligence/meter',[AssetMaintenanceIntelligenceController::class,'meter'])->name('intelligence.meter');
    Route::post('/{asset}/intelligence/recalculate',[AssetMaintenanceIntelligenceController::class,'recalculate'])->name('intelligence.recalculate');
});
