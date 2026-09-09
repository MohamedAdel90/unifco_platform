<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class PublicUniversalCompactAttachmentsPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('public.request.store')) {
            foreach (['problem_photos','equipment_photos','previous_reports','supporting_images','supporting_documents'] as $field) {
                $files = $request->file($field);
                if (is_array($files) && count(array_filter($files)) > 2) {
                    return back()->withInput()->withErrors([$field => 'الحد الأقصى مرفقان فقط لكل بند.']);
                }
            }

            if ($request->hasFile('request_video')) {
                $request->validate([
                    'request_video' => ['file','mimetypes:video/mp4,video/quicktime,video/webm,video/x-m4v','max:30720'],
                ], [
                    'request_video.mimetypes' => 'يرجى رفع مقطع فيديو صالح فقط.',
                    'request_video.max' => 'حجم مقطع الفيديو أكبر من الحد المسموح.',
                ]);
            }

            $response = $next($request);

            if ($request->hasFile('request_video') && method_exists($response, 'getTargetUrl')) {
                $target = (string) $response->getTargetUrl();
                if (preg_match('~/request-received/([^/?#]+)~', $target, $m)) {
                    $reference = urldecode($m[1]);
                    $path = $request->file('request_video')->store('public-service-requests/videos', 'public');
                    if (Schema::hasColumn('public_service_requests', 'video_attachment_path')) {
                        DB::table('public_service_requests')->where('reference_no', $reference)->update([
                            'video_attachment_path' => $path,
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            return $response;
        }

        $response = $next($request);
        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $style = <<<'HTML'
<style id="unifco-universal-compact-attachments-v2">
#uf-upload-rows{display:grid;gap:7px}
.uf-upload-row{display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;grid-template-areas:"label actions" "preview preview" "error error"!important;align-items:center!important;column-gap:9px!important;row-gap:7px!important;padding:7px 9px!important;min-height:47px!important;border:1px dashed #cbdced!important;border-radius:8px!important;background:#fff!important;margin:0!important;overflow:visible!important}
.uf-upload-row>b,.uf-upload-row>.uf-attachment-label{grid-area:label!important;display:flex!important;align-items:center!important;gap:7px!important;justify-self:stretch!important;color:#0b2d5f!important;font-size:9.5px!important;font-weight:900!important;min-width:0!important}
.uf-compact-actions{grid-area:actions!important;display:flex!important;align-items:center!important;gap:6px!important;direction:ltr!important}
.uf-upload-btn{width:38px!important;min-width:38px!important;max-width:38px!important;height:34px!important;min-height:34px!important;padding:0!important;border-radius:6px!important;display:inline-grid!important;place-items:center!important;font-size:0!important;line-height:0!important;overflow:hidden!important}
.uf-upload-btn.camera{background:#071f4d!important;border-color:#071f4d!important;color:#fff!important}
.uf-upload-btn.device{background:#1773cf!important;border-color:#1773cf!important;color:#fff!important}
.uf-upload-btn svg,.uf-upload-btn .uf-compact-svg{width:17px!important;height:17px!important;display:block!important;fill:none!important;stroke:currentColor!important;stroke-width:1.8!important;stroke-linecap:round!important;stroke-linejoin:round!important}
.uf-upload-btn span:not(.uf-compact-icon){display:none!important}
.uf-row-meta{display:inline-flex!important;align-items:center!important;gap:5px!important;color:#6e819a!important;font-size:7.7px!important;font-weight:800!important;white-space:nowrap!important;margin-inline-start:5px!important}
.uf-row-limit{padding:2px 7px!important;border-radius:999px!important;background:#f1f6fc!important;color:#627b9a!important}
.uf-compact-preview-strip{grid-area:preview!important;grid-column:1/-1!important;display:none!important;align-items:center!important;gap:7px!important;flex-wrap:wrap!important;padding:8px 2px 1px!important;margin:0!important;border-top:1px solid #eef3f8!important;min-height:0!important}
.uf-upload-row.has-compact-previews .uf-compact-preview-strip{display:flex!important}
.uf-compact-preview{width:56px!important;height:56px!important;flex:0 0 56px!important;border:1px solid #cfddeb!important;border-radius:7px!important;background:#f7faff!important;position:relative!important;overflow:visible!important;cursor:pointer!important;padding:0!important;display:grid!important;place-items:center!important;color:#176dca!important}
.uf-compact-preview img,.uf-compact-preview video{width:100%!important;height:100%!important;object-fit:cover!important;border-radius:6px!important;display:block!important}
.uf-compact-doc{width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;padding:4px;overflow:hidden}
.uf-compact-doc svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.8}
.uf-compact-doc span{width:100%;font-size:6.5px;font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding:0 2px}
.uf-compact-remove{position:absolute!important;top:-6px!important;left:-6px!important;width:19px!important;height:19px!important;border:2px solid #fff!important;border-radius:50%!important;background:#d93646!important;color:#fff!important;display:grid!important;place-items:center!important;padding:0!important;font:900 13px/1 Arial!important;cursor:pointer!important;z-index:4!important;box-shadow:0 2px 5px rgba(7,31,77,.2)!important}
.uf-video-play{position:absolute!important;inset:0!important;display:grid!important;place-items:center!important;color:#fff!important;font-size:18px!important;text-shadow:0 1px 4px rgba(0,0,0,.55)!important;pointer-events:none!important}
.uf-video-duration{position:absolute!important;right:3px!important;bottom:3px!important;padding:1px 4px!important;border-radius:4px!important;background:rgba(4,23,54,.82)!important;color:#fff!important;font:800 7px Cairo!important}
.uf-row-error{grid-area:error!important;grid-column:1/-1!important;color:#c92135!important;font-size:8px!important;font-weight:800!important;display:none!important}.uf-row-error.show{display:block!important}
#uf-files-summary{display:none!important}
.uf-attachment-viewer{position:fixed;inset:0;z-index:999999;display:none;align-items:center;justify-content:center;background:rgba(5,18,42,.82);padding:18px}.uf-attachment-viewer.show{display:flex}.uf-attachment-viewer-card{position:relative;width:min(920px,94vw);height:min(700px,88vh);background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.35)}.uf-attachment-viewer-close{position:absolute;left:10px;top:10px;z-index:3;width:32px;height:32px;border:0;border-radius:50%;background:#071f4d;color:#fff;font:900 20px/1 Arial;cursor:pointer}.uf-attachment-viewer-title{position:absolute;right:14px;top:13px;left:54px;color:#08295d;font:800 10px Cairo;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:right}.uf-attachment-viewer-body{width:100%;height:100%;padding:48px 16px 16px;display:flex;align-items:center;justify-content:center;background:#f7f9fc}.uf-attachment-viewer-body img,.uf-attachment-viewer-body video{max-width:100%;max-height:100%;object-fit:contain;border-radius:7px}.uf-attachment-viewer-body iframe{width:100%;height:100%;border:0;background:#fff}.uf-attachment-viewer-generic{display:flex;flex-direction:column;align-items:center;gap:10px;color:#173866;text-align:center}.uf-attachment-viewer-generic a{height:36px;padding:0 15px;border-radius:7px;background:#071f4d;color:#fff;text-decoration:none;display:inline-flex;align-items:center;font:900 9px Cairo}
@media(max-width:650px){.uf-upload-row{grid-template-columns:minmax(0,1fr) auto!important}.uf-row-meta{display:none!important}.uf-upload-btn{width:36px!important;min-width:36px!important;height:32px!important;min-height:32px!important}.uf-compact-preview{width:52px!important;height:52px!important;flex-basis:52px!important}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-universal-compact-attachments-script-v2">
(()=>{
 const rowsRoot=()=>document.getElementById('uf-upload-rows');
 const cameraSvg='<svg class="uf-compact-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/><circle cx="12" cy="14" r="3.5"/></svg>';
 const videoSvg='<svg class="uf-compact-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3z"/></svg>';
 const uploadSvg='<svg class="uf-compact-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>';
 const docSvg='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M10 12h6M10 16h6"/></svg>';
 let viewerUrl=null;

 const ensureViewer=()=>{
   let viewer=document.getElementById('uf-attachment-viewer');
   if(viewer)return viewer;
   viewer=document.createElement('div');viewer.id='uf-attachment-viewer';viewer.className='uf-attachment-viewer';
   viewer.innerHTML='<div class="uf-attachment-viewer-card" role="dialog" aria-modal="true"><button type="button" class="uf-attachment-viewer-close" aria-label="إغلاق">×</button><div class="uf-attachment-viewer-title"></div><div class="uf-attachment-viewer-body"></div></div>';
   document.body.appendChild(viewer);
   const close=()=>{viewer.classList.remove('show');viewer.querySelector('.uf-attachment-viewer-body').innerHTML='';if(viewerUrl){URL.revokeObjectURL(viewerUrl);viewerUrl=null;}};
   viewer.querySelector('.uf-attachment-viewer-close').addEventListener('click',close);viewer.addEventListener('click',e=>{if(e.target===viewer)close();});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&viewer.classList.contains('show'))close();});
   return viewer;
 };

 const openPreview=file=>{
   const viewer=ensureViewer(),body=viewer.querySelector('.uf-attachment-viewer-body'),title=viewer.querySelector('.uf-attachment-viewer-title');
   if(viewerUrl)URL.revokeObjectURL(viewerUrl);viewerUrl=URL.createObjectURL(file);title.textContent=file.name||'معاينة المرفق';body.innerHTML='';
   if((file.type||'').startsWith('image/')){const img=document.createElement('img');img.src=viewerUrl;img.alt=file.name||'صورة';body.appendChild(img);}
   else if((file.type||'').startsWith('video/')){const v=document.createElement('video');v.src=viewerUrl;v.controls=true;v.playsInline=true;body.appendChild(v);}
   else if(file.type==='application/pdf'||/\.pdf$/i.test(file.name||'')){const f=document.createElement('iframe');f.src=viewerUrl;f.title=file.name||'PDF';body.appendChild(f);}
   else{const box=document.createElement('div');box.className='uf-attachment-viewer-generic';box.innerHTML=docSvg+'<b></b><a target="_blank" rel="noopener">فتح الملف</a>';box.querySelector('b').textContent=file.name||'ملف مرفق';box.querySelector('a').href=viewerUrl;body.appendChild(box);}
   viewer.classList.add('show');
 };

 const setFiles=(input,files)=>{if(typeof DataTransfer==='undefined')return false;const dt=new DataTransfer();files.forEach(f=>dt.items.add(f));input.files=dt.files;return true;};
 const rowInputs=row=>[...row.querySelectorAll('input[type=file]')].filter(i=>i.dataset.ufVideo!=='1');
 const rowEntries=row=>rowInputs(row).flatMap(input=>[...(input.files||[])].map((file,index)=>({file,input,index})));

 const enforceRowLimit=(row,changedInput)=>{
   const others=rowInputs(row).filter(i=>i!==changedInput).flatMap(i=>[...(i.files||[])]);
   const available=Math.max(0,2-others.length),chosen=[...(changedInput.files||[])];
   if(chosen.length<=available)return true;
   setFiles(changedInput,chosen.slice(0,available));
   showError(row,'الحد الأقصى صورتان/مرفقان فقط لهذا البند.');
   return false;
 };
 const showError=(row,msg='')=>{let e=row.querySelector('.uf-row-error');if(!e){e=document.createElement('div');e.className='uf-row-error';row.appendChild(e);}e.textContent=msg;e.classList.toggle('show',!!msg);if(msg)setTimeout(()=>{if(e.textContent===msg)e.classList.remove('show');},3500);};
 const removeEntry=(row,input,index)=>{const files=[...(input.files||[])];if(!files[index])return;setFiles(input,files.filter((_,i)=>i!==index));input.dispatchEvent(new Event('change',{bubbles:true}));};

 const renderRow=row=>{
   if(row.id==='uf-video-upload-row')return;
   let strip=row.querySelector('.uf-compact-preview-strip');
   if(!strip){strip=document.createElement('div');strip.className='uf-compact-preview-strip';row.appendChild(strip);}
   strip.querySelectorAll('[data-preview-url]').forEach(el=>{try{URL.revokeObjectURL(el.dataset.previewUrl)}catch(_){}});strip.innerHTML='';
   const entries=rowEntries(row),badge=row.querySelector('.uf-file-count');if(badge)badge.textContent=String(entries.length);
   row.classList.toggle('has-compact-previews',entries.length>0);
   entries.forEach(({file,input,index})=>{
     const item=document.createElement('button');item.type='button';item.className='uf-compact-preview';item.title='اضغط لمعاينة '+(file.name||'المرفق');
     if((file.type||'').startsWith('image/')){const url=URL.createObjectURL(file);item.dataset.previewUrl=url;const img=document.createElement('img');img.src=url;img.alt=file.name||'صورة';item.appendChild(img);}
     else{const doc=document.createElement('span');doc.className='uf-compact-doc';doc.innerHTML=docSvg+'<span></span>';doc.querySelector('span').textContent=file.name||'ملف';item.appendChild(doc);}
     item.addEventListener('click',()=>openPreview(file));
     const remove=document.createElement('button');remove.type='button';remove.className='uf-compact-remove';remove.textContent='×';remove.title='حذف المرفق';remove.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();removeEntry(row,input,index);});item.appendChild(remove);strip.appendChild(item);
   });
 };

 const decorateRow=row=>{
   if(!row||row.id==='uf-video-upload-row')return;
   if(row.dataset.ufCompactReady!=='1'){
     row.dataset.ufCompactReady='1';
     const buttons=[...row.querySelectorAll('.uf-upload-btn')];
     if(buttons.length){const actions=document.createElement('div');actions.className='uf-compact-actions';buttons.forEach(btn=>{const isCamera=btn.classList.contains('camera');btn.innerHTML=isCamera?cameraSvg:uploadSvg;btn.title=isCamera?'التقاط صورة':'رفع من الجهاز';btn.setAttribute('aria-label',btn.title);actions.appendChild(btn);});row.appendChild(actions);}
     const label=row.querySelector('b');if(label&&!label.querySelector('.uf-row-meta')){const meta=document.createElement('span');meta.className='uf-row-meta';meta.innerHTML='<span class="uf-row-limit">الحد الأقصى: 2</span>';label.appendChild(meta);}
   }
   rowInputs(row).forEach(input=>{if(input.dataset.ufLimitBound!=='1'){input.dataset.ufLimitBound='1';input.addEventListener('change',()=>{showError(row,'');enforceRowLimit(row,input);renderRow(row);},true);}});
   renderRow(row);
 };

 const ensureVideoRow=()=>{
   const rows=rowsRoot();if(!rows||document.getElementById('uf-video-upload-row'))return;
   const row=document.createElement('div');row.className='uf-upload-row';row.id='uf-video-upload-row';row.dataset.ufCompactReady='1';
   row.innerHTML='<b>إضافة مقطع فيديو <span class="uf-row-meta"><span class="uf-row-limit">فيديو واحد · 15 ثانية فقط</span></span></b><div class="uf-compact-actions"><button type="button" class="uf-upload-btn camera" id="uf-video-camera" title="تصوير فيديو" aria-label="تصوير فيديو">'+videoSvg+'</button><button type="button" class="uf-upload-btn device" id="uf-video-device" title="رفع فيديو من الجهاز" aria-label="رفع فيديو من الجهاز">'+uploadSvg+'</button></div><span class="uf-file-count" style="display:none">0</span><input id="uf-video-input" class="uf-upload-input" data-uf-video="1" type="file" name="request_video" accept="video/mp4,video/quicktime,video/webm,video/x-m4v"><div class="uf-compact-preview-strip"></div><div class="uf-row-error"></div>';
   rows.appendChild(row);
   const input=row.querySelector('#uf-video-input');row.querySelector('#uf-video-camera').addEventListener('click',()=>{input.setAttribute('capture','environment');input.click();});row.querySelector('#uf-video-device').addEventListener('click',()=>{input.removeAttribute('capture');input.click();});input.addEventListener('change',()=>validateVideo(input,row));
 };

 const clearVideo=(input,row,msg='')=>{input.value='';const strip=row.querySelector('.uf-compact-preview-strip');strip.innerHTML='';row.classList.remove('has-compact-previews');const badge=row.querySelector('.uf-file-count');if(badge)badge.textContent='0';showError(row,msg);};
 const validateVideo=(input,row)=>{const file=input.files?.[0];if(!file){clearVideo(input,row);return;}if(!(file.type||'').startsWith('video/')){clearVideo(input,row,'يرجى اختيار ملف فيديو فقط.');return;}const probe=document.createElement('video');probe.preload='metadata';const url=URL.createObjectURL(file);probe.src=url;probe.onloadedmetadata=()=>{const duration=Number(probe.duration||0);URL.revokeObjectURL(url);if(!duration||duration>15.25){clearVideo(input,row,'مدة الفيديو يجب ألا تتجاوز 15 ثانية.');return;}renderVideo(file,row,duration);};probe.onerror=()=>{URL.revokeObjectURL(url);clearVideo(input,row,'تعذر قراءة مدة الفيديو. يرجى اختيار فيديو آخر.');};};
 const renderVideo=(file,row,duration)=>{const strip=row.querySelector('.uf-compact-preview-strip');strip.innerHTML='';const item=document.createElement('button');item.type='button';item.className='uf-compact-preview';item.title='اضغط لمعاينة الفيديو';const url=URL.createObjectURL(file);item.dataset.previewUrl=url;const video=document.createElement('video');video.src=url;video.muted=true;video.preload='metadata';video.playsInline=true;item.appendChild(video);const play=document.createElement('span');play.className='uf-video-play';play.textContent='▶';item.appendChild(play);const dur=document.createElement('span');dur.className='uf-video-duration';dur.textContent='00:'+String(Math.ceil(duration)).padStart(2,'0');item.appendChild(dur);const remove=document.createElement('button');remove.type='button';remove.className='uf-compact-remove';remove.textContent='×';remove.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();URL.revokeObjectURL(url);clearVideo(row.querySelector('#uf-video-input'),row);});item.appendChild(remove);item.addEventListener('click',()=>openPreview(file));strip.appendChild(item);row.classList.add('has-compact-previews');const badge=row.querySelector('.uf-file-count');if(badge)badge.textContent='1';showError(row,'');};

 const init=()=>{const rows=rowsRoot();if(!rows)return false;rows.querySelectorAll('.uf-upload-row').forEach(decorateRow);ensureVideoRow();return true;};
 const start=()=>init();
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>{if(!start()){let n=0,t=setInterval(()=>{if(start()||++n>50)clearInterval(t)},80);}});else if(!start()){let n=0,t=setInterval(()=>{if(start()||++n>50)clearInterval(t)},80);}
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
