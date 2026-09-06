<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CmsArabicSourcePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user() || ! $request->routeIs('admin.homepage.sections.edit', 'admin.maintenance-cms', 'admin.client-portal-cms')) {
            return $response;
        }

        if (! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</body>')) {
            return $response;
        }

        $injection = <<<'HTML'
<style id="cms-arabic-source-style">
.cms-source-note{margin:10px 0 16px;padding:11px 13px;border:1px solid #cfe0f4;border-radius:10px;background:#f3f8ff;color:#38516f;font-size:11px;line-height:1.65}.cms-source-note b{color:#183a68}.cms-en-generated{background:#f7f9fc!important;color:#56657a!important}.cms-en-generated-select{background:#f7f9fc!important;color:#56657a!important;pointer-events:none}.cms-en-image-custom{outline:2px solid #f0c66a!important}.cms-en-image-custom-note{display:block;margin-top:4px;font-size:9px;color:#80651c;font-weight:700}.cms-en-image-inherited-note{display:block;margin-top:4px;font-size:9px;color:#6f819a;font-weight:700}
</style>
<script>
(function(){
  function isEnglishName(name){return /(^|_)en(_|\[)/.test(name||'') || (name||'').indexOf('scalar_en_')===0 || (name||'').indexOf('item_en_')===0 || (name||'').indexOf('check_en_')===0;}
  function isArabicName(name){return (name||'').indexOf('scalar_ar_')===0 || (name||'').indexOf('item_ar_')===0 || (name||'').indexOf('check_ar_')===0;}
  function counterpart(name){
    if((name||'').indexOf('scalar_ar_')===0)return name.replace('scalar_ar_','scalar_en_');
    if((name||'').indexOf('item_ar_')===0)return name.replace('item_ar_','item_en_');
    if((name||'').indexOf('check_ar_')===0)return name.replace('check_ar_','check_en_');
    return '';
  }
  function arabicCounterpart(name){
    if((name||'').indexOf('scalar_en_')===0)return name.replace('scalar_en_','scalar_ar_');
    if((name||'').indexOf('item_en_')===0)return name.replace('item_en_','item_ar_');
    if((name||'').indexOf('check_en_')===0)return name.replace('check_en_','check_ar_');
    return '';
  }
  function isImage(input){var n=(input.name||'').toLowerCase();return input.classList.contains('img-picker-target') || n.indexOf('_image')!==-1 || /(^|_)image($|_|\[)/.test(n);}
  function findByName(name){if(!name)return null;try{return document.querySelector('[name="'+CSS.escape(name)+'"]');}catch(e){return document.getElementsByName(name)[0]||null;}}

  function addSourceNote(){
    var form=document.querySelector('#homepage-section-form, form[action*="homepage/sections"], form[action*="maintenance-cms"], form[action*="client-portal-cms"]');
    if(!form || document.querySelector('.cms-source-note'))return;
    var note=document.createElement('div');
    note.className='cms-source-note';
    note.innerHTML='<b>Arabic is the primary source.</b> Arabic text is translated automatically to English. Arabic images are copied to English automatically until a different English image is selected; a custom English image is then preserved and never changes the Arabic image.';
    form.insertBefore(note,form.firstChild);
  }

  function refreshPreview(input){
    var field=input?input.closest('[data-img-field]'):null;
    var pv=field?field.querySelector('[data-img-preview]'):null;
    if(!pv)return;
    var v=(input.value||'').trim();
    if(!v){pv.innerHTML='<span class="hp-img-ph">No image</span>';return;}
    pv.innerHTML='<img src="'+v.replace(/"/g,'&quot;')+'" alt=""><button type="button" class="hp-img-clear" data-img-clear title="Remove image">×</button>';
  }

  function setEnglishImageState(en,inherited){
    if(!en)return;
    en.dataset.cmsImageInherited=inherited?'1':'0';
    en.classList.toggle('cms-en-image-custom',!inherited);
    var field=en.closest('[data-img-field]');
    if(!field)return;
    field.querySelectorAll('.cms-en-image-custom-note,.cms-en-image-inherited-note').forEach(function(n){n.remove();});
    var note=document.createElement('span');
    note.className=inherited?'cms-en-image-inherited-note':'cms-en-image-custom-note';
    note.textContent=inherited?'Inherited from Arabic':'Custom English image — preserved';
    field.appendChild(note);
  }

  function initializeImageState(){
    document.querySelectorAll('input[name^="scalar_ar_"],input[name^="item_ar_"],input[name^="check_ar_"]').forEach(function(ar){
      if(!isImage(ar))return;
      var en=findByName(counterpart(ar.name||''));
      if(!en)return;
      ar.dataset.cmsLastImage=ar.value||'';
      var inherited=!(en.value||'').trim() || (en.value||'')===(ar.value||'');
      setEnglishImageState(en,inherited);
      if(!(en.value||'').trim() && (ar.value||'').trim()){
        en.value=ar.value;
        refreshPreview(en);
      }
    });
  }

  function handleImageEvent(e){
    var input=e.target;
    if(!(input instanceof HTMLInputElement) || !isImage(input))return;

    var name=input.name||'';
    if(isArabicName(name)){
      var en=findByName(counterpart(name));
      var previous=input.dataset.cmsLastImage||'';
      var current=input.value||'';
      if(en){
        var englishValue=en.value||'';
        var inherited=en.dataset.cmsImageInherited==='1' || !englishValue.trim() || englishValue===previous;
        if(inherited){
          en.value=current;
          refreshPreview(en);
          setEnglishImageState(en,true);
        }
      }
      input.dataset.cmsLastImage=current;
      refreshPreview(input);
      e.stopImmediatePropagation();
      return;
    }

    if(isEnglishName(name)){
      var ar=findByName(arabicCounterpart(name));
      var inherited=!(input.value||'').trim() || (!!ar && (input.value||'')===(ar.value||''));
      setEnglishImageState(input,inherited);
      refreshPreview(input);
      e.stopImmediatePropagation();
    }
  }

  function lockEnglish(){
    document.querySelectorAll('input[name],textarea[name],select[name]').forEach(function(el){
      if(!isEnglishName(el.name))return;
      if(el.type==='hidden')return;

      if(isImage(el)){
        el.readOnly=false;
        el.classList.remove('cms-en-generated');
        var field=el.closest('[data-img-field]');
        if(field)field.classList.remove('cms-en-image-locked');
        return;
      }

      if(el.tagName==='SELECT'){
        el.classList.add('cms-en-generated-select');
        el.dataset.cmsGenerated='1';
        el.tabIndex=-1;
      }else{
        el.readOnly=true;
        el.classList.add('cms-en-generated');
      }
    });
  }

  function syncMode(ar){
    if(!ar || !isArabicName(ar.name) || !ar.name.endsWith('_display_mode'))return;
    var en=findByName(counterpart(ar.name));
    if(!en)return;
    en.value=ar.value;
    en.dispatchEvent(new Event('change',{bubbles:true}));
  }

  document.addEventListener('input',handleImageEvent,true);
  document.addEventListener('change',function(e){
    var el=e.target;
    if(el instanceof HTMLInputElement && isImage(el)){
      handleImageEvent(e);
      return;
    }
    if(el && el.tagName==='SELECT')syncMode(el);
  },true);

  document.addEventListener('keydown',function(e){
    var el=e.target;
    if(el && el.tagName==='SELECT' && isEnglishName(el.name))e.preventDefault();
  },true);

  function prepareSubmit(form){
    if(form.dataset.cmsReadySubmit==='1')return true;
    form.dataset.cmsReadySubmit='waiting';

    document.querySelectorAll('input[name^="scalar_ar_"],textarea[name^="scalar_ar_"],input[name^="item_ar_"],textarea[name^="item_ar_"],input[name^="check_ar_"]').forEach(function(el){
      if(!isImage(el))el.dispatchEvent(new Event('input',{bubbles:true}));
    });

    var started=Date.now();
    var wait=function(){
      var busy=document.querySelector('.cms-translating');
      if((!busy && Date.now()-started>1100) || Date.now()-started>9000){
        document.querySelectorAll('select[data-cms-generated="1"]').forEach(function(el){el.disabled=false;});
        form.dataset.cmsReadySubmit='1';
        form.requestSubmit();
        return;
      }
      setTimeout(wait,180);
    };
    setTimeout(wait,180);
    return false;
  }

  document.addEventListener('submit',function(e){
    var form=e.target;
    if(!(form instanceof HTMLFormElement))return;
    if(!form.querySelector('[name^="scalar_ar_"],[name^="item_ar_"],[name^="check_ar_"]'))return;
    if(form.dataset.cmsReadySubmit==='1')return;
    e.preventDefault();
    prepareSubmit(form);
  },true);

  addSourceNote();
  lockEnglish();
  initializeImageState();
  setTimeout(function(){lockEnglish();initializeImageState();},100);
  setTimeout(function(){lockEnglish();initializeImageState();},500);
})();
</script>
HTML;

        $response->setContent(str_replace('</body>', $injection."\n</body>", $html));

        return $response;
    }
}
