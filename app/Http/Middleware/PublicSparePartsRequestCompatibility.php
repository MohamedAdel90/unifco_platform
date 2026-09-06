<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicSparePartsRequestCompatibility
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) return $response;
        $html = (string) $response->getContent();
        if ($html === '' || ! str_contains($html, '</body>')) return $response;

        $script = <<<'HTML'
<script id="unifco-spare-parts-compatibility">
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('requestForm');
    const panel = document.getElementById('sparePartsPanel');
    if (!form || !panel) return;

    if (!form.querySelector('input[name="asset_id"]')) {
        const assetId = document.createElement('input');
        assetId.type = 'hidden';
        assetId.name = 'asset_id';
        form.appendChild(assetId);
    }

    const originalUploadInputs = Array.from(form.querySelectorAll(':scope > .section:nth-of-type(4) input[type="file"]'));
    const customRequired = Array.from(panel.querySelectorAll('[required]'));
    const spareSelected = () => form.querySelector('input[name="request_subtype"]:checked')?.value === 'SPARE_PARTS_QUOTE';

    function applyMode() {
        const spare = spareSelected();
        originalUploadInputs.forEach(input => input.disabled = spare);
        customRequired.forEach(input => input.required = spare);
        panel.querySelectorAll('input[type="file"]').forEach(input => input.disabled = !spare);
    }

    form.querySelectorAll('input[name="request_intent"], input[name="request_subtype"]').forEach(input => {
        input.addEventListener('change', () => setTimeout(applyMode, 0));
    });
    applyMode();
});
</script>
HTML;

        $response->setContent(str_replace('</body>', $script.'</body>', $html));
        return $response;
    }
}
