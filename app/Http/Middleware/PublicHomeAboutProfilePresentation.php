<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeAboutProfilePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || str_contains($html, 'id="unifco-profile-modal"')) {
            return $response;
        }

        $isArabic = str_contains($html, '<html lang="ar"');
        $image = $isArabic
            ? '/images/home/unifco-about-card-ar.webp'
            : '/images/home/unifco-about-card-en.webp';
        $alt = $isArabic ? 'تعرف على UNIFCO' : 'About UNIFCO';
        $close = $isArabic ? 'إغلاق' : 'Close';

        $style = <<<'HTML'
<style id="unifco-profile-modal-style">
#unifco-profile-modal{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:24px;background:rgba(5,18,39,.78);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
#unifco-profile-modal.is-open{display:flex}
.unifco-profile-dialog{position:relative;display:inline-block;max-width:96vw;max-height:94vh;border-radius:18px;overflow:hidden;background:transparent;box-shadow:0 34px 100px rgba(0,0,0,.4)}
.unifco-profile-artwork{display:flex;align-items:center;justify-content:center;max-width:96vw;max-height:94vh}
.unifco-profile-artwork img{display:block;width:auto;height:auto;max-width:96vw;max-height:94vh;object-fit:contain;border-radius:18px;background:#fff}
.unifco-profile-close{position:absolute;z-index:3;top:16px;left:16px;width:44px;height:44px;border:1px solid rgba(18,43,82,.13);border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.94);color:#102b55;font-size:26px;line-height:1;cursor:pointer;box-shadow:0 4px 18px rgba(0,0,0,.12)}
body.unifco-profile-open{overflow:hidden}
@media(max-width:620px){#unifco-profile-modal{padding:6px}.unifco-profile-dialog,.unifco-profile-artwork{max-width:98vw;max-height:96vh}.unifco-profile-artwork img{max-width:98vw;max-height:96vh;border-radius:10px}.unifco-profile-close{top:10px;left:10px;width:38px;height:38px;font-size:22px}}
</style>
HTML;

        $modal = '<div id="unifco-profile-modal" aria-hidden="true" role="dialog" aria-modal="true">'
            .'<div class="unifco-profile-dialog">'
            .'<button type="button" class="unifco-profile-close" data-unifco-profile-close aria-label="'.e($close).'">×</button>'
            .'<div class="unifco-profile-artwork"><img src="'.e($image).'" alt="'.e($alt).'"></div>'
            .'</div></div>';

        $script = <<<'HTML'
<script id="unifco-profile-modal-script">
(()=>{
 const modal=document.getElementById('unifco-profile-modal');
 if(!modal)return;
 const trigger=document.querySelector('#about .about-copy > .btn');
 const close=()=>{modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');document.body.classList.remove('unifco-profile-open');};
 const open=()=>{modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.classList.add('unifco-profile-open');modal.querySelector('[data-unifco-profile-close]')?.focus();};
 if(trigger){trigger.setAttribute('href','#unifco-profile-modal');trigger.setAttribute('aria-haspopup','dialog');trigger.addEventListener('click',e=>{e.preventDefault();open();});}
 modal.querySelector('[data-unifco-profile-close]')?.addEventListener('click',close);
 modal.addEventListener('click',e=>{if(e.target===modal)close();});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal.classList.contains('is-open'))close();});
})();
</script>
HTML;

        $html = str_replace('</head>', $style."\n</head>", $html);
        $html = str_replace('</body>', $modal."\n".$script."\n</body>", $html);
        $response->setContent($html);

        return $response;
    }
}
