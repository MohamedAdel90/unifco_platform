<?php

use App\Http\Controllers\Api\PlatformApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.token:status')->get('/v1/status',[PlatformApiController::class,'status']);
Route::middleware('api.token:summary')->get('/v1/summary',[PlatformApiController::class,'summary']);
