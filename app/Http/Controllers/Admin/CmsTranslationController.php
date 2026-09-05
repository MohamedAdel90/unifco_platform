<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsTranslationController extends Controller
{
    public function translate(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'ADMIN', 403);

        return response()->json([
            'message' => 'Automatic CMS translation is temporarily paused by administrator decision.',
            'paused' => true,
        ], 503);
    }
}
