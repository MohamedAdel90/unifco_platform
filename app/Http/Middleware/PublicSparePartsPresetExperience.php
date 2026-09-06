<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicSparePartsPresetExperience
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</body>')) {
            return $response;
        }

        $style = <<<'HTML'
<style id="unifco-spare-parts-preset-experience">
body.spare-preset .hero h1{font-size:clamp(28px,3.7vw,44px)!important}
body.spare-preset #requestForm>.section:first-of-type{display:none!important}
body.spare-preset #requestForm{padding-top:24px!important}
body.spare-preset #sparePartsPanel{margin-top:0!important}
body.spare-preset .submitbar{border-top:1px solid #edf1f5!important}
</style>
HTML;

        $script = <<<'HTML'
<script id="unifco-spare-parts-preset-experience-script">
(function(){
  function ready(fn){ if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded',fn); else fn(); }
  ready(function(){
    const form = document.getElementById('requestForm');
    if (!form) return;

    document.body.classList.add('spare-preset');
    form.classList.add('spare-mode');

    const quote = form.querySelector('input[name="request_intent"][value="QUOTATION"]');
    const spare = form.querySelector('input[name="request_subtype"][value="SPARE_PARTS_QUOTE"]');
    if (quote) quote.checked = true;
    if (spare) spare.checked = true;

    const heroTitle = document.querySelector('.hero h1');
    const heroText = document.querySelector('.hero p');
    if (document.documentElement.lang === 'ar') {
      if (heroTitle) heroTitle.textContent = 'طلب قطع الغيار';
      if (heroText) heroText.textContent = 'نموذج موحد وسريع لطلب قطع الغيار وربطها بالعميل والأصل والمستودع وإصدار تذكرة مرجعية فور الإرسال.';
    } else {
      if (heroTitle) heroTitle.textContent = 'Spare Parts Request';
      if (heroText) heroText.textContent = 'A single-page flow linked to customer, asset and inventory with an instant reference ticket.';
    }

    window.setTimeout(function(){
      const panel = document.getElementById('sparePartsPanel');
      if (panel) panel.scrollIntoView({block:'start'});
    }, 80);
  });
})();
</script>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
