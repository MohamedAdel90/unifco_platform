<?php

use App\Http\Controllers\HR\HrDocumentsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('hr')->name('hr.')->group(function () {
    Route::get('/self-service/documents',[HrDocumentsController::class,'employeeIndex'])->name('self-service.documents.index');
    Route::post('/self-service/documents',[HrDocumentsController::class,'storeEmployeeRequest'])->name('self-service.documents.store');

    Route::get('/documents',[HrDocumentsController::class,'index'])->middleware('permission:hr.employee.read')->name('documents.index');
    Route::post('/documents/templates',[HrDocumentsController::class,'storeTemplate'])->middleware('permission:hr.employee.manage')->name('documents.templates.store');
    Route::post('/documents/{service}/decide',[HrDocumentsController::class,'decide'])->middleware('permission:workflow.approval.decide')->name('documents.decide');
    Route::post('/documents/{service}/issue',[HrDocumentsController::class,'issue'])->middleware('permission:hr.employee.manage')->name('documents.issue');
    Route::get('/documents/{service}',[HrDocumentsController::class,'show'])->name('documents.show');
    Route::get('/documents/{service}/print',[HrDocumentsController::class,'printable'])->name('documents.print');
});
