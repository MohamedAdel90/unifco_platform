<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CmsArabicSourcePresentationV2
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
<style id="cms-arabic-source-v2-style">
.cms-source-note-v2{margin:10px 0 16px;padding:11px 13px;border:1px solid #cfe0f4;border-radius:10px;background:#f3f8ff;color:#38516f;font-size:11px;line-height:1.65}.cms-source-note-v2 b{color:#183a68}.cms-en-generated-v2{background:#f7f9fc!important;color:#56657a!important}.cms-en-generated-select-v2{background:#f7f9fc!important;color:#56657a!important;pointer-events:none}
</style>
<script>
(function(){
  function isEnglishName(name){return /(^|_)en(_|\[)/.test(name||'') || (name||'').indexOf('scalar_en_')===0 || (name||'').indexOf('item_en_')===0 || (name||'').indexOf('check_en_')===0;}
  function isArabicName(name){return (name||'').indexOf('scalar_ar_')===0 || (name||'').indexOf('item_ar_')===0 || (name||'').indexOf('check_ar_')===0;}
  function counterpart(name,toLocale){
    name=name||'';
    if(toLocale==='en'){
      if(name.indexOf('scalar_ar_')===0)return name.replace('scalar_ar_','scalar_en_');
      if(name.indexOf('item_ar_')===0)return name.replace('item_ar_','item_en_');
      if(name.indexOf('check_ar_')===0)return name.replace('check_ar_','check_en_');
    }
    if(toLocale==='ar'){
      if(name.indexOf('scalar_en_')===0)return name.replace('scalar_en_','scalar_ar_');
      if(name.indexOf('item_en_')===0)return name.replace('item_en_','item_ar_');
      if(name.indexOf('check_en_')===0)return name.replace('check_en_','check_ar_');
    }
    return '';
  }
  function isImage(input){var n=(input.name||'').toLowerCase();return input.classList.contains('img-picker-target') || n.indexOf('_image')!==-1 || /(^|_)image($|_|\[)/.test(n);}
  function findByName(name){if(!name)return null;try{return document.querySelector('[name="'+CSS.escape(name)+'"]');}catch(e){return document.getElementsByName(name)[0]||null;}}

  function addSourceNote(){
    var form=document.querySelector('#homepage-section-form, form[action*="homepage/sections"], form[action*="maintenance-cms"], form[action*="client-portal-cms"]');
    if(!form || document.querySelector('.cms-source-note-v2'))return;
    var old=document.querySelector('.cms-source-note');if(old)old.remove();
    var note=document.createElement('div');
    note.className='cms-source-note-v2';
    note.innerHTML='<b>Arabic is the primary source.</b> Arabic text is translated automatically to English. Images copy from Arabic to English automatically unless a different English image is selected manually.';
    form.insertBefore(note,form.firstChild);
  }

  function unlockEnglishImagesAndLockText(){
    document.querySelectorAll('input[name],textarea[name],select[name]').forEach(function(el){
      if(!isEnglishName(el.name) || el.type==='hidden')return;
      if(isImage(el)){
        el.readOnly=false;
        el.classList.remove('cms-en-generated','cms-en-generated-v2');
        var field=el.closest('[data-img-field]');
        if(field)field.classList.remove('cms-en-image-locked');
        return;
      }
      if(el.tagName==='SELECT'){
        el.classList.add('cms-en-generated-select-v2');
        el.dataset.cmsGenerated='1';
        el.tabIndex=-1;
      }else{
        el.readOnly=true;
        el.classList.add('cms-en-generated-v2');
      }
    });
  }

  function syncMode(ar){
    if(!ar || !isArabicName(ar.name) || !ar.name.endsWith('_display_mode'))return;
    var en=findByName(counterpart(ar.name,'en'));
    if(!en)return;
    en.value=ar.value;
    en.dispatchEvent(new Event('change',{bubbles:true}));
  }

  function refreshPreview(input){
    var field=input?input.closest('[data-img-field]'):null;
    var pv=field?field.querySelector('[data-img-preview]'):null;
    if(!pv)return;
    var v=(input.value||'').trim();
    if(!v){pv.innerHTML='<span class="hp-img-ph">No image</span>';return;}
    pv.innerHTML='<img src="'+v.replace(/"/g,'&quot;')+'" alt=""><button type="button" class="hp-img-clear" data-img-clear title="Remove image">×</button>';
  }

  function initializeImagePairs(){
    document.querySelectorAll('input[name^="scalar_ar_"],input[name^="item_ar_"],input[name^="check_ar_"]').forEach(function(ar){
      if(!isImage(ar))return;
      var en=findByName(counterpart(ar.name,'en'));
      if(!en)return;
      ar.dataset.lastArabicImage=ar.value||'';
      en.dataset.manualEnglishImage=((en.value||'')!=='' && (en.value||'')!==(ar.value||''))?'1':'0';
    });
  }

  function handleImageInput(input){
    if(!isImage(input))return false;
    var name=input.name||'';

    if(isEnglishName(name)){
      var ar=findByName(counterpart(name,'ar'));
      var arValue=ar?(ar.value||''):'';
      input.dataset.manualEnglishImage=((input.value||'')!=='' && (input.value||'')!==arValue)?'1':'0';
      refreshPreview(input);
      return true;
    }

    if(isArabicName(name)){
      var en=findByName(counterpart(name,'en'));
      var previous=input.dataset.lastArabicImage||'';
      var next=input.value||'';
      if(en){
        var enValue=en.value||'';
        var hasOverride=en.dataset.manualEnglishImage==='1' || (enValue!=='' && enValue!==previous && enValue!==next);
        if(!hasOverride){
          en.value=next;
          en.dataset.manualEnglishImage='0';
          refreshPreview(en);
        }
      }
      input.dataset.lastArabicImage=next;
      refreshPreview(input);
      return true;
    }
    return false;
  }

  document.addEventListener('input',function(e){
    var el=e.target;
    if(el instanceof HTMLInputElement && handleImageInput(el))e.stopImmediatePropagation();
  },true);

  document.addEventListener('change',function(e){
    var el=e.target;
    if(el && el.tagName==='SELECT')syncMode(el);
    if(el instanceof HTMLInputElement && handleImageInput(el))e.stopImmediatePropagation();
  },true);

  document.addEventListener('keydown',function(e){
    var el=e.target;
    if(el && el.tagName==='SELECT' && isEnglishName(el.name))e.preventDefault();
  },true);

  function prepareSubmit(form){
    if(form.dataset.cmsReadySubmit==='1')return;
    form.dataset.cmsReadySubmit='waiting';
    document.querySelectorAll('input[name^="scalar_ar_"],textarea[name^="scalar_ar_"],input[name^="item_ar_"],textarea[name^="item_ar_"],input[name^="check_ar_"]').forEach(function(el){
      if(!isImage(el))el.dispatchEvent(new Event('input',{bubbles:true}));
    });
    var started=Date.now();
    var wait=function(){
      var busy=document.querySelector('.cms-translating');
      if((!busy && Date.now()-started>1100) || Date.now()-started>9000){
        form.dataset.cmsReadySubmit='1';
        form.requestSubmit();
        return;
      }
      setTimeout(wait,180);
    };
    setTimeout(wait,180);
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
  unlockEnglishImagesAndLockText();
  initializeImagePairs();
  setTimeout(function(){unlockEnglishImagesAndLockText();initializeImagePairs();},100);
  setTimeout(function(){unlockEnglishImagesAndLockText();initializeImagePairs();},500);
})();
</script>
HTML;

        $response->setContent(str_replace('</body>', $injection."\n</body>", $html));

        return $response;
    }
}
