<?php

use App\Http\Controllers\Reporting\{ExecutiveReportController,ReportSubscriptionController};
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('reports')->name('reporting.')->group(function () {
    Route::get('/executive.csv',[ExecutiveReportController::class,'csv'])->middleware('permission:reporting.executive.read')->name('executive.csv');
    Route::get('/subscriptions',[ReportSubscriptionController::class,'index'])->middleware('permission:reporting.executive.read')->name('subscriptions.index');
    Route::post('/subscriptions',[ReportSubscriptionController::class,'store'])->middleware('permission:reporting.executive.read')->name('subscriptions.store');
    Route::delete('/subscriptions/{subscription}',[ReportSubscriptionController::class,'destroy'])->middleware('permission:reporting.executive.read')->name('subscriptions.destroy');
});
