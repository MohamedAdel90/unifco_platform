<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAboutCmsWorkspacePresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('admin.homepage.sections.edit') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $section = $request->route('section');
        if (! is_object($section) || ($section->section_key ?? null) !== 'about') {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</body>')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-about-cms-workspace-style-v1">
.about-workspace{display:grid;gap:16px;margin-top:8px}.about-workspace *{box-sizing:border-box}
.about-workspace-intro{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px 18px;border:1px solid #d8e4f3;border-radius:14px;background:linear-gradient(135deg,#f7faff,#edf4fd)}
.about-workspace-intro h2{margin:0;color:#071f4d;font-size:17px}.about-workspace-intro p{margin:4px 0 0;color:#62748d;font-size:10.5px;line-height:1.65}.about-workspace-badge{flex:0 0 auto;padding:7px 11px;border-radius:999px;background:#071f4d;color:#fff;font-size:9px;font-weight:900;letter-spacing:.04em}
.about-work-tabs{display:flex;gap:7px;flex-wrap:wrap;position:sticky;top:8px;z-index:6;padding:8px;background:rgba(246,249,253,.94);backdrop-filter:blur(8px);border:1px solid #e0e7f0;border-radius:12px}.about-work-tab{border:1px solid #ced9e8;background:#fff;color:#2d4364;border-radius:9px;padding:8px 12px;font-size:10.5px;font-weight:900;cursor:pointer}.about-work-tab.active{background:#071f4d;color:#fff;border-color:#071f4d}
.about-work-card{background:#fff;border:1px solid #dfe6ef;border-radius:14px;overflow:hidden;box-shadow:0 2px 7px rgba(14,35,69,.035)}.about-work-head{width:100%;display:flex;align-items:center;gap:12px;text-align:start;border:0;background:#fff;padding:15px 17px;cursor:pointer;color:#071f4d}.about-work-head:hover{background:#fbfdff}.about-work-icon{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:#edf3fb;color:#17366c;font-size:15px;font-weight:900;flex:0 0 auto}.about-work-head-copy{min-width:0;flex:1}.about-work-head strong{display:block;font-size:13px}.about-work-head small{display:block;margin-top:3px;color:#78879a;font-size:9.5px;line-height:1.5}.about-work-chevron{font-size:16px;color:#7c8da4;transition:.18s}.about-work-card.collapsed .about-work-chevron{transform:rotate(-90deg)}
.about-work-body{border-top:1px solid #edf1f6;padding:16px}.about-work-card.collapsed .about-work-body{display:none}
.about-lang-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.about-lang-panel{border:1px solid #e2e8f0;border-radius:11px;padding:13px;background:#fcfdff}.about-lang-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:9px;padding-bottom:8px;border-bottom:1px solid #e8edf4}.about-lang-head strong{font-size:11px;color:#17366c}.about-lang-head span{font-size:9px;color:#8a97aa;font-weight:700}.about-lang-panel .about-field-unit{margin:0 0 12px}.about-lang-panel .about-field-unit:last-child{margin-bottom:0}.about-field-unit>label,.about-field-unit label{font-size:10px!important;color:#344764!important;margin:0 0 5px!important;text-transform:none!important}.about-field-note{margin-top:5px;color:#8190a3;font-size:9px;line-height:1.45}
.about-visual-guide{display:grid;grid-template-columns:180px 1fr;gap:14px;align-items:center;padding:12px;margin-bottom:13px;border-radius:11px;background:#f7f9fc;border:1px dashed #cad6e5}.about-visual-mini{aspect-ratio:4/3;border-radius:10px;background:linear-gradient(145deg,#0a2855,#174a7a);display:grid;place-items:center;color:#fff;font-size:10px;font-weight:900;text-align:center;padding:14px}.about-visual-guide strong{display:block;color:#17366c;font-size:11px}.about-visual-guide p{margin:4px 0 0;color:#6c7d94;font-size:9.5px;line-height:1.65}
.about-repeater-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.about-repeater-grid>.card{margin:0!important;border-color:#e1e8f1!important;box-shadow:none!important}.about-repeater-grid .item-block{background:#fff!important}.about-repeater-grid .col-head{padding-bottom:8px;border-bottom:1px solid #edf1f6}.about-repeater-grid .col-head b{font-size:11px!important}.about-repeater-grid .item-title{color:#17366c!important}
.about-settings-row{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(220px,.6fr);gap:14px}.about-setting-box{border:1px solid #e1e8f1;border-radius:11px;padding:13px;background:#fcfdff}.about-setting-box h4{margin:0 0 5px;color:#17366c;font-size:11px}.about-setting-box p{margin:0;color:#75859b;font-size:9.5px;line-height:1.6}.about-setting-box .cms-display-mode-box{margin-top:10px!important;margin-bottom:0!important}.about-hidden-source{display:none!important}
.about-page-divider{display:flex;align-items:center;gap:10px;margin:3px 0}.about-page-divider:before,.about-page-divider:after{content:"";height:1px;background:#dfe6ef;flex:1}.about-page-divider span{font-size:9px;font-weight:900;color:#8a97aa;text-transform:uppercase;letter-spacing:.08em}
#homepage-section-form.about-workspace-ready>.seg,#homepage-section-form.about-workspace-ready>.card[data-repeater],#homepage-section-form.about-workspace-ready>.about-page-field{display:none!important}
#homepage-section-form.about-workspace-ready .about-workspace .seg,#homepage-section-form.about-workspace-ready .about-workspace .card[data-repeater],#homepage-section-form.about-workspace-ready .about-workspace .about-page-field{display:block!important}
@media(max-width:900px){.about-lang-grid,.about-repeater-grid,.about-settings-row{grid-template-columns:1fr}.about-visual-guide{grid-template-columns:120px 1fr}}
@media(max-width:620px){.about-workspace-intro{align-items:flex-start;flex-direction:column}.about-work-tabs{position:static}.about-work-tab{flex:1 1 42%}.about-work-body{padding:12px}.about-lang-panel{padding:10px}.about-visual-guide{grid-template-columns:1fr}.about-visual-mini{max-width:180px}.about-work-head{padding:13px}.about-work-icon{width:30px;height:30px}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-about-cms-workspace-script-v1">
(()=>{
 const q=(s,r=document)=>r.querySelector(s), qa=(s,r=document)=>Array.from(r.querySelectorAll(s));
 const byName=n=>{try{return q('[name="'+CSS.escape(n)+'"]')}catch(e){return document.getElementsByName(n)[0]||null}};
 const labelMap={
  image:'Homepage main image',kicker:'Section kicker / eyebrow',title:'Main heading',text:'Main description',button:'CTA button text',
  page_visual_image:'About Us main visual image',page_strapline:'Top strapline',page_eyebrow:'Page eyebrow',page_title:'Page title',page_subtitle:'Page subtitle',
  page_intro_text:'Introduction text',page_intro_secondary_text:'Secondary introduction',page_quote_text:'Quote / statement',
  page_apart_title:'What Sets Us Apart — title',page_apart_text:'What Sets Us Apart — text',page_mission_title:'Mission — title',page_mission_text:'Mission — text',
  page_vision_title:'Vision — title',page_vision_text:'Vision — text',page_people_title:'People & Technology — title',page_people_text:'People & Technology — text',page_footer_note_text:'Footer note'
 };
 const pageKeys=['page_strapline','page_eyebrow','page_title','page_subtitle','page_intro_text','page_intro_secondary_text','page_quote_text','page_apart_title','page_apart_text','page_mission_title','page_mission_text','page_vision_title','page_vision_text','page_people_title','page_people_text','page_footer_note_text'];
 function fieldUnit(name,label){
  const input=byName(name); if(!input)return null;
  let root=input.closest('.about-page-field');
  if(!root && input.closest('.cms-display-mode-box'))root=input.closest('.cms-display-mode-box');
  if(!root && input.closest('[data-img-field]')){
    const field=input.closest('[data-img-field]');
    const prev=field.previousElementSibling;
    root=document.createElement('div');root.className='about-field-unit';
    field.parentNode.insertBefore(root,(prev&&prev.tagName==='LABEL')?prev:field);
    if(prev&&prev.tagName==='LABEL')root.appendChild(prev);root.appendChild(field);
  }
  if(!root){
    const prev=input.previousElementSibling;
    root=document.createElement('div');root.className='about-field-unit';
    input.parentNode.insertBefore(root,(prev&&prev.tagName==='LABEL')?prev:input);
    if(prev&&prev.tagName==='LABEL')root.appendChild(prev);root.appendChild(input);
  }
  root.classList.add('about-field-unit');
  const lab=q('label',root);
  if(lab && label)lab.textContent=label;
  return root;
 }
 function panel(locale,title){const el=document.createElement('div');el.className='about-lang-panel';el.innerHTML='<div class="about-lang-head"><strong>'+title+'</strong><span>'+(locale==='ar'?'RTL · العربية':'LTR · English')+'</span></div>';return el}
 function card(id,icon,title,sub,collapsed=false){const s=document.createElement('section');s.className='about-work-card'+(collapsed?' collapsed':'');s.dataset.aboutSection=id;s.innerHTML='<button type="button" class="about-work-head"><span class="about-work-icon">'+icon+'</span><span class="about-work-head-copy"><strong>'+title+'</strong><small>'+sub+'</small></span><span class="about-work-chevron">⌄</span></button><div class="about-work-body"></div>';q('.about-work-head',s).addEventListener('click',()=>s.classList.toggle('collapsed'));return s}
 function repeater(locale,key){return qa('.card[data-repeater]').find(c=>{const t=(q('.col-head b',c)?.textContent||'').toLowerCase();return t.includes(key.toLowerCase())&&t.includes(locale==='ar'?'arabic':'english')})||null}
 function appendFields(host,locale,keys){keys.forEach(k=>{const u=fieldUnit('scalar_'+locale+'_'+k,labelMap[k]||k);if(u)host.appendChild(u)})}
 function makeBilingual(body,keys){const grid=document.createElement('div');grid.className='about-lang-grid';const ar=panel('ar','Arabic content');const en=panel('en','English content');appendFields(ar,'ar',keys);appendFields(en,'en',keys);grid.append(ar,en);body.appendChild(grid)}
 function init(){
  const form=q('#homepage-section-form'); if(!form||form.dataset.aboutWorkspace==='1')return;form.dataset.aboutWorkspace='1';
  const workspace=document.createElement('div');workspace.className='about-workspace';workspace.innerHTML='<div class="about-workspace-intro"><div><h2>About CMS Workspace</h2><p>Homepage About and the full About Us page are separated below. Every group maps directly to a visible part of the website.</p></div><span class="about-workspace-badge">CONTENT MAP</span></div><div class="about-work-tabs"></div>';
  const first=form.firstElementChild;form.insertBefore(workspace,first);
  const tabs=q('.about-work-tabs',workspace);
  const specs=[['settings','⚙','Section Settings','Display mode, section behavior and shared design controls.'],['content','Aa','Main Content','The kicker, heading, description and CTA shown beside the homepage image.'],['visual','▣','Main Image','The large visual used on the homepage About block.'],['features','✦','Features','The four service/value points displayed in the homepage About grid.'],['stats','#','Statistics','The dark-blue KPI/statistics bar below the homepage About content.'],['page','↗','About Us Page','The separate full About Us page: visual, copy, values, mission, vision and supporting sections.']];
  const cards={};specs.forEach((s,i)=>{const c=card(s[0],s[1],s[2],s[3],s[0]==='page');cards[s[0]]=c;workspace.appendChild(c);const b=document.createElement('button');b.type='button';b.className='about-work-tab'+(i===0?' active':'');b.textContent=s[2];b.addEventListener('click',()=>{qa('.about-work-tab',tabs).forEach(x=>x.classList.remove('active'));b.classList.add('active');c.classList.remove('collapsed');c.scrollIntoView({behavior:'smooth',block:'start'})});tabs.appendChild(b)});
  // Settings: keep Arabic display mode as the single visible control and mirror it to EN.
  const setBody=q('.about-work-body',cards.settings);const setGrid=document.createElement('div');setGrid.className='about-settings-row';
  const design=document.createElement('div');design.className='about-setting-box';design.innerHTML='<h4>Display Mode</h4><p>One shared design setting for both languages. Structured Section uses CMS fields; Full Section Image replaces the structured homepage About block.</p>';
  const arMode=byName('scalar_ar_display_mode');const enMode=byName('scalar_en_display_mode');const arModeBox=arMode?.closest('.cms-display-mode-box');const enModeBox=enMode?.closest('.cms-display-mode-box');if(arModeBox)design.appendChild(arModeBox);if(enModeBox)enModeBox.classList.add('about-hidden-source');
  const governance=document.createElement('div');governance.className='about-setting-box';governance.innerHTML='<h4>Section controls</h4><p>Enable / Disable is managed from the Homepage Sections list. Sort order remains in the Save bar at the bottom of this page.</p>';
  setGrid.append(design,governance);setBody.appendChild(setGrid);
  const arSelect=arModeBox?.querySelector('select');const enSelect=enModeBox?.querySelector('select');if(arSelect&&enSelect){enSelect.value=arSelect.value;arSelect.addEventListener('change',()=>{enSelect.value=arSelect.value;enSelect.dispatchEvent(new Event('change',{bubbles:true}))})}
  // Main content.
  makeBilingual(q('.about-work-body',cards.content),['kicker','title','text','button']);
  // Main image.
  const visualBody=q('.about-work-body',cards.visual);visualBody.insertAdjacentHTML('afterbegin','<div class="about-visual-guide"><div class="about-visual-mini">HOMEPAGE<br>ABOUT IMAGE</div><div><strong>Where this appears</strong><p>This is the large image on the left side of the Homepage About section. Use the image library, upload, resize and aspect-ratio controls here.</p></div></div>');makeBilingual(visualBody,['image']);
  const profAr=repeater('ar','profile_images'),profEn=repeater('en','profile_images');if(profAr||profEn){const d=document.createElement('div');d.className='about-page-divider';d.innerHTML='<span>Optional profile images</span>';visualBody.appendChild(d);const g=document.createElement('div');g.className='about-repeater-grid';if(profAr)g.appendChild(profAr);if(profEn)g.appendChild(profEn);visualBody.appendChild(g)}
  // Features and statistics.
  [['features','points'],['stats','stats']].forEach(([section,key])=>{const body=q('.about-work-body',cards[section]);const g=document.createElement('div');g.className='about-repeater-grid';const ar=repeater('ar',key),en=repeater('en',key);if(ar)g.appendChild(ar);if(en)g.appendChild(en);body.appendChild(g)});
  // About Us page.
  const pageBody=q('.about-work-body',cards.page);pageBody.insertAdjacentHTML('afterbegin','<div class="about-visual-guide"><div class="about-visual-mini">ABOUT US<br>PAGE VISUAL</div><div><strong>Separate page content</strong><p>These fields do not replace the Homepage About content. They control the dedicated About Us page reached from the CTA / navigation.</p></div></div>');
  makeBilingual(pageBody,['page_visual_image']);const div=document.createElement('div');div.className='about-page-divider';div.innerHTML='<span>Page copy</span>';pageBody.appendChild(div);makeBilingual(pageBody,pageKeys);
  const values=document.createElement('div');values.className='about-page-divider';values.innerHTML='<span>Values / principles</span>';pageBody.appendChild(values);const vg=document.createElement('div');vg.className='about-repeater-grid';const va=repeater('ar','page_values'),ve=repeater('en','page_values');if(va)vg.appendChild(va);if(ve)vg.appendChild(ve);pageBody.appendChild(vg);
  form.classList.add('about-workspace-ready');
  // Friendly label cleanup and context notes.
  qa('.about-lang-panel input,.about-lang-panel textarea').forEach(el=>{const unit=el.closest('.about-field-unit');if(!unit)return;const key=(el.name||'').replace(/^scalar_(ar|en)_/,'');const lab=q('label',unit);if(lab&&labelMap[key])lab.textContent=labelMap[key]});
 }
 const run=()=>setTimeout(init,80);if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run);else run();
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
