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
        $title = $isArabic ? 'تعرف على UNIFCO' : 'Discover UNIFCO';
        $close = $isArabic ? 'إغلاق' : 'Close';
        $fullPage = $isArabic ? 'عرض صفحة من نحن كاملة' : 'View full About page';
        $aboutUrl = route('public.about');

        $style = <<<'HTML'
<style id="unifco-profile-modal-style">
#unifco-profile-modal{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:28px;background:rgba(3,18,42,.72);backdrop-filter:blur(7px)}
#unifco-profile-modal.is-open{display:flex}
.unifco-profile-dialog{position:relative;width:min(1050px,96vw);max-height:90vh;overflow:auto;border-radius:18px;background:#fff;box-shadow:0 28px 80px rgba(0,0,0,.32)}
.unifco-profile-head{position:sticky;top:0;z-index:3;display:flex;align-items:center;justify-content:space-between;gap:18px;padding:18px 22px;border-bottom:1px solid #e1e6ed;background:rgba(255,255,255,.97);backdrop-filter:blur(10px)}
.unifco-profile-head h2{margin:0;color:#071f4d;font-size:22px}
.unifco-profile-close{display:grid;width:38px;height:38px;place-items:center;border:1px solid #d9e0e9;border-radius:50%;background:#fff;color:#071f4d;font-size:22px;cursor:pointer}
.unifco-profile-body{padding:24px}
.unifco-profile-body .about-grid{margin:0}
.unifco-profile-body .about-copy .btn{display:none}
.unifco-profile-body .stats{margin-top:24px}
.unifco-profile-actions{display:flex;justify-content:center;padding:0 24px 26px}
body.unifco-profile-open{overflow:hidden}
@media(max-width:700px){#unifco-profile-modal{padding:10px}.unifco-profile-dialog{width:100%;max-height:94vh;border-radius:14px}.unifco-profile-head{padding:14px 16px}.unifco-profile-body{padding:16px}.unifco-profile-body .about-grid{display:block}.unifco-profile-body .about-media{margin-bottom:24px}.unifco-profile-body .about-media img{height:260px}.unifco-profile-body .about-stamp{right:8px;bottom:8px}.unifco-profile-body .stats{grid-template-columns:1fr 1fr}}
</style>
HTML;

        $modal = '<div id="unifco-profile-modal" role="dialog" aria-modal="true" aria-labelledby="unifco-profile-title" aria-hidden="true">'
            .'<div class="unifco-profile-dialog">'
            .'<div class="unifco-profile-head"><h2 id="unifco-profile-title">'.e($title).'</h2><button class="unifco-profile-close" type="button" data-unifco-profile-close aria-label="'.e($close).'">×</button></div>'
            .'<div class="unifco-profile-body" id="unifco-profile-body"></div>'
            .'<div class="unifco-profile-actions"><a class="btn" href="'.e($aboutUrl).'">'.e($fullPage).'</a></div>'
            .'</div></div>';

        $script = <<<'HTML'
<script id="unifco-profile-modal-script">
(()=>{
 const modal=document.getElementById('unifco-profile-modal');
 if(!modal)return;
 const body=modal.querySelector('#unifco-profile-body');
 const source=document.querySelector('#about .wrap');
 const trigger=document.querySelector('#about .about-copy > .btn');
 const close=()=>{modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');document.body.classList.remove('unifco-profile-open');};
 const open=()=>{
   if(body && source && !body.dataset.loaded){
     const grid=source.querySelector('.about-grid');
     const stats=source.querySelector('.stats');
     if(grid) body.appendChild(grid.cloneNode(true));
     if(stats) body.appendChild(stats.cloneNode(true));
     body.dataset.loaded='1';
   }
   modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.classList.add('unifco-profile-open');
   modal.querySelector('[data-unifco-profile-close]')?.focus();
 };
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
