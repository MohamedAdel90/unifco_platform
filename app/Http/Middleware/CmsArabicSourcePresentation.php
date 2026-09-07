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
.cms-source-note{margin:10px 0 16px;padding:11px 13px;border:1px solid #cfe0f4;border-radius:10px;background:#f3f8ff;color:#38516f;font-size:11px;line-height:1.65}.cms-source-note b{color:#183a68}.cms-en-generated{background:#f7f9fc!important;color:#56657a!important}.cms-en-generated-select{background:#f7f9fc!important;color:#56657a!important;pointer-events:none}.cms-en-image-locked .hp-img-actions{display:none!important}.cms-en-image-locked .hp-img-input{background:#f7f9fc!important;color:#56657a!important;pointer-events:none}.cms-en-image-locked:after{content:'Synced from Arabic';display:block;margin-top:4px;font-size:9px;color:#6f819a;font-weight:700}
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
  function isImage(input){var n=(input.name||'').toLowerCase();return input.classList.contains('img-picker-target') || n.indexOf('_image')!==-1 || /(^|_)image($|_|\[)/.test(n);}
  function findByName(name){if(!name)return null;try{return document.querySelector('[name="'+CSS.escape(name)+'"]');}catch(e){return document.getElementsByName(name)[0]||null;}}

  function addSourceNote(){
    var form=document.querySelector('#homepage-section-form, form[action*="homepage/sections"], form[action*="maintenance-cms"], form[action*="client-portal-cms"]');
    if(!form || document.querySelector('.cms-source-note'))return;
    var note=document.createElement('div');
    note.className='cms-source-note';
    note.innerHTML='<b>Arabic is the primary source.</b> Enter text and choose images once in Arabic. English text is generated automatically and normal images are reused automatically in English.';
    form.insertBefore(note,form.firstChild);
  }

  function lockEnglish(){
    document.querySelectorAll('input[name],textarea[name],select[name]').forEach(function(el){
      if(!isEnglishName(el.name))return;
      if(el.type==='hidden')return;
      if(el.tagName==='SELECT'){
        el.classList.add('cms-en-generated-select');
        el.dataset.cmsGenerated='1';
        el.tabIndex=-1;
      }else{
        el.readOnly=true;
        el.classList.add('cms-en-generated');
      }
      if(isImage(el)){
        var field=el.closest('[data-img-field]');
        if(field)field.classList.add('cms-en-image-locked');
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

  document.addEventListener('change',function(e){
    var el=e.target;
    if(el && el.tagName==='SELECT')syncMode(el);
  },true);

  document.addEventListener('click',function(e){
    var action=e.target.closest('.hp-img-upload,.hp-img-select,[data-img-clear]');
    if(!action)return;
    var field=action.closest('[data-img-field]');
    var input=field?field.querySelector('.img-picker-target'):null;
    if(input && isEnglishName(input.name)){
      e.preventDefault();e.stopImmediatePropagation();
    }
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
  setTimeout(lockEnglish,100);
  setTimeout(lockEnglish,500);
})();
</script>
HTML;

        $response->setContent(str_replace('</body>', $injection."\n</body>", $html));

        return $response;
    }
}
