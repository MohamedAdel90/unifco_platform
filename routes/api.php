<?php

use App\Http\Controllers\Api\{IdentityApiController,PlatformApiController};
use Illuminate\Support\Facades\Route;

Route::post('/v1/auth/login',[IdentityApiController::class,'login']);
Route::middleware('jwt')->prefix('v1')->group(function () {
    Route::get('/auth/me',[IdentityApiController::class,'me']);
    Route::get('/identity/users',[IdentityApiController::class,'users']);
    Route::post('/identity/users',[IdentityApiController::class,'storeUser']);
    Route::patch('/identity/users/{user}',[IdentityApiController::class,'updateUser']);
    Route::get('/identity/permissions',[IdentityApiController::class,'permissions']);
    Route::post('/identity/permissions',[IdentityApiController::class,'grantPermission']);
    Route::delete('/identity/permissions/{id}',[IdentityApiController::class,'revokePermission']);
    Route::get('/status',[PlatformApiController::class,'status']);
    Route::get('/summary',[PlatformApiController::class,'summary']);
});

// Legacy scoped API tokens remain supported for backward-compatible integrations.
Route::middleware('api.token:status')->get('/v1/legacy/status',[PlatformApiController::class,'status']);
Route::middleware('api.token:summary')->get('/v1/legacy/summary',[PlatformApiController::class,'summary']);
