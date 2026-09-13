<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemAdminMobilePolish
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || ! str_contains($html, 'System Administration')) {
            return $response;
        }

        $polish = <<<'HTML'
<style id="system-admin-mobile-polish-style">
.side .nav-group.system-admin-polished .nav-children .nav-link.nav-secondary::before{content:none!important;display:none!important}
.side .nav-group.system-admin-polished .nav-children .nav-link{padding-left:8px!important;margin-left:0!important;gap:0!important}
.side .nav-group.system-admin-polished .system-matrix-label{position:relative;padding-right:24px;cursor:default}
.side .nav-group.system-admin-polished .system-matrix-toggle{display:none;position:absolute;right:2px;top:50%;transform:translateY(-50%);width:22px;height:22px;border:0;border-radius:6px;background:transparent;color:#8fa2bd;font-size:14px;line-height:22px;text-align:center;cursor:pointer}
@media(max-width:850px){
  .side .nav-group.system-admin-polished .system-matrix-label{cursor:pointer;user-select:none;padding:9px 30px 7px 7px;margin-top:8px}
  .side .nav-group.system-admin-polished .system-matrix-toggle{display:block}
  .side .nav-group.system-admin-polished .system-matrix-section.is-collapsed>.nav-link{display:none!important}
  .side .nav-group.system-admin-polished .system-matrix-section{border-radius:8px}
  .side .nav-group.system-admin-polished .nav-children .nav-link{min-height:38px;padding:8px 8px!important;margin:1px 0!important;align-items:center}
  .side .nav-group.system-admin-polished .nav-children{padding-bottom:10px}
}
html[dir="rtl"] .side .nav-group.system-admin-polished .system-matrix-label{padding-left:24px;padding-right:7px}
html[dir="rtl"] .side .nav-group.system-admin-polished .system-matrix-toggle{right:auto;left:2px}
@media(max-width:850px){html[dir="rtl"] .side .nav-group.system-admin-polished .system-matrix-label{padding-left:30px;padding-right:7px}}
</style>
<script id="system-admin-mobile-polish-script">
(function(){
  function applySystemAdminPolish(){
    var groups=[].slice.call(document.querySelectorAll('.side .nav-group'));
    var group=groups.find(function(g){var s=g.querySelector('summary');return s && s.textContent.trim().indexOf('System Administration')!==-1});
    if(!group)return false;
    var box=group.querySelector('.nav-children');
    if(!box || box.dataset.mobilePolished==='1')return true;

    group.classList.add('system-admin-polished');
    [].slice.call(box.querySelectorAll(':scope > .nav-link')).forEach(function(a){a.classList.add('system-admin-link')});

    var labels=[].slice.call(box.querySelectorAll(':scope > .system-matrix-label'));
    labels.forEach(function(label){
      var section=document.createElement('div');
      section.className='system-matrix-section';
      box.insertBefore(section,label);
      section.appendChild(label);

      var toggle=document.createElement('button');
      toggle.type='button';
      toggle.className='system-matrix-toggle';
      toggle.setAttribute('aria-label','Toggle '+label.textContent.trim());
      label.appendChild(toggle);

      var next=section.nextSibling;
      while(next && !(next.nodeType===1 && next.classList.contains('system-matrix-label'))){
        var move=next;
        next=next.nextSibling;
        if(move.nodeType===1 && move.classList.contains('nav-link'))section.appendChild(move);
      }

      var hasActive=!!section.querySelector('.nav-link.active');
      var isMobile=window.matchMedia('(max-width:850px)').matches;
      if(isMobile && !hasActive)section.classList.add('is-collapsed');
      toggle.textContent=section.classList.contains('is-collapsed')?'⌄':'⌃';

      function toggleSection(e){
        if(!window.matchMedia('(max-width:850px)').matches)return;
        e.preventDefault();
        section.classList.toggle('is-collapsed');
        toggle.textContent=section.classList.contains('is-collapsed')?'⌄':'⌃';
      }
      label.addEventListener('click',toggleSection);
    });

    box.dataset.mobilePolished='1';
    return true;
  }

  if(!applySystemAdminPolish()){
    var attempts=0;
    var timer=setInterval(function(){attempts++;if(applySystemAdminPolish()||attempts>20)clearInterval(timer)},50);
  }
})();
</script>
HTML;

        $html = str_replace('</body>', $polish."\n</body>", $html);
        $response->setContent($html);

        return $response;
    }
}
