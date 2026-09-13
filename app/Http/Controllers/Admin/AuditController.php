<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $tenant=$request->user()->tenant_id;
        $query=DB::table('audit_logs')->where('tenant_id',$tenant)->latest('id');
        if ($request->filled('module')) $query->where('action','like',$request->string('module').'.%');
        if ($request->filled('action')) $query->where('action','like','%'.$request->string('action').'%');
        if ($request->filled('correlation_id')) $query->where('correlation_id',$request->string('correlation_id'));
        if ($request->filled('user_id')) $query->where('user_id',$request->integer('user_id'));
        if ($request->filled('ip')) $query->where('ip_address',$request->string('ip'));
        if ($request->filled('entity_type')) $query->where('entity_type','like','%'.$request->string('entity_type').'%');
        if ($request->filled('entity_id')) $query->where('entity_id',$request->integer('entity_id'));
        if ($request->filled('from')) $query->whereDate('created_at','>=',$request->date('from'));
        if ($request->filled('to')) $query->whereDate('created_at','<=',$request->date('to'));
        $modules=DB::table('audit_logs')->where('tenant_id',$tenant)->distinct()->pluck('action')->map(fn($action)=>str_contains($action,'.')?explode('.',$action,2)[0]:'system')->unique()->sort()->values();
        return view('admin.audit.index',[
            'logs'=>$query->paginate(50)->withQueryString(),'modules'=>$modules,
            'users'=>User::where('tenant_id',$tenant)->orderBy('name')->get(['id','name','email']),
        ]);
    }
}
