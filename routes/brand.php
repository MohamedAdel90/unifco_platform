<?php

use App\Http\Controllers\BrandAssetController;
use Illuminate\Support\Facades\Route;

Route::get('/brand/unifco-logo-v2.webp', [BrandAssetController::class, 'logo'])->name('brand.logo');
