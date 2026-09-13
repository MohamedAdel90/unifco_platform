<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query=DB::table('audit_logs')->where('tenant_id',$request->user()->tenant_id)->latest('id');
        if ($request->filled('action')) $query->where('action','like','%'.$request->string('action').'%');
        if ($request->filled('correlation_id')) $query->where('correlation_id',$request->string('correlation_id'));
        if ($request->filled('user_id')) $query->where('user_id',$request->integer('user_id'));
        if ($request->filled('ip')) $query->where('ip_address',$request->string('ip'));
        if ($request->filled('entity_type')) $query->where('entity_type','like','%'.$request->string('entity_type').'%');
        if ($request->filled('entity_id')) $query->where('entity_id',$request->integer('entity_id'));
        if ($request->filled('from')) $query->whereDate('created_at','>=',$request->date('from'));
        if ($request->filled('to')) $query->whereDate('created_at','<=',$request->date('to'));
        return view('admin.audit.index',['logs'=>$query->paginate(50)->withQueryString()]);
    }
}
