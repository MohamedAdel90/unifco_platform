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
            $countFiles = static function ($value) use (&$countFiles): int {
                if (is_array($value)) {
                    return array_sum(array_map($countFiles, $value));
                }
                return $value ? 1 : 0;
            };

            foreach ($request->allFiles() as $field => $files) {
                if ($field === 'request_video') {
                    continue;
                }

                if ($countFiles($files) > 2) {
                    return back()->withInput()->withErrors([
                        $field => 'الحد الأقصى مرفقان فقط لكل بند.',
                    ]);
                }
            }

            if ($request->hasFile('request_video')) {
                $request->validate([
                    'request_video' => ['file', 'mimetypes:video/mp4,video/quicktime,video/webm,video/x-m4v', 'max:30720'],
                ], [
                    'request_video.file' => 'يسمح بمقطع فيديو واحد فقط.',
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
        if (! str_contains($html, 'id="uf-upload-rows"')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-universal-compact-attachments-v3">
#uf-upload-rows{display:grid!important;gap:8px!important}
#uf-files-summary,.uf-inline-previews{display:none!important}
.uf-upload-row{display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;grid-template-areas:"meta actions" "preview preview" "error error"!important;align-items:center!important;column-gap:10px!important;row-gap:7px!important;width:100%!important;min-height:50px!important;padding:8px 10px!important;margin:0!important;border:1px dashed #cbdced!important;border-radius:9px!important;background:#fff!important;box-sizing:border-box!important;overflow:visible!important}
.uf-attachment-meta{grid-area:meta!important;display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:7px!important;min-width:0!important;direction:rtl!important}
.uf-attachment-meta>b,.uf-attachment-meta>.uf-attachment-label{display:flex!important;align-items:center!important;gap:5px!important;margin:0!important;color:#0b2d5f!important;font-size:9.5px!important;font-weight:900!important;white-space:normal!important;line-height:1.45!important}
.uf-attachment-meta .uf-file-count{position:static!important;display:inline-grid!important;place-items:center!important;width:25px!important;min-width:25px!important;height:25px!important;border-radius:50%!important;background:#eaf2fb!important;color:#54718f!important;font-size:8px!important;font-weight:900!important;margin:0!important}
.uf-attachment-meta .uf-file-count.has-files{background:#176dca!important;color:#fff!important}
.uf-row-limit{display:inline-flex!important;align-items:center!important;height:22px!important;padding:0 7px!important;border-radius:999px!important;background:#f1f6fc!important;color:#647d99!important;font-size:7.5px!important;font-weight:800!important;white-space:nowrap!important}
.uf-compact-actions{grid-area:actions!important;display:flex!important;align-items:center!important;justify-content:flex-end!important;gap:6px!important;direction:ltr!important;margin:0!important}
.uf-upload-btn{position:static!important;width:34px!important;min-width:34px!important;max-width:34px!important;height:32px!important;min-height:32px!important;padding:0!important;margin:0!important;border-radius:7px!important;display:inline-grid!important;place-items:center!important;font-size:0!important;line-height:0!important;overflow:hidden!important;box-shadow:none!important;transform:none!important}
.uf-upload-btn.camera{background:#071f4d!important;border:1px solid #071f4d!important;color:#fff!important}
.uf-upload-btn.device{background:#1976d2!important;border:1px solid #1976d2!important;color:#fff!important}
.uf-upload-btn:hover{filter:brightness(.94)!important;transform:none!important}
.uf-upload-btn svg{width:16px!important;height:16px!important;display:block!important;fill:none!important;stroke:currentColor!important;stroke-width:1.9!important;stroke-linecap:round!important;stroke-linejoin:round!important}
.uf-upload-btn span{display:none!important}
.uf-upload-input{position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;overflow:hidden!important;pointer-events:none!important;clip:rect(0 0 0 0)!important;clip-path:inset(50%)!important}
.uf-compact-preview-strip{grid-area:preview!important;grid-column:1/-1!important;display:none!important;align-items:center!important;justify-content:flex-start!important;gap:8px!important;flex-wrap:wrap!important;width:100%!important;padding:9px 0 1px!important;margin:0!important;border-top:1px solid #edf3f9!important;direction:rtl!important}
.uf-upload-row.has-compact-previews .uf-compact-preview-strip{display:flex!important}
.uf-compact-preview{width:62px!important;height:62px!important;flex:0 0 62px!important;border:1px solid #cddbea!important;border-radius:8px!important;background:#f7faff!important;position:relative!important;display:grid!important;place-items:center!important;overflow:visible!important;color:#176dca!important;cursor:pointer!important}
.uf-compact-preview:hover{border-color:#7baee5!important;box-shadow:0 3px 10px rgba(7,31,77,.08)!important}
.uf-compact-preview-media{width:100%!important;height:100%!important;border:0!important;padding:0!important;margin:0!important;border-radius:7px!important;background:transparent!important;overflow:hidden!important;display:grid!important;place-items:center!important;cursor:pointer!important}
.uf-compact-preview img,.uf-compact-preview video{width:100%!important;height:100%!important;object-fit:cover!important;display:block!important;border-radius:7px!important}
.uf-compact-doc{width:100%!important;height:100%!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;gap:3px!important;padding:5px!important;box-sizing:border-box!important;overflow:hidden!important}
.uf-compact-doc svg{width:21px!important;height:21px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.8!important}
.uf-compact-doc span{width:100%!important;font-size:6.5px!important;font-weight:900!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;text-align:center!important}
.uf-compact-remove{position:absolute!important;top:-6px!important;left:-6px!important;width:19px!important;height:19px!important;border:2px solid #fff!important;border-radius:50%!important;background:#d93646!important;color:#fff!important;display:grid!important;place-items:center!important;padding:0!important;margin:0!important;font:900 13px/1 Arial!important;cursor:pointer!important;z-index:5!important;box-shadow:0 2px 5px rgba(7,31,77,.2)!important}
.uf-row-error{grid-area:error!important;grid-column:1/-1!important;display:none!important;color:#c51f33!important;background:#fff5f6!important;border:1px solid #ffd6db!important;border-radius:6px!important;padding:5px 7px!important;font-size:8px!important;font-weight:800!important;line-height:1.5!important}.uf-row-error.show{display:block!important}
.uf-video-play{position:absolute!important;inset:0!important;display:grid!important;place-items:center!important;color:#fff!important;font-size:18px!important;text-shadow:0 1px 4px rgba(0,0,0,.55)!important;pointer-events:none!important}.uf-video-duration{position:absolute!important;right:3px!important;bottom:3px!important;padding:1px 4px!important;border-radius:4px!important;background:rgba(4,23,54,.82)!important;color:#fff!important;font:800 7px Cairo!important}
.uf-attachment-viewer{position:fixed!important;inset:0!important;z-index:999999!important;display:none!important;align-items:center!important;justify-content:center!important;background:rgba(5,18,42,.84)!important;padding:18px!important}.uf-attachment-viewer.show{display:flex!important}.uf-attachment-viewer-card{position:relative!important;width:min(920px,94vw)!important;height:min(700px,88vh)!important;background:#fff!important;border-radius:12px!important;overflow:hidden!important;box-shadow:0 24px 80px rgba(0,0,0,.35)!important}.uf-attachment-viewer-close{position:absolute!important;left:10px!important;top:10px!important;z-index:3!important;width:32px!important;height:32px!important;border:0!important;border-radius:50%!important;background:#071f4d!important;color:#fff!important;font:900 20px/1 Arial!important;cursor:pointer!important}.uf-attachment-viewer-title{position:absolute!important;right:14px!important;top:13px!important;left:54px!important;color:#08295d!important;font:800 10px Cairo!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;text-align:right!important}.uf-attachment-viewer-body{width:100%!important;height:100%!important;padding:48px 16px 16px!important;display:flex!important;align-items:center!important;justify-content:center!important;background:#f7f9fc!important;box-sizing:border-box!important}.uf-attachment-viewer-body img,.uf-attachment-viewer-body video{max-width:100%!important;max-height:100%!important;object-fit:contain!important;border-radius:7px!important}.uf-attachment-viewer-body iframe{width:100%!important;height:100%!important;border:0!important;background:#fff!important}.uf-attachment-viewer-generic{display:flex!important;flex-direction:column!important;align-items:center!important;gap:10px!important;color:#173866!important;text-align:center!important}.uf-attachment-viewer-generic a{height:36px!important;padding:0 15px!important;border-radius:7px!important;background:#071f4d!important;color:#fff!important;text-decoration:none!important;display:inline-flex!important;align-items:center!important;font:900 9px Cairo!important}
@media(max-width:650px){.uf-upload-row{grid-template-columns:minmax(0,1fr) auto!important;padding:8px!important}.uf-row-limit{display:none!important}.uf-upload-btn{width:32px!important;min-width:32px!important;max-width:32px!important;height:30px!important;min-height:30px!important}.uf-compact-preview{width:56px!important;height:56px!important;flex-basis:56px!important}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-universal-compact-attachments-script-v3">
(()=>{
  const root=()=>document.getElementById('uf-upload-rows');
  if(!root())return;

  const cameraSvg='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/><circle cx="12" cy="14" r="3.5"/></svg>';
  const videoSvg='<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3z"/></svg>';
  const uploadSvg='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>';
  const docSvg='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M10 12h6M10 16h6"/></svg>';
  const states=new WeakMap();
  let viewerUrl=null;

  const signature=f=>[f.name,f.size,f.lastModified,f.type].join('|');
  const unique=files=>{const seen=new Set();return files.filter(f=>{const k=signature(f);if(seen.has(k))return false;seen.add(k);return true;});};
  const setFiles=(input,files)=>{if(!input||typeof DataTransfer==='undefined')return false;const dt=new DataTransfer();files.forEach(f=>dt.items.add(f));input.files=dt.files;return true;};
  const inputsFor=row=>[...row.querySelectorAll('input[type="file"]')].filter(i=>i.dataset.ufVideo!=='1');
  const stateFor=row=>{if(!states.has(row)){const existing=unique(inputsFor(row).flatMap(i=>[...(i.files||[])]));states.set(row,{files:existing});}return states.get(row);};
  const canonicalFor=row=>inputsFor(row)[0]||null;

  const showError=(row,msg='')=>{let box=row.querySelector('.uf-row-error');if(!box){box=document.createElement('div');box.className='uf-row-error';row.appendChild(box);}box.textContent=msg;box.classList.toggle('show',!!msg);if(msg)setTimeout(()=>{if(box.textContent===msg)box.classList.remove('show');},3800);};
  const syncPhysical=(row)=>{const state=stateFor(row),inputs=inputsFor(row),canonical=canonicalFor(row);if(!canonical)return;setFiles(canonical,state.files);inputs.slice(1).forEach(i=>setFiles(i,[]));};

  const ensureViewer=()=>{let viewer=document.getElementById('uf-attachment-viewer');if(viewer)return viewer;viewer=document.createElement('div');viewer.id='uf-attachment-viewer';viewer.className='uf-attachment-viewer';viewer.innerHTML='<div class="uf-attachment-viewer-card" role="dialog" aria-modal="true"><button type="button" class="uf-attachment-viewer-close" aria-label="إغلاق">×</button><div class="uf-attachment-viewer-title"></div><div class="uf-attachment-viewer-body"></div></div>';document.body.appendChild(viewer);const close=()=>{viewer.classList.remove('show');viewer.querySelector('.uf-attachment-viewer-body').innerHTML='';if(viewerUrl){URL.revokeObjectURL(viewerUrl);viewerUrl=null;}};viewer.querySelector('.uf-attachment-viewer-close').addEventListener('click',close);viewer.addEventListener('click',e=>{if(e.target===viewer)close();});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&viewer.classList.contains('show'))close();});return viewer;};
  const openPreview=file=>{const viewer=ensureViewer(),body=viewer.querySelector('.uf-attachment-viewer-body'),title=viewer.querySelector('.uf-attachment-viewer-title');if(viewerUrl)URL.revokeObjectURL(viewerUrl);viewerUrl=URL.createObjectURL(file);title.textContent=file.name||'معاينة المرفق';body.innerHTML='';if((file.type||'').startsWith('image/')){const img=document.createElement('img');img.src=viewerUrl;img.alt=file.name||'صورة';body.appendChild(img);}else if((file.type||'').startsWith('video/')){const v=document.createElement('video');v.src=viewerUrl;v.controls=true;v.playsInline=true;body.appendChild(v);}else if(file.type==='application/pdf'||/\.pdf$/i.test(file.name||'')){const frame=document.createElement('iframe');frame.src=viewerUrl;frame.title=file.name||'PDF';body.appendChild(frame);}else{const box=document.createElement('div');box.className='uf-attachment-viewer-generic';box.innerHTML=docSvg+'<b></b><a target="_blank" rel="noopener">فتح الملف</a>';box.querySelector('b').textContent=file.name||'ملف مرفق';box.querySelector('a').href=viewerUrl;body.appendChild(box);}viewer.classList.add('show');};

  const renderRow=row=>{if(row.id==='uf-video-upload-row')return;const state=stateFor(row);let strip=row.querySelector('.uf-compact-preview-strip');if(!strip){strip=document.createElement('div');strip.className='uf-compact-preview-strip';row.appendChild(strip);}strip.querySelectorAll('[data-preview-url]').forEach(el=>{try{URL.revokeObjectURL(el.dataset.previewUrl)}catch(_){}});strip.innerHTML='';const badge=row.querySelector('.uf-file-count');if(badge){badge.textContent=String(state.files.length);badge.classList.toggle('has-files',state.files.length>0);}row.classList.toggle('has-compact-previews',state.files.length>0);state.files.forEach((file,index)=>{const item=document.createElement('div');item.className='uf-compact-preview';const media=document.createElement('button');media.type='button';media.className='uf-compact-preview-media';media.title='اضغط لمعاينة '+(file.name||'المرفق');if((file.type||'').startsWith('image/')){const url=URL.createObjectURL(file);item.dataset.previewUrl=url;const img=document.createElement('img');img.src=url;img.alt=file.name||'صورة';media.appendChild(img);}else{const doc=document.createElement('span');doc.className='uf-compact-doc';doc.innerHTML=docSvg+'<span></span>';doc.querySelector('span').textContent=file.name||'ملف';media.appendChild(doc);}media.addEventListener('click',()=>openPreview(file));const remove=document.createElement('button');remove.type='button';remove.className='uf-compact-remove';remove.textContent='×';remove.title='حذف المرفق';remove.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();state.files.splice(index,1);syncPhysical(row);renderRow(row);});item.appendChild(media);item.appendChild(remove);strip.appendChild(item);});};

  const absorbSelection=(row,input)=>{const state=stateFor(row),picked=[...(input.files||[])];if(!picked.length)return;const merged=unique([...state.files,...picked]);if(merged.length>2){state.files=merged.slice(0,2);showError(row,'الحد الأقصى مرفقان فقط لهذا البند. تم الاحتفاظ بأول مرفقين فقط.');}else{state.files=merged;showError(row,'');}syncPhysical(row);renderRow(row);};

  const decorate=row=>{if(!row||row.id==='uf-video-upload-row')return;let label=row.querySelector('b,.uf-attachment-label');let badge=row.querySelector('.uf-file-count');let meta=row.querySelector('.uf-attachment-meta');if(!meta){meta=document.createElement('div');meta.className='uf-attachment-meta';if(label)meta.appendChild(label);if(badge)meta.appendChild(badge);const limit=document.createElement('span');limit.className='uf-row-limit';limit.textContent='الحد الأقصى: 2';meta.appendChild(limit);row.prepend(meta);}let actions=row.querySelector('.uf-compact-actions');if(!actions){actions=document.createElement('div');actions.className='uf-compact-actions';const buttons=[...row.querySelectorAll('.uf-upload-btn')];buttons.forEach(btn=>{const isCamera=btn.classList.contains('camera');btn.innerHTML=isCamera?cameraSvg:uploadSvg;btn.title=isCamera?'التقاط صورة':'رفع من الجهاز';btn.setAttribute('aria-label',btn.title);actions.appendChild(btn);});row.appendChild(actions);}inputsFor(row).forEach(input=>{if(input.dataset.ufV3Bound==='1')return;input.dataset.ufV3Bound='1';input.addEventListener('change',()=>absorbSelection(row,input),true);});syncPhysical(row);renderRow(row);};

  const ensureVideoRow=()=>{const rows=root();if(!rows||document.getElementById('uf-video-upload-row'))return;const row=document.createElement('div');row.className='uf-upload-row';row.id='uf-video-upload-row';row.innerHTML='<div class="uf-attachment-meta"><b>إضافة مقطع فيديو</b><span class="uf-file-count">0</span><span class="uf-row-limit">فيديو واحد · 15 ثانية</span></div><div class="uf-compact-actions"><button type="button" class="uf-upload-btn camera" id="uf-video-camera" title="تصوير فيديو" aria-label="تصوير فيديو">'+videoSvg+'</button><button type="button" class="uf-upload-btn device" id="uf-video-device" title="رفع فيديو" aria-label="رفع فيديو">'+uploadSvg+'</button></div><input id="uf-video-input" class="uf-upload-input" data-uf-video="1" type="file" name="request_video" accept="video/mp4,video/quicktime,video/webm,video/x-m4v"><div class="uf-compact-preview-strip"></div><div class="uf-row-error"></div>';rows.appendChild(row);const input=row.querySelector('#uf-video-input');row.querySelector('#uf-video-camera').addEventListener('click',()=>{input.setAttribute('capture','environment');input.click();});row.querySelector('#uf-video-device').addEventListener('click',()=>{input.removeAttribute('capture');input.click();});input.addEventListener('change',()=>validateVideo(input,row));};
  const clearVideo=(input,row,msg='')=>{input.value='';const strip=row.querySelector('.uf-compact-preview-strip');strip.querySelectorAll('[data-preview-url]').forEach(el=>{try{URL.revokeObjectURL(el.dataset.previewUrl)}catch(_){}});strip.innerHTML='';row.classList.remove('has-compact-previews');const badge=row.querySelector('.uf-file-count');if(badge){badge.textContent='0';badge.classList.remove('has-files');}showError(row,msg);};
  const validateVideo=(input,row)=>{const file=input.files?.[0];if(!file){clearVideo(input,row);return;}if(!(file.type||'').startsWith('video/')){clearVideo(input,row,'يرجى اختيار ملف فيديو فقط.');return;}const probe=document.createElement('video');probe.preload='metadata';const url=URL.createObjectURL(file);probe.src=url;probe.onloadedmetadata=()=>{const duration=Number(probe.duration||0);URL.revokeObjectURL(url);if(!duration||duration>15.25){clearVideo(input,row,'مدة الفيديو يجب ألا تتجاوز 15 ثانية.');return;}renderVideo(file,row,duration);};probe.onerror=()=>{URL.revokeObjectURL(url);clearVideo(input,row,'تعذر قراءة مدة الفيديو. يرجى اختيار فيديو آخر.');};};
  const renderVideo=(file,row,duration)=>{const strip=row.querySelector('.uf-compact-preview-strip');strip.innerHTML='';const item=document.createElement('div');item.className='uf-compact-preview';const media=document.createElement('button');media.type='button';media.className='uf-compact-preview-media';const url=URL.createObjectURL(file);item.dataset.previewUrl=url;const video=document.createElement('video');video.src=url;video.muted=true;video.preload='metadata';video.playsInline=true;media.appendChild(video);media.addEventListener('click',()=>openPreview(file));const play=document.createElement('span');play.className='uf-video-play';play.textContent='▶';const dur=document.createElement('span');dur.className='uf-video-duration';dur.textContent='00:'+String(Math.ceil(duration)).padStart(2,'0');const remove=document.createElement('button');remove.type='button';remove.className='uf-compact-remove';remove.textContent='×';remove.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();URL.revokeObjectURL(url);clearVideo(row.querySelector('#uf-video-input'),row);});item.appendChild(media);item.appendChild(play);item.appendChild(dur);item.appendChild(remove);strip.appendChild(item);row.classList.add('has-compact-previews');const badge=row.querySelector('.uf-file-count');if(badge){badge.textContent='1';badge.classList.add('has-files');}showError(row,'');};

  document.addEventListener('click',e=>{const btn=e.target.closest?.('.uf-upload-row:not(#uf-video-upload-row) .uf-upload-btn');if(!btn)return;e.preventDefault();e.stopImmediatePropagation();const row=btn.closest('.uf-upload-row'),inputs=inputsFor(row);if(!inputs.length)return;const isCamera=btn.classList.contains('camera');const input=isCamera?(inputs.find(i=>i.hasAttribute('capture'))||inputs[0]):(inputs.find(i=>!i.hasAttribute('capture'))||inputs[inputs.length-1]);if(isCamera)input.setAttribute('capture','environment');else input.removeAttribute('capture');input.click();},true);

  const init=()=>{const rows=root();if(!rows)return false;rows.querySelectorAll('.uf-upload-row').forEach(decorate);ensureVideoRow();return true;};
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>{init();});else init();
  const observer=new MutationObserver(()=>{root()?.querySelectorAll('.uf-upload-row').forEach(decorate);ensureVideoRow();});
  observer.observe(root(),{childList:true,subtree:true});
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
