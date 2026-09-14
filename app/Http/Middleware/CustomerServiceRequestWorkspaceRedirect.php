<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerServiceRequestWorkspaceRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        if($request->isMethod('GET') && $request->path()==='customer/requests' && $request->user()?->role==='CUSTOMER'){
            return redirect()->route('customer.service-requests.index',$request->query());
        }

        return $next($request);
    }
}
