<?php

use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\HomepageSectionController;
use App\Http\Controllers\Admin\HomepageProjectController;
use App\Http\Controllers\Admin\HomepageClientController;
use App\Http\Controllers\Admin\HomepageImageController;
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
    Route::prefix('admin/homepage')->name('admin.homepage.')->group(function () {
        Route::get('/sections', [HomepageSectionController::class, 'index'])->name('sections.index');
        Route::get('/sections/{section}/edit', [HomepageSectionController::class, 'edit'])->name('sections.edit');
        Route::post('/sections/{section}/preview', [HomepageSectionController::class, 'preview'])->name('sections.preview');
        Route::put('/sections/{section}', [HomepageSectionController::class, 'update'])->name('sections.update');
        Route::post('/sections/{section}/toggle', [HomepageSectionController::class, 'toggle'])->name('sections.toggle');
        Route::get('/projects', [HomepageProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/create', [HomepageProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects', [HomepageProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}/edit', [HomepageProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{project}', [HomepageProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [HomepageProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('/projects/{project}/toggle', [HomepageProjectController::class, 'toggle'])->name('projects.toggle');
        Route::get('/clients', [HomepageClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [HomepageClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [HomepageClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}/edit', [HomepageClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{client}', [HomepageClientController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{client}', [HomepageClientController::class, 'destroy'])->name('clients.destroy');
        Route::post('/clients/{client}/toggle', [HomepageClientController::class, 'toggle'])->name('clients.toggle');
        Route::get('/images/list', [HomepageImageController::class, 'list'])->name('images.list');
        Route::post('/images/upload', [HomepageImageController::class, 'upload'])->name('images.upload');
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
