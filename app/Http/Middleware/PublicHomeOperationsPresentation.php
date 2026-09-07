<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeOperationsPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</head>')) {
            return $response;
        }

        $loginUrl = route('login');
        $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');

        $style = <<<'HTML'
<style id="unifco-operations-layout-final">
/* Final, isolated presentation for the two operations cards. */
.operations{padding:38px 0 64px!important;background:linear-gradient(180deg,#f7f9fc 0%,#fff 100%)!important}
.operation-split{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;gap:18px!important;align-items:stretch!important;overflow:visible!important;box-shadow:none!important;border-radius:0!important;direction:ltr!important}
.operation-card{min-width:0!important;min-height:430px!important;border-radius:18px!important;overflow:hidden!important;border:1px solid #dce4ee!important;box-shadow:0 16px 38px rgba(7,31,77,.09)!important}

/* Maintenance card */
.maintenance-card{display:grid!important;grid-template-columns:34% 66%!important;padding:0!important;background:linear-gradient(145deg,#08275a 0%,#0c3974 100%)!important;color:#fff!important;direction:ltr!important;border-color:#143e74!important}
.maintenance-photo{min-height:430px!important;height:100%!important;overflow:hidden!important}
.maintenance-photo img{width:100%!important;height:100%!important;object-fit:cover!important;object-position:center!important}
.operation-content{padding:38px 32px!important;display:flex!important;flex-direction:column!important;justify-content:center!important;direction:rtl!important;text-align:right!important}
.maintenance-card h2{font-size:clamp(25px,2vw,31px)!important;line-height:1.4!important;color:#fff!important;margin:0 0 12px!important;padding-bottom:15px!important;position:relative!important}
.maintenance-card h2:after{content:""!important;position:absolute!important;right:0!important;left:auto!important;bottom:0!important;width:44px!important;height:4px!important;border-radius:99px!important;background:#ce122d!important}
.maintenance-card p{max-width:none!important;margin:0!important;color:#dbe6f4!important;font-size:11px!important;line-height:1.9!important}
.maintenance-card .check-grid{display:grid!important;grid-template-columns:1fr 1fr!important;gap:11px 16px!important;margin:20px 0 24px!important}
.maintenance-card .check{font-size:10px!important;color:#fff!important;font-weight:800!important;white-space:normal!important}
.maintenance-card>.btn,.maintenance-card .operation-content>.btn{align-self:stretch!important;min-width:0!important}

/* Portal card: content on the left, dashboard on the right. */
.portal-card{position:relative!important;display:grid!important;grid-template-columns:minmax(0,1.03fr) minmax(300px,.97fr)!important;grid-template-areas:"title device" "copy device" "checks device" "button device"!important;grid-template-rows:auto auto auto 1fr!important;column-gap:30px!important;row-gap:0!important;align-content:center!important;align-items:center!important;min-height:430px!important;padding:38px 30px 34px 34px!important;background:linear-gradient(145deg,#fff 0%,#f7f9fc 100%)!important;direction:ltr!important;isolation:isolate!important;overflow:hidden!important}
.portal-card:before{content:""!important;position:absolute!important;inset:0!important;background:radial-gradient(circle at 84% 60%,rgba(7,31,77,.06),transparent 40%)!important;pointer-events:none!important;z-index:0!important}
.portal-card h2,.portal-card>p,.portal-card .check-grid,.portal-card>.btn{position:relative!important;z-index:2!important;width:100%!important;max-width:none!important;min-width:0!important;direction:rtl!important;text-align:right!important}
.portal-card h2{grid-area:title!important;align-self:end!important;font-size:clamp(25px,2vw,31px)!important;line-height:1.4!important;color:#071f4d!important;margin:0 0 12px!important;padding:0 0 15px!important;white-space:normal!important;word-break:normal!important;overflow-wrap:normal!important}
.portal-card h2:after{content:""!important;position:absolute!important;right:0!important;left:auto!important;bottom:0!important;width:44px!important;height:4px!important;border-radius:99px!important;background:#ce122d!important}
.portal-card>p{grid-area:copy!important;margin:0!important;color:#68758a!important;font-size:11px!important;line-height:1.9!important}
.portal-card .check-grid{grid-area:checks!important;display:grid!important;grid-template-columns:1fr 1fr!important;gap:11px 16px!important;margin:20px 0 24px!important}
.portal-card .check{font-size:10px!important;color:#071f4d!important;font-weight:800!important;white-space:normal!important}
.portal-card>.btn{grid-area:button!important;justify-self:start!important;align-self:start!important;width:auto!important;min-width:180px!important;display:inline-flex!important;z-index:8!important;pointer-events:auto!important;cursor:pointer!important;touch-action:manipulation!important}
.portal-device{grid-area:device!important;position:relative!important;z-index:2!important;right:auto!important;left:auto!important;bottom:auto!important;top:auto!important;transform:none!important;transform-origin:center!important;justify-self:end!important;align-self:center!important;width:100%!important;height:320px!important;max-width:100%!important;max-height:320px!important;margin:0!important;object-fit:contain!important;object-position:center!important;filter:drop-shadow(0 22px 24px rgba(9,29,58,.17))!important;pointer-events:none!important}

@media(max-width:1180px){
  .portal-card{grid-template-columns:minmax(0,1fr) minmax(260px,.9fr)!important;column-gap:22px!important;padding:32px 24px 30px!important}
  .portal-device{height:280px!important;max-height:280px!important}
  .maintenance-card{grid-template-columns:36% 64%!important}
  .operation-content{padding:32px 26px!important}
}
@media(max-width:980px){
  .operation-split{grid-template-columns:1fr!important}
  .operation-card{min-height:390px!important}
  .portal-card{grid-template-columns:minmax(0,1fr) minmax(280px,.9fr)!important}
}
@media(max-width:700px){
  .operations{padding:24px 0 44px!important}
  .operation-card{min-height:0!important;border-radius:14px!important}
  .maintenance-card{grid-template-columns:1fr!important}
  .maintenance-photo{min-height:240px!important;max-height:240px!important}
  .operation-content{padding:27px 21px!important}
  .portal-card{display:grid!important;grid-template-columns:1fr!important;grid-template-areas:"title" "copy" "checks" "device" "button"!important;min-height:0!important;padding:27px 21px!important;row-gap:8px!important}
  .portal-card .check-grid,.maintenance-card .check-grid{grid-template-columns:1fr 1fr!important;gap:10px 12px!important}
  .portal-device{width:100%!important;height:220px!important;max-height:220px!important;justify-self:center!important;margin:4px 0 10px!important}
  .portal-card>.btn{justify-self:stretch!important;width:100%!important}
}
@media(max-width:430px){
  .portal-card .check-grid,.maintenance-card .check-grid{grid-template-columns:1fr!important}
  .portal-device{height:190px!important;max-height:190px!important}
}
</style>
HTML;

        $script = '<script id="unifco-portal-login-action">document.addEventListener("DOMContentLoaded",function(){var b=document.querySelector(".portal-card > .btn");if(!b)return;b.setAttribute("href","'.$safeLoginUrl.'");b.setAttribute("role","link");b.addEventListener("click",function(e){e.preventDefault();window.location.assign("'.$safeLoginUrl.'");});});</script>';

        $response->setContent(str_replace('</head>', $style."\n".$script."\n</head>", $html));
        return $response;
    }
}
