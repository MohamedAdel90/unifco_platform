<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminHomepageCmsPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('admin.homepage.sections.edit') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '<form id="homepage-section-form"')) {
            return $response;
        }

        $section = $request->route('section');
        $sectionKey = is_object($section) && isset($section->section_key) ? (string) $section->section_key : 'section';
        $aboutHelp = $sectionKey === 'about'
            ? ' قسم About يتضمن الآن مجموعتين مستقلتين: محتوى About في الصفحة الرئيسية، ثم محتوى صفحة About Us الكاملة بالصورة والنصوص العربية والإنجليزية.'
            : '';

        $guide = '<div class="cms-context-guide">'
            .'<div class="cms-context-copy"><span class="cms-context-badge">CMS</span><div><strong>Editing: '.e($sectionKey).'</strong><p>مكتبة الصور أصبحت مرتبطة بهذا القسم. استخدم المعاينة قبل الحفظ لأنها الأقرب لشكل الصفحة العامة الفعلي.'.e($aboutHelp).'</p></div></div>'
            .'<div class="cms-context-actions"><button type="button" data-cms-preview="ar">Preview AR</button><button type="button" data-cms-preview="en">Preview EN</button></div>'
            .'</div>';

        $html = str_replace('<form id="homepage-section-form"', $guide.'<form id="homepage-section-form"', $html, $count);
        if ($count < 1) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-admin-homepage-cms-presentation-v2">
.cms-context-guide{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:14px 0 4px;padding:13px 14px;background:linear-gradient(135deg,#f7faff,#eef4fd);border:1px solid #d7e4f5;border-radius:12px;box-shadow:0 5px 16px rgba(23,54,108,.05)}
.cms-context-copy{display:flex;align-items:flex-start;gap:10px;min-width:0}.cms-context-copy strong{display:block;color:#17366c;font-size:13px}.cms-context-copy p{margin:3px 0 0;color:#66758a;font-size:10.5px;line-height:1.65}.cms-context-badge{display:inline-grid;place-items:center;min-width:38px;height:28px;padding:0 8px;border-radius:7px;background:#17366c;color:#fff;font-size:9px;font-weight:900;letter-spacing:.05em}
.cms-context-actions{display:flex;gap:7px;flex:0 0 auto}.cms-context-actions button{border:1px solid #adc3e2;background:#fff;color:#17366c;border-radius:8px;padding:8px 11px;font-size:10.5px;font-weight:900;cursor:pointer}.cms-context-actions button:hover{background:#17366c;color:#fff;border-color:#17366c}
.img-picker-head:before{content:"Section image library";display:block;color:#17366c;font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px}.img-picker-head strong{font-size:14px!important}.img-picker{border-color:#d6e3f4!important;background:#f9fbfe!important}.img-picker-grid{max-height:360px;overflow:auto;padding-inline-end:2px}.img-picker-cell{transition:.16s}.img-picker-cell:hover{transform:translateY(-1px)}
.about-cms-heading{margin:20px 0 8px;padding:10px 12px;border-radius:9px;background:#071f4d;color:#fff;font-size:12px;font-weight:900;letter-spacing:.02em}.about-cms-heading small{display:block;margin-top:3px;color:#c9d7eb;font-size:9.5px;font-weight:600;line-height:1.5}.about-page-field{border-inline-start:3px solid #d7193f;padding-inline-start:10px}.about-page-field label{color:#071f4d!important}.about-page-field .hp-img-field{margin-top:5px}
@media(max-width:760px){.cms-context-guide{align-items:stretch;flex-direction:column}.cms-context-actions{width:100%}.cms-context-actions button{flex:1}.cms-context-copy p{font-size:10px}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-admin-homepage-cms-presentation-script-v2">
(()=>{
  const bind=()=>{
    document.querySelectorAll('[data-cms-preview]').forEach(btn=>{
      if(btn.dataset.cmsBound==='1')return;
      btn.dataset.cmsBound='1';
      btn.addEventListener('click',()=>{
        const locale=btn.dataset.cmsPreview;
        const existing=document.querySelector('[data-preview-locale="'+locale+'"]');
        if(existing){existing.click();return;}
        alert('Preview is not available for this section.');
      });
    });
    const help=document.getElementById('img-picker-help');
    if(help && !help.dataset.contextUpdated){
      help.dataset.contextUpdated='1';
      help.textContent='Choose the exact image field first. The library below is filtered for this homepage section.';
    }
  };
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',bind);else bind();
})();
</script>
HTML;

        if ($sectionKey === 'about') {
            $aboutScript = <<<'HTML'
<script id="unifco-about-cms-groups-v1">
(()=>{
 const friendly={
   page_visual_image:'Main visual image',page_strapline:'Strapline',page_eyebrow:'Eyebrow',page_title:'Page title',page_subtitle:'Subtitle',
   page_intro_text:'Introduction',page_intro_secondary_text:'Second introduction',page_quote_text:'Quote',
   page_apart_title:'What Sets Us Apart title',page_apart_text:'What Sets Us Apart text',
   page_mission_title:'Mission title',page_mission_text:'Mission text',page_vision_title:'Vision title',page_vision_text:'Vision text',
   page_people_title:'People & Technology title',page_people_text:'People & Technology text',page_footer_note_text:'Footer note'
 };
 const escapeHtml=s=>String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 const fieldWrap=input=>{
   if(!input)return null;
   let label=input.previousElementSibling;
   if(label && label.tagName==='LABEL'){
     const locale=(input.name||'').includes('_ar_')?'AR':'EN';
     const key=(input.name||'').replace(/^scalar_(ar|en)_/,'');
     label.textContent=(friendly[key]||key)+' ('+locale+')';
   }
   const box=document.createElement('div');box.className='about-page-field';
   input.parentNode.insertBefore(box,label||input);
   if(label)box.appendChild(label);box.appendChild(input);
   return box;
 };
 const enhanceImage=input=>{
   if(!input || input.closest('[data-img-field]'))return;
   const box=input.closest('.about-page-field')||fieldWrap(input); if(!box)return;
   input.classList.add('img-picker-target','hp-img-input');
   input.setAttribute('data-img-label',(input.name||'').includes('_ar_')?'About Us visual image (AR)':'About Us visual image (EN)');
   const shell=document.createElement('div');shell.className='hp-img-field';shell.setAttribute('data-img-field','');
   const preview=document.createElement('div');preview.className='hp-img-preview';preview.setAttribute('data-img-preview','');
   const value=(input.value||'').trim();preview.innerHTML=value?'<img src="'+escapeHtml(value)+'" alt="">':'<span class="hp-img-ph">No image</span>';
   const body=document.createElement('div');
   input.parentNode.insertBefore(shell,input);shell.appendChild(preview);shell.appendChild(body);body.appendChild(input);
   const actions=document.createElement('div');actions.className='hp-img-actions';actions.innerHTML='<button type="button" class="btn-sm primary hp-img-select">Choose Existing</button> <button type="button" class="btn-sm hp-img-upload">Upload Image</button>';
   body.appendChild(actions);
 };
 const addHeading=(input,title,sub)=>{
   if(!input)return;
   const card=input.closest('.card'); if(!card)return;
   const locale=(input.name||'').includes('_ar_')?'AR':'EN';
   const h=document.createElement('div');h.className='about-cms-heading';h.innerHTML=title+' · '+locale+'<small>'+sub+'</small>';
   const label=input.previousElementSibling;
   card.insertBefore(h,label&&label.tagName==='LABEL'?label:input);
 };
 const init=()=>{
   ['ar','en'].forEach(locale=>{
     const prefix='scalar_'+locale+'_';
     const first=document.querySelector('[name="'+prefix+'page_visual_image"]');
     addHeading(first,'About Us Page — Visual','الصورة المحددة في صفحة About Us. يمكن اختيارها من المكتبة أو رفع صورة جديدة.');
     Object.keys(friendly).forEach(key=>{const input=document.querySelector('[name="'+prefix+key+'"]');if(input&&!input.closest('.about-page-field'))fieldWrap(input);});
     enhanceImage(document.querySelector('[name="'+prefix+'page_visual_image"]'));
   });
   document.querySelectorAll('[data-repeater]').forEach(zone=>{
     if((zone.textContent||'').includes('page_values') || (zone.dataset.base||'').includes('page_values'))zone.classList.add('about-page-field');
   });
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
</script>
HTML;
            $script .= $aboutScript;
        }

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
