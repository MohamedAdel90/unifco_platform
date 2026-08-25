<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicAssetQrInsecureFallback
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type');
        if (! str_contains($contentType, 'text/html')) return $response;

        $html = $response->getContent();
        if (! is_string($html) || ! str_contains($html, 'id="assetQrSection"') || str_contains($html, 'id="assetQrPhotoFallback"')) return $response;

        $rtl = $request->query('lang') !== 'en';
        $photoLabel = $rtl ? 'التقاط صورة لرمز QR' : 'Take QR Photo';
        $photoHint = $rtl ? 'يعمل أيضًا عند فتح الصفحة بدون HTTPS' : 'Works when the page is opened without HTTPS';
        $decodeError = $rtl ? 'لم أتمكن من قراءة رمز QR من الصورة. قرّب الكاميرا وحاول مرة أخرى.' : 'Could not read the QR code from the photo. Move closer and try again.';
        $loading = $rtl ? 'جاري قراءة رمز QR...' : 'Reading QR code...';

        $input = '<input id="assetQrPhotoFallback" type="file" accept="image/*" capture="environment" hidden>';
        $html = str_replace('</body>', $input.'</body>', $html);

        $copy = json_encode(['label'=>$photoLabel,'hint'=>$photoHint,'decodeError'=>$decodeError,'loading'=>$loading], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $js = '<script id="asset-qr-insecure-fallback">(()=>{const c='.$copy.',btn=document.getElementById("assetScanButton"),file=document.getElementById("assetQrPhotoFallback"),msg=document.getElementById("assetLookupMessage");if(!btn||!file)return;const secure=window.isSecureContext||location.hostname==="localhost"||location.hostname==="127.0.0.1";if(secure)return;const title=btn.querySelector("b"),hint=btn.querySelector("small");if(title)title.textContent=c.label;if(hint)hint.textContent=c.hint;btn.addEventListener("click",e=>{e.preventDefault();e.stopImmediatePropagation();file.value="";file.click()},true);file.addEventListener("change",async()=>{const image=file.files&&file.files[0];if(!image)return;if(msg)msg.textContent=c.loading;if(typeof Html5Qrcode==="undefined"){if(msg)msg.textContent=c.decodeError;return}const reader=new Html5Qrcode("assetQrReader");try{const decoded=await reader.scanFile(image,true);const manual=document.getElementById("assetManualKey");if(manual)manual.value=decoded;document.getElementById("assetManualFind")?.click()}catch(e){if(msg)msg.textContent=c.decodeError}finally{try{reader.clear()}catch(e){}}})})();</script>';
        $html = str_replace('</body>', $js.'</body>', $html);
        $response->setContent($html);
        return $response;
    }
}
