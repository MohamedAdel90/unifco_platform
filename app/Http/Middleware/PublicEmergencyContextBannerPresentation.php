<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicEmergencyContextBannerPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) return $response;

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="service-subtype"')) return $response;

        $style = <<<'HTML'
<style id="unifco-emergency-context-banner-v1">
#uf-emergency-context-banner{display:none;margin:10px 0 14px;border:1px solid #ef3348;border-radius:10px;background:linear-gradient(90deg,#fff7f8,#fff1f3);min-height:74px;padding:13px 18px;color:#c8172c;align-items:center;gap:16px;direction:rtl}
#uf-emergency-context-banner.show{display:flex}.uf-ecb-icon{width:50px;height:50px;flex:0 0 50px;border-left:1px solid #f0a7b0;display:grid;place-items:center;font-size:29px}.uf-ecb-copy{flex:1;min-width:0}.uf-ecb-copy strong{display:block;font-size:16px;font-weight:900;margin-bottom:3px}.uf-ecb-copy span{display:block;font-size:10.5px;font-weight:800;line-height:1.7}.uf-ecb-priority{flex:0 0 260px;text-align:center;font-size:11px;font-weight:900;line-height:1.7;border-right:1px solid #f0a7b0;padding-right:16px}
@media(max-width:700px){#uf-emergency-context-banner{align-items:flex-start;padding:12px}.uf-ecb-icon{width:40px;height:40px;flex-basis:40px;font-size:23px}.uf-ecb-copy strong{font-size:14px}.uf-ecb-priority{display:none}}
</style>
HTML;
        $script = <<<'HTML'
<script id="unifco-emergency-context-banner-script-v1">
(()=>{const subtype=document.getElementById('service-subtype');if(!subtype)return;let banner=document.getElementById('uf-emergency-context-banner');if(!banner){banner=document.createElement('div');banner.id='uf-emergency-context-banner';banner.innerHTML='<div class="uf-ecb-icon" aria-hidden="true">🚨</div><div class="uf-ecb-copy"><strong>طلب صيانة طارئة</strong><span>هذا الطلب مخصص للأعطال العاجلة التي تؤثر على التشغيل أو السلامة، وسيتم التعامل معه بأولوية قصوى من فريقنا.</span></div><div class="uf-ecb-priority">سلامة منشأتك واستمرارية أعمالك<br>هي أولويتنا</div>';const topPanel=subtype.closest('section.panel')||subtype.closest('.panel');if(topPanel)topPanel.appendChild(banner)}const sync=()=>banner.classList.toggle('show',subtype.value==='urgent');subtype.addEventListener('change',sync);sync()})();
</script>
HTML;
        if (str_contains($html, '</head>')) $html = str_replace('</head>', $style."\n</head>", $html);
        if (str_contains($html, '</body>')) $html = str_replace('</body>', $script."\n</body>", $html);
        $response->setContent($html);
        return $response;
    }
}
