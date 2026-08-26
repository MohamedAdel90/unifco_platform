<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CustomerPortalDashboardPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $request->routeIs('customer.portal') || ! auth()->check() || ! auth()->user()->customer_id || ! method_exists($response,'getContent') || ! method_exists($response,'setContent')) return $response;

        $html=(string)$response->getContent();
        if ($html==='' || !str_contains($html,'Customer 360 Dashboard')) return $response;

        $orders=DB::table('work_orders as w')->join('assets as a','a.id','=','w.asset_id')
            ->where('a.customer_id',auth()->user()->customer_id)
            ->orderByDesc('w.id')->limit(4)->get(['w.work_order_no','w.status','w.priority']);
        if ($orders->isEmpty()) return $response;

        $items=$orders->map(fn($w)=>'<div class="item"><div><div class="title">'.e($w->work_order_no).'</div><div class="sub">'.e($w->priority).' · '.e($w->status).'</div></div><span class="pill">Work Order</span></div>')->implode('');
        $panel='<section class="card panel" style="margin:14px 0"><h3>Recent Work Orders</h3><div class="recent">'.$items.'</div></section>';

        $needle='<section class="stats">';
        $position=strpos($html,$needle);
        if ($position!==false) {
            $html=substr($html,0,$position).$panel.substr($html,$position);
            $response->setContent($html);
        }

        return $response;
    }
}
