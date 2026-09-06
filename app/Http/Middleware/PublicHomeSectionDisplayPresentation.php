<?php

namespace App\Http\Middleware;

use App\Models\HomepageSection;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeSectionDisplayPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</body>')) {
            return $response;
        }

        $locale = str_contains($html, '<html lang="en"') ? 'en' : 'ar';
        $payload = [];

        foreach (HomepageSection::query()->where('is_active', true)->get() as $section) {
            $data = $locale === 'en' ? ($section->data_en ?? []) : ($section->data_ar ?? []);

            if ($section->section_key === 'operations') {
                foreach (['maintenance', 'portal'] as $prefix) {
                    $payload[$prefix] = [
                        'mode' => $data[$prefix.'_display_mode'] ?? 'structured',
                        'image' => $data[$prefix.'_full_section_image'] ?? '',
                    ];
                }
                continue;
            }

            $payload[$section->section_key] = [
                'mode' => $data['display_mode'] ?? 'structured',
                'image' => $data['full_section_image'] ?? '',
            ];
        }

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';

        $script = <<<'HTML'
<style id="unifco-full-section-image-style">
.unifco-full-section-image{width:100%;margin:0;padding:0;line-height:0;background:#fff;overflow:hidden}.unifco-full-section-image img{display:block;width:100%;height:auto;max-width:none;margin:0;padding:0;object-fit:contain}.unifco-full-card-image{height:100%;min-height:100%;display:flex;align-items:center;justify-content:center;background:#fff}.unifco-full-card-image img{width:100%;height:100%;object-fit:cover}
.unifco-full-hero-image{position:relative;left:50%;transform:translateX(-50%);width:min(1400px,calc(100vw - 48px));max-width:none;margin:20px 0 18px;border-radius:14px;background:#071f4d;box-shadow:0 14px 36px rgba(7,31,77,.13);overflow:hidden}.unifco-full-hero-image img{width:100%;height:auto;max-height:none;object-fit:contain;object-position:center center}
@media(max-width:900px){.unifco-full-hero-image{width:calc(100vw - 24px);margin:12px 0 12px;border-radius:10px;box-shadow:0 8px 22px rgba(7,31,77,.11)}}
@media(max-width:520px){.unifco-full-hero-image{width:100vw;margin:0;border-radius:0;box-shadow:none}.unifco-full-hero-image img{width:100%;height:auto}}
</style>
<script>
(function(){
  var config=__DISPLAY_PAYLOAD__;
  var selectorMap={
    hero:['.hero'],
    capabilities:['.capability-bar'],
    about:['.about-grid'],
    services:['.service-grid'],
    process:['.process-wrap','.process'],
    industries:['.industry-grid'],
    maintenance:['.maintenance-card'],
    portal:['.portal-card'],
    why:['.why-grid'],
    showcase:['.project-panel','.showcase'],
    clients:['.client-strip','.clients-grid','.clients-showcase','.clients'],
    emergency:['.emergency-panel','.emergency-cta'],
    footer_cta:['.final-cta'],
    footer:['footer']
  };

  function findTarget(key){
    var selectors=selectorMap[key]||[];
    for(var i=0;i<selectors.length;i++){
      var el=document.querySelector(selectors[i]);
      if(!el)continue;
      if(['maintenance','portal','showcase','emergency','footer_cta','footer','hero','capabilities','process'].indexOf(key)!==-1)return el;
      return el.closest('section')||el.closest('.section')||el;
    }
    return null;
  }

  function apply(key,item){
    if(!item || item.mode!=='full_section_image' || !item.image)return;
    var target=findTarget(key);if(!target)return;
    var wrapper=document.createElement('div');
    if(key==='hero'){
      wrapper.className='unifco-full-section-image unifco-full-hero-image';
    }else if(key==='maintenance'||key==='portal'||key==='showcase'||key==='emergency'){
      wrapper.className='unifco-full-section-image unifco-full-card-image';
    }else{
      wrapper.className='unifco-full-section-image';
    }
    var img=document.createElement('img');img.src=item.image;img.alt='';wrapper.appendChild(img);
    target.replaceWith(wrapper);
  }

  Object.keys(config||{}).forEach(function(key){apply(key,config[key]);});
})();
</script>
HTML;

        $script = str_replace('__DISPLAY_PAYLOAD__', $json, $script);
        $response->setContent(str_replace('</body>', $script."\n</body>", $html));

        return $response;
    }
}
