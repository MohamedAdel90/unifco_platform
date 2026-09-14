<?php

namespace App\Providers;

use App\Http\Controllers\Admin\UatResetLinkController;
use App\Http\Controllers\Auth\UatResetController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class UatResetServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(function () {
            Route::post('/admin/users/{user}/uat-reset-link',UatResetLinkController::class)
                ->middleware(['auth','permission:users.reset_password'])
                ->name('admin.users.uat-reset-link');

            Route::get('/uat-reset/{token}',[UatResetController::class,'show'])
                ->name('uat-reset.show');
            Route::post('/uat-reset/{token}',[UatResetController::class,'update'])
                ->name('uat-reset.update');
        });
    }
}
