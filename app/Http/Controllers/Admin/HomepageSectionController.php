<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Services\HomepageContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageSectionController extends Controller
{
    public function index(Request $request): View
    {
        $this->adminOnly($request);
        $sections = HomepageSection::query()->orderBy('sort_order')->get();

        return view('admin.homepage.sections.index', compact('sections'));
    }

    public function edit(Request $request, HomepageSection $section): View
    {
        $this->adminOnly($request);

        return view('admin.homepage.sections.edit', ['section' => $section]);
    }

    public function update(Request $request, HomepageSection $section): RedirectResponse
    {
        $this->adminOnly($request);
        $data = $request->validate([
            'data_ar' => ['required', 'json'],
            'data_en' => ['required', 'json'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $section->update([
            'data_ar' => json_decode($data['data_ar'], true),
            'data_en' => json_decode($data['data_en'], true),
            'sort_order' => $data['sort_order'] ?? $section->sort_order,
        ]);
        HomepageContentService::clearAllCache();

        return back()->with('status', 'Section "'.$section->section_key.'" updated. Public cache cleared.');
    }

    public function toggle(Request $request, HomepageSection $section): RedirectResponse
    {
        $this->adminOnly($request);
        $section->update(['is_active' => ! $section->is_active]);
        HomepageContentService::clearAllCache();

        return back()->with('status', 'Section "'.$section->section_key.'" '.($section->is_active ? 'enabled' : 'disabled').'.');
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()?->role === 'ADMIN', 403);
    }
}
