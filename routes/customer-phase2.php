<?php

use App\Http\Controllers\{CustomerActionCenterController,CustomerPortalPhase2ActionController};
use App\Http\Controllers\Workflow\CustomerActionInboxController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/customer/actions', CustomerActionCenterController::class)->name('customer.actions');
    Route::post('/customer/contracts/{contract}/renewal-request', [CustomerPortalPhase2ActionController::class,'requestContractRenewal'])->name('customer.contracts.renewal-request');
    Route::post('/customer/invoices/{invoice}/query', [CustomerPortalPhase2ActionController::class,'invoiceQuery'])->name('customer.invoices.query');
    Route::post('/customer/invoices/{invoice}/payment-proof', [CustomerPortalPhase2ActionController::class,'paymentProof'])->name('customer.invoices.payment-proof');
    Route::post('/customer/work-orders/{workOrder}/revisit', [CustomerPortalPhase2ActionController::class,'requestRevisit'])->name('customer.work-orders.revisit');

    Route::get('/workflow/customer-actions', [CustomerActionInboxController::class,'index'])->name('workflow.customer-actions.index');
    Route::post('/workflow/customer-actions/{action}/resolve', [CustomerActionInboxController::class,'resolve'])->name('workflow.customer-actions.resolve');
    Route::get('/workflow/customer-actions/{action}/attachment', [CustomerActionInboxController::class,'attachment'])->name('workflow.customer-actions.attachment');
});
