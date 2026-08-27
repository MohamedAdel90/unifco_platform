<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerPortalMobileLogoutPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response=$next($request);
        $user=$request->user();

        if(!$user || $user->role!=='CUSTOMER' || !method_exists($response,'getContent') || !method_exists($response,'setContent')) {
            return $response;
        }

        $html=(string)$response->getContent();
        if($html==='' || !str_contains(strtolower($response->headers->get('Content-Type','text/html')),'text/html')) {
            return $response;
        }

        if(str_contains($html,'data-customer-mobile-logout')) {
            return $response;
        }

        $logout='<style data-customer-mobile-logout>\n'
            .'@media (max-width:760px){.customer-mobile-logout{display:block!important;position:fixed;right:14px;bottom:14px;z-index:9999;margin:0}.customer-mobile-logout button{display:flex;align-items:center;gap:7px;border:0;border-radius:999px;background:#06275c;color:#fff;padding:10px 14px;box-shadow:0 8px 24px rgba(3,31,76,.28);font:700 12px Inter,Arial,sans-serif;cursor:pointer}.customer-mobile-logout button:before{content:"↪";font-size:14px}}\n'
            .'@media (min-width:761px){.customer-mobile-logout{display:none!important}}\n'
            .'</style>'
            .'<form data-customer-mobile-logout class="customer-mobile-logout" method="POST" action="'.e(route('logout')).'">'
            .'<input type="hidden" name="_token" value="'.e(csrf_token()).'">'
            .'<button type="submit" aria-label="Sign out">Sign out</button>'
            .'</form>';

        $pos=strripos($html,'</body>');
        if($pos!==false) $html=substr($html,0,$pos).$logout.substr($html,$pos);
        else $html.=$logout;

        $response->setContent($html);
        return $response;
    }
}
