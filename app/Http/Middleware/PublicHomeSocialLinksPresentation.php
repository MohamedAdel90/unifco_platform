<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHomeSocialLinksPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.home') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</body>')) {
            return $response;
        }

        $linkedinUrl = 'https://www.linkedin.com/company/unifco-fm/about/?viewAsMember=true';

        $presentation = <<<HTML
<style id="unifco-home-social-links">
.footer .socials a{width:44px!important;height:44px!important;border-radius:50%!important;border:1px solid rgba(255,255,255,.32)!important;display:grid!important;place-items:center!important;color:#fff!important;background:rgba(255,255,255,.025)!important;font-size:13px!important;text-decoration:none!important;transition:.18s!important}
.footer .socials a:hover{background:rgba(255,255,255,.10)!important;border-color:rgba(255,255,255,.65)!important;transform:translateY(-2px)!important}
</style>
<script id="unifco-home-linkedin-link">
(()=>{
  const wireLinkedIn=()=>{
    const socials=document.querySelector('.footer .socials');
    if(!socials)return;
    const items=[...socials.children];
    const linkedin=items.find(el=>el.textContent.trim().toLowerCase()==='in');
    if(!linkedin||linkedin.tagName==='A')return;
    const link=document.createElement('a');
    link.href='{$linkedinUrl}';
    link.target='_blank';
    link.rel='noopener noreferrer';
    link.setAttribute('aria-label','UNIFCO on LinkedIn');
    link.title='LinkedIn';
    link.innerHTML=linkedin.innerHTML;
    linkedin.replaceWith(link);
  };
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',wireLinkedIn,{once:true});else wireLinkedIn();
})();
</script>
HTML;

        $response->setContent(str_replace('</body>', $presentation."\n</body>", $html));

        return $response;
    }
}
