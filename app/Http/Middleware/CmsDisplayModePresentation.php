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
        $uploadUrl = json_encode(route('admin.homepage.images.upload'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '""';

        $injection = <<<'HTML'
<style id="cms-display-mode-style">
.cms-display-mode-box{margin:0 0 14px;padding:12px;border:1px solid #cfdced;border-radius:10px;background:#f7faff}.cms-display-mode-box label{display:block!important;margin:0 0 6px!important;font-size:11px!important;font-weight:800!important;color:#263b5d!important}.cms-display-mode-box select{width:100%;padding:9px 10px;border:1px solid #c8d5e5;border-radius:8px;background:#fff;color:#17243c;font-size:12px}.cms-full-image-field{margin-top:10px}.cms-full-image-field .hp-img-field{margin-top:4px}.cms-display-mode-help{margin-top:6px;font-size:10px;color:#6d7e96;line-height:1.45}.cms-legacy-hero-mode{display:none!important}
.cms-image-dimensions{grid-column:1/-1;margin-top:4px;padding:8px;border:1px solid #d9e5f2;border-radius:8px;background:#f7faff;color:#526780;font-size:10px;font-weight:700;line-height:1.35}.cms-image-dimension-info{display:flex;align-items:center;gap:6px;min-height:18px}.cms-image-dimension-info strong{color:#17366c;font-size:10px}.cms-image-dimensions[data-state="loading"] .cms-image-dimension-info{color:#7b8798}.cms-image-dimensions[data-state="error"]{background:#fff7f8;border-color:#f0d7dc}.cms-image-dimensions[data-state="error"] .cms-image-dimension-info{color:#9b3444}.cms-image-dimensions[data-state="empty"]{background:#fafbfd}.cms-image-dimensions[data-state="empty"] .cms-image-dimension-info{color:#8592a4}.cms-image-ratio{font-weight:600;color:#73839a}
.cms-image-resize-controls{display:none;margin-top:8px;padding-top:8px;border-top:1px solid #dde7f2}.cms-image-dimensions[data-state="ready"] .cms-image-resize-controls{display:block}.cms-image-resize-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:7px}.cms-image-resize-field label{display:block!important;margin:0 0 3px!important;font-size:9px!important;font-weight:800!important;color:#596b82!important;text-transform:none!important}.cms-image-resize-field input{width:100%!important;box-sizing:border-box!important;padding:6px 7px!important;border:1px solid #cbd8e7!important;border-radius:6px!important;background:#fff!important;color:#17304f!important;font-size:11px!important}.cms-image-resize-actions{display:flex;align-items:center;justify-content:space-between;gap:7px;margin-top:7px;flex-wrap:wrap}.cms-image-lock{display:inline-flex!important;align-items:center!important;gap:5px!important;margin:0!important;font-size:9px!important;color:#63758b!important;font-weight:700!important}.cms-image-lock input{margin:0!important}.cms-image-resize-btn{border:1px solid #b8cbe4;background:#eef4fd;color:#174d96;border-radius:6px;padding:6px 9px;font-size:10px;font-weight:800;cursor:pointer}.cms-image-resize-btn:disabled{opacity:.55;cursor:wait}.cms-image-resize-note{width:100%;font-size:9px;color:#7a899b;font-weight:500}.cms-image-resize-status{font-size:9px;font-weight:700;color:#61748d}.cms-image-resize-status.error{color:#a12f42}.cms-image-resize-status.success{color:#257246}
@media(max-width:600px){.cms-image-resize-grid{grid-template-columns:1fr 1fr}.cms-image-resize-actions{align-items:flex-start}.cms-image-resize-btn{width:100%}}
</style>
<script>
(function(){
  var portalPayload=__PORTAL_PAYLOAD__;
  var resizeUploadUrl=__RESIZE_UPLOAD_URL__;

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
    select.addEventListener('change',function(){input.value=select.value;input.dispatchEvent(new Event('input',{bubbles:true}));updateVisibility(select);syncHeroCompatibility(select);});
    updateVisibility(select);syncHeroCompatibility(select);
  }

  function fieldLocale(name){return name.indexOf('_ar_')!==-1?'ar':(name.indexOf('_en_')!==-1?'en':'');}
  function hideLegacyHeroMode(locale){var legacy=document.querySelector('[name="scalar_'+locale+'_render_mode"]');if(!legacy)return;legacy.value='background';legacy.classList.add('cms-legacy-hero-mode');var label=legacy.previousElementSibling;if(label&&label.tagName==='LABEL')label.classList.add('cms-legacy-hero-mode');var help=document.querySelector('[data-hero-mode-help="'+locale+'"]');if(help)help.classList.add('cms-legacy-hero-mode');}
  function syncHeroCompatibility(select){if(!select||select.name.indexOf('scalar_')!==0||!select.name.endsWith('_display_mode'))return;var locale=fieldLocale(select.name);if(locale)hideLegacyHeroMode(locale);}
  function updateVisibility(select){var box=select.closest('.cms-display-mode-box');if(!box)return;var image=box.querySelector('.cms-full-image-field');if(image)image.style.display=select.value==='full_section_image'?'block':'none';}

  function enhanceFullImage(input){
    if(!input || input.dataset.fullImageReady==='1')return;
    input.dataset.fullImageReady='1';
    var value=input.value||'';input.classList.add('img-picker-target','hp-img-input');input.setAttribute('data-img-label','Full Section Image');
    var wrap=document.createElement('div');wrap.className='cms-full-image-field';
    wrap.innerHTML='<label>Full Section Image</label><div class="hp-img-field" data-img-field><div class="hp-img-preview" data-img-preview>'+(value?'<img src="'+value.replace(/"/g,'&quot;')+'" alt=""><button type="button" class="hp-img-clear" data-img-clear>×</button>':'<span class="hp-img-ph">No image</span>')+'</div><div><div class="cms-image-input-slot"></div><div class="hp-img-actions"><button type="button" class="btn-sm hp-img-select">Choose Existing</button> <button type="button" class="btn-sm hp-img-upload">Upload Image</button></div></div></div>';
    var slot=wrap.querySelector('.cms-image-input-slot');input.parentNode.insertBefore(wrap,input);slot.appendChild(input);
  }

  function groupDisplayFields(){document.querySelectorAll('input[name$="_display_mode"]').forEach(function(input){var fullName=input.name.replace(/_display_mode$/,'_full_section_image');var full=document.querySelector('[name="'+CSS.escape(fullName)+'"]');if(!full)return;enhanceFullImage(full);var fullWrap=full.closest('.cms-full-image-field');var box=document.createElement('div');box.className='cms-display-mode-box';box.innerHTML='<label>Display Mode</label><div class="cms-mode-slot"></div><div class="cms-display-mode-help"><b>Structured Section</b>: editable CMS content. <b>Full Section Image</b>: one complete image replaces the structured layout.</div>';input.parentNode.insertBefore(box,input);box.querySelector('.cms-mode-slot').appendChild(input);if(fullWrap)box.appendChild(fullWrap);makeSelect(input);});}
  function injectPortalFields(){if(!portalPayload||!portalPayload.prefix)return;document.querySelectorAll('.cms-card').forEach(function(card,index){var locale=index===0?'ar':'en';var data=portalPayload[locale]||{};if(card.querySelector('[name="scalar_'+locale+'_'+portalPayload.prefix+'_display_mode"]'))return;var box=document.createElement('div');box.innerHTML='<input type="text" name="scalar_'+locale+'_'+portalPayload.prefix+'_display_mode" value="'+String(data.display_mode||'structured').replace(/"/g,'&quot;')+'"><input type="text" name="scalar_'+locale+'_'+portalPayload.prefix+'_full_section_image" value="'+String(data.full_section_image||'').replace(/"/g,'&quot;')+'">';card.insertBefore(box,card.children[1]||null);});}

  function gcd(a,b){while(b){var t=b;b=a%b;a=t;}return a||1;}
  function dimensionHost(input){return input.closest('.hp-img-field')||input.closest('[data-img-field]')||input.parentElement;}
  function csrfToken(){var meta=document.querySelector('meta[name="csrf-token"]');if(meta&&meta.content)return meta.content;var hidden=document.querySelector('input[name="_token"]');return hidden?hidden.value:'';}

  function ensureDimensionBadge(input){
    if(!input)return null;var host=dimensionHost(input);if(!host)return null;
    var badge=host.querySelector(':scope > .cms-image-dimensions');
    if(!badge){
      badge=document.createElement('div');badge.className='cms-image-dimensions';badge.setAttribute('data-state','empty');
      badge.innerHTML='<div class="cms-image-dimension-info"></div><div class="cms-image-resize-controls"><div class="cms-image-resize-grid"><div class="cms-image-resize-field"><label>Width (px)</label><input type="number" min="1" max="8000" step="1" data-resize-width></div><div class="cms-image-resize-field"><label>Height (px)</label><input type="number" min="1" max="8000" step="1" data-resize-height></div></div><div class="cms-image-resize-actions"><label class="cms-image-lock"><input type="checkbox" data-resize-lock checked> Keep aspect ratio</label><button type="button" class="cms-image-resize-btn" data-resize-apply>Resize & Use Image</button><span class="cms-image-resize-status" data-resize-status></span><div class="cms-image-resize-note">Creates a new resized copy and automatically selects it. Original image remains unchanged.</div></div></div>';
      host.appendChild(badge);bindResizeControls(input,badge);
    }
    return badge;
  }

  function setDimensionText(input,state,html){var badge=ensureDimensionBadge(input);if(!badge)return;badge.setAttribute('data-state',state);var info=badge.querySelector('.cms-image-dimension-info');if(info)info.innerHTML=html;}
  function setResizeStatus(badge,text,type){var s=badge.querySelector('[data-resize-status]');if(!s)return;s.textContent=text||'';s.className='cms-image-resize-status'+(type?' '+type:'');}

  function bindResizeControls(input,badge){
    var w=badge.querySelector('[data-resize-width]'),h=badge.querySelector('[data-resize-height]'),lock=badge.querySelector('[data-resize-lock]'),btn=badge.querySelector('[data-resize-apply]');
    function ratio(){var ow=parseInt(input.dataset.naturalWidth||'0',10),oh=parseInt(input.dataset.naturalHeight||'0',10);return ow&&oh?ow/oh:0;}
    w.addEventListener('input',function(){if(!lock.checked)return;var r=ratio();var v=parseInt(w.value||'0',10);if(r&&v)h.value=Math.max(1,Math.round(v/r));});
    h.addEventListener('input',function(){if(!lock.checked)return;var r=ratio();var v=parseInt(h.value||'0',10);if(r&&v)w.value=Math.max(1,Math.round(v*r));});
    btn.addEventListener('click',function(){
      var src=String(input.value||'').trim(),tw=parseInt(w.value||'0',10),th=parseInt(h.value||'0',10);
      if(!src){setResizeStatus(badge,'Select an image first.','error');return;}
      if(!tw||!th||tw<1||th<1||tw>8000||th>8000){setResizeStatus(badge,'Enter valid dimensions from 1 to 8000 px.','error');return;}
      btn.disabled=true;setResizeStatus(badge,'Resizing…','');
      resizeImageSource(src,tw,th).then(function(file){setResizeStatus(badge,'Uploading resized image…','');return uploadResizedFile(file);}).then(function(url){input.value=url;input.dispatchEvent(new Event('input',{bubbles:true}));setResizeStatus(badge,'Resized image applied.','success');}).catch(function(err){setResizeStatus(badge,err&&err.message?err.message:'Resize failed.','error');}).finally(function(){btn.disabled=false;});
    });
  }

  function resizeImageSource(src,width,height){
    return new Promise(function(resolve,reject){
      var img=new Image();img.crossOrigin='anonymous';
      img.onload=function(){
        try{
          var canvas=document.createElement('canvas');canvas.width=width;canvas.height=height;
          var ctx=canvas.getContext('2d',{alpha:true});ctx.imageSmoothingEnabled=true;ctx.imageSmoothingQuality='high';ctx.drawImage(img,0,0,width,height);
          canvas.toBlob(function(blob){if(!blob){reject(new Error('Could not create resized image.'));return;}resolve(new File([blob],'cms-resized-'+width+'x'+height+'.webp',{type:'image/webp',lastModified:Date.now()}));},'image/webp',0.94);
        }catch(e){reject(new Error('This image cannot be resized in the browser. Upload it first, then try again.'));}
      };
      img.onerror=function(){reject(new Error('Could not load the selected image for resizing.'));};
      try{img.src=new URL(src,window.location.href).href;}catch(e){img.src=src;}
    });
  }

  function uploadResizedFile(file){
    var fd=new FormData();fd.append('file',file);
    return fetch(resizeUploadUrl,{method:'POST',headers:{'X-CSRF-TOKEN':csrfToken(),'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',body:fd}).then(function(r){return r.text().then(function(text){var data=null;try{data=text?JSON.parse(text):null;}catch(e){}if(!r.ok)throw new Error((data&&data.message)?data.message:'Upload failed (HTTP '+r.status+')');if(!data||!data.image||!data.image.url)throw new Error('Resize upload completed without an image URL.');return data.image.url;});});
  }

  function updateImageDimensions(input){
    if(!input)return;var src=String(input.value||'').trim();var token=String(Date.now())+Math.random();input.dataset.dimensionToken=token;
    if(!src){input.dataset.naturalWidth='';input.dataset.naturalHeight='';setDimensionText(input,'empty','<strong>Image dimensions:</strong> No image selected');return;}
    setDimensionText(input,'loading','<strong>Image dimensions:</strong> Reading…');
    var img=new Image();
    img.onload=function(){
      if(input.dataset.dimensionToken!==token)return;var w=img.naturalWidth||img.width||0,h=img.naturalHeight||img.height||0;if(!w||!h){setDimensionText(input,'error','<strong>Image dimensions:</strong> Unable to read');return;}
      input.dataset.naturalWidth=String(w);input.dataset.naturalHeight=String(h);
      var d=gcd(w,h),rw=Math.round(w/d),rh=Math.round(h/d);var ratio=(rw<=40&&rh<=40)?rw+':'+rh:(w/h).toFixed(2)+':1';
      setDimensionText(input,'ready','<strong>Image dimensions:</strong> '+w+' × '+h+' px <span class="cms-image-ratio">• Ratio '+ratio+'</span>');
      var badge=ensureDimensionBadge(input);var wi=badge.querySelector('[data-resize-width]'),hi=badge.querySelector('[data-resize-height]');if(document.activeElement!==wi)wi.value=w;if(document.activeElement!==hi)hi.value=h;
    };
    img.onerror=function(){if(input.dataset.dimensionToken!==token)return;setDimensionText(input,'error','<strong>Image dimensions:</strong> Could not read this image');};
    try{img.src=new URL(src,window.location.href).href;}catch(e){img.src=src;}
  }

  function scanImageDimensions(root){
    root=root||document;var inputs=[];
    if(root.matches&&root.matches('.img-picker-target,.hp-img-input,[data-img-input]'))inputs.push(root);
    if(root.querySelectorAll)root.querySelectorAll('.img-picker-target,.hp-img-input,[data-img-input]').forEach(function(i){inputs.push(i);});
    inputs.forEach(function(input){if(input.dataset.dimensionReady==='1')return;input.dataset.dimensionReady='1';input.addEventListener('input',function(){updateImageDimensions(input);});input.addEventListener('change',function(){updateImageDimensions(input);});updateImageDimensions(input);});
  }

  injectPortalFields();groupDisplayFields();hideLegacyHeroMode('ar');hideLegacyHeroMode('en');scanImageDimensions(document);
  var observer=new MutationObserver(function(records){records.forEach(function(record){record.addedNodes.forEach(function(node){if(node.nodeType===1)scanImageDimensions(node);});});});observer.observe(document.body,{childList:true,subtree:true});
})();
</script>
HTML;

        $injection = str_replace(['__PORTAL_PAYLOAD__', '__RESIZE_UPLOAD_URL__'], [$payload, $uploadUrl], $injection);
        $response->setContent(str_replace('</body>', $injection."\n</body>", $html));

        return $response;
    }
}
