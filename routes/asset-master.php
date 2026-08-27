<?php

use App\Http\Controllers\Maintenance\AssetMasterController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('asset-master')->name('asset-master.')->group(function(){
    Route::get('/',[AssetMasterController::class,'index'])->name('index');
    Route::post('/',[AssetMasterController::class,'store'])->name('store');
    Route::post('/templates',[AssetMasterController::class,'storeTemplate'])->name('templates.store');
    Route::get('/{asset}',[AssetMasterController::class,'show'])->name('show');
    Route::put('/{asset}',[AssetMasterController::class,'update'])->name('update');
    Route::post('/{asset}/verify',[AssetMasterController::class,'verify'])->name('verify');
    Route::post('/{asset}/documents',[AssetMasterController::class,'document'])->name('documents.store');
    Route::get('/documents/{document}/download',[AssetMasterController::class,'download'])->name('documents.download');
});
