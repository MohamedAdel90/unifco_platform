<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicSharedIntakeSubmitBridge
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.current-maintenance') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        if (! str_contains($html, 'id="uf-shared-intake"') || ! str_contains($html, '</body>')) {
            return $response;
        }

        $script = <<<'HTML'
<script id="unifco-shared-intake-submit-bridge-v1">
(function(){
  const form=document.getElementById('maintenance-form');
  const root=document.getElementById('uf-shared-intake');
  if(!form||!root)return;

  const q=(s)=>document.querySelector(s);
  const value=(s)=>((q(s)?.value)||'').trim();
  const set=(s,v)=>{const el=q(s);if(!el)return;el.removeAttribute('readonly');el.value=v||'';el.dispatchEvent(new Event('input',{bubbles:true}));el.dispatchEvent(new Event('change',{bubbles:true}));};

  // The former asset panel is now only a compatibility data source. It must not block the unified intake.
  document.querySelectorAll('#asset-section [required]').forEach(el=>el.removeAttribute('required'));

  form.addEventListener('click',function(e){
    const btn=e.target.closest('button[type="submit"],input[type="submit"]');
    if(!btn||!form.contains(btn))return;

    e.preventDefault();
    e.stopImmediatePropagation();

    const customerMode=root.querySelector('[data-uf-customer].active')?.dataset.ufCustomer||'current';
    const assetMode=root.querySelector('[data-uf-asset].active')?.dataset.ufAsset||'registered';

    if(customerMode==='new'){
      set('#company_name',value('#uf-new-company'));
      set('#responsible_person',value('#uf-new-contact'));
      set('#mobile',value('#uf-new-mobile'));
      set('#email',value('#uf-new-email'));
    }

    if(assetMode==='new'){
      const assetName=value('#uf-new-asset-name');
      const assetType=value('#uf-new-asset-type')||assetName||'GENERAL';
      const brand=value('#uf-new-asset-brand');
      const model=value('#uf-new-asset-model');
      set('#asset_id','');
      set('#asset_type',assetType);
      set('#equipment_brand',brand);
      set('#equipment_model',model);
      set('#manual_name',assetName);
      set('#manual_type',assetType);
      set('#manual_brand',brand);
      set('#manual_model',model);
    }else{
      // asset_id is nullable server-side; preserve it when lookup resolved one,
      // otherwise keep a safe descriptive type instead of invoking the obsolete popup validator.
      if(!value('#asset_type')) set('#asset_type',value('#uf-asset-search')||'GENERAL');
    }

    // Validate the fields that still belong to the real request form, but ignore hidden compatibility controls.
    const hiddenCompat=[...form.querySelectorAll('#asset-section [required], #routine-form>section.panel:first-of-type [required]')];
    hiddenCompat.forEach(el=>el.removeAttribute('required'));

    if(!form.checkValidity()){
      form.reportValidity();
      return;
    }

    // Native submit intentionally bypasses the legacy submit listener that still validates the now-hidden asset UI.
    HTMLFormElement.prototype.submit.call(form);
  },true);
})();
</script>
HTML;

        $response->setContent(str_replace('</body>', $script.'</body>', $html));
        return $response;
    }
}
