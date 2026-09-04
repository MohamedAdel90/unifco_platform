<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Services\HomepageContentService;
use App\Services\HomepageSectionSchema;
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
        $schema = HomepageSectionSchema::fields($section->section_key);

        return view('admin.homepage.sections.edit', ['section' => $section, 'schema' => $schema]);
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
