<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Customer,User};
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemCustomerPortalUsersController extends Controller
{
    public function index(Request $request): View
    {
        app(AuthorizationService::class)->authorize($request->user(),'users.view');
        $tenant=$request->user()->tenant_id;
        $q=trim((string)$request->query('q'));
        $customerId=$request->integer('customer_id');
        $status=trim((string)$request->query('status'));

        $base=User::query()->where('tenant_id',$tenant)->whereNotNull('customer_id')->where('role','CUSTOMER');
        $users=(clone $base)
            ->with('activeRoles')
            ->when($q,fn($x)=>$x->where(fn($y)=>$y->where('name','like',"%{$q}%")->orWhere('email','like',"%{$q}%")))
            ->when($customerId,fn($x)=>$x->where('customer_id',$customerId))
            ->when($status,fn($x)=>$x->where('status',$status))
            ->orderBy('name')->paginate(25)->withQueryString();

        $customers=Customer::where('tenant_id',$tenant)->orderBy('name')->get(['id','customer_code','name','status']);
        $customerNames=$customers->pluck('name','id');
        $scopes=collect();
        $siteNames=collect();
        $stats=[
            'total'=>(clone $base)->count(),
            'active'=>(clone $base)->where('status','ACTIVE')->count(),
            'admins'=>(clone $base)->where('status','ACTIVE')->count(),
            'customers'=>(clone $base)->distinct('customer_id')->count('customer_id'),
        ];
        // Kept for view compatibility. There is only one portal account type.
        $portalRoles=['CUSTOMER_ADMIN'=>'Full Customer Account'];

        return view('admin.customer-portal-users.index',compact('users','customers','customerNames','scopes','siteNames','stats','portalRoles'));
    }
}
