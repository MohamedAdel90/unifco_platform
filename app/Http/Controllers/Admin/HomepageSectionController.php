<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Services\HomepageContentService;
use App\Services\HomepageHeroRenderer;
use App\Services\HomepageSectionMapper;
use App\Services\HomepageSectionSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        $schema = HomepageSectionSchema::fields($section->section_key);

        return view('admin.homepage.sections.edit', ['section' => $section, 'schema' => $schema]);
    }

    public function preview(Request $request, HomepageSection $section): Response
    {
        $this->adminOnly($request);
        $locale = $request->validate([
            'locale' => ['required', 'in:ar,en'],
        ])['locale'];

        $schema = HomepageSectionSchema::fields($section->section_key);
        $existing = $locale === 'ar' ? ($section->data_ar ?? []) : ($section->data_en ?? []);
        $draft = $this->assemble($request, $schema, $locale, $existing);
        $draftPublic = HomepageSectionMapper::toPublic($section->section_key, $draft);

        $home = app(HomepageContentService::class)->getContent($locale);
        $home = array_replace($home, $draftPublic);
        $home['lang'] = $locale;
        $home['dir'] = $locale === 'ar' ? 'rtl' : 'ltr';
        $home['language'] = $locale === 'ar' ? 'EN' : 'AR';

        $home['eyebrow'] = $home['hero_eyebrow'] ?? $home['eyebrow'] ?? '';
        $home['hero_proof'] = $home['hero_proofs'] ?? $home['hero_proof'] ?? [];
        $home['services_sub'] = $home['services_text'] ?? $home['services_sub'] ?? '';
        $home['services_button'] = $home['services_button'] ?? $home['all_services'] ?? ($locale === 'ar' ? 'عرض جميع الخدمات' : 'View All Services');
        $home['industries_button'] = $home['industries_button'] ?? $home['all_industries'] ?? ($locale === 'ar' ? 'عرض جميع القطاعات' : 'View All Industries');

        $html = view('public.partials.home-reference-layout', compact('home'))->render();
        $html = app(HomepageHeroRenderer::class)->render($html, $home);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function update(Request $request, HomepageSection $section): RedirectResponse
    {
        $this->adminOnly($request);
        $data = $request->validate([
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $schema = HomepageSectionSchema::fields($section->section_key);

        if ($request->has('data_ar') && $request->has('data_en')) {
            $submittedAr = json_decode($request->input('data_ar'), true) ?: [];
            $submittedEn = json_decode($request->input('data_en'), true) ?: [];
            $dataAr = array_replace($section->data_ar ?? [], $submittedAr);
            $dataEn = array_replace($section->data_en ?? [], $submittedEn);
        } else {
            $dataAr = $this->assemble($request, $schema, 'ar', $section->data_ar ?? []);
            $dataEn = $this->assemble($request, $schema, 'en', $section->data_en ?? []);
        }

        $section->update([
            'data_ar' => $dataAr,
            'data_en' => $dataEn,
            'sort_order' => $data['sort_order'] ?? $section->sort_order,
        ]);
        HomepageContentService::clearAllCache();

        return back()->with('status', 'Section "'.$section->section_key.'" updated. Public cache cleared.');
    }

    private function assemble(Request $request, array $schema, string $locale, array $existing = []): array
    {
        $result = $existing;

        foreach (($schema['scalars'] ?? []) as $field) {
            $base = "scalar_{$locale}_{$field}";
            if ($request->has($base)) {
                $value = $request->input($base);
                if ($field === 'render_mode') {
                    $value = HomepageHeroRenderer::normalizeMode((string) $value);
                }
                $result[$field] = $value;
            }
        }

        foreach (($schema['checks'] ?? []) as $field) {
            $base = "check_{$locale}_{$field}";
            $rows = array_values(array_filter((array) $request->input($base, []), fn ($v) => $v !== '' && $v !== null));
            $result[$field] = $rows;
        }

        foreach (($schema['items'] ?? []) as $listKey => $itemFields) {
            $base = "item_{$locale}_{$listKey}";
            $items = [];
            $rows = (array) $request->input("{$base}_index", []);
            foreach ($rows as $index) {
                $item = [];
                foreach ($itemFields as $f) {
                    $key = "{$base}_{$index}_{$f}";
                    if ($request->has($key)) {
                        $item[$f] = $request->input($key);
                    }
                }
                if ($item !== []) {
                    $items[] = $item;
                }
            }
            $result[$listKey] = $items;
        }

        return $result;
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
