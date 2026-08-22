<?php

use App\Http\Controllers\NavigationWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('workspace')->name('workspace.')->group(function () {
    Route::get('/{workspace}',[NavigationWorkspaceController::class,'show'])->name('show');
});
