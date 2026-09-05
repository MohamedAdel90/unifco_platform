<?php

use App\Http\Controllers\Admin\HomepageSectionController;
use App\Models\HomepageSection;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/maintenance-cms', function () {
        $section = HomepageSection::query()->where('section_key', 'operations')->firstOrFail();
        app(HomepageSectionController::class)->edit(request(), $section); // keeps the same admin authorization rule

        return view('admin.portal-cms.edit', [
            'section' => $section,
            'cmsType' => 'maintenance',
            'cmsTitle' => 'Maintenance CMS',
            'publicUrl' => route('public.current-maintenance'),
        ]);
    })->name('maintenance-cms');

    Route::get('/client-portal-cms', function () {
        $section = HomepageSection::query()->where('section_key', 'operations')->firstOrFail();
        app(HomepageSectionController::class)->edit(request(), $section); // keeps the same admin authorization rule

        return view('admin.portal-cms.edit', [
            'section' => $section,
            'cmsType' => 'portal',
            'cmsTitle' => 'Client Portal CMS',
            'publicUrl' => route('customer.portal'),
        ]);
    })->name('client-portal-cms');
});
