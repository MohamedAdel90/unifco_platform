<?php

use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\CmsImageTranslationController;
use App\Http\Controllers\Admin\HomepageSectionController;
use App\Http\Controllers\Admin\HomepageProjectController;
use App\Http\Controllers\Admin\HomepageClientController;
use App\Http\Controllers\Admin\HomepageImageController;
use App\Http\Controllers\Admin\TemporaryFileController;
use App\Http\Controllers\Admin\UserAdministrationController;
use App\Http\Controllers\Admin\{ImpersonationController,SystemAdminDashboardController,SystemOperationsController};
use App\Http\Controllers\Admin\AccessControlController;
use App\Http\Controllers\Admin\SystemCatalogController;
use App\Http\Controllers\NavigationWorkspaceController;
use App\Http\Controllers\Operations\{OperationsManagerDashboardController,ServiceRequestOperationsController};
use Illuminate\Support\Facades\Route;

Route::get('/temporary-files/{token}', [TemporaryFileController::class, 'show'])
    ->whereUuid('token')
    ->name('temporary-files.show');

Route::middleware('auth')->group(function () {
    Route::get('/system-admin',SystemAdminDashboardController::class)->middleware('permission:system.dashboard.view')->name('system-admin.dashboard');
    Route::get('/operations-manager',OperationsManagerDashboardController::class)->middleware('permission:operations.dashboard.view')->name('operations-manager.dashboard');
    Route::prefix('operations-manager')->name('operations-manager.')->group(function () {
        Route::get('/service-requests',[ServiceRequestOperationsController::class,'index'])->middleware('permission:service_requests.read')->name('service-requests.index');
        Route::post('/service-requests/{serviceRequest}/assign',[ServiceRequestOperationsController::class,'assign'])->middleware('permission:service_requests.assign')->name('service-requests.assign');
        Route::post('/service-requests/{serviceRequest}/escalate',[ServiceRequestOperationsController::class,'escalate'])->middleware('permission:service_requests.escalate')->name('service-requests.escalate');
    });
    Route::post('/admin/users/{user}/impersonate',[ImpersonationController::class,'start'])->middleware('permission:impersonation.read_only')->name('admin.impersonation.start');
    Route::delete('/admin/impersonation',[ImpersonationController::class,'stop'])->name('admin.impersonation.stop');
    Route::prefix('admin/system')->name('admin.system.')->group(function(){
        Route::get('/sessions',[SystemOperationsController::class,'sessions'])->name('sessions');
        Route::get('/login-activity',[SystemOperationsController::class,'sessions'])->name('login-activity');
        Route::post('/sessions/{session}/revoke',[SystemOperationsController::class,'revokeSession'])->name('sessions.revoke');
        Route::get('/security-events',[SystemOperationsController::class,'securityEvents'])->name('security-events');
        Route::get('/invitations',[SystemOperationsController::class,'invitations'])->name('invitations');
        Route::get('/scheduled-jobs',[SystemOperationsController::class,'scheduledJobs'])->name('scheduled-jobs');
        Route::post('/scheduled-jobs/{job}/retry',[SystemOperationsController::class,'retryJob'])->name('scheduled-jobs.retry');
        Route::view('/integrations','admin.system.integrations')->middleware('permission:integrations.view')->name('integrations');
        Route::view('/scope-enforcement-audit','admin.system.scope-enforcement-audit')->middleware('permission:scope.audit.view')->name('scope-audit');
        Route::get('/organization',[SystemCatalogController::class,'organization'])->name('organization');
        Route::post('/organizations',[SystemCatalogController::class,'storeOrganization'])->name('organizations.store');
        Route::post('/job-positions',[SystemCatalogController::class,'storePosition'])->name('job-positions.store');
        Route::get('/master-data',[SystemCatalogController::class,'masterData'])->name('master-data');
        Route::post('/master-data',[SystemCatalogController::class,'storeMasterData'])->name('master-data.store');
        Route::post('/master-data/{entry}/status',[SystemCatalogController::class,'masterDataStatus'])->name('master-data.status');
        Route::get('/email-templates',[SystemCatalogController::class,'emailTemplates'])->name('email-templates');
        Route::post('/email-templates',[SystemCatalogController::class,'storeEmailTemplate'])->name('email-templates.store');
    });
    Route::prefix('admin/access-control')->name('admin.access-control.')->middleware('permission:roles.view')->group(function(){
        Route::get('/',[AccessControlController::class,'index'])->name('index');
        Route::post('/roles',[AccessControlController::class,'role'])->middleware('permission:roles.manage')->name('roles.store');
        Route::post('/roles/{role}/status',[AccessControlController::class,'roleStatus'])->middleware('permission:roles.manage')->name('roles.status');
        Route::post('/scopes',[AccessControlController::class,'scope'])->middleware('permission:scopes.manage')->name('scopes.store');
        Route::post('/scopes/{scope}/status',[AccessControlController::class,'scopeStatus'])->middleware('permission:scopes.manage')->name('scopes.status');
        Route::post('/approval-authorities',[AccessControlController::class,'authority'])->middleware('permission:approval_authorities.manage')->name('authorities.store');
        Route::post('/approval-authorities/{authority}/status',[AccessControlController::class,'authorityStatus'])->middleware('permission:approval_authorities.manage')->name('authorities.status');
        Route::post('/invitations/{invitation}/revoke',[AccessControlController::class,'revokeInvitation'])->middleware('permission:invitations.manage')->name('invitations.revoke');
        Route::post('/invitations/{invitation}/resend',[AccessControlController::class,'resendInvitation'])->middleware('permission:invitations.manage')->name('invitations.resend');
    });
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
        Route::post('/projects/{project}/toggle', [HomepageProjectController::class,'toggle'])->name('projects.toggle');
        Route::get('/clients', [HomepageClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [HomepageClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [HomepageClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}/edit', [HomepageClientController::class, 'edit'])->name('clients.edit');
        Route::put('/clients/{client}', [HomepageClientController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{client}', [HomepageClientController::class, 'destroy'])->name('clients.destroy');
        Route::post('/clients/{client}/toggle', [HomepageClientController::class,'toggle'])->name('clients.toggle');
        Route::get('/images/list', [HomepageImageController::class, 'list'])->name('images.list');
        Route::post('/images/upload', [HomepageImageController::class, 'upload'])->name('images.upload');
        Route::post('/images/translate', [CmsImageTranslationController::class, 'translate'])->name('images.translate');
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
        Route::post('/{user}/sessions/{session}/revoke',[UserAdministrationController::class,'revokeSession'])->name('sessions.revoke');
    });
});
