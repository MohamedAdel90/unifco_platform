<?php

use App\Http\Controllers\Admin\CmsTranslationController;
use App\Http\Controllers\Admin\HomepageSectionController;
use App\Models\HomepageSection;
use App\Services\HomepageContentService;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/cms/translate-ar-en', [CmsTranslationController::class, 'translate'])
        ->name('cms.translate-ar-en');

    Route::post('/maintenance-cms/toggle', function () {
        abort_unless(request()->user()?->role === 'ADMIN', 403);
        $section = HomepageSection::query()->where('section_key', 'operations')->firstOrFail();
        $ar = $section->data_ar ?? [];
        $en = $section->data_en ?? [];
        $enabled = (bool) ($ar['maintenance_enabled'] ?? true);
        $ar['maintenance_enabled'] = ! $enabled;
        $en['maintenance_enabled'] = ! $enabled;
        $section->update(['data_ar' => $ar, 'data_en' => $en]);
        HomepageContentService::clearAllCache();

        return back()->with('status', 'Maintenance '.($enabled ? 'disabled' : 'enabled').'.');
    })->name('maintenance-cms.toggle');

    Route::post('/client-portal-cms/toggle', function () {
        abort_unless(request()->user()?->role === 'ADMIN', 403);
        $section = HomepageSection::query()->where('section_key', 'operations')->firstOrFail();
        $ar = $section->data_ar ?? [];
        $en = $section->data_en ?? [];
        $enabled = (bool) ($ar['portal_enabled'] ?? true);
        $ar['portal_enabled'] = ! $enabled;
        $en['portal_enabled'] = ! $enabled;
        $section->update(['data_ar' => $ar, 'data_en' => $en]);
        HomepageContentService::clearAllCache();

        return back()->with('status', 'Client Portal '.($enabled ? 'disabled' : 'enabled').'.');
    })->name('client-portal-cms.toggle');

    Route::get('/maintenance-cms', function () {
        $section = HomepageSection::query()->where('section_key', 'operations')->firstOrFail();
        app(HomepageSectionController::class)->edit(request(), $section);

        return view('admin.portal-cms.edit', [
            'section' => $section,
            'cmsType' => 'maintenance',
            'cmsTitle' => 'Maintenance CMS',
            'publicUrl' => route('public.current-maintenance'),
        ]);
    })->name('maintenance-cms');

    Route::get('/client-portal-cms', function () {
        $section = HomepageSection::query()->where('section_key', 'operations')->firstOrFail();
        app(HomepageSectionController::class)->edit(request(), $section);

        return view('admin.portal-cms.edit', [
            'section' => $section,
            'cmsType' => 'portal',
            'cmsTitle' => 'Client Portal CMS',
            'publicUrl' => route('customer.portal'),
        ]);
    })->name('client-portal-cms');
});
