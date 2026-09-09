<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
<style id="unifco-universal-compact-attachments-v1">
#uf-upload-rows{display:grid;gap:7px}
.uf-upload-row{display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;grid-template-areas:"label actions" "preview preview"!important;align-items:center!important;column-gap:9px!important;row-gap:7px!important;padding:7px 9px!important;min-height:47px!important;border:1px dashed #cbdced!important;border-radius:8px!important;background:#fff!important;margin:0!important;overflow:visible!important}
.uf-upload-row>b,.uf-upload-row>.uf-attachment-label{grid-area:label!important;display:flex!important;align-items:center!important;gap:7px!important;justify-self:stretch!important;color:#0b2d5f!important;font-size:9.5px!important;font-weight:900!important;min-width:0!important}
.uf-compact-actions{grid-area:actions!important;display:flex!important;align-items:center!important;gap:6px!important;direction:ltr!important}
.uf-upload-row>.uf-upload-btn{grid-area:auto!important}
.uf-upload-btn{width:38px!important;min-width:38px!important;max-width:38px!important;height:34px!important;min-height:34px!important;padding:0!important;border-radius:6px!important;display:inline-grid!important;place-items:center!important;font-size:0!important;line-height:0!important;overflow:hidden!important}
.uf-upload-btn.camera{background:#071f4d!important;border-color:#071f4d!important;color:#fff!important}
.uf-upload-btn.device{background:#1773cf!important;border-color:#1773cf!important;color:#fff!important}
.uf-upload-btn svg,.uf-upload-btn .uf-compact-svg{width:17px!important;height:17px!important;display:block!important;fill:none!important;stroke:currentColor!important;stroke-width:1.8!important;stroke-linecap:round!important;stroke-linejoin:round!important}
.uf-upload-btn span:not(.uf-compact-icon){display:none!important}
.uf-row-meta{display:inline-flex!important;align-items:center!important;gap:5px!important;color:#6e819a!important;font-size:7.7px!important;font-weight:800!important;white-space:nowrap!important;margin-inline-start:5px!important}
.uf-row-limit{padding:2px 7px!important;border-radius:999px!important;background:#f1f6fc!important;color:#627b9a!important}
.uf-inline-previews,.uf-compact-preview-strip{grid-area:preview!important;grid-column:1/-1!important;display:none;align-items:center!important;gap:6px!important;flex-wrap:wrap!important;padding:7px 2px 1px!important;margin:0!important;border-top:1px solid #eef3f8!important;min-height:0!important}
.uf-upload-row.has-inline-previews .uf-inline-previews,.uf-upload-row.has-compact-previews .uf-compact-preview-strip{display:flex!important}
.uf-inline-preview,.uf-compact-preview{width:54px!important;height:54px!important;flex:0 0 54px!important;border:1px solid #cfddeb!important;border-radius:7px!important;background:#f7faff!important;position:relative!important;overflow:visible!important;cursor:pointer!important;padding:0!important}
.uf-compact-preview video,.uf-compact-preview img{width:100%!important;height:100%!important;object-fit:cover!important;border-radius:6px!important;display:block!important}
.uf-compact-remove{position:absolute!important;top:-5px!important;left:-5px!important;width:18px!important;height:18px!important;border:2px solid #fff!important;border-radius:50%!important;background:#d93646!important;color:#fff!important;display:grid!important;place-items:center!important;padding:0!important;font:900 13px/1 Arial!important;cursor:pointer!important;z-index:3!important}
.uf-video-play{position:absolute!important;inset:0!important;display:grid!important;place-items:center!important;color:#fff!important;font-size:18px!important;text-shadow:0 1px 4px rgba(0,0,0,.55)!important;pointer-events:none!important}
.uf-video-duration{position:absolute!important;right:3px!important;bottom:3px!important;padding:1px 4px!important;border-radius:4px!important;background:rgba(4,23,54,.82)!important;color:#fff!important;font:800 7px Cairo!important}
.uf-video-error{grid-area:preview!important;grid-column:1/-1!important;color:#c92135!important;font-size:8px!important;font-weight:800!important;padding-top:3px!important;display:none}.uf-video-error.show{display:block!important}
#uf-files-summary{display:none!important}
@media(max-width:650px){.uf-upload-row{grid-template-columns:minmax(0,1fr) auto!important}.uf-row-meta{display:none!important}.uf-upload-btn{width:36px!important;min-width:36px!important;height:32px!important;min-height:32px!important}.uf-inline-preview,.uf-compact-preview{width:50px!important;height:50px!important;flex-basis:50px!important}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-universal-compact-attachments-script-v1">
(()=>{
 const root=()=>document.getElementById('uf-upload-rows');
 const cameraSvg='<svg class="uf-compact-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/><circle cx="12" cy="14" r="3.5"/></svg>';
 const videoSvg='<svg class="uf-compact-svg" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3z"/></svg>';
 const uploadSvg='<svg class="uf-compact-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>';
 let modalUrl=null;

 const ensureModal=()=>{
   let modal=document.getElementById('uf-compact-video-modal');
   if(modal)return modal;
   modal=document.createElement('div');modal.id='uf-compact-video-modal';
   modal.style.cssText='position:fixed;inset:0;z-index:999999;display:none;align-items:center;justify-content:center;background:rgba(5,18,42,.82);padding:20px';
   modal.innerHTML='<div style="position:relative;width:min(820px,94vw);max-height:88vh;background:#071f4d;border-radius:12px;padding:42px 14px 14px"><button type="button" aria-label="إغلاق" style="position:absolute;left:10px;top:8px;width:30px;height:30px;border:0;border-radius:50%;background:#fff;color:#071f4d;font-size:20px;cursor:pointer">×</button><video controls playsinline style="display:block;width:100%;max-height:76vh;border-radius:8px;background:#000"></video></div>';
   document.body.appendChild(modal);
   const close=()=>{modal.style.display='none';const v=modal.querySelector('video');v.pause();v.removeAttribute('src');if(modalUrl){URL.revokeObjectURL(modalUrl);modalUrl=null;}};
   modal.querySelector('button').addEventListener('click',close);modal.addEventListener('click',e=>{if(e.target===modal)close()});
   return modal;
 };
 const openVideo=file=>{const m=ensureModal(),v=m.querySelector('video');if(modalUrl)URL.revokeObjectURL(modalUrl);modalUrl=URL.createObjectURL(file);v.src=modalUrl;m.style.display='flex';};

 const allFiles=row=>[...row.querySelectorAll('input[type=file]')].flatMap(i=>[...(i.files||[])]);
 const setInputFiles=(input,files)=>{if(typeof DataTransfer==='undefined')return;const dt=new DataTransfer();files.forEach(f=>dt.items.add(f));input.files=dt.files;};

 const enforceTwo=input=>{
   if(input.dataset.ufVideo==='1')return true;
   const files=[...(input.files||[])];
   if(files.length<=2)return true;
   setInputFiles(input,files.slice(0,2));
   alert('الحد الأقصى مرفقان فقط لكل بند.');
   return false;
 };

 const decorateRow=row=>{
   if(!row||row.dataset.ufCompactReady==='1')return;
   row.dataset.ufCompactReady='1';
   const buttons=[...row.querySelectorAll('.uf-upload-btn')];
   if(buttons.length){
     const actions=document.createElement('div');actions.className='uf-compact-actions';
     buttons.forEach(btn=>{const isCamera=btn.classList.contains('camera');btn.innerHTML=isCamera?cameraSvg:uploadSvg;btn.title=isCamera?'التقاط صورة':'رفع من الجهاز';btn.setAttribute('aria-label',btn.title);actions.appendChild(btn);});
     row.appendChild(actions);
   }
   const label=row.querySelector('b');
   if(label){
     const meta=document.createElement('span');meta.className='uf-row-meta';meta.innerHTML='<span class="uf-row-limit">الحد الأقصى: 2</span>';
     label.appendChild(meta);
   }
   row.querySelectorAll('input[type=file]').forEach(input=>{if(!input.dataset.ufLimitBound){input.dataset.ufLimitBound='1';input.addEventListener('change',()=>{enforceTwo(input);setTimeout(()=>renderExisting(row),0);},true);}});
 };

 const renderExisting=row=>{
   const count=allFiles(row).length;
   const badge=row.querySelector('.uf-file-count');if(badge)badge.textContent=String(count);
 };

 const ensureVideoRow=()=>{
   const rows=root();if(!rows||document.getElementById('uf-video-upload-row'))return;
   const row=document.createElement('div');row.className='uf-upload-row';row.id='uf-video-upload-row';row.dataset.ufCompactReady='1';
   row.innerHTML='<b>إضافة مقطع فيديو <span class="uf-row-meta"><span class="uf-row-limit">فيديو واحد · 15 ثانية فقط</span></span></b><div class="uf-compact-actions"><button type="button" class="uf-upload-btn camera" id="uf-video-camera" title="تصوير فيديو" aria-label="تصوير فيديو">'+videoSvg+'</button><button type="button" class="uf-upload-btn device" id="uf-video-device" title="رفع فيديو من الجهاز" aria-label="رفع فيديو من الجهاز">'+uploadSvg+'</button></div><input id="uf-video-input" class="uf-upload-input" data-uf-video="1" type="file" name="request_video" accept="video/mp4,video/quicktime,video/webm,video/x-m4v"><div class="uf-compact-preview-strip"></div><div class="uf-video-error"></div>';
   rows.appendChild(row);
   const input=row.querySelector('#uf-video-input'),camera=row.querySelector('#uf-video-camera'),device=row.querySelector('#uf-video-device');
   camera.addEventListener('click',()=>{input.setAttribute('capture','environment');input.click();});
   device.addEventListener('click',()=>{input.removeAttribute('capture');input.click();});
   input.addEventListener('change',()=>validateVideo(input,row));
 };

 const clearVideo=(input,row,msg='')=>{input.value='';const strip=row.querySelector('.uf-compact-preview-strip');strip.innerHTML='';row.classList.remove('has-compact-previews');const error=row.querySelector('.uf-video-error');error.textContent=msg;error.classList.toggle('show',!!msg);};
 const validateVideo=(input,row)=>{
   const file=input.files?.[0];const error=row.querySelector('.uf-video-error');error.classList.remove('show');
   if(!file){clearVideo(input,row);return;}
   if(!(file.type||'').startsWith('video/')){clearVideo(input,row,'يرجى اختيار ملف فيديو فقط.');return;}
   const probe=document.createElement('video');probe.preload='metadata';const url=URL.createObjectURL(file);probe.src=url;
   probe.onloadedmetadata=()=>{const duration=Number(probe.duration||0);URL.revokeObjectURL(url);if(!duration||duration>15.25){clearVideo(input,row,'مدة الفيديو يجب ألا تتجاوز 15 ثانية.');return;}renderVideo(file,row,duration);};
   probe.onerror=()=>{URL.revokeObjectURL(url);clearVideo(input,row,'تعذر قراءة مدة الفيديو. يرجى اختيار فيديو آخر.');};
 };
 const renderVideo=(file,row,duration)=>{
   const strip=row.querySelector('.uf-compact-preview-strip');strip.innerHTML='';
   const item=document.createElement('button');item.type='button';item.className='uf-compact-preview';item.title='اضغط لمعاينة الفيديو';
   const url=URL.createObjectURL(file);const video=document.createElement('video');video.src=url;video.muted=true;video.preload='metadata';video.playsInline=true;item.appendChild(video);
   const play=document.createElement('span');play.className='uf-video-play';play.textContent='▶';item.appendChild(play);
   const dur=document.createElement('span');dur.className='uf-video-duration';dur.textContent='00:'+String(Math.ceil(duration)).padStart(2,'0');item.appendChild(dur);
   const remove=document.createElement('button');remove.type='button';remove.className='uf-compact-remove';remove.textContent='×';remove.title='حذف الفيديو';remove.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();URL.revokeObjectURL(url);clearVideo(row.querySelector('#uf-video-input'),row);});item.appendChild(remove);
   item.addEventListener('click',()=>openVideo(file));strip.appendChild(item);row.classList.add('has-compact-previews');
 };

 const init=()=>{const rows=root();if(!rows)return false;rows.querySelectorAll('.uf-upload-row').forEach(decorateRow);ensureVideoRow();return true;};
 const observer=new MutationObserver(()=>{if(init()){};});
 const boot=()=>{if(init()){observer.observe(root(),{childList:true,subtree:true});return;}let n=0,t=setInterval(()=>{if(init()||++n>80){clearInterval(t);if(root())observer.observe(root(),{childList:true,subtree:true});}},50);};
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);
        return $response;
    }
}
