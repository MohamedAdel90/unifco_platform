<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class PublicFinalUnifiedAttachmentsPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('public.request.store')) {
            foreach ($request->allFiles() as $field => $files) {
                if ($field === 'request_video') {
                    continue;
                }
                $flat = [];
                array_walk_recursive($files, static function ($file) use (&$flat): void {
                    if ($file) $flat[] = $file;
                });
                if (count($flat) > 2) {
                    return back()->withInput()->withErrors([$field => 'الحد الأقصى مرفقان فقط لكل بند.']);
                }
            }

            if ($request->hasFile('request_video')) {
                $request->validate([
                    'request_video' => ['file','mimetypes:video/mp4,video/quicktime,video/webm,video/x-m4v','max:30720'],
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
        if (! str_contains($html, 'uf-upload-rows')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-final-unified-attachments-v2">
#uf-upload-rows{display:grid!important;gap:8px!important}
#uf-files-summary,.uf-inline-previews,.uf-preview-groups,.uf-preview-summary-title,.uf-compact-preview-strip{display:none!important}
#uf-upload-rows .uf-upload-row{display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;grid-template-areas:"meta actions" "preview preview" "error error"!important;gap:8px 10px!important;align-items:center!important;padding:9px 10px!important;margin:0!important;border:1px dashed #cbdced!important;border-radius:9px!important;background:#fff!important;min-height:50px!important;box-sizing:border-box!important}
#uf-upload-rows .uf-attachment-meta{grid-area:meta!important;display:flex!important;align-items:center!important;gap:7px!important;direction:rtl!important;min-width:0!important}
#uf-upload-rows .uf-attachment-meta b{margin:0!important;color:#0b2d5f!important;font:900 9.5px Cairo,Tahoma,sans-serif!important;line-height:1.45!important}
#uf-upload-rows .uf-file-count{display:inline-grid!important;place-items:center!important;width:24px!important;height:24px!important;min-width:24px!important;border-radius:50%!important;background:#eaf2fb!important;color:#5d7591!important;font:900 8px Cairo!important}
#uf-upload-rows .uf-file-count.has-files{background:#176dca!important;color:#fff!important}
#uf-upload-rows .uf-row-limit{display:inline-flex!important;align-items:center!important;height:21px!important;padding:0 7px!important;border-radius:999px!important;background:#f1f6fc!important;color:#667e98!important;font:800 7.3px Cairo!important;white-space:nowrap!important}
#uf-upload-rows .uf-final-actions{grid-area:actions!important;display:flex!important;gap:6px!important;align-items:center!important;direction:ltr!important}
#uf-upload-rows .uf-upload-btn{width:34px!important;min-width:34px!important;max-width:34px!important;height:32px!important;min-height:32px!important;padding:0!important;margin:0!important;border-radius:7px!important;display:grid!important;place-items:center!important;font-size:0!important;line-height:0!important;box-shadow:none!important;transform:none!important}
#uf-upload-rows .uf-upload-btn.camera{background:#071f4d!important;border:1px solid #071f4d!important;color:#fff!important}
#uf-upload-rows .uf-upload-btn.device{background:#1976d2!important;border:1px solid #1976d2!important;color:#fff!important}
#uf-upload-rows .uf-upload-btn svg{width:16px!important;height:16px!important;fill:none!important;stroke:currentColor!important;stroke-width:1.9!important;stroke-linecap:round!important;stroke-linejoin:round!important}
#uf-upload-rows input[type=file]{position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;pointer-events:none!important}
#uf-upload-rows .uf-final-preview-strip{grid-area:preview!important;grid-column:1/-1!important;display:none!important;gap:8px!important;align-items:center!important;flex-wrap:wrap!important;padding:9px 0 1px!important;border-top:1px solid #edf3f9!important;direction:rtl!important}
#uf-upload-rows .uf-upload-row.has-final-previews .uf-final-preview-strip{display:flex!important}
#uf-upload-rows .uf-final-preview{width:60px!important;height:60px!important;flex:0 0 60px!important;border:1px solid #cddbea!important;border-radius:8px!important;background:#f7faff!important;position:relative!important;display:grid!important;place-items:center!important;overflow:visible!important}
#uf-upload-rows .uf-final-preview-media{width:100%!important;height:100%!important;padding:0!important;border:0!important;border-radius:7px!important;background:transparent!important;overflow:hidden!important;display:grid!important;place-items:center!important}
#uf-upload-rows .uf-final-preview img,#uf-upload-rows .uf-final-preview video{width:100%!important;height:100%!important;object-fit:cover!important;border-radius:7px!important;display:block!important}
#uf-upload-rows .uf-final-doc{padding:5px!important;color:#176dca!important;font:800 7px Cairo!important;text-align:center!important;overflow:hidden!important}
#uf-upload-rows .uf-final-remove{position:absolute!important;top:-6px!important;left:-6px!important;width:19px!important;height:19px!important;border:2px solid #fff!important;border-radius:50%!important;background:#d93646!important;color:#fff!important;display:grid!important;place-items:center!important;padding:0!important;font:900 13px/1 Arial!important;z-index:4!important}
#uf-upload-rows .uf-row-error{grid-area:error!important;grid-column:1/-1!important;display:none!important;padding:5px 7px!important;border:1px solid #ffd6db!important;border-radius:6px!important;background:#fff5f6!important;color:#c51f33!important;font:800 8px Cairo!important}.uf-row-error.show{display:block!important}
.uf-final-viewer{position:fixed!important;inset:0!important;z-index:999999!important;display:none!important;align-items:center!important;justify-content:center!important;background:rgba(5,18,42,.84)!important;padding:18px!important}.uf-final-viewer.show{display:flex!important}.uf-final-viewer-card{position:relative!important;width:min(920px,94vw)!important;height:min(700px,88vh)!important;background:#fff!important;border-radius:12px!important;overflow:hidden!important}.uf-final-viewer-close{position:absolute!important;left:10px!important;top:10px!important;z-index:3!important;width:32px!important;height:32px!important;border:0!important;border-radius:50%!important;background:#071f4d!important;color:#fff!important;font:900 20px Arial!important}.uf-final-viewer-title{position:absolute!important;right:14px!important;top:13px!important;left:54px!important;color:#08295d!important;font:800 10px Cairo!important;text-align:right!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important}.uf-final-viewer-body{width:100%!important;height:100%!important;padding:48px 16px 16px!important;box-sizing:border-box!important;display:flex!important;align-items:center!important;justify-content:center!important;background:#f7f9fc!important}.uf-final-viewer-body img,.uf-final-viewer-body video{max-width:100%!important;max-height:100%!important;object-fit:contain!important}.uf-final-viewer-body iframe{width:100%!important;height:100%!important;border:0!important;background:#fff!important}
@media(max-width:650px){#uf-upload-rows .uf-row-limit{display:none!important}#uf-upload-rows .uf-upload-btn{width:32px!important;min-width:32px!important;max-width:32px!important;height:30px!important;min-height:30px!important}#uf-upload-rows .uf-final-preview{width:54px!important;height:54px!important;flex-basis:54px!important}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-final-unified-attachments-script-v2">
(()=>{
 const cameraSvg='<svg viewBox="0 0 24 24"><path d="M4 8h3l1.5-2h7L17 8h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/><circle cx="12" cy="14" r="3.5"/></svg>';
 const uploadSvg='<svg viewBox="0 0 24 24"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>';
 const videoSvg='<svg viewBox="0 0 24 24"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3z"/></svg>';
 const states=new WeakMap();let viewerUrl=null,rootObserver=null,waitObserver=null;
 const root=()=>document.getElementById('uf-upload-rows');
 const sig=f=>[f.name,f.size,f.lastModified,f.type].join('|');
 const uniq=files=>{const s=new Set();return files.filter(f=>{const k=sig(f);if(s.has(k))return false;s.add(k);return true})};
 const setFiles=(input,files)=>{if(!input||typeof DataTransfer==='undefined')return;const dt=new DataTransfer();files.forEach(f=>dt.items.add(f));input.files=dt.files};
 const inputs=row=>[...row.querySelectorAll('input[type=file]')].filter(i=>i.dataset.ufVideo!=='1');
 const getState=row=>{if(!states.has(row))states.set(row,{files:uniq(inputs(row).flatMap(i=>[...(i.files||[])]))});return states.get(row)};
 const sync=row=>{const list=inputs(row),s=getState(row);if(!list.length)return;setFiles(list[0],s.files);list.slice(1).forEach(i=>setFiles(i,[]))};
 const error=(row,msg='')=>{let e=row.querySelector('.uf-row-error');if(!e){e=document.createElement('div');e.className='uf-row-error';row.appendChild(e)}e.textContent=msg;e.classList.toggle('show',!!msg);if(msg)setTimeout(()=>e.classList.remove('show'),3000)};
 const ensureViewer=()=>{let v=document.getElementById('uf-final-viewer');if(v)return v;v=document.createElement('div');v.id='uf-final-viewer';v.className='uf-final-viewer';v.innerHTML='<div class="uf-final-viewer-card"><button type="button" class="uf-final-viewer-close">×</button><div class="uf-final-viewer-title"></div><div class="uf-final-viewer-body"></div></div>';document.body.appendChild(v);const close=()=>{v.classList.remove('show');v.querySelector('.uf-final-viewer-body').innerHTML='';if(viewerUrl){URL.revokeObjectURL(viewerUrl);viewerUrl=null}};v.querySelector('.uf-final-viewer-close').onclick=close;v.onclick=e=>{if(e.target===v)close()};return v};
 const open=file=>{const v=ensureViewer(),b=v.querySelector('.uf-final-viewer-body');if(viewerUrl)URL.revokeObjectURL(viewerUrl);viewerUrl=URL.createObjectURL(file);v.querySelector('.uf-final-viewer-title').textContent=file.name||'معاينة المرفق';b.innerHTML='';if(file.type.startsWith('image/')){const x=document.createElement('img');x.src=viewerUrl;b.appendChild(x)}else if(file.type.startsWith('video/')){const x=document.createElement('video');x.src=viewerUrl;x.controls=true;x.playsInline=true;b.appendChild(x)}else if(file.type==='application/pdf'||/\.pdf$/i.test(file.name)){const x=document.createElement('iframe');x.src=viewerUrl;b.appendChild(x)}else{const a=document.createElement('a');a.href=viewerUrl;a.target='_blank';a.textContent=file.name||'فتح الملف';b.appendChild(a)}v.classList.add('show')};
 const render=row=>{if(row.id==='uf-video-upload-row')return;const s=getState(row);let strip=row.querySelector('.uf-final-preview-strip');if(!strip){strip=document.createElement('div');strip.className='uf-final-preview-strip';row.appendChild(strip)}strip.querySelectorAll('[data-url]').forEach(x=>{try{URL.revokeObjectURL(x.dataset.url)}catch(_){}});strip.innerHTML='';const badge=row.querySelector('.uf-file-count');if(badge){badge.textContent=s.files.length;badge.classList.toggle('has-files',s.files.length>0)}row.classList.toggle('has-final-previews',s.files.length>0);s.files.forEach((file,index)=>{const item=document.createElement('div');item.className='uf-final-preview';const media=document.createElement('button');media.type='button';media.className='uf-final-preview-media';if(file.type.startsWith('image/')){const u=URL.createObjectURL(file);item.dataset.url=u;const img=document.createElement('img');img.src=u;media.appendChild(img)}else{const d=document.createElement('div');d.className='uf-final-doc';d.textContent=file.name;media.appendChild(d)}media.onclick=()=>open(file);const rm=document.createElement('button');rm.type='button';rm.className='uf-final-remove';rm.textContent='×';rm.onclick=e=>{e.preventDefault();e.stopPropagation();s.files.splice(index,1);sync(row);render(row)};item.append(media,rm);strip.appendChild(item)})};
 const decorate=row=>{if(!row||row.id==='uf-video-upload-row'||row.dataset.ufFinalReady==='1')return;row.dataset.ufFinalReady='1';row.querySelectorAll('.uf-inline-previews,.uf-compact-preview-strip').forEach(x=>x.remove());let label=row.querySelector('b,.uf-attachment-label');const meta=document.createElement('div');meta.className='uf-attachment-meta';if(label)meta.appendChild(label);let badge=row.querySelector('.uf-file-count');if(!badge){badge=document.createElement('span');badge.className='uf-file-count';badge.textContent='0'}meta.appendChild(badge);const lim=document.createElement('span');lim.className='uf-row-limit';lim.textContent='الحد الأقصى: 2';meta.appendChild(lim);row.prepend(meta);const actions=document.createElement('div');actions.className='uf-final-actions';row.querySelectorAll('.uf-upload-btn').forEach(btn=>{const cam=btn.classList.contains('camera');btn.innerHTML=cam?cameraSvg:uploadSvg;btn.title=cam?'التقاط صورة':'رفع من الجهاز';actions.appendChild(btn)});row.appendChild(actions);inputs(row).forEach(i=>{i.multiple=true;i.dataset.ufFinalBound='1'});sync(row);render(row)};
 const absorb=(row,input)=>{const s=getState(row),picked=[...(input.files||[])];const merged=uniq([...s.files,...picked]);if(merged.length>2){s.files=merged.slice(0,2);error(row,'الحد الأقصى مرفقان فقط لهذا البند.')}else{s.files=merged;error(row,'')}sync(row);render(row)};
 const ensureVideo=()=>{const r=root();if(!r||document.getElementById('uf-video-upload-row'))return;const row=document.createElement('div');row.className='uf-upload-row';row.id='uf-video-upload-row';row.innerHTML='<div class="uf-attachment-meta"><b>إضافة مقطع فيديو</b><span class="uf-file-count">0</span><span class="uf-row-limit">فيديو واحد · 15 ثانية</span></div><div class="uf-final-actions"><button type="button" class="uf-upload-btn camera" id="uf-video-cam">'+videoSvg+'</button><button type="button" class="uf-upload-btn device" id="uf-video-dev">'+uploadSvg+'</button></div><input type="file" id="uf-video-input" data-uf-video="1" name="request_video" accept="video/mp4,video/quicktime,video/webm,video/x-m4v"><div class="uf-final-preview-strip"></div><div class="uf-row-error"></div>';r.appendChild(row);const input=row.querySelector('#uf-video-input');row.querySelector('#uf-video-cam').onclick=()=>{input.setAttribute('capture','environment');input.click()};row.querySelector('#uf-video-dev').onclick=()=>{input.removeAttribute('capture');input.click()};input.onchange=()=>{const f=input.files?.[0];if(!f)return;const probe=document.createElement('video'),u=URL.createObjectURL(f);probe.preload='metadata';probe.src=u;probe.onloadedmetadata=()=>{URL.revokeObjectURL(u);if(!probe.duration||probe.duration>15.25){input.value='';error(row,'مدة الفيديو يجب ألا تتجاوز 15 ثانية.');return}const strip=row.querySelector('.uf-final-preview-strip');strip.innerHTML='';const item=document.createElement('div');item.className='uf-final-preview';const vid=document.createElement('video');vid.src=URL.createObjectURL(f);vid.muted=true;item.appendChild(vid);const rm=document.createElement('button');rm.type='button';rm.className='uf-final-remove';rm.textContent='×';rm.onclick=()=>{input.value='';strip.innerHTML='';row.classList.remove('has-final-previews');row.querySelector('.uf-file-count').textContent='0'};item.onclick=()=>open(f);item.appendChild(rm);strip.appendChild(item);row.classList.add('has-final-previews');row.querySelector('.uf-file-count').textContent='1'};probe.onerror=()=>{URL.revokeObjectURL(u);input.value='';error(row,'تعذر قراءة الفيديو.')}}};
 const bindRoot=()=>{const r=root();if(!r)return false;r.querySelectorAll('.uf-upload-row').forEach(decorate);ensureVideo();if(rootObserver)rootObserver.disconnect();rootObserver=new MutationObserver(records=>{for(const rec of records){for(const n of rec.addedNodes){if(n.nodeType===1&&n.classList?.contains('uf-upload-row'))decorate(n)}}ensureVideo()});rootObserver.observe(r,{childList:true});return true};
 document.addEventListener('click',e=>{const btn=e.target.closest?.('#uf-upload-rows .uf-upload-row:not(#uf-video-upload-row) .uf-upload-btn');if(!btn)return;e.preventDefault();e.stopImmediatePropagation();const row=btn.closest('.uf-upload-row'),list=inputs(row);if(!list.length)return;const cam=btn.classList.contains('camera');const input=cam?(list.find(i=>i.hasAttribute('capture'))||list[0]):(list.find(i=>!i.hasAttribute('capture'))||list[list.length-1]);if(cam)input.setAttribute('capture','environment');else input.removeAttribute('capture');input.click()},true);
 document.addEventListener('change',e=>{const input=e.target;if(!(input instanceof HTMLInputElement)||input.type!=='file'||!input.closest('#uf-upload-rows')||input.dataset.ufVideo==='1')return;e.stopImmediatePropagation();absorb(input.closest('.uf-upload-row'),input)},true);
 if(!bindRoot()){waitObserver=new MutationObserver(()=>{if(bindRoot()){waitObserver.disconnect();waitObserver=null}});waitObserver.observe(document.documentElement,{childList:true,subtree:true});setTimeout(()=>{if(waitObserver){waitObserver.disconnect();waitObserver=null}},5000)}
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
