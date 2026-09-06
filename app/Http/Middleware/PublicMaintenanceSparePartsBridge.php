<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicMaintenanceSparePartsBridge
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</body>')) {
            return $response;
        }

        $target = route('public.current-maintenance.spare-parts', ['lang' => 'ar']);
        $targetJson = json_encode($target, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $script = <<<HTML
<script id="unifco-maintenance-spare-parts-bridge">
(function(){
  function ready(fn){ if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn); else fn(); }
  ready(function(){
    const serviceType = document.getElementById('service-type');
    const serviceSubtype = document.getElementById('service-subtype');
    if (!serviceType || !serviceSubtype) return;

    let navigating = false;
    function openSparePartsForm(){
      if (navigating) return;
      if (serviceType.value === 'quotation' && serviceSubtype.value === 'parts') {
        navigating = true;
        window.location.assign({$targetJson});
      }
    }

    serviceType.addEventListener('change', function(){
      window.setTimeout(openSparePartsForm, 0);
    });
    serviceSubtype.addEventListener('change', openSparePartsForm);
  });
})();
</script>
HTML;

        $response->setContent(str_replace('</body>', $script.'</body>', $html));

        return $response;
    }
}
