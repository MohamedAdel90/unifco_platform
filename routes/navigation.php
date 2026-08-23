<?php

use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\TemporaryFileController;
use App\Http\Controllers\Admin\UserAdministrationController;
use App\Http\Controllers\NavigationWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/temporary-files/{token}', [TemporaryFileController::class, 'show'])
    ->whereUuid('token')
    ->name('temporary-files.show');

Route::middleware('auth')->group(function () {
    Route::get('/admin', fn () => redirect()->route('admin.temporary-files.index'))->name('admin.index');
    Route::prefix('workspace')->name('workspace.')->group(function () {
        Route::get('/skills-certifications',fn()=>redirect()->route('hr.performance.index'))->name('skills-certifications');
        Route::get('/system-settings',fn()=>redirect()->route('admin.branding.index'))->name('system-settings');
        Route::get('/{workspace}',[NavigationWorkspaceController::class,'show'])->name('show');
    });
    Route::prefix('admin/branding')->name('admin.branding.')->group(function () {
        Route::get('/',[BrandingController::class,'index'])->name('index');
        Route::post('/logo',[BrandingController::class,'update'])->name('update');
        Route::post('/reset',[BrandingController::class,'reset'])->name('reset');
    });
    Route::prefix('admin/temporary-files')->name('admin.temporary-files.')->group(function () {
        Route::get('/', [TemporaryFileController::class, 'index'])->name('index');
        Route::post('/', [TemporaryFileController::class, 'store'])->name('store');
        Route::delete('/{token}', [TemporaryFileController::class, 'destroy'])->whereUuid('token')->name('destroy');
    });
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/export/csv',[UserAdministrationController::class,'export'])->name('export');
        Route::post('/import',[UserAdministrationController::class,'import'])->name('import');
        Route::post('/bulk',[UserAdministrationController::class,'bulk'])->name('bulk');
        Route::get('/create',[UserAdministrationController::class,'create'])->name('create');
        Route::post('/',[UserAdministrationController::class,'store'])->name('store');
        Route::get('/{user}',[UserAdministrationController::class,'show'])->name('show');
        Route::get('/{user}/edit',[UserAdministrationController::class,'edit'])->name('edit');
        Route::put('/{user}',[UserAdministrationController::class,'update'])->name('update');
        Route::post('/{user}/status',[UserAdministrationController::class,'status'])->name('status');
        Route::post('/{user}/security',[UserAdministrationController::class,'security'])->name('security');
        Route::post('/{user}/reset-password',[UserAdministrationController::class,'resetPassword'])->name('reset-password');
        Route::post('/{user}/permission',[UserAdministrationController::class,'permission'])->name('permission');
        Route::post('/{user}/api-tokens/{token}/revoke',[UserAdministrationController::class,'revokeToken'])->name('api-tokens.revoke');
    });
});
