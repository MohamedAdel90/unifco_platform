<?php

namespace App\Http\Middleware;

use App\Models\HomepageSection;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CmsDisplayModePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user() || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $isCms = $request->routeIs('admin.homepage.sections.edit', 'admin.maintenance-cms', 'admin.client-portal-cms');
        if (! $isCms) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</body>')) {
            return $response;
        }

        $portalPayload = [];
        if ($request->routeIs('admin.maintenance-cms', 'admin.client-portal-cms')) {
            $section = HomepageSection::query()->where('section_key', 'operations')->first();
            if ($section) {
                $prefix = $request->routeIs('admin.maintenance-cms') ? 'maintenance' : 'portal';
                $portalPayload = [
                    'prefix' => $prefix,
                    'ar' => [
                        'display_mode' => $section->data_ar[$prefix.'_display_mode'] ?? 'structured',
                        'full_section_image' => $section->data_ar[$prefix.'_full_section_image'] ?? '',
                    ],
                    'en' => [
                        'display_mode' => $section->data_en[$prefix.'_display_mode'] ?? 'structured',
                        'full_section_image' => $section->data_en[$prefix.'_full_section_image'] ?? '',
                    ],
                ];
            }
        }

        $payload = json_encode($portalPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';

        $injection = <<<'HTML'
<style id="cms-display-mode-style">
.cms-display-mode-box{margin:0 0 14px;padding:12px;border:1px solid #cfdced;border-radius:10px;background:#f7faff}.cms-display-mode-box label{display:block!important;margin:0 0 6px!important;font-size:11px!important;font-weight:800!important;color:#263b5d!important}.cms-display-mode-box select{width:100%;padding:9px 10px;border:1px solid #c8d5e5;border-radius:8px;background:#fff;color:#17243c;font-size:12px}.cms-full-image-field{margin-top:10px}.cms-full-image-field .hp-img-field{margin-top:4px}.cms-display-mode-help{margin-top:6px;font-size:10px;color:#6d7e96;line-height:1.45}.cms-legacy-hero-mode{display:none!important}
.cms-image-dimensions{grid-column:1/-1;display:flex;align-items:center;gap:6px;margin-top:3px;padding:6px 8px;border:1px solid #d9e5f2;border-radius:7px;background:#f7faff;color:#526780;font-size:10px;font-weight:700;line-height:1.35}.cms-image-dimensions strong{color:#17366c;font-size:10px}.cms-image-dimensions[data-state="loading"]{color:#7b8798}.cms-image-dimensions[data-state="error"]{color:#9b3444;background:#fff7f8;border-color:#f0d7dc}.cms-image-dimensions[data-state="empty"]{color:#8592a4;background:#fafbfd}.cms-image-dimensions .cms-image-ratio{font-weight:600;color:#73839a}
</style>
<script>
(function(){
  var portalPayload=__PORTAL_PAYLOAD__;

  function makeSelect(input){
    if(!input || input.dataset.displayModeReady==='1')return;
    input.dataset.displayModeReady='1';
    var select=document.createElement('select');
    select.name=input.name;
    select.innerHTML='<option value="structured">Structured Section</option><option value="full_section_image">Full Section Image</option>';
    select.value=(input.value==='full_section_image')?'full_section_image':'structured';
    input.removeAttribute('name');
    input.style.display='none';
    input.parentNode.insertBefore(select,input.nextSibling);
    select.addEventListener('change',function(){
      input.value=select.value;
      input.dispatchEvent(new Event('input',{bubbles:true}));
      updateVisibility(select);
      syncHeroCompatibility(select);
    });
    updateVisibility(select);
    syncHeroCompatibility(select);
  }

  function fieldLocale(name){return name.indexOf('_ar_')!==-1?'ar':(name.indexOf('_en_')!==-1?'en':'');}

  function hideLegacyHeroMode(locale){
    var legacy=document.querySelector('[name="scalar_'+locale+'_render_mode"]');
    if(!legacy)return;
    legacy.value='background';
    legacy.classList.add('cms-legacy-hero-mode');
    var label=legacy.previousElementSibling;
    if(label && label.tagName==='LABEL')label.classList.add('cms-legacy-hero-mode');
    var help=document.querySelector('[data-hero-mode-help="'+locale+'"]');
    if(help)help.classList.add('cms-legacy-hero-mode');
  }

  function syncHeroCompatibility(select){
    if(!select || select.name.indexOf('scalar_')!==0 || !select.name.endsWith('_display_mode'))return;
    var locale=fieldLocale(select.name);
    if(locale)hideLegacyHeroMode(locale);
  }

  function updateVisibility(select){
    var box=select.closest('.cms-display-mode-box');
    if(!box)return;
    var image=box.querySelector('.cms-full-image-field');
    if(image)image.style.display=select.value==='full_section_image'?'block':'none';
  }

  function enhanceFullImage(input){
    if(!input || input.dataset.fullImageReady==='1')return;
    input.dataset.fullImageReady='1';
    var value=input.value||'';
    input.classList.add('img-picker-target','hp-img-input');
    input.setAttribute('data-img-label','Full Section Image');
    var wrap=document.createElement('div');
    wrap.className='cms-full-image-field';
    wrap.innerHTML='<label>Full Section Image</label><div class="hp-img-field" data-img-field><div class="hp-img-preview" data-img-preview>'+(value?'<img src="'+value.replace(/"/g,'&quot;')+'" alt=""><button type="button" class="hp-img-clear" data-img-clear>×</button>':'<span class="hp-img-ph">No image</span>')+'</div><div><div class="cms-image-input-slot"></div><div class="hp-img-actions"><button type="button" class="btn-sm hp-img-select">Choose Existing</button> <button type="button" class="btn-sm hp-img-upload">Upload Image</button></div></div></div>';
    var slot=wrap.querySelector('.cms-image-input-slot');
    input.parentNode.insertBefore(wrap,input);
    slot.appendChild(input);
  }

  function groupDisplayFields(){
    document.querySelectorAll('input[name$="_display_mode"]').forEach(function(input){
      var fullName=input.name.replace(/_display_mode$/,'_full_section_image');
      var full=document.querySelector('[name="'+CSS.escape(fullName)+'"]');
      if(!full)return;
      enhanceFullImage(full);
      var fullWrap=full.closest('.cms-full-image-field');
      var box=document.createElement('div');
      box.className='cms-display-mode-box';
      box.innerHTML='<label>Display Mode</label><div class="cms-mode-slot"></div><div class="cms-display-mode-help"><b>Structured Section</b>: editable CMS content. <b>Full Section Image</b>: one complete image replaces the structured layout.</div>';
      input.parentNode.insertBefore(box,input);
      box.querySelector('.cms-mode-slot').appendChild(input);
      if(fullWrap)box.appendChild(fullWrap);
      makeSelect(input);
    });
  }

  function injectPortalFields(){
    if(!portalPayload || !portalPayload.prefix)return;
    document.querySelectorAll('.cms-card').forEach(function(card,index){
      var locale=index===0?'ar':'en';
      var data=portalPayload[locale]||{};
      if(card.querySelector('[name="scalar_'+locale+'_'+portalPayload.prefix+'_display_mode"]'))return;
      var box=document.createElement('div');
      box.innerHTML='<input type="text" name="scalar_'+locale+'_'+portalPayload.prefix+'_display_mode" value="'+String(data.display_mode||'structured').replace(/"/g,'&quot;')+'"><input type="text" name="scalar_'+locale+'_'+portalPayload.prefix+'_full_section_image" value="'+String(data.full_section_image||'').replace(/"/g,'&quot;')+'">';
      card.insertBefore(box,card.children[1]||null);
    });
  }

  function gcd(a,b){while(b){var t=b;b=a%b;a=t;}return a||1;}

  function dimensionHost(input){
    return input.closest('.hp-img-field') || input.closest('[data-img-field]') || input.parentElement;
  }

  function ensureDimensionBadge(input){
    if(!input)return null;
    var host=dimensionHost(input);
    if(!host)return null;
    var badge=host.querySelector(':scope > .cms-image-dimensions');
    if(!badge){
      badge=document.createElement('div');
      badge.className='cms-image-dimensions';
      badge.setAttribute('data-state','empty');
      host.appendChild(badge);
    }
    return badge;
  }

  function setDimensionText(input,state,html){
    var badge=ensureDimensionBadge(input);
    if(!badge)return;
    badge.setAttribute('data-state',state);
    badge.innerHTML=html;
  }

  function updateImageDimensions(input){
    if(!input)return;
    var src=String(input.value||'').trim();
    var token=String(Date.now())+Math.random();
    input.dataset.dimensionToken=token;
    if(!src){
      setDimensionText(input,'empty','<strong>Image dimensions:</strong> No image selected');
      return;
    }
    setDimensionText(input,'loading','<strong>Image dimensions:</strong> Reading…');
    var img=new Image();
    img.onload=function(){
      if(input.dataset.dimensionToken!==token)return;
      var w=img.naturalWidth||img.width||0;
      var h=img.naturalHeight||img.height||0;
      if(!w||!h){setDimensionText(input,'error','<strong>Image dimensions:</strong> Unable to read');return;}
      var d=gcd(w,h),rw=Math.round(w/d),rh=Math.round(h/d);
      var ratio=(rw<=40&&rh<=40)?rw+':'+rh:(w/h).toFixed(2)+':1';
      setDimensionText(input,'ready','<strong>Image dimensions:</strong> '+w+' × '+h+' px <span class="cms-image-ratio">• Ratio '+ratio+'</span>');
    };
    img.onerror=function(){
      if(input.dataset.dimensionToken!==token)return;
      setDimensionText(input,'error','<strong>Image dimensions:</strong> Could not read this image');
    };
    img.src=src;
  }

  function scanImageDimensions(root){
    root=root||document;
    var inputs=[];
    if(root.matches && root.matches('.img-picker-target,.hp-img-input,[data-img-input]'))inputs.push(root);
    if(root.querySelectorAll)root.querySelectorAll('.img-picker-target,.hp-img-input,[data-img-input]').forEach(function(i){inputs.push(i);});
    inputs.forEach(function(input){
      if(input.dataset.dimensionReady==='1')return;
      input.dataset.dimensionReady='1';
      input.addEventListener('input',function(){updateImageDimensions(input);});
      input.addEventListener('change',function(){updateImageDimensions(input);});
      updateImageDimensions(input);
    });
  }

  injectPortalFields();
  groupDisplayFields();
  hideLegacyHeroMode('ar');
  hideLegacyHeroMode('en');
  scanImageDimensions(document);

  var observer=new MutationObserver(function(records){
    records.forEach(function(record){
      record.addedNodes.forEach(function(node){if(node.nodeType===1)scanImageDimensions(node);});
    });
  });
  observer.observe(document.body,{childList:true,subtree:true});
})();
</script>
HTML;

        $injection = str_replace('__PORTAL_PAYLOAD__', $payload, $injection);
        $response->setContent(str_replace('</body>', $injection."\n</body>", $html));

        return $response;
    }
}
