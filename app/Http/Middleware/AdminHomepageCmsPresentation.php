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

        $guide = '<div class="cms-context-guide">'
            .'<div class="cms-context-copy"><span class="cms-context-badge">CMS</span><div><strong>Editing: '.e($sectionKey).'</strong><p>مكتبة الصور أصبحت مرتبطة بهذا القسم. استخدم المعاينة قبل الحفظ لأنها الأقرب لشكل الصفحة العامة الفعلي.</p></div></div>'
            .'<div class="cms-context-actions"><button type="button" data-cms-preview="ar">Preview AR</button><button type="button" data-cms-preview="en">Preview EN</button></div>'
            .'</div>';

        $html = str_replace('<form id="homepage-section-form"', $guide.'<form id="homepage-section-form"', $html, $count);
        if ($count < 1) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-admin-homepage-cms-presentation-v1">
.cms-context-guide{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:14px 0 4px;padding:13px 14px;background:linear-gradient(135deg,#f7faff,#eef4fd);border:1px solid #d7e4f5;border-radius:12px;box-shadow:0 5px 16px rgba(23,54,108,.05)}
.cms-context-copy{display:flex;align-items:flex-start;gap:10px;min-width:0}.cms-context-copy strong{display:block;color:#17366c;font-size:13px}.cms-context-copy p{margin:3px 0 0;color:#66758a;font-size:10.5px;line-height:1.65}.cms-context-badge{display:inline-grid;place-items:center;min-width:38px;height:28px;padding:0 8px;border-radius:7px;background:#17366c;color:#fff;font-size:9px;font-weight:900;letter-spacing:.05em}
.cms-context-actions{display:flex;gap:7px;flex:0 0 auto}.cms-context-actions button{border:1px solid #adc3e2;background:#fff;color:#17366c;border-radius:8px;padding:8px 11px;font-size:10.5px;font-weight:900;cursor:pointer}.cms-context-actions button:hover{background:#17366c;color:#fff;border-color:#17366c}
.img-picker-head:before{content:"Section image library";display:block;color:#17366c;font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px}.img-picker-head strong{font-size:14px!important}.img-picker{border-color:#d6e3f4!important;background:#f9fbfe!important}.img-picker-grid{max-height:360px;overflow:auto;padding-inline-end:2px}.img-picker-cell{transition:.16s}.img-picker-cell:hover{transform:translateY(-1px)}
@media(max-width:760px){.cms-context-guide{align-items:stretch;flex-direction:column}.cms-context-actions{width:100%}.cms-context-actions button{flex:1}.cms-context-copy p{font-size:10px}}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-admin-homepage-cms-presentation-script-v1">
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

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
