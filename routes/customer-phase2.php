<?php

use App\Http\Controllers\{CustomerActionCenterController,CustomerPortalPhase2ActionController};
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/customer/actions', CustomerActionCenterController::class)->name('customer.actions');
    Route::post('/customer/contracts/{contract}/renewal-request', [CustomerPortalPhase2ActionController::class,'requestContractRenewal'])->name('customer.contracts.renewal-request');
    Route::post('/customer/invoices/{invoice}/query', [CustomerPortalPhase2ActionController::class,'invoiceQuery'])->name('customer.invoices.query');
});
