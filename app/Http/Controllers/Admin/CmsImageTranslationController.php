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
        app(\App\Services\AuthorizationService::class)->authorize($request->user(), 'homepage.manage');

        $data = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        return response()->json($service->translateUrl($data['url']));
    }
}
