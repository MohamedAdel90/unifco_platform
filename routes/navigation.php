<?php

use App\Http\Controllers\Admin\UserAdministrationController;
use App\Http\Controllers\NavigationWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::prefix('workspace')->name('workspace.')->group(function () {
        Route::get('/{workspace}',[NavigationWorkspaceController::class,'show'])->name('show');
    });
    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/export/csv',[UserAdministrationController::class,'export'])->name('export');
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
    });
});
