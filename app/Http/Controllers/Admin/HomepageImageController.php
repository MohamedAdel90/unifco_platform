<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HomepageImageController extends Controller
{
    private const DIRECTORY = 'homepage-media';
    private const META_DIRECTORY = 'homepage-media-meta';
    private const LEGACY_TEMP_DIRECTORY = 'temporary-files';

    public function list(Request $request): JsonResponse
    {
        $this->adminOnly($request);
        $sectionKey = $this->sectionKey($request);

        return response()->json([
            'context' => $sectionKey,
            'images' => $this->availableImages($sectionKey),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $this->adminOnly($request);
        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $file = $data['file'];
        $mime = strtolower((string) ($file->getMimeType() ?: ''));
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (! isset($allowed[$mime])) {
            throw ValidationException::withMessages([
                'file' => 'Unsupported image format. Please upload JPG, PNG, WEBP or GIF. Photos selected from iPhone HEIC/HEIF are converted automatically by the CMS before upload.',
            ]);
        }

        $extension = $allowed[$mime];
        $filename = Str::uuid().'.'.$extension;
        $path = $file->storeAs(self::DIRECTORY, $filename, 'public');

        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(500, 'The image could not be saved to permanent homepage storage.');
        }

        $sectionKey = $this->sectionKey($request);
        Storage::disk('public')->put(
            self::META_DIRECTORY.'/'.$filename.'.json',
            json_encode([
                'section_key' => $sectionKey,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now()->toIso8601String(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return response()->json([
            'image' => [
                'url' => Storage::disk('public')->url($path),
                'name' => $file->getClientOriginalName(),
                'delete_key' => $filename,
                'deletable' => true,
                'section' => $sectionKey,
            ],
        ]);
    }

    public function destroy(Request $request, string $image): JsonResponse
    {
        $this->adminOnly($request);

        if (! preg_match('/^[0-9a-f-]{36}\.(?:jpe?g|png|webp|gif)$/i', $image)) {
            abort(422, 'This image cannot be deleted from the CMS library.');
        }

        $path = self::DIRECTORY.'/'.$image;
        if (! Storage::disk('public')->exists($path)) {
            abort(404, 'Image not found.');
        }

        if (! Storage::disk('public')->delete($path)) {
            abort(500, 'The image could not be deleted.');
        }

        Storage::disk('public')->delete(self::META_DIRECTORY.'/'.$image.'.json');

        return response()->json([
            'deleted' => true,
            'image' => $image,
        ]);
    }

    private function availableImages(?string $sectionKey): array
    {
        $referenced = $sectionKey ? $this->referencedUrls($sectionKey) : [];

        $permanent = collect(Storage::disk('public')->files(self::DIRECTORY))
            ->filter(fn (string $path) => preg_match('/\.(?:jpe?g|png|webp|gif)$/i', $path))
            ->map(function (string $path) use ($sectionKey, $referenced) {
                $filename = basename($path);
                $meta = $this->imageMeta($filename);
                $url = Storage::disk('public')->url($path);
                $scope = trim((string) ($meta['section_key'] ?? ''));

                if ($sectionKey && $scope !== $sectionKey && ! in_array($url, $referenced, true)) {
                    return null;
                }

                return [
                    'url' => $url,
                    'name' => $meta['original_name'] ?? $filename,
                    'delete_key' => $filename,
                    'deletable' => true,
                    'section' => $scope ?: null,
                    'source' => 'cms',
                ];
            })
            ->filter()
            ->values()
            ->all();

        $staticHome = $this->staticImages(public_path('images/home'), 'home');
        $projects = $this->staticImages(public_path('images/home/projects'), 'projects');
        $clients = $this->staticImages(public_path('images/home/clients'), 'clients');

        $static = match ($sectionKey) {
            'projects' => $projects,
            'clients' => $clients,
            default => $staticHome,
        };

        $legacyTemporary = [];
        if (! $sectionKey) {
            $legacyTemporary = collect(Storage::disk('local')->directories(self::LEGACY_TEMP_DIRECTORY))
                ->map(function (string $directory) {
                    $token = basename($directory);
                    $metaPath = self::LEGACY_TEMP_DIRECTORY.'/'.$token.'/metadata.json';
                    if (! Storage::disk('local')->exists($metaPath)) {
                        return null;
                    }
                    $meta = json_decode(Storage::disk('local')->get($metaPath), true);
                    if (! is_array($meta) || ! str_starts_with((string) ($meta['mime'] ?? ''), 'image/')) {
                        return null;
                    }

                    return [
                        'url' => route('temporary-files.show', $token),
                        'name' => $meta['original_name'] ?? $token,
                        'deletable' => false,
                        'source' => 'legacy',
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        return collect(array_merge($permanent, $static, $legacyTemporary))
            ->unique('url')
            ->values()
            ->all();
    }

    private function staticImages(string $directory, string $source): array
    {
        return collect(glob($directory.'/*.{webp,jpg,jpeg,png,gif,svg}', GLOB_BRACE) ?: [])
            ->map(fn ($path) => [
                'url' => str_replace(public_path(), '', $path),
                'name' => basename($path),
                'deletable' => false,
                'source' => $source,
            ])
            ->values()
            ->all();
    }

    private function sectionKey(Request $request): ?string
    {
        $explicit = trim((string) $request->input('section', $request->query('section', '')));
        if ($explicit !== '' && preg_match('/^[a-z0-9_-]+$/i', $explicit)) {
            return strtolower($explicit);
        }

        $referer = (string) $request->headers->get('referer', '');
        $path = (string) parse_url($referer, PHP_URL_PATH);
        if (preg_match('#/admin/homepage/sections/(\d+)/edit/?$#', $path, $matches)) {
            return HomepageSection::query()->whereKey((int) $matches[1])->value('section_key');
        }

        return null;
    }

    private function imageMeta(string $filename): array
    {
        $path = self::META_DIRECTORY.'/'.$filename.'.json';
        if (! Storage::disk('public')->exists($path)) {
            return [];
        }

        $decoded = json_decode(Storage::disk('public')->get($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function referencedUrls(string $sectionKey): array
    {
        $section = HomepageSection::query()->where('section_key', $sectionKey)->first();
        if (! $section) {
            return [];
        }

        $urls = [];
        $walk = function (mixed $value) use (&$walk, &$urls): void {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $walk($item);
                }
                return;
            }

            if (! is_string($value)) {
                return;
            }

            $value = trim($value);
            if ($value !== '' && preg_match('#(?:/storage/homepage-media/|/images/home/|temporary-files)#', $value)) {
                $urls[] = $value;
            }
        };

        $walk($section->data_ar ?? []);
        $walk($section->data_en ?? []);

        return array_values(array_unique($urls));
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()?->role === 'ADMIN', 403);
    }
}
