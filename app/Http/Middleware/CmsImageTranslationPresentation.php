<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CmsImageTranslationPresentation
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

        $url = route('admin.homepage.images.translate');
        $csrf = csrf_token();

        $injection = <<<'HTML'
<style id="cms-image-translation-style">
.cms-image-ai-status{margin:8px 0 0;padding:8px 10px;border-radius:8px;background:#f4f7fb;border:1px solid #d9e2ee;color:#596b84;font-size:10px;line-height:1.45}.cms-image-ai-status.busy{background:#eef6ff;border-color:#cfe0f5;color:#2b5687}.cms-image-ai-status.ok{background:#edf8f1;border-color:#c9e9d4;color:#27663f}.cms-image-ai-status.warn{background:#fff8e8;border-color:#ecdba9;color:#7b6421}
</style>
<script>
(function(){
  var endpoint='__TRANSLATE_URL__';
  var csrf='__CSRF__';
  var pending=new Map();

  function isImage(input){
    if(!input || !(input instanceof HTMLInputElement))return false;
    var name=(input.name||'').toLowerCase();
    return input.classList.contains('img-picker-target') || name.indexOf('_image')!==-1 || /(^|_)image($|_|\[)/.test(name);
  }

  function isArabicImage(input){
    var name=input.name||'';
    return isImage(input) && (name.indexOf('scalar_ar_')===0 || name.indexOf('item_ar_')===0 || name.indexOf('check_ar_')===0);
  }

  function englishName(name){
    if(name.indexOf('scalar_ar_')===0)return name.replace('scalar_ar_','scalar_en_');
    if(name.indexOf('item_ar_')===0)return name.replace('item_ar_','item_en_');
    if(name.indexOf('check_ar_')===0)return name.replace('check_ar_','check_en_');
    return '';
  }

  function findByName(name){
    if(!name)return null;
    try{return document.querySelector('[name="'+CSS.escape(name)+'"]');}
    catch(e){return document.getElementsByName(name)[0]||null;}
  }

  function statusNode(input){
    var field=input.closest('[data-img-field]') || input.parentElement;
    if(!field)return null;
    var node=field.querySelector('.cms-image-ai-status');
    if(!node){
      node=document.createElement('div');
      node.className='cms-image-ai-status';
      field.appendChild(node);
    }
    return node;
  }

  function setStatus(input,text,kind){
    var node=statusNode(input);if(!node)return;
    node.className='cms-image-ai-status '+(kind||'');
    node.textContent=text;
  }

  function refreshPreview(input){
    var field=input.closest('[data-img-field]');
    var pv=field?field.querySelector('[data-img-preview]'):null;
    if(!pv)return;
    var v=(input.value||'').trim();
    if(!v){pv.innerHTML='<span class="hp-img-ph">No image</span>';return;}
    pv.innerHTML='<img src="'+v.replace(/"/g,'&quot;')+'" alt=""><button type="button" class="hp-img-clear" data-img-clear title="Remove image">×</button>';
  }

  function applyEnglish(input,url){
    var en=findByName(englishName(input.name||''));
    if(!en)return;
    en.value=url;
    refreshPreview(en);
  }

  function translateImage(input){
    var url=(input.value||'').trim();
    if(!url){applyEnglish(input,'');return;}

    setStatus(input,'Checking image for Arabic text…','busy');
    var key=url;
    var task=pending.get(key);
    if(!task){
      task=fetch(endpoint,{
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},
        body:JSON.stringify({url:url})
      }).then(function(r){return r.json().then(function(data){if(!r.ok)throw new Error(data.message||('HTTP '+r.status));return data;});})
        .finally(function(){pending.delete(key);});
      pending.set(key,task);
    }

    task.then(function(data){
      var english=(data&&data.english_url)?data.english_url:url;
      applyEnglish(input,english);
      if(data.status==='translated'){
        setStatus(input,'Arabic text found. English image created automatically.','ok');
      }else if(data.status==='same'){
        setStatus(input,'No Arabic text found. The same image is used for Arabic and English.','ok');
      }else if(data.status==='unavailable'){
        setStatus(input,'Image AI is connected but OPENAI_API_KEY is not configured on the server yet. Original image is being used.','warn');
      }else if(data.status==='unsupported'){
        setStatus(input,'This legacy image location cannot be translated automatically. Re-upload it to CMS media to enable image translation.','warn');
      }else{
        setStatus(input,'Image translation could not complete. Original image is being used for English.','warn');
      }
    }).catch(function(){
      applyEnglish(input,url);
      setStatus(input,'Image translation request failed. Original image is being used for English.','warn');
    });
  }

  var timers=new WeakMap();
  document.addEventListener('input',function(e){
    var input=e.target;
    if(!isArabicImage(input))return;
    var old=timers.get(input);if(old)clearTimeout(old);
    timers.set(input,setTimeout(function(){translateImage(input);},350));
  });
})();
</script>
HTML;

        $injection = str_replace(['__TRANSLATE_URL__', '__CSRF__'], [e($url), e($csrf)], $injection);
        $response->setContent(str_replace('</body>', $injection."\n</body>", $html));

        return $response;
    }
}
