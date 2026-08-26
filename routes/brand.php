<?php

use App\Http\Controllers\BrandAssetController;
use Illuminate\Support\Facades\Route;

// Keep the current v3 URL as the canonical route while preserving the v2
// endpoint used by existing pages, integrations and release qualification.
Route::get('/brand/unifco-logo-v3.webp', [BrandAssetController::class, 'logo'])->name('brand.logo');
Route::get('/brand/unifco-logo-v2.webp', [BrandAssetController::class, 'logo'])->name('brand.logo.v2');
