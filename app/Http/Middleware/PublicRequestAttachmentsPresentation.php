<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicRequestAttachmentsPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('request-service')) {
            return $response;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        if (! str_contains($contentType, 'text/html') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</head>') || ! str_contains($html, '</body>')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="public-request-camera-attachments">
.attachment-enhanced{display:grid;gap:12px;padding:14px!important;border:1px dashed #c8d6e6!important;border-radius:13px!important;background:#fbfdff!important}
.attachment-enhanced>.attachment-title{display:flex;align-items:center;justify-content:space-between;gap:10px;color:#132b50;font-size:11px;font-weight:900}
.attachment-enhanced>.attachment-title small{color:#8997aa;font-size:8px;font-weight:600}
.attachment-actions{display:grid;grid-template-columns:1fr 1fr;gap:9px}
.attachment-action{min-height:52px;border-radius:9px;border:1px solid #cedae8;background:#fff;color:#0a356d;display:flex;align-items:center;justify-content:center;gap:9px;padding:8px 12px;font:inherit;font-size:10px;font-weight:900;cursor:pointer;text-decoration:none}
.attachment-action.camera{background:#08265a;color:#fff;border-color:#08265a}
.attachment-action svg{width:19px;height:19px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round;flex:0 0 auto}
.attachment-action-text{display:grid;gap:1px;text-align:center}.attachment-action-text small{font-size:7.5px;font-weight:600;opacity:.75}
.attachment-native-input{position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;pointer-events:none!important;overflow:hidden!important}
.attachment-preview{display:none;grid-template-columns:repeat(auto-fill,minmax(105px,1fr));gap:9px;padding-top:2px}.attachment-preview.has-files{display:grid}
.attachment-preview-item{position:relative;min-width:0;border:1px solid #dbe4ed;border-radius:9px;background:#fff;overflow:hidden;min-height:88px}
.attachment-preview-item img{display:block;width:100%;height:92px;object-fit:cover;background:#eef3f8}
.attachment-preview-file{height:92px;display:grid;place-items:center;padding:10px;text-align:center;color:#52647c;font-size:8px;font-weight:800;word-break:break-word;background:#f6f9fc}
.attachment-remove{position:absolute;top:5px;right:5px;width:23px;height:23px;border:1px solid #d4deea;border-radius:50%;background:#fff;color:#193150;display:grid;place-items:center;font-size:15px;line-height:1;cursor:pointer;box-shadow:0 2px 6px rgba(10,35,70,.12)}
html[dir="rtl"] .attachment-remove{right:auto;left:5px}
.attachment-count{display:none;color:#718197;font-size:8px;font-weight:700}.attachment-count.show{display:block}
@media(max-width:520px){.attachment-actions{grid-template-columns:1fr}.attachment-action{min-height:50px}.attachment-preview{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
HTML;

        $script = <<<'HTML'
<script id="public-request-camera-attachments-logic">
(function(){
  const ar=document.documentElement.dir==='rtl';
  const copy=ar?{
    camera:'التقاط صورة',cameraSub:'باستخدام الكاميرا',gallery:'اختيار من الجهاز',gallerySub:'من الصور أو الملفات',count:n=>`${n} ملف مرفق`,equipment:'صور المعدة',equipmentHint:'أرفق صوراً واضحة للمعدة ذات الصلة بالطلب.',problem:'صور المشكلة',problemHint:'أرفق صوراً توضح المشكلة إن وجدت.',report:'تقرير سابق (إن وجد)',reportHint:'يمكن رفع تقرير سابق أو تصوير صفحاته بالكاميرا.'
  }:{
    camera:'Take Photo',cameraSub:'Use camera',gallery:'Choose from Device',gallerySub:'Photos or files',count:n=>`${n} file${n===1?'':'s'} attached`,equipment:'Equipment Photos',equipmentHint:'Attach clear photos of the equipment related to the request.',problem:'Problem Photos',problemHint:'Attach photos that show the issue, if available.',report:'Previous Report (if available)',reportHint:'Upload a previous report or photograph its pages.'
  };
  const cameraIcon='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5h3l1.4-2h7.2l1.4 2h3a2 2 0 0 1 2 2V19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9.5a2 2 0 0 1 2-2Z"/><circle cx="12" cy="14" r="4"/></svg>';
  const uploadIcon='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5"/><path d="M5 14v5h14v-5"/></svg>';

  const configs=[
    {name:'equipment_photos[]',title:copy.equipment,hint:copy.equipmentHint,images:true},
    {name:'problem_photos[]',title:copy.problem,hint:copy.problemHint,images:true},
    {name:'previous_reports[]',title:copy.report,hint:copy.reportHint,images:false}
  ];

  function enhance(config){
    const input=document.querySelector(`input[name="${config.name}"]`);
    if(!input || input.dataset.cameraEnhanced==='1') return;
    input.dataset.cameraEnhanced='1';
    input.classList.add('attachment-native-input');
    const host=input.closest('.upload')||input.parentElement;
    if(!host) return;
    host.classList.add('attachment-enhanced');

    const existingLabel=host.querySelector(':scope > span');
    if(existingLabel) existingLabel.style.display='none';

    const title=document.createElement('div');
    title.className='attachment-title';
    title.innerHTML=`<span>${config.title}</span><small>${config.hint}</small>`;

    const actions=document.createElement('div');
    actions.className='attachment-actions';
    const gallery=document.createElement('button');
    gallery.type='button'; gallery.className='attachment-action gallery';
    gallery.innerHTML=`${uploadIcon}<span class="attachment-action-text"><b>${copy.gallery}</b><small>${copy.gallerySub}</small></span>`;
    const camera=document.createElement('button');
    camera.type='button'; camera.className='attachment-action camera';
    camera.innerHTML=`${cameraIcon}<span class="attachment-action-text"><b>${copy.camera}</b><small>${copy.cameraSub}</small></span>`;
    actions.append(gallery,camera);

    const cameraInput=document.createElement('input');
    cameraInput.type='file'; cameraInput.accept='image/*'; cameraInput.setAttribute('capture','environment');
    cameraInput.className='attachment-native-input'; cameraInput.tabIndex=-1;

    const preview=document.createElement('div'); preview.className='attachment-preview';
    const count=document.createElement('div'); count.className='attachment-count';

    input.before(title,actions,cameraInput);
    input.after(preview,count);

    gallery.addEventListener('click',()=>input.click());
    camera.addEventListener('click',()=>cameraInput.click());

    function setFiles(files){
      const dt=new DataTransfer(); files.forEach(file=>dt.items.add(file)); input.files=dt.files; render();
    }
    function merge(files){
      const all=[...input.files];
      files.forEach(file=>{if(!all.some(x=>x.name===file.name&&x.size===file.size&&x.lastModified===file.lastModified)) all.push(file)});
      setFiles(all);
    }
    function render(){
      const files=[...input.files]; preview.innerHTML='';
      files.forEach((file,index)=>{
        const item=document.createElement('div'); item.className='attachment-preview-item';
        if(file.type.startsWith('image/')){
          const img=document.createElement('img'); img.alt=file.name; const url=URL.createObjectURL(file); img.src=url; img.addEventListener('load',()=>URL.revokeObjectURL(url),{once:true}); item.append(img);
        }else{
          const box=document.createElement('div'); box.className='attachment-preview-file'; box.textContent=file.name; item.append(box);
        }
        const remove=document.createElement('button'); remove.type='button'; remove.className='attachment-remove'; remove.setAttribute('aria-label',ar?'حذف الملف':'Remove file'); remove.textContent='×';
        remove.addEventListener('click',()=>setFiles(files.filter((_,i)=>i!==index))); item.append(remove); preview.append(item);
      });
      preview.classList.toggle('has-files',files.length>0); count.classList.toggle('show',files.length>0); count.textContent=files.length?copy.count(files.length):'';
    }
    input.addEventListener('change',render);
    cameraInput.addEventListener('change',()=>{if(cameraInput.files?.length)merge([...cameraInput.files]);cameraInput.value='';});
    render();
  }
  configs.forEach(enhance);
})();
</script>
HTML;

        $html = str_replace('</head>', $style."\n</head>", $html);
        $html = str_replace('</body>', $script."\n</body>", $html);
        $response->setContent($html);

        return $response;
    }
}
