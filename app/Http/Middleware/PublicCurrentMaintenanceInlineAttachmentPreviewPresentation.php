<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCurrentMaintenanceInlineAttachmentPreviewPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="uf-request-workspace"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-inline-attachment-previews-v1">
/* Keep every attachment preview inside its own upload section. */
#uf-files-summary{display:none!important}
.uf-upload-row{overflow:visible!important}
.uf-inline-previews{grid-column:1/-1!important;display:none;gap:7px;align-items:center;flex-wrap:wrap;padding-top:7px;margin-top:1px;border-top:1px solid #edf2f7}
.uf-upload-row.has-inline-previews .uf-inline-previews{display:flex!important}
.uf-inline-preview{width:58px;height:58px;flex:0 0 58px;border:1px solid #d1ddeb;border-radius:7px;background:#f7faff;position:relative;display:grid;place-items:center;overflow:visible;cursor:pointer;padding:0;color:#176dca}
.uf-inline-preview:hover{border-color:#7fb0e7;box-shadow:0 3px 10px rgba(7,31,77,.08)}
.uf-inline-preview img{width:100%;height:100%;display:block;object-fit:cover;border-radius:6px}
.uf-inline-preview-doc{width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;padding:5px;text-align:center;overflow:hidden}
.uf-inline-preview-doc svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.8;flex:0 0 20px}
.uf-inline-preview-doc span{display:block;width:100%;font-size:6.8px;font-weight:900;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.uf-inline-remove{position:absolute;top:-5px;left:-5px;z-index:4;width:18px;height:18px;border:2px solid #fff;border-radius:50%;background:#d93646;color:#fff;padding:0;display:grid;place-items:center;font:900 13px/13px Arial,sans-serif;cursor:pointer;box-shadow:0 2px 5px rgba(7,31,77,.2)}
.uf-inline-remove:hover{background:#b91f31}
.uf-attachment-modal{position:fixed;inset:0;z-index:999999;display:none;align-items:center;justify-content:center;background:rgba(5,18,42,.82);padding:24px}
.uf-attachment-modal.show{display:flex}
.uf-attachment-modal-card{position:relative;width:min(960px,94vw);height:min(720px,88vh);background:#fff;border-radius:12px;box-shadow:0 24px 80px rgba(0,0,0,.35);overflow:hidden;display:flex;align-items:center;justify-content:center}
.uf-attachment-modal-close{position:absolute;top:10px;left:10px;z-index:3;width:34px;height:34px;border:0;border-radius:50%;background:#071f4d;color:#fff;font:900 22px/1 Arial;cursor:pointer;display:grid;place-items:center}
.uf-attachment-modal-title{position:absolute;right:16px;top:12px;left:58px;z-index:2;color:#08295d;font:800 10px Cairo;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:right}
.uf-attachment-modal-body{width:100%;height:100%;padding:48px 18px 18px;display:flex;align-items:center;justify-content:center;background:#f8fafc}
.uf-attachment-modal-body img{max-width:100%;max-height:100%;object-fit:contain;border-radius:6px}
.uf-attachment-modal-body iframe{width:100%;height:100%;border:0;border-radius:6px;background:#fff}
.uf-attachment-modal-generic{display:flex;flex-direction:column;align-items:center;gap:10px;color:#173866;text-align:center;padding:24px}
.uf-attachment-modal-generic svg{width:54px;height:54px;fill:none;stroke:#176dca;stroke-width:1.5}
.uf-attachment-modal-generic b{font-size:12px;max-width:520px;overflow-wrap:anywhere}
.uf-attachment-modal-generic small{font-size:9px;color:#7c899b}
.uf-attachment-modal-generic a{display:inline-flex;align-items:center;justify-content:center;height:36px;padding:0 16px;border-radius:7px;background:#071f4d;color:#fff;text-decoration:none;font:900 9px Cairo}
@media(max-width:650px){.uf-inline-previews{gap:6px}.uf-inline-preview{width:54px;height:54px;flex-basis:54px}.uf-attachment-modal{padding:10px}.uf-attachment-modal-card{width:96vw;height:86vh}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-inline-attachment-previews-script-v1">
(()=>{
  const rowsRoot=document.getElementById('uf-upload-rows');
  if(!rowsRoot)return;

  const docIcon='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M10 12h6M10 16h6"/></svg>';
  let modalUrl=null;

  const ensureModal=()=>{
    let modal=document.getElementById('uf-attachment-modal');
    if(modal)return modal;
    modal=document.createElement('div');
    modal.id='uf-attachment-modal';
    modal.className='uf-attachment-modal';
    modal.innerHTML='<div class="uf-attachment-modal-card" role="dialog" aria-modal="true" aria-label="معاينة المرفق"><button type="button" class="uf-attachment-modal-close" aria-label="إغلاق">×</button><div class="uf-attachment-modal-title"></div><div class="uf-attachment-modal-body"></div></div>';
    document.body.appendChild(modal);
    const close=()=>{
      modal.classList.remove('show');
      modal.querySelector('.uf-attachment-modal-body').innerHTML='';
      if(modalUrl){URL.revokeObjectURL(modalUrl);modalUrl=null;}
    };
    modal.querySelector('.uf-attachment-modal-close').addEventListener('click',close);
    modal.addEventListener('click',e=>{if(e.target===modal)close();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal.classList.contains('show'))close();});
    return modal;
  };

  const openPreview=file=>{
    const modal=ensureModal(),body=modal.querySelector('.uf-attachment-modal-body'),title=modal.querySelector('.uf-attachment-modal-title');
    if(modalUrl){URL.revokeObjectURL(modalUrl);modalUrl=null;}
    modalUrl=URL.createObjectURL(file);
    title.textContent=file.name||'معاينة المرفق';
    body.innerHTML='';
    if((file.type||'').startsWith('image/')){
      const img=document.createElement('img');img.src=modalUrl;img.alt=file.name||'المرفق';body.appendChild(img);
    }else if(file.type==='application/pdf'||/\.pdf$/i.test(file.name||'')){
      const frame=document.createElement('iframe');frame.src=modalUrl;frame.title=file.name||'PDF';body.appendChild(frame);
    }else{
      const box=document.createElement('div');box.className='uf-attachment-modal-generic';box.innerHTML=docIcon+'<b></b><small></small><a target="_blank" rel="noopener">فتح الملف للعرض</a>';
      box.querySelector('b').textContent=file.name||'ملف مرفق';
      box.querySelector('small').textContent=file.type||'نوع الملف غير محدد';
      box.querySelector('a').href=modalUrl;
      body.appendChild(box);
    }
    modal.classList.add('show');
  };

  const removeFile=(input,index)=>{
    const files=[...(input.files||[])];
    if(!files[index]||typeof DataTransfer==='undefined')return;
    const dt=new DataTransfer();
    files.forEach((file,i)=>{if(i!==index)dt.items.add(file);});
    input.files=dt.files;
    if(!input.files.length&&input.dataset.ufArchived==='1')input.remove();
    input.dispatchEvent(new Event('change',{bubbles:true}));
    setTimeout(renderAll,0);
  };

  const entriesForRow=row=>[...row.querySelectorAll('.uf-upload-input')].flatMap(input=>[...(input.files||[])].map((file,index)=>({file,input,index})));

  const renderRow=row=>{
    let holder=row.querySelector('.uf-inline-previews');
    if(!holder){holder=document.createElement('div');holder.className='uf-inline-previews';row.appendChild(holder);}
    holder.querySelectorAll('[data-uf-url]').forEach(el=>{try{URL.revokeObjectURL(el.dataset.ufUrl);}catch(_){}});
    holder.innerHTML='';
    const entries=entriesForRow(row),badge=row.querySelector('.uf-file-count');
    if(badge)badge.textContent=String(entries.length);
    row.classList.toggle('has-inline-previews',entries.length>0);
    entries.forEach(({file,input,index})=>{
      const item=document.createElement('button');item.type='button';item.className='uf-inline-preview';item.title='اضغط لمعاينة '+(file.name||'المرفق');
      if((file.type||'').startsWith('image/')){
        const url=URL.createObjectURL(file);item.dataset.ufUrl=url;
        const img=document.createElement('img');img.src=url;img.alt=file.name||'صورة مرفقة';item.appendChild(img);
      }else{
        const doc=document.createElement('span');doc.className='uf-inline-preview-doc';doc.innerHTML=docIcon+'<span></span>';doc.querySelector('span').textContent=file.name||'ملف';item.appendChild(doc);
      }
      item.addEventListener('click',()=>openPreview(file));
      const remove=document.createElement('button');remove.type='button';remove.className='uf-inline-remove';remove.textContent='×';remove.title='حذف المرفق';remove.setAttribute('aria-label','حذف '+(file.name||'المرفق'));
      remove.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();removeFile(input,index);});
      item.appendChild(remove);holder.appendChild(item);
    });
  };

  const renderAll=()=>document.querySelectorAll('.uf-upload-row').forEach(renderRow);

  document.addEventListener('change',e=>{if(e.target?.classList?.contains('uf-upload-input'))setTimeout(renderAll,0);},true);
  const observer=new MutationObserver(()=>setTimeout(renderAll,0));
  observer.observe(rowsRoot,{childList:true,subtree:true});
  renderAll();
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
