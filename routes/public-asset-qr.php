<?php

use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/service-assets/lookup', [PublicSiteController::class, 'assetLookup'])
    ->middleware('throttle:30,1')
    ->name('public.asset.lookup');
