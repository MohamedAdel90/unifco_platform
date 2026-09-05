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
.cms-display-mode-box{margin:0 0 14px;padding:12px;border:1px solid #cfdced;border-radius:10px;background:#f7faff}.cms-display-mode-box label{display:block!important;margin:0 0 6px!important;font-size:11px!important;font-weight:800!important;color:#263b5d!important}.cms-display-mode-box select{width:100%;padding:9px 10px;border:1px solid #c8d5e5;border-radius:8px;background:#fff;color:#17243c;font-size:12px}.cms-full-image-field{margin-top:10px}.cms-full-image-field .hp-img-field{margin-top:4px}.cms-display-mode-help{margin-top:6px;font-size:10px;color:#6d7e96;line-height:1.45}
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

  function syncHeroCompatibility(select){
    if(!select || select.name.indexOf('scalar_')!==0 || !select.name.endsWith('_display_mode'))return;
    var locale=fieldLocale(select.name);
    if(!locale)return;
    var legacy=document.querySelector('[name="scalar_'+locale+'_render_mode"]');
    if(legacy){
      legacy.value='background';
      var parent=legacy.closest('label')||legacy.parentElement;
      if(parent)parent.style.display='none';
      if(legacy.previousElementSibling && legacy.previousElementSibling.tagName==='LABEL')legacy.previousElementSibling.style.display='none';
      var help=document.querySelector('[data-hero-mode-help="'+locale+'"]');if(help)help.style.display='none';
    }
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
      box.innerHTML='<label>Display Mode</label><div class="cms-mode-slot"></div><div class="cms-display-mode-help">Structured Section keeps the editable CMS layout. Full Section Image replaces that layout with one complete image.</div>';
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

  injectPortalFields();
  groupDisplayFields();
})();
</script>
HTML;

        $injection = str_replace('__PORTAL_PAYLOAD__', $payload, $injection);
        $response->setContent(str_replace('</body>', $injection."\n</body>", $html));

        return $response;
    }
}
