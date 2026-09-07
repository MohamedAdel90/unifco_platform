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
<style id="unifco-about-cms-simple-style-v2">
.about-simple{display:grid;gap:14px;margin:10px 0}.about-simple *{box-sizing:border-box}
.about-simple-top{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 16px;border:1px solid #d8e4f3;border-radius:13px;background:#f7faff}
.about-simple-top h2{margin:0;color:#071f4d;font-size:16px}.about-simple-top p{margin:4px 0 0;color:#6d7d92;font-size:10px;line-height:1.55}.about-simple-tag{padding:6px 10px;border-radius:999px;background:#071f4d;color:#fff;font-size:9px;font-weight:900;white-space:nowrap}
.about-nav{display:grid;grid-template-columns:repeat(4,1fr);gap:7px}.about-nav button{border:1px solid #d7e0eb;background:#fff;color:#334866;border-radius:9px;padding:9px 8px;font-size:10px;font-weight:900;cursor:pointer}.about-nav button.active{background:#071f4d;color:#fff;border-color:#071f4d}
.about-pane{display:none;background:#fff;border:1px solid #dfe6ef;border-radius:13px;padding:15px}.about-pane.active{display:block}.about-pane-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:13px}.about-pane-head h3{margin:0;color:#071f4d;font-size:14px}.about-pane-head p{margin:3px 0 0;color:#7b899b;font-size:9.5px;line-height:1.5}
.about-primary{display:grid;gap:11px}.about-field{border:1px solid #e3e9f1;border-radius:10px;padding:11px;background:#fcfdff}.about-field label{display:block!important;margin:0 0 5px!important;color:#334866!important;font-size:10px!important;font-weight:800!important;text-transform:none!important}.about-field input,.about-field textarea,.about-field select{width:100%}.about-field-title{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:7px}.about-field-title strong{font-size:10.5px;color:#17366c}.about-source-pill{font-size:8px;font-weight:900;color:#087443;background:#eaf8f1;border-radius:999px;padding:4px 7px}
.about-en{margin-top:8px;border-top:1px dashed #dbe3ed;padding-top:8px}.about-en summary{cursor:pointer;color:#62748d;font-size:9px;font-weight:800;list-style:none}.about-en summary::-webkit-details-marker{display:none}.about-en summary:after{content:' ▾';color:#9aa6b6}.about-en[open] summary{margin-bottom:8px;color:#17366c}
.about-row{display:grid;grid-template-columns:1fr 1fr;gap:11px}.about-note{padding:9px 11px;border-radius:9px;background:#f5f8fc;color:#6b7b91;font-size:9px;line-height:1.55;border:1px solid #e4ebf3}.about-note strong{color:#17366c}
.about-repeat{display:grid;grid-template-columns:1fr 1fr;gap:12px}.about-repeat>.card{margin:0!important;box-shadow:none!important;border-color:#e1e8f1!important}.about-repeat .col-head b{font-size:10.5px!important}.about-repeat .item-block{background:#fff!important}.about-repeat .item-subrow{gap:8px!important}
.about-subsection{border:1px solid #e2e8f0;border-radius:11px;margin-top:10px;overflow:hidden}.about-subsection>summary{cursor:pointer;padding:11px 12px;background:#f9fbfd;color:#17366c;font-size:10.5px;font-weight:900;list-style:none}.about-subsection>summary::-webkit-details-marker{display:none}.about-subsection>summary:after{content:'+';float:right;color:#8391a4;font-size:14px}.about-subsection[open]>summary:after{content:'−'}.about-subsection-body{padding:11px;border-top:1px solid #e9eef4}
.about-mode-wrap{max-width:720px}.about-hidden{display:none!important}
#homepage-section-form.about-simple-ready>.seg,#homepage-section-form.about-simple-ready>.card[data-repeater],#homepage-section-form.about-simple-ready>.about-page-field{display:none!important}
#homepage-section-form.about-simple-ready .about-simple .card[data-repeater],#homepage-section-form.about-simple-ready .about-simple .about-page-field{display:block!important}
@media(max-width:850px){.about-repeat,.about-row{grid-template-columns:1fr}.about-nav{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.about-simple-top{align-items:flex-start;flex-direction:column}.about-nav{grid-template-columns:1fr 1fr}.about-pane{padding:11px}.about-nav button{font-size:9px}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-about-cms-simple-script-v2">
(()=>{
 const q=(s,r=document)=>r.querySelector(s), qa=(s,r=document)=>Array.from(r.querySelectorAll(s));
 const byName=n=>{try{return q('[name="'+CSS.escape(n)+'"]')}catch(e){return document.getElementsByName(n)[0]||null}};
 const labels={
  kicker:'Kicker / من نحن',title:'Main heading',text:'Description',button:'CTA button',image:'Main image',full_section_image:'Full section image',
  page_visual_image:'Main visual image',page_strapline:'Top strapline',page_eyebrow:'Eyebrow',page_title:'Page title',page_subtitle:'Subtitle',
  page_intro_text:'Introduction',page_intro_secondary_text:'Second introduction',page_quote_text:'Quote',page_apart_title:'What Sets Us Apart — title',page_apart_text:'What Sets Us Apart — text',
  page_mission_title:'Mission — title',page_mission_text:'Mission — text',page_vision_title:'Vision — title',page_vision_text:'Vision — text',page_people_title:'People & Technology — title',page_people_text:'People & Technology — text',page_footer_note_text:'Footer note'
 };
 const pageKeys=['page_visual_image','page_strapline','page_eyebrow','page_title','page_subtitle','page_intro_text','page_intro_secondary_text','page_quote_text','page_apart_title','page_apart_text','page_mission_title','page_mission_text','page_vision_title','page_vision_text','page_people_title','page_people_text','page_footer_note_text'];
 function rootFor(input){
  if(!input)return null;
  let root=input.closest('.about-page-field')||input.closest('[data-img-field]')||input.closest('.cms-display-mode-box');
  if(root&&root.matches('[data-img-field]')){const prev=root.previousElementSibling;const w=document.createElement('div');w.className='about-field';root.parentNode.insertBefore(w,(prev&&prev.tagName==='LABEL')?prev:root);if(prev&&prev.tagName==='LABEL')w.appendChild(prev);w.appendChild(root);return w}
  if(root){root.classList.add('about-field');return root}
  const prev=input.previousElementSibling;const w=document.createElement('div');w.className='about-field';input.parentNode.insertBefore(w,(prev&&prev.tagName==='LABEL')?prev:input);if(prev&&prev.tagName==='LABEL')w.appendChild(prev);w.appendChild(input);return w;
 }
 function field(locale,key){const input=byName('scalar_'+locale+'_'+key);if(!input)return null;const root=rootFor(input);const lab=q('label',root);if(lab)lab.textContent=(labels[key]||key)+(locale==='ar'?'':' (EN)');return root}
 function pair(key){const ar=field('ar',key),en=field('en',key);if(!ar&&!en)return null;const box=document.createElement('div');box.className='about-field';const head=document.createElement('div');head.className='about-field-title';head.innerHTML='<strong>'+(labels[key]||key)+'</strong><span class="about-source-pill">AR PRIMARY</span>';box.appendChild(head);if(ar){ar.classList.remove('about-field');box.appendChild(ar)}if(en){en.classList.remove('about-field');const d=document.createElement('details');d.className='about-en';d.innerHTML='<summary>English translation / optional override</summary>';d.appendChild(en);box.appendChild(d)}return box}
 function repeater(locale,key){return qa('.card[data-repeater]').find(c=>{const t=(q('.col-head b',c)?.textContent||'').toLowerCase();return t.includes(key.toLowerCase())&&t.includes(locale==='ar'?'arabic':'english')})||null}
 function repeatPair(key){const wrap=document.createElement('div');wrap.className='about-repeat';const ar=repeater('ar',key),en=repeater('en',key);if(ar)wrap.appendChild(ar);if(en)wrap.appendChild(en);return (ar||en)?wrap:null}
 function pane(id,title,sub){const s=document.createElement('section');s.className='about-pane';s.dataset.pane=id;s.innerHTML='<div class="about-pane-head"><div><h3>'+title+'</h3><p>'+sub+'</p></div></div><div class="about-primary"></div>';return s}
 function addPair(host,key){const p=pair(key);if(p)host.appendChild(p)}
 function details(title,open=false){const d=document.createElement('details');d.className='about-subsection';if(open)d.open=true;d.innerHTML='<summary>'+title+'</summary><div class="about-subsection-body about-primary"></div>';return d}
 function init(){
  const form=q('#homepage-section-form');if(!form||form.dataset.aboutSimple==='1')return;form.dataset.aboutSimple='1';
  const app=document.createElement('div');app.className='about-simple';app.innerHTML='<div class="about-simple-top"><div><h2>About CMS</h2><p>Edit what visitors see. Arabic is the primary source; English is available only when you need to review or override it.</p></div><span class="about-simple-tag">4 SIMPLE STEPS</span></div><div class="about-nav"></div>';
  form.insertBefore(app,form.firstElementChild);const nav=q('.about-nav',app);
  const specs=[['settings','1 · Settings'],['home','2 · Homepage'],['items','3 · Features & Stats'],['page','4 · About Us Page']];
  specs.forEach((s,i)=>{const b=document.createElement('button');b.type='button';b.textContent=s[1];if(i===0)b.classList.add('active');b.onclick=()=>{qa('.about-nav button',app).forEach(x=>x.classList.remove('active'));b.classList.add('active');qa('.about-pane',app).forEach(x=>x.classList.toggle('active',x.dataset.pane===s[0]));};nav.appendChild(b)});
  const settings=pane('settings','Section Settings','One design setting for both languages. Full Section Image is only shown when you choose that mode.');
  const home=pane('home','Homepage About','Everything visible in the About block on the homepage: text, image and CTA.');
  const items=pane('items','Features & Statistics','Manage the four feature points and the KPI/statistics bar.');
  const page=pane('page','About Us Page','Content for the separate full About Us page. Advanced text is grouped so the page stays short.');
  [settings,home,items,page].forEach((p,i)=>{if(i===0)p.classList.add('active');app.appendChild(p)});
  // Shared display mode: show AR control only and mirror to EN.
  const sbody=q('.about-primary',settings);const modeAr=byName('scalar_ar_display_mode'),modeEn=byName('scalar_en_display_mode');const modeArBox=modeAr?.closest('.cms-display-mode-box'),modeEnBox=modeEn?.closest('.cms-display-mode-box');
  if(modeArBox){modeArBox.classList.add('about-mode-wrap');sbody.appendChild(modeArBox)}if(modeEnBox)modeEnBox.classList.add('about-hidden');const arSelect=modeArBox?.querySelector('select'),enSelect=modeEnBox?.querySelector('select');if(arSelect&&enSelect){enSelect.value=arSelect.value;arSelect.addEventListener('change',()=>{enSelect.value=arSelect.value;enSelect.dispatchEvent(new Event('change',{bubbles:true}))})}
  const full=pair('full_section_image');if(full)sbody.appendChild(full);sbody.insertAdjacentHTML('beforeend','<div class="about-note"><strong>Tip:</strong> Use Structured Section for normal editable content. Use Full Section Image only when the whole About block is already designed inside one image.</div>');
  // Homepage in one compact flow.
  const hbody=q('.about-primary',home);['kicker','title','text','image','button'].forEach(k=>addPair(hbody,k));
  const prof=repeatPair('profile_images');if(prof){const d=details('Optional profile images');q('.about-subsection-body',d).appendChild(prof);hbody.appendChild(d)}
  // Features + stats, each in one collapsible group.
  const ibody=q('.about-primary',items);[['Homepage features','points'],['Statistics bar','stats']].forEach(([title,key],i)=>{const rp=repeatPair(key);if(rp){const d=details(title,i===0);q('.about-subsection-body',d).appendChild(rp);ibody.appendChild(d)}});ibody.insertAdjacentHTML('beforeend','<div class="about-note">Arabic entries are the source. Keep the same row order in English so translation and icons remain aligned.</div>');
  // About page: essentials open, long copy collapsed.
  const pbody=q('.about-primary',page);['page_visual_image','page_eyebrow','page_title','page_subtitle','page_intro_text'].forEach(k=>addPair(pbody,k));
  const more=details('More page copy');q('.about-subsection-body',more).classList.add('about-primary');['page_strapline','page_intro_secondary_text','page_quote_text','page_footer_note_text'].forEach(k=>addPair(q('.about-subsection-body',more),k));pbody.appendChild(more);
  const story=details('Mission, Vision & supporting sections');['page_apart_title','page_apart_text','page_mission_title','page_mission_text','page_vision_title','page_vision_text','page_people_title','page_people_text'].forEach(k=>addPair(q('.about-subsection-body',story),k));pbody.appendChild(story);
  const vals=repeatPair('page_values');if(vals){const d=details('Page values / principles');q('.about-subsection-body',d).appendChild(vals);pbody.appendChild(d)}
  form.classList.add('about-simple-ready');
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else setTimeout(init,0);
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
