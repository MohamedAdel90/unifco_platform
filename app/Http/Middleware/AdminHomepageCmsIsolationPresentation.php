<?php

namespace App\Http\Middleware;

use App\Models\HomepageSection;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminHomepageCmsIsolationPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('admin.homepage.sections.edit') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $routeSection = $request->route('section');
        $sectionKey = $routeSection instanceof HomepageSection
            ? (string) $routeSection->section_key
            : (string) HomepageSection::query()->whereKey((int) $routeSection)->value('section_key');

        $sectionKey = preg_match('/^[a-z0-9_-]+$/i', $sectionKey) ? strtolower($sectionKey) : '';
        if ($sectionKey === '') {
            return $response;
        }

        $html = (string) $response->getContent();
        $listUrl = route('admin.homepage.images.list');
        $uploadUrl = route('admin.homepage.images.upload');
        $liveUrl = route('public.home');

        $style = <<<'HTML'
<style id="unifco-homepage-cms-isolation-v1">
.cms-section-context{margin:0 0 12px;padding:10px 12px;border:1px solid #cfe0f6;border-radius:9px;background:#f4f8fd;color:#35506e;font-size:11px;line-height:1.6;display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap}
.cms-section-context strong{color:#17366c}.cms-section-context .cms-context-actions{display:flex;gap:7px;align-items:center;flex-wrap:wrap}.cms-section-context a{display:inline-flex;align-items:center;justify-content:center;min-height:34px;padding:6px 11px;border:1px solid #9fb6da;border-radius:7px;background:#fff;color:#17366c;text-decoration:none;font-size:10.5px;font-weight:800}.cms-section-context a.primary{background:#17366c;color:#fff;border-color:#17366c}.img-picker[data-cms-isolated="1"]{border-color:#cfe0f6!important;box-shadow:0 0 0 2px rgba(37,99,235,.04)}.img-picker[data-cms-isolated="1"] .img-picker-head strong:after{content:" · isolated";color:#1f4e9c;font-size:9px;font-weight:800}.cms-preview-note{font-size:9.5px;color:#728096;line-height:1.5;margin-inline-start:4px}.cms-preview-note b{color:#53647d}
@media(max-width:760px){.cms-section-context{align-items:flex-start}.cms-section-context .cms-context-actions{width:100%}.cms-section-context a{flex:1}}
</style>
HTML;

        $sectionJson = json_encode($sectionKey, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $listJson = json_encode($listUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $uploadJson = json_encode($uploadUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $liveJson = json_encode($liveUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $script = <<<HTML
<script id="unifco-homepage-cms-isolation-script-v1">
(function(){
  var section={$sectionJson};
  var listUrl={$listJson};
  var uploadUrl={$uploadJson};
  var liveUrl={$liveJson};
  var originalFetch=window.fetch.bind(window);

  function withSection(url){
    try{
      var u=new URL(url,window.location.origin);
      if(u.pathname===new URL(listUrl,window.location.origin).pathname || u.pathname===new URL(uploadUrl,window.location.origin).pathname){
        u.searchParams.set('section',section);
        return u.toString();
      }
    }catch(e){}
    return url;
  }

  function currentImageValues(){
    return Array.from(document.querySelectorAll('.img-picker-target')).map(function(i){return (i.value||'').trim();}).filter(Boolean);
  }

  function staticAllowed(img,current){
    var url=(img&&img.url)||'';
    var name=((img&&img.name)||'').toLowerCase();
    var source=(img&&img.source)||'';
    if(current.indexOf(url)!==-1)return true;
    if(source==='cms')return true;
    if(section==='projects')return source==='projects';
    if(section==='clients')return source==='clients';
    if(source!=='home')return true;

    var rules={
      hero:/hero|banner|homepage|home-hero/,
      about:/about|technician|team|company/,
      services:/service|generator|ups|transformer|hvac|mep|facility|electrical|maintenance|ats/,
      industries:/industry|sector/,
      operations:/operation|portal|maintenance|technician/,
      process:/process|workflow|step/,
      capabilities:/capability|facility|asset|service/,
      why:/why|shield|team|quality|support/,
      emergency:/emergency|urgent|response/,
      footer:/footer|contact|logo/
    };
    var rule=rules[section];
    return rule ? rule.test(name+' '+url.toLowerCase()) : false;
  }

  window.fetch=function(input,init){
    var rawUrl=typeof input==='string'?input:(input&&input.url?input.url:'');
    var scopedUrl=withSection(rawUrl);
    var options=init?Object.assign({},init):{};
    var method=(options.method||((input&&input.method)||'GET')).toUpperCase();

    if(method==='POST' && options.body instanceof FormData && scopedUrl.indexOf(new URL(uploadUrl,window.location.origin).pathname)!==-1){
      if(!options.body.has('section'))options.body.append('section',section);
    }

    var requestInput=typeof input==='string'?scopedUrl:(rawUrl!==scopedUrl?new Request(scopedUrl,input):input);
    return originalFetch(requestInput,options).then(function(response){
      var listPath=new URL(listUrl,window.location.origin).pathname;
      var responsePath='';
      try{responsePath=new URL(scopedUrl,window.location.origin).pathname;}catch(e){}
      if(method!=='GET' || responsePath!==listPath || !response.ok)return response;

      return response.clone().json().then(function(data){
        var current=currentImageValues();
        data.context=section;
        data.images=(data.images||[]).filter(function(img){return staticAllowed(img,current);});
        var headers=new Headers(response.headers);
        headers.set('Content-Type','application/json');
        return new Response(JSON.stringify(data),{status:response.status,statusText:response.statusText,headers:headers});
      }).catch(function(){return response;});
    });
  };

  function decorate(){
    var form=document.getElementById('homepage-section-form');
    if(form && !document.querySelector('.cms-section-context')){
      var box=document.createElement('div');
      box.className='cms-section-context';
      box.innerHTML='<div><strong>Editing section: '+section+'</strong><br>Image Library is isolated to this section. Images uploaded here stay assigned to this section.</div><div class="cms-context-actions"><a href="'+liveUrl+'" target="_blank" rel="noopener">Live Homepage</a><a class="primary" href="'+liveUrl+'#'+section+'" target="_blank" rel="noopener">Open live section</a></div>';
      form.parentNode.insertBefore(box,form);
    }

    var picker=document.querySelector('.img-picker');
    if(picker){
      picker.setAttribute('data-cms-isolated','1');
      var help=picker.querySelector('#img-picker-help');
      if(help)help.textContent='Section library: '+section+'. Choose the exact image field first, then select or upload an image.';
    }

    var previewActions=document.querySelector('.preview-actions');
    if(previewActions && !previewActions.querySelector('.cms-preview-note')){
      var note=document.createElement('span');
      note.className='cms-preview-note';
      note.innerHTML='<b>Draft Preview</b> shows CMS data. Use Live Homepage after Save to verify the final presentation layer.';
      previewActions.appendChild(note);
    }
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',decorate);else decorate();
})();
</script>
HTML;

        $html = str_replace('</head>', $style.$script.'</head>', $html);
        $response->setContent($html);

        return $response;
    }
}
