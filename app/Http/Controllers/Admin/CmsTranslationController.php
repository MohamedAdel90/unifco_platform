<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CmsTranslationController extends Controller
{
    public function translate(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'ADMIN', 403);

        $data = $request->validate([
            'text' => ['required', 'string', 'max:5000'],
        ]);

        $text = trim($data['text']);
        if ($text === '') {
            return response()->json(['translation' => '']);
        }

        try {
            $response = Http::timeout(12)
                ->retry(1, 250)
                ->get('https://translate.googleapis.com/translate_a/single', [
                    'client' => 'gtx',
                    'sl' => 'ar',
                    'tl' => 'en',
                    'dt' => 't',
                    'q' => $text,
                ]);

            if (! $response->successful()) {
                return response()->json(['message' => 'Translation service is temporarily unavailable.'], 503);
            }

            $payload = $response->json();
            $segments = is_array($payload[0] ?? null) ? $payload[0] : [];
            $translation = '';

            foreach ($segments as $segment) {
                if (is_array($segment) && isset($segment[0])) {
                    $translation .= (string) $segment[0];
                }
            }

            if ($translation === '') {
                return response()->json(['message' => 'No translation was returned.'], 503);
            }

            return response()->json(['translation' => $translation]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Translation service is temporarily unavailable.'], 503);
        }
    }
}
