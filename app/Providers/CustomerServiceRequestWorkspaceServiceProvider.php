<?php

namespace App\Providers;

use App\Http\Controllers\CustomerServiceRequestController;
use App\Http\Middleware\CustomerServiceRequestWorkspaceRedirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CustomerServiceRequestWorkspaceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        app('router')->pushMiddlewareToGroup('web',CustomerServiceRequestWorkspaceRedirect::class);

        Route::middleware(['web','auth'])->group(function(){
            Route::get('/customer/service-requests',[CustomerServiceRequestController::class,'index'])
                ->name('customer.service-requests.index');
            Route::get('/customer/service-requests/{serviceRequest}',[CustomerServiceRequestController::class,'show'])
                ->whereNumber('serviceRequest')
                ->name('customer.service-requests.show');
        });
    }
}
