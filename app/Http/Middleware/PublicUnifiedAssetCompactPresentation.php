<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicUnifiedAssetCompactPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('public.request-service') || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $style = <<<'HTML'
<style id="unifco-unified-asset-compact-v1">
/* Shared compact geometry for the unified service-request asset step.
   Intentionally language-neutral so the exact same sizing is used in AR and EN. */
.uf-mode-grid{
    gap:10px!important;
    margin-bottom:12px!important;
}
.uf-mode{
    min-height:50px!important;
    height:auto!important;
    padding:6px 12px!important;
    gap:10px!important;
}
.uf-mode-text strong{
    line-height:1.3!important;
}
.uf-mode-text span{
    margin-top:1px!important;
    line-height:1.35!important;
}

.uf-asset-results{
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
    gap:12px!important;
    align-items:stretch!important;
}
.uf-asset-option{
    min-height:78px!important;
    height:auto!important;
    padding:7px 12px!important;
    justify-content:center!important;
}
.uf-asset-option:before{
    top:9px!important;
}
.uf-asset-thumb{
    height:27px!important;
    margin:0 0 2px!important;
}
.uf-asset-thumb svg{
    width:40px!important;
    height:28px!important;
}
.uf-asset-option strong{
    margin-bottom:1px!important;
    line-height:1.25!important;
}
.uf-asset-option span{
    line-height:1.3!important;
}
.uf-asset-option .code{
    line-height:1.25!important;
}

@media(max-width:650px){
    .uf-mode-grid{
        grid-template-columns:1fr!important;
        gap:8px!important;
    }
    .uf-mode{
        min-height:48px!important;
        padding:6px 10px!important;
    }
    .uf-asset-results{
        grid-template-columns:1fr!important;
        gap:8px!important;
    }
    .uf-asset-option{
        min-height:72px!important;
        padding:6px 11px!important;
    }
    .uf-asset-thumb{
        height:24px!important;
    }
    .uf-asset-thumb svg{
        width:36px!important;
        height:24px!important;
    }
}
</style>
HTML;

        $html = str_replace('</head>', $style.'</head>', $html);
        $response->setContent($html);

        return $response;
    }
}
