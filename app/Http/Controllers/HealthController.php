<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{DB,Storage};

class HealthController extends Controller
{
    public function live(): JsonResponse { return response()->json(['status'=>'ok','service'=>'unifco']); }

    public function ready(): JsonResponse
    {
        $checks=['database'=>false,'storage'=>false];
        try { DB::select('select 1'); $checks['database']=true; } catch (\Throwable) {}
        try { Storage::disk(config('filesystems.default'))->put('health/.ready','ok'); Storage::disk(config('filesystems.default'))->delete('health/.ready'); $checks['storage']=true; } catch (\Throwable) {}
        $ready=!in_array(false,$checks,true);
        return response()->json(['status'=>$ready?'ready':'not_ready','checks'=>$checks],$ready?200:503);
    }
}
