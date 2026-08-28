<?php

use App\Http\Controllers\CustomerAssetGovernanceController;
use App\Http\Controllers\Maintenance\{AssetMasterController,AssetMaintenanceIntelligenceController,AssetCustodyController,AssetWarrantyInsuranceController,AgreedAssetIntelligenceController,AssetAcceptanceController};
use Illuminate\Support\Facades\Route;

// Legacy EAM entry points are kept only as compatibility redirects. The Professional
// Asset Master is the single governed asset system for creation and Asset 360.
Route::middleware('auth')->group(function(){
    Route::get('/eam/assets',fn()=>redirect()->route('asset-master.index'));
    Route::get('/eam/assets/create',fn()=>redirect()->route('asset-master.index'));
});

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
    Route::post('/{asset}/acceptance-profile',[AssetAcceptanceController::class,'updateProfile'])->name('acceptance-profile.update');
    Route::post('/{asset}/acceptance-documents',[AssetAcceptanceController::class,'document'])->name('acceptance-documents.store');
    Route::get('/documents/{document}/download',[AssetMasterController::class,'download'])->name('documents.download');

    Route::get('/{asset}/custody',[AssetCustodyController::class,'show'])->name('custody.show');
    Route::post('/{asset}/custody',[AssetCustodyController::class,'assign'])->name('custody.assign');
    Route::post('/{asset}/custody/{custody}/return',[AssetCustodyController::class,'return'])->name('custody.return');
    Route::post('/{asset}/transfers',[AssetCustodyController::class,'requestTransfer'])->name('transfers.request');
    Route::post('/{asset}/transfers/{transfer}/review',[AssetCustodyController::class,'review'])->name('transfers.review');

    Route::get('/{asset}/coverage',[AssetWarrantyInsuranceController::class,'show'])->name('coverage.show');
    Route::post('/{asset}/coverage',[AssetWarrantyInsuranceController::class,'store'])->name('coverage.store');
    Route::post('/{asset}/coverage/{coverage}/renew',[AssetWarrantyInsuranceController::class,'renew'])->name('coverage.renew');
    Route::post('/{asset}/coverage/{coverage}/claims',[AssetWarrantyInsuranceController::class,'submitClaim'])->name('coverage.claims.store');
    Route::post('/{asset}/coverage/claims/{claim}/review',[AssetWarrantyInsuranceController::class,'reviewClaim'])->name('coverage.claims.review');

    Route::get('/{asset}/intelligence',[AssetMaintenanceIntelligenceController::class,'show'])->name('intelligence.show');
    Route::post('/{asset}/intelligence/meter',[AssetMaintenanceIntelligenceController::class,'meter'])->name('intelligence.meter');
    Route::post('/{asset}/intelligence/recalculate',[AssetMaintenanceIntelligenceController::class,'recalculate'])->name('intelligence.recalculate');
    Route::post('/{asset}/intelligence/pm',[AgreedAssetIntelligenceController::class,'pm'])->name('intelligence.pm');
    Route::post('/{asset}/intelligence/inspection',[AgreedAssetIntelligenceController::class,'inspection'])->name('intelligence.inspection');
    Route::post('/{asset}/intelligence/failure',[AgreedAssetIntelligenceController::class,'failure'])->name('intelligence.failure');
    Route::get('/{asset}/intelligence/agreed-snapshot',[AgreedAssetIntelligenceController::class,'agreedSnapshot'])->name('intelligence.agreed-snapshot');
});

Route::middleware('auth')->prefix('customer-assets')->name('customer-assets.')->group(function(){
    Route::get('/governance',[CustomerAssetGovernanceController::class,'index'])->name('governance');
    Route::post('/submissions',[CustomerAssetGovernanceController::class,'store'])->name('submissions.store');
    Route::post('/import',[CustomerAssetGovernanceController::class,'import'])->name('import');
    Route::post('/submissions/{submission}/review',[CustomerAssetGovernanceController::class,'review'])->name('submissions.review');
    Route::get('/submissions/{submission}/audit',[CustomerAssetGovernanceController::class,'audit'])->name('submissions.audit');
});
