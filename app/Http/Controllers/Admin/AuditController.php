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
        return view('admin.audit.index',['logs'=>$query->paginate(50)->withQueryString()]);
    }
}
