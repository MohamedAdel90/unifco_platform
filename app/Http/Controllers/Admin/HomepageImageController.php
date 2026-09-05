<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HomepageImageController extends Controller
{
    private const DIRECTORY = 'homepage-media';
    private const LEGACY_TEMP_DIRECTORY = 'temporary-files';

    public function list(Request $request): JsonResponse
    {
        $this->adminOnly($request);

        return response()->json(['images' => $this->availableImages()]);
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

        return response()->json([
            'image' => [
                'url' => Storage::disk('public')->url($path),
                'name' => $file->getClientOriginalName(),
            ],
        ]);
    }

    private function availableImages(): array
    {
        $permanent = collect(Storage::disk('public')->files(self::DIRECTORY))
            ->filter(fn (string $path) => preg_match('/\.(?:jpe?g|png|webp|gif)$/i', $path))
            ->map(fn (string $path) => [
                'url' => Storage::disk('public')->url($path),
                'name' => basename($path),
            ])
            ->values()
            ->all();

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

                return ['url' => route('temporary-files.show', $token), 'name' => $meta['original_name'] ?? $token];
            })
            ->filter()
            ->values()
            ->all();

        $staticHome = collect(glob(public_path('images/home/*.{webp,jpg,jpeg,png,gif,svg}', GLOB_BRACE)) ?: [])
            ->map(fn ($path) => ['url' => str_replace(public_path(), '', $path), 'name' => basename($path)])
            ->values()
            ->all();

        $projects = collect(glob(public_path('images/home/projects/*.{webp,jpg,jpeg,png,gif,svg}', GLOB_BRACE)) ?: [])
            ->map(fn ($path) => ['url' => str_replace(public_path(), '', $path), 'name' => basename($path)])
            ->values()
            ->all();

        $clients = collect(glob(public_path('images/home/clients/*.{webp,jpg,jpeg,png,gif,svg}', GLOB_BRACE)) ?: [])
            ->map(fn ($path) => ['url' => str_replace(public_path(), '', $path), 'name' => basename($path)])
            ->values()
            ->all();

        return collect(array_merge($permanent, $staticHome, $projects, $clients, $legacyTemporary))
            ->unique('url')
            ->values()
            ->all();
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()?->role === 'ADMIN', 403);
    }
}
