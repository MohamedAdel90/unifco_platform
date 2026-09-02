<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomepageImageController extends Controller
{
    private const DIRECTORY = 'temporary-files';

    public function list(Request $request): JsonResponse
    {
        $this->adminOnly($request);

        return response()->json(['images' => $this->availableImages()]);
    }

    public function upload(Request $request): JsonResponse
    {
        $this->adminOnly($request);
        $data = $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
        ]);

        $file = $data['file'];
        $mime = (string) ($file->getMimeType() ?: 'image/png');
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'png',
        };
        $token = (string) Str::uuid();
        $directory = self::DIRECTORY.'/'.$token;
        $path = $file->storeAs($directory, 'file.'.$extension, 'local');

        Storage::disk('local')->put($directory.'/metadata.json', json_encode([
            'token' => $token,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->name,
            'uploaded_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return response()->json([
            'image' => [
                'url' => route('temporary-files.show', $token),
                'name' => $file->getClientOriginalName(),
            ],
        ]);
    }

    private function availableImages(): array
    {
        $fromTmp = collect(Storage::disk('local')->directories(self::DIRECTORY))
            ->map(function (string $directory) {
                $token = basename($directory);
                $metaPath = self::DIRECTORY.'/'.$token.'/metadata.json';
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

        $fromProjects = collect(glob(public_path('images/home/projects/*.{webp,jpg,jpeg,png}', GLOB_BRACE)) ?: [])
            ->map(fn ($p) => ['url' => str_replace(public_path(), '', $p), 'name' => basename($p)])
            ->values()
            ->all();

        $fromClients = collect(glob(public_path('images/home/clients/*.{webp,jpg,jpeg,png}', GLOB_BRACE)) ?: [])
            ->map(fn ($p) => ['url' => str_replace(public_path(), '', $p), 'name' => basename($p)])
            ->values()
            ->all();

        return array_merge($fromProjects, $fromClients, $fromTmp);
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()?->role === 'ADMIN', 403);
    }
}
