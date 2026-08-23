<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TemporaryFileController extends Controller
{
    private const DIRECTORY = 'temporary-files';

    public function index(Request $request): View
    {
        $this->adminOnly($request);

        $files = collect(Storage::disk('local')->directories(self::DIRECTORY))
            ->map(fn (string $directory) => $this->metadata(basename($directory)))
            ->filter()
            ->sortByDesc('uploaded_at')
            ->values();

        return view('admin.temporary-files.index', compact('files'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->adminOnly($request);

        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,gif,pdf',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,application/pdf',
                'max:20480',
            ],
        ]);

        $file = $data['file'];
        $mime = (string) ($file->getMimeType() ?: 'application/octet-stream');
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            default => abort(422, 'Unsupported temporary file type.'),
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

        return back()->with('status', 'Temporary file uploaded. Its public link is ready to copy.');
    }

    public function show(string $token): StreamedResponse
    {
        abort_unless(Str::isUuid($token), 404);
        $metadata = $this->metadata($token);
        abort_unless($metadata && Storage::disk('local')->exists($metadata['path']), 404);

        return Storage::disk('local')->response(
            $metadata['path'],
            $metadata['original_name'],
            [
                'Content-Type' => $metadata['mime'],
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }

    public function destroy(Request $request, string $token): RedirectResponse
    {
        $this->adminOnly($request);
        abort_unless(Str::isUuid($token), 404);

        $directory = self::DIRECTORY.'/'.$token;
        abort_unless(Storage::disk('local')->exists($directory.'/metadata.json'), 404);
        Storage::disk('local')->deleteDirectory($directory);

        return back()->with('status', 'Temporary file deleted. Its public link no longer works.');
    }

    private function metadata(string $token): ?array
    {
        if (! Str::isUuid($token)) {
            return null;
        }

        $path = self::DIRECTORY.'/'.$token.'/metadata.json';
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $metadata = json_decode(Storage::disk('local')->get($path), true);
        if (! is_array($metadata) || ($metadata['token'] ?? null) !== $token) {
            return null;
        }

        $metadata['public_url'] = route('temporary-files.show', $token);
        $metadata['is_image'] = str_starts_with((string) ($metadata['mime'] ?? ''), 'image/');

        return $metadata;
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()?->role === 'ADMIN', 403);
    }
}
