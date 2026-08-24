<?php

use App\Http\Controllers\PublicServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/services/{slug}', [PublicServiceController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('public.services.show');
