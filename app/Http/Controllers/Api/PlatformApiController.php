<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Customer,Employee,Journal,ProductionOrder,Project,PurchaseOrder,WorkOrder};
use Illuminate\Http\JsonResponse;

class PlatformApiController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json(['data'=>[
            'platform'=>'UNIFCO','version'=>'v2','status'=>'ok','tenant_id'=>auth()->user()->tenant_id,
        ]]);
    }

    public function summary(): JsonResponse
    {
        return response()->json(['data'=>[
            'journals'=>Journal::count(),'employees'=>Employee::count(),'purchase_orders'=>PurchaseOrder::count(),
            'customers'=>Customer::count(),'projects'=>Project::count(),'production_orders'=>ProductionOrder::count(),
            'work_orders'=>WorkOrder::count(),'assets'=>Asset::count(),
        ]]);
    }
}
