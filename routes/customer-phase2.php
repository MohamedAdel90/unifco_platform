<?php

use App\Http\Controllers\CustomerActionCenterController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/customer/actions', CustomerActionCenterController::class)->name('customer.actions');
});
