<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Services\HomepageContentService;
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

        // Start from the real public homepage payload, then overlay only the
        // currently edited unsaved section. No database write or cache clear.
        $home = app(HomepageContentService::class)->getContent($locale);
        $home = array_replace($home, $draftPublic);
        $home['lang'] = $locale;
        $home['dir'] = $locale === 'ar' ? 'rtl' : 'ltr';
        $home['language'] = $locale === 'ar' ? 'EN' : 'AR';

        // Keep compatibility aliases in sync after the draft overlay.
        $home['eyebrow'] = $home['hero_eyebrow'] ?? $home['eyebrow'] ?? '';
        $home['hero_proof'] = $home['hero_proofs'] ?? $home['hero_proof'] ?? [];
        $home['services_sub'] = $home['services_text'] ?? $home['services_sub'] ?? '';
        $home['services_button'] = $home['services_button'] ?? $home['all_services'] ?? ($locale === 'ar' ? 'عرض جميع الخدمات' : 'View All Services');
        $home['industries_button'] = $home['industries_button'] ?? $home['all_industries'] ?? ($locale === 'ar' ? 'عرض جميع القطاعات' : 'View All Industries');

        $html = view('public.partials.home-reference-layout', compact('home'))->render();

        // The reference layout has a legacy fallback image in its stylesheet.
        // For Hero preview, replace that source at the element itself rather
        // than adding another CSS background layer. This guarantees that the
        // draft image is the only photographic background rendered and avoids
        // old/new Hero images visually overlapping after a CMS image change.
        $heroImage = trim((string) ($home['hero_image'] ?? ''));
        if ($heroImage !== '') {
            $safeHeroImage = htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8');
            $heroBackground = "linear-gradient(90deg,rgba(3,22,48,.98) 0%,rgba(5,30,62,.91) 30%,rgba(5,30,62,.54) 50%,rgba(5,30,62,.06) 74%),url('{$safeHeroImage}')";
            $heroTag = '<section class="hero" style="background-image:'.$heroBackground.'!important;background-repeat:no-repeat!important;background-size:cover!important;">';
            $html = str_replace('<section class="hero">', $heroTag, $html);
        }

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
        // Compatibility layer: only replace fields owned by the current schema.
        // Unknown/legacy keys stay untouched so editing one CMS section cannot
        // silently delete data still consumed by the public homepage.
        $result = $existing;

        foreach (($schema['scalars'] ?? []) as $field) {
            $base = "scalar_{$locale}_{$field}";
            if ($request->has($base)) {
                $result[$field] = $request->input($base);
            }
        }

        foreach (($schema['checks'] ?? []) as $field) {
            $base = "check_{$locale}_{$field}";
            $rows = array_values(array_filter((array) $request->input($base, []), fn ($v) => $v !== '' && $v !== null));
            // The checklist is rendered by this editor, so an empty submission
            // intentionally clears this known field without touching other keys.
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
            // Same rule as checklists: this field belongs to the current schema,
            // therefore removing all rows must persist as an empty list.
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
