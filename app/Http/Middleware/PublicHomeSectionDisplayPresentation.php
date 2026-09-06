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

        // The public homepage locale is selected by the lang query parameter.
        // Do not infer it from serialized HTML because attribute formatting/order can vary.
        $locale = $request->query('lang') === 'en' ? 'en' : 'ar';
        $payload = [];

        foreach (HomepageSection::query()->where('is_active', true)->get() as $section) {
            $data = $locale === 'en' ? ($section->data_en ?? []) : ($section->data_ar ?? []);

            if ($section->section_key === 'operations') {
                $operationLinks = [
                    'maintenance' => [
                        'href' => route('public.request-service'),
                        'label' => $locale === 'en' ? 'Explore Maintenance Services' : 'تعرف على خدمات الصيانة',
                    ],
                    'portal' => [
                        'href' => route('login'),
                        'label' => $locale === 'en' ? 'Client Login' : 'دخول حساب العميل',
                    ],
                ];

                foreach (['maintenance', 'portal'] as $prefix) {
                    $payload[$prefix] = [
                        'mode' => $data[$prefix.'_display_mode'] ?? 'structured',
                        'image' => $data[$prefix.'_full_section_image'] ?? '',
                        'href' => $operationLinks[$prefix]['href'],
                        'label' => $operationLinks[$prefix]['label'],
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
.unifco-full-section-image{width:100%;margin:0;padding:0;line-height:0;background:#fff;overflow:hidden}.unifco-full-section-image img{display:block;width:100%;height:auto;max-width:none;margin:0;padding:0;object-fit:contain}.unifco-full-card-image{position:relative;height:100%;min-height:100%;display:flex;align-items:center;justify-content:center;background:#fff;overflow:hidden}.unifco-full-card-image img{width:100%;height:100%;object-fit:cover}.unifco-full-card-hotspot{position:absolute;z-index:8;display:block;background:transparent;border-radius:8px;cursor:pointer;-webkit-tap-highlight-color:transparent;touch-action:manipulation}.unifco-full-card-hotspot:focus-visible{outline:3px solid #fff;outline-offset:2px;box-shadow:0 0 0 5px rgba(206,18,45,.9)}.unifco-full-card-hotspot--maintenance{left:41.5%;top:86.2%;width:55.5%;height:10.2%}.unifco-full-card-hotspot--portal{left:57.5%;top:78.3%;width:39%;height:11.8%}
.unifco-full-hero-image{position:relative;left:auto;transform:none;width:100vw;max-width:none;margin:0 calc(50% - 50vw);border-radius:0;background:#071f4d;box-shadow:none;overflow:hidden}.unifco-full-hero-image img{display:block;width:100vw;height:auto;max-width:none;max-height:none;object-fit:contain;object-position:center center}
@media(max-width:900px){.unifco-full-hero-image{width:100vw;margin:0 calc(50% - 50vw);border-radius:0;box-shadow:none}.unifco-full-hero-image img{width:100vw;height:auto}}
@media(max-width:520px){.unifco-full-hero-image{width:100vw;margin:0 calc(50% - 50vw);border-radius:0;box-shadow:none}.unifco-full-hero-image img{width:100vw;height:auto}.unifco-full-card-hotspot--maintenance{left:40.5%;top:85.7%;width:57%;height:11%}.unifco-full-card-hotspot--portal{left:56.5%;top:77.5%;width:40.5%;height:13%}}
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

  function addHotspot(wrapper,key,item){
    if((key!=='maintenance'&&key!=='portal')||!item.href)return;
    var link=document.createElement('a');
    link.className='unifco-full-card-hotspot unifco-full-card-hotspot--'+key;
    link.href=item.href;
    link.setAttribute('aria-label',item.label||'Open');
    link.setAttribute('title',item.label||'Open');
    link.setAttribute('data-operation-action',key);
    wrapper.appendChild(link);
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
    addHotspot(wrapper,key,item);
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
