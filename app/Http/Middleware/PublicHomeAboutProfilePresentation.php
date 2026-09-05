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
            ? '/images/home/unifco-about-card-ar.webp?v=20260905-2'
            : '/images/home/unifco-about-card-en.webp?v=20260905-2';
        $alt = $isArabic ? 'تعرف على UNIFCO' : 'About UNIFCO';
        $close = $isArabic ? 'إغلاق' : 'Close';
        $localeClass = $isArabic ? 'is-arabic' : 'is-english';

        $style = <<<'HTML'
<style id="unifco-profile-modal-style">
#unifco-profile-modal{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:8px;background:rgba(5,18,39,.82);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
#unifco-profile-modal.is-open{display:flex}
.unifco-profile-dialog{position:relative;width:98vw;height:96vh;border-radius:18px;overflow:auto;background:#0a1730;box-shadow:0 34px 100px rgba(0,0,0,.45);-webkit-overflow-scrolling:touch}
.unifco-profile-artwork{position:relative;display:flex;align-items:flex-start;justify-content:center;min-width:100%;min-height:100%}
.unifco-profile-artwork img{display:block;height:auto;object-fit:contain;background:#fff}
.unifco-profile-dialog.is-english .unifco-profile-artwork{align-items:center}
.unifco-profile-dialog.is-english .unifco-profile-artwork img{width:auto;max-width:96vw;max-height:94vh;border-radius:16px}
.unifco-profile-dialog.is-arabic .unifco-profile-artwork{justify-content:flex-start}
.unifco-profile-dialog.is-arabic .unifco-profile-artwork img{width:384vw;max-width:none;max-height:none;border-radius:10px}
.unifco-profile-close{position:fixed;z-index:4;top:18px;left:18px;width:44px;height:44px;border:1px solid rgba(18,43,82,.13);border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.96);color:#102b55;font-size:26px;line-height:1;cursor:pointer;box-shadow:0 4px 18px rgba(0,0,0,.16)}
.unifco-profile-image-error{display:none;align-items:center;justify-content:center;min-height:55vh;width:100%;padding:32px;color:#fff;text-align:center;font-size:16px}
.unifco-profile-artwork.has-error .unifco-profile-image-error{display:flex}
.unifco-profile-artwork.has-error img{display:none}
body.unifco-profile-open{overflow:hidden}
@media(min-width:900px){.unifco-profile-dialog.is-arabic .unifco-profile-artwork img{width:180vw}}
@media(max-width:620px){#unifco-profile-modal{padding:3px}.unifco-profile-dialog{width:99vw;height:97vh;border-radius:10px}.unifco-profile-dialog.is-english .unifco-profile-artwork img{max-width:98vw;max-height:95vh;border-radius:10px}.unifco-profile-dialog.is-arabic .unifco-profile-artwork img{width:392vw}.unifco-profile-close{top:12px;left:12px;width:38px;height:38px;font-size:22px}}
</style>
HTML;

        $errorText = $isArabic
            ? 'تعذر تحميل صورة بطاقة UNIFCO. أعد تحميل الصفحة.'
            : 'The UNIFCO profile artwork could not be loaded. Please refresh the page.';

        $modal = '<div id="unifco-profile-modal" aria-hidden="true" role="dialog" aria-modal="true">'
            .'<div class="unifco-profile-dialog '.$localeClass.'">'
            .'<button type="button" class="unifco-profile-close" data-unifco-profile-close aria-label="'.e($close).'">×</button>'
            .'<div class="unifco-profile-artwork">'
            .'<img src="'.e($image).'" alt="'.e($alt).'" loading="eager" decoding="sync" data-unifco-profile-image>'
            .'<div class="unifco-profile-image-error">'.e($errorText).'</div>'
            .'</div></div></div>';

        $script = <<<'HTML'
<script id="unifco-profile-modal-script">
(()=>{
 const modal=document.getElementById('unifco-profile-modal');
 if(!modal)return;
 const trigger=document.querySelector('#about .about-copy > .btn');
 const image=modal.querySelector('[data-unifco-profile-image]');
 const artwork=modal.querySelector('.unifco-profile-artwork');
 const dialog=modal.querySelector('.unifco-profile-dialog');
 const close=()=>{modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');document.body.classList.remove('unifco-profile-open');};
 const open=()=>{
   modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.classList.add('unifco-profile-open');
   if(dialog&&dialog.classList.contains('is-arabic')){dialog.scrollTop=0;dialog.scrollLeft=0;}
   modal.querySelector('[data-unifco-profile-close]')?.focus();
 };
 if(image){image.addEventListener('error',()=>{artwork?.classList.add('has-error');});}
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
