<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageClient;
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

        if ($section->section_key === 'clients') {
            $this->hydrateClientLogosForEditor($section);
        }

        return view('admin.homepage.sections.edit', ['section' => $section, 'schema' => $schema]);
    }

    public function preview(Request $request, HomepageSection $section): Response
    {
        $this->adminOnly($request);
        $locale = $request->validate([
            'locale' => ['required', 'in:ar,en'],
        ])['locale'];

        $schema = HomepageSectionSchema::fields($section->section_key);
        $existingAr = $section->data_ar ?? [];
        $existingEn = $section->data_en ?? [];
        $draftAr = $this->assemble($request, $schema, 'ar', $existingAr);
        $draftEn = $this->assemble($request, $schema, 'en', $existingEn);

        if ($section->section_key === 'services') {
            [$draftAr, $draftEn] = $this->synchronizeServiceImages($draftAr, $draftEn, $existingAr, $existingEn);
        }

        if ($section->section_key === 'clients') {
            [$draftAr, $draftEn] = $this->synchronizeClientLogos($draftAr, $draftEn, $existingAr, $existingEn);
        }

        $draft = $locale === 'ar' ? $draftAr : $draftEn;
        $draftPublic = HomepageSectionMapper::toPublic($section->section_key, $draft);

        $home = app(HomepageContentService::class)->getContent($locale);
        $home = array_replace($home, $draftPublic);
        $home['lang'] = $locale;
        $home['dir'] = $locale === 'ar' ? 'rtl' : 'ltr';
        $home['language'] = $locale === 'ar' ? 'EN' : 'AR';

        if ($section->section_key === 'clients') {
            $home['showcase_clients'] = $this->clientRowsForPreview($draft, $locale);
        }

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
        $existingAr = $section->data_ar ?? [];
        $existingEn = $section->data_en ?? [];

        if ($request->has('data_ar') && $request->has('data_en')) {
            $submittedAr = json_decode($request->input('data_ar'), true) ?: [];
            $submittedEn = json_decode($request->input('data_en'), true) ?: [];
            $dataAr = array_replace($existingAr, $submittedAr);
            $dataEn = array_replace($existingEn, $submittedEn);
        } else {
            $dataAr = $this->assemble($request, $schema, 'ar', $existingAr);
            $dataEn = $this->assemble($request, $schema, 'en', $existingEn);
        }

        if ($section->section_key === 'services') {
            [$dataAr, $dataEn] = $this->synchronizeServiceImages($dataAr, $dataEn, $existingAr, $existingEn);
        }

        if ($section->section_key === 'clients') {
            [$dataAr, $dataEn] = $this->synchronizeClientLogos($dataAr, $dataEn, $existingAr, $existingEn);
        }

        $section->update([
            'data_ar' => $dataAr,
            'data_en' => $dataEn,
            'sort_order' => $data['sort_order'] ?? $section->sort_order,
        ]);

        if ($section->section_key === 'clients') {
            $this->persistClientLogos($dataAr, $request->user()?->id);
        }

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
            $existingItems = is_array($existing[$listKey] ?? null) ? $existing[$listKey] : [];
            $rows = (array) $request->input("{$base}_index", []);

            foreach ($rows as $index) {
                $index = (int) $index;
                $item = is_array($existingItems[$index] ?? null) ? $existingItems[$index] : [];

                foreach ($itemFields as $field) {
                    $key = "{$base}_{$index}_{$field}";
                    if ($request->has($key)) {
                        $item[$field] = $request->input($key);
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

    private function synchronizeServiceImages(array $dataAr, array $dataEn, array $existingAr, array $existingEn): array
    {
        $arItems = is_array($dataAr['items'] ?? null) ? $dataAr['items'] : [];
        $enItems = is_array($dataEn['items'] ?? null) ? $dataEn['items'] : [];
        $oldArItems = is_array($existingAr['items'] ?? null) ? $existingAr['items'] : [];
        $oldEnItems = is_array($existingEn['items'] ?? null) ? $existingEn['items'] : [];

        $oldArByNumber = $this->itemsByNumber($oldArItems);
        $oldEnByNumber = $this->itemsByNumber($oldEnItems);
        $arByNumber = $this->itemsByNumber($arItems, true);
        $enByNumber = $this->itemsByNumber($enItems, true);

        $numbers = array_values(array_unique(array_merge(array_keys($arByNumber), array_keys($enByNumber))));

        foreach ($numbers as $number) {
            $arIndex = $arByNumber[$number]['index'] ?? null;
            $enIndex = $enByNumber[$number]['index'] ?? null;
            $newAr = $arIndex !== null ? (string) ($arItems[$arIndex]['image'] ?? '') : '';
            $newEn = $enIndex !== null ? (string) ($enItems[$enIndex]['image'] ?? '') : '';
            $oldAr = (string) ($oldArByNumber[$number]['item']['image'] ?? '');
            $oldEn = (string) ($oldEnByNumber[$number]['item']['image'] ?? '');

            $arChanged = $arIndex !== null && $newAr !== $oldAr;
            $enChanged = $enIndex !== null && $newEn !== $oldEn;
            $englishWasInherited = $oldEn === '' || $oldEn === $oldAr;

            if ($enIndex !== null && $arChanged && ! $enChanged && $englishWasInherited) {
                $enItems[$enIndex]['image'] = $newAr;
            }
        }

        $dataAr['items'] = $arItems;
        $dataEn['items'] = $enItems;

        return [$dataAr, $dataEn];
    }

    private function hydrateClientLogosForEditor(HomepageSection $section): void
    {
        $ar = $section->data_ar ?? [];
        $en = $section->data_en ?? [];

        if ($this->logoPaths($ar['logos'] ?? []) !== [] || $this->logoPaths($en['logos'] ?? []) !== []) {
            return;
        }

        $rows = HomepageClient::active()->ordered()->get()
            ->map(fn (HomepageClient $client) => ['image' => (string) $client->image])
            ->filter(fn (array $row) => trim($row['image']) !== '')
            ->values()
            ->all();

        if ($rows === []) {
            return;
        }

        $ar['logos'] = $rows;
        $en['logos'] = $rows;
        $section->setAttribute('data_ar', $ar);
        $section->setAttribute('data_en', $en);
    }

    private function synchronizeClientLogos(array $dataAr, array $dataEn, array $existingAr, array $existingEn): array
    {
        $newAr = $this->logoPaths($dataAr['logos'] ?? []);
        $newEn = $this->logoPaths($dataEn['logos'] ?? []);
        $oldAr = $this->logoPaths($existingAr['logos'] ?? []);
        $oldEn = $this->logoPaths($existingEn['logos'] ?? []);

        $arChanged = $newAr !== $oldAr;
        $enChanged = $newEn !== $oldEn;
        $englishWasInherited = $oldEn === [] || $oldEn === $oldAr;

        if ($arChanged && ! $enChanged && $englishWasInherited) {
            $dataEn['logos'] = array_map(fn (string $image) => ['image' => $image], $newAr);
        }

        return [$dataAr, $dataEn];
    }

    private function logoPaths(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $paths = [];
        foreach ($rows as $row) {
            $image = is_array($row) ? trim((string) ($row['image'] ?? $row[0] ?? '')) : trim((string) $row);
            if ($image !== '') {
                $paths[] = $image;
            }
        }

        return array_values($paths);
    }

    private function persistClientLogos(array $data, ?int $userId): void
    {
        $paths = $this->logoPaths($data['logos'] ?? []);
        $clients = HomepageClient::query()->orderBy('sort_order')->orderBy('id')->get();

        foreach ($paths as $index => $image) {
            $client = $clients->get($index) ?? new HomepageClient();
            $client->fill([
                'sort_order' => $index + 1,
                'is_active' => true,
                'image' => $image,
                'updated_by' => $userId,
            ]);
            if (! $client->exists) {
                $client->name_ar = '';
                $client->name_en = '';
            }
            $client->save();
        }

        foreach ($clients->slice(count($paths)) as $client) {
            $client->update(['is_active' => false, 'updated_by' => $userId]);
        }

        HomepageClient::clearCache();
    }

    private function clientRowsForPreview(array $data, string $locale): array
    {
        $paths = $this->logoPaths($data['logos'] ?? []);
        $existing = HomepageClient::query()->orderBy('sort_order')->orderBy('id')->get();

        return array_map(function (string $image, int $index) use ($existing, $locale) {
            $name = (string) ($existing->get($index)?->{"name_{$locale}"} ?? '');

            return [$image, $name];
        }, $paths, array_keys($paths));
    }

    private function itemsByNumber(array $items, bool $includeIndex = false): array
    {
        $mapped = [];
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $number = str_pad((string) ($item['number'] ?? ''), 2, '0', STR_PAD_LEFT);
            if ($number === '00' || $number === '') {
                continue;
            }
            $mapped[$number] = $includeIndex ? ['index' => $index, 'item' => $item] : ['item' => $item];
        }

        return $mapped;
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
