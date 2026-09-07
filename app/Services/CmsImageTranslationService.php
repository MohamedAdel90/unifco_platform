<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CmsImageTranslationService
{
    private const META_DIRECTORY = 'homepage-media/image-translations';
    private const OUTPUT_DIRECTORY = 'homepage-media/translated';

    public function translateUrl(string $url): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['status' => 'same', 'english_url' => '', 'contains_arabic' => false];
        }

        $source = $this->resolveLocalImage($url);
        if (! $source) {
            return [
                'status' => 'unsupported',
                'english_url' => $url,
                'contains_arabic' => null,
                'message' => 'Only locally stored CMS images can be translated automatically.',
            ];
        }

        [$absolutePath, $mime] = $source;
        $fingerprint = sha1($absolutePath.'|'.((string) @filemtime($absolutePath)).'|'.((string) @filesize($absolutePath)));
        $metaPath = self::META_DIRECTORY.'/'.$fingerprint.'.json';

        if (Storage::disk('public')->exists($metaPath)) {
            $cached = json_decode(Storage::disk('public')->get($metaPath), true);
            if (is_array($cached) && isset($cached['english_url'])) {
                return $cached + ['cached' => true];
            }
        }

        $apiKey = trim((string) env('OPENAI_API_KEY', ''));
        if ($apiKey === '') {
            return [
                'status' => 'unavailable',
                'english_url' => $url,
                'contains_arabic' => null,
                'message' => 'OPENAI_API_KEY is not configured on the server.',
            ];
        }

        try {
            $analysis = $this->analyzeArabicText($absolutePath, $mime, $apiKey);
            if (! ($analysis['contains_arabic'] ?? false)) {
                $result = [
                    'status' => 'same',
                    'english_url' => $url,
                    'contains_arabic' => false,
                    'translations' => [],
                ];
                $this->cacheResult($metaPath, $result);

                return $result;
            }

            $englishUrl = $this->editArabicTextToEnglish($absolutePath, $analysis['translations'] ?? [], $apiKey);
            $result = [
                'status' => 'translated',
                'english_url' => $englishUrl,
                'contains_arabic' => true,
                'translations' => $analysis['translations'] ?? [],
            ];
            $this->cacheResult($metaPath, $result);

            return $result;
        } catch (\Throwable $e) {
            report($e);

            return [
                'status' => 'failed',
                'english_url' => $url,
                'contains_arabic' => null,
                'message' => 'Automatic image translation failed. The original image is being used for English.',
            ];
        }
    }

    private function analyzeArabicText(string $absolutePath, string $mime, string $apiKey): array
    {
        $base64 = base64_encode((string) file_get_contents($absolutePath));
        $model = trim((string) env('OPENAI_VISION_MODEL', 'gpt-5.6-luna')) ?: 'gpt-5.6-luna';

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(90)
            ->retry(1, 400)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $model,
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => 'Inspect this CMS image. Determine whether any visible Arabic text is embedded inside the image pixels. Return ONLY valid compact JSON in this exact shape: {"contains_arabic":true|false,"translations":[{"ar":"Arabic text exactly as visible","en":"natural concise English translation"}]}. Include every distinct Arabic text block. Do not treat logos or non-Arabic symbols as Arabic text.',
                        ],
                        [
                            'type' => 'input_image',
                            'image_url' => 'data:'.$mime.';base64,'.$base64,
                            'detail' => 'high',
                        ],
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI image analysis failed: HTTP '.$response->status().' '.$response->body());
        }

        $text = $this->extractResponseText($response->json());
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)) ?? $text);
        $json = json_decode($text, true);

        if (! is_array($json) || ! array_key_exists('contains_arabic', $json)) {
            throw new RuntimeException('OpenAI image analysis returned invalid JSON.');
        }

        $translations = collect($json['translations'] ?? [])
            ->filter(fn ($pair) => is_array($pair) && trim((string) ($pair['ar'] ?? '')) !== '' && trim((string) ($pair['en'] ?? '')) !== '')
            ->map(fn ($pair) => ['ar' => trim((string) $pair['ar']), 'en' => trim((string) $pair['en'])])
            ->values()
            ->all();

        return [
            'contains_arabic' => (bool) $json['contains_arabic'],
            'translations' => $translations,
        ];
    }

    private function editArabicTextToEnglish(string $absolutePath, array $translations, string $apiKey): string
    {
        $model = trim((string) env('OPENAI_IMAGE_MODEL', 'gpt-image-2')) ?: 'gpt-image-2';
        $pairs = collect($translations)
            ->map(fn ($pair) => '"'.($pair['ar'] ?? '').'" -> "'.($pair['en'] ?? '').'"')
            ->implode("\n");

        $prompt = "Edit this image with extremely high visual fidelity. Preserve the exact composition, people, products, logos, colors, lighting, spacing, crop, aspect ratio, icons and all non-text artwork. Change ONLY visible Arabic wording into English. Remove the Arabic glyphs cleanly and place the English translation in the same visual position, with matching hierarchy, alignment, approximate font weight, size and color. Do not add new marketing copy, do not translate brand names unless they are ordinary descriptive Arabic text, and do not redesign the image.\n\nDetected translations:\n".$pairs;

        $stream = fopen($absolutePath, 'r');
        if ($stream === false) {
            throw new RuntimeException('Cannot open CMS image for editing.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(240)
                ->attach('image[]', $stream, basename($absolutePath))
                ->post('https://api.openai.com/v1/images/edits', [
                    'model' => $model,
                    'prompt' => $prompt,
                    'size' => 'auto',
                    'quality' => trim((string) env('OPENAI_IMAGE_QUALITY', 'medium')) ?: 'medium',
                    'output_format' => 'png',
                ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI image edit failed: HTTP '.$response->status().' '.$response->body());
        }

        $encoded = (string) data_get($response->json(), 'data.0.b64_json', '');
        $bytes = $encoded !== '' ? base64_decode($encoded, true) : false;
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('OpenAI image edit returned no image data.');
        }

        $path = self::OUTPUT_DIRECTORY.'/'.Str::uuid().'.png';
        if (! Storage::disk('public')->put($path, $bytes)) {
            throw new RuntimeException('Could not persist translated CMS image.');
        }

        return Storage::disk('public')->url($path);
    }

    private function extractResponseText(array $payload): string
    {
        $parts = [];
        foreach ($payload['output'] ?? [] as $output) {
            if (! is_array($output)) {
                continue;
            }
            foreach ($output['content'] ?? [] as $content) {
                if (is_array($content) && isset($content['text']) && is_string($content['text'])) {
                    $parts[] = $content['text'];
                }
            }
        }

        return trim(implode("\n", $parts));
    }

    private function resolveLocalImage(string $url): ?array
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: $url);
        $path = '/'.ltrim($path, '/');

        if (str_starts_with($path, '/storage/')) {
            $relative = substr($path, strlen('/storage/'));
            if ($relative !== false && Storage::disk('public')->exists($relative)) {
                $absolute = Storage::disk('public')->path($relative);
                return [$absolute, $this->mimeFor($absolute)];
            }
        }

        if (str_starts_with($path, '/images/')) {
            $absolute = public_path(ltrim($path, '/'));
            if (is_file($absolute)) {
                return [$absolute, $this->mimeFor($absolute)];
            }
        }

        return null;
    }

    private function mimeFor(string $absolutePath): string
    {
        $mime = (string) (@mime_content_type($absolutePath) ?: 'image/png');
        return str_starts_with($mime, 'image/') ? $mime : 'image/png';
    }

    private function cacheResult(string $metaPath, array $result): void
    {
        Storage::disk('public')->put($metaPath, json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
