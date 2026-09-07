<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CmsImageTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsImageTranslationController extends Controller
{
    public function translate(Request $request, CmsImageTranslationService $service): JsonResponse
    {
        abort_unless($request->user()?->role === 'ADMIN', 403);

        $data = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        return response()->json($service->translateUrl($data['url']));
    }
}
