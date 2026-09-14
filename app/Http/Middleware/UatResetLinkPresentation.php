<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class UatResetLinkPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response=$next($request);
        if(!$request->routeIs('admin.users.show') || !$request->user() || !method_exists($response,'getContent')) return $response;
        $content=$response->getContent();
        if(!is_string($content) || !str_contains(strtolower((string)$response->headers->get('Content-Type','')),'text/html')) return $response;

        $id=(int)$request->route('user');
        $managed=User::where('tenant_id',$request->user()->tenant_id)->find($id);
        if(!$managed || $managed->id===$request->user()->id || ($managed->user_type??'')!=='EXTERNAL' || !Str::endsWith(strtolower($managed->email),'@unifco.local')) return $response;

        $action=e(route('admin.users.uat-reset-link',['user'=>$managed->id]));
        $csrf=e(csrf_token());
        $block='<form method="POST" action="'.$action.'" style="margin-top:10px" onsubmit="return confirm(\'Generate a single-use UAT reset link valid for 30 minutes and revoke active sessions?\')">'
            .'<input type="hidden" name="_token" value="'.$csrf.'"><button class="btn secondary" type="submit">Generate UAT Reset Link</button></form>'
            .'<div class="note">For <b>@unifco.local</b> test identities only. The administrator receives a one-time link, never the user password. The link expires after 30 minutes and its generation is audited.</div>';

        if($request->session()->has('uat_reset_url')) {
            $url=e((string)$request->session()->get('uat_reset_url'));
            $expires=e((string)$request->session()->get('uat_reset_expires_at'));
            $block.='<div style="margin-top:10px;padding:12px;border:1px solid #f1d69a;background:#fff8e8;border-radius:9px">'
                .'<b style="display:block;color:#815800;margin-bottom:6px">One-time UAT reset link — shown once</b>'
                .'<a href="'.$url.'" target="_blank" rel="noopener" style="word-break:break-all">'.$url.'</a>'
                .'<small style="display:block;margin-top:6px;color:#7b6a43">Expires: '.$expires.'</small></div>';
        }

        $marker='<div class="note">The administrator never sees or chooses the user password. Reset uses an audited one-time link and revokes existing sessions.</div>';
        if(str_contains($content,$marker)) {
            $content=str_replace($marker,$marker.$block,$content);
            $response->setContent($content);
        }
        return $response;
    }
}
