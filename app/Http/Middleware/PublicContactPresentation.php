<?php

namespace App\Http\Middleware;

use App\Support\UnifcoContact;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicContactPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->user() || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $route = $request->route()?->getName();
        if (! in_array($route, [
            'public.home',
            'public.quote',
            'public.request-service',
            'public.emergency',
            'public.request.received',
            'public.service.detail',
        ], true)) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || str_contains($html, 'data-unifco-public-contact')) {
            return $response;
        }

        if ($route === 'public.home') {
            $contact = $this->homepageContactPresentation();
        } else {
            $contact = $this->floatingContactPresentation();
        }

        $pos = strripos($html, '</body>');
        $html = $pos === false ? $html.$contact : substr($html, 0, $pos).$contact.substr($html, $pos);
        $response->setContent($html);

        return $response;
    }

    private function homepageContactPresentation(): string
    {
        $waUrl = e(UnifcoContact::whatsappUrl('Hello UNIFCO, I would like to inquire about your services.'));
        $mailUrl = e(UnifcoContact::mailto());

        return '<style data-unifco-public-contact>'
            .'.unifco-contact-icons{display:flex;align-items:center;gap:10px;margin-top:10px}'
            .'.unifco-contact-icon{width:38px;height:38px;border-radius:50%;display:inline-grid;place-items:center;border:1px solid rgba(255,255,255,.28);color:#fff;text-decoration:none;transition:.18s;background:rgba(255,255,255,.06)}'
            .'.unifco-contact-icon:hover{transform:translateY(-2px);background:rgba(255,255,255,.13)}'
            .'.unifco-contact-icon svg{width:20px;height:20px;display:block;fill:currentColor}'
            .'.unifco-nav-contact{position:relative;display:inline-flex;align-items:center}'
            .'.unifco-nav-contact-toggle{background:none;border:0;padding:0;color:#25354d;font:800 12px Cairo,Tahoma,Arial,sans-serif;cursor:pointer;white-space:nowrap}'
            .'body[dir=ltr] .unifco-nav-contact-toggle{font-family:Inter,Arial,sans-serif}'
            .'.unifco-nav-contact-menu{position:absolute;top:calc(100% + 18px);inset-inline-end:0;display:none;gap:8px;padding:9px;background:#fff;border:1px solid #e1e6ed;border-radius:10px;box-shadow:0 12px 32px rgba(10,31,62,.16);z-index:120}'
            .'.unifco-nav-contact.open .unifco-nav-contact-menu{display:flex}'
            .'.unifco-nav-contact-menu .unifco-contact-icon{width:36px;height:36px;color:#071f4d;border-color:#dce3ec;background:#f7f9fb}'
            .'.unifco-nav-contact-menu .unifco-contact-icon.wa{color:#168653}.unifco-nav-contact-menu .unifco-contact-icon.mail{color:#0b3c78}'
            .'@media(max-width:1080px){.unifco-nav-contact{display:none}}'
            .'</style>'
            .'<script data-unifco-public-contact>(function(){'
            .'const wa=`'.$waUrl.'`,mail=`'.$mailUrl.'`;'
            .'const icon=(type,href)=>{const a=document.createElement("a");a.className="unifco-contact-icon "+type;a.href=href;a.setAttribute("aria-label",type==="wa"?"WhatsApp":"Email");if(type==="wa"){a.target="_blank";a.rel="noopener";a.innerHTML=`<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.7 11.7 0 0 0 12.1 0C5.6 0 .4 5.2.4 11.6c0 2 .5 4 1.5 5.7L0 24l6.9-1.8a11.7 11.7 0 0 0 5.2 1.3h.1c6.4 0 11.6-5.2 11.6-11.6 0-3.1-1.2-6-3.3-8.4Zm-8.4 18a9.6 9.6 0 0 1-4.9-1.3l-.4-.2-4.1 1.1 1.1-4-.3-.4a9.6 9.6 0 1 1 8.6 4.8Zm5.3-7.2c-.3-.1-1.7-.8-1.9-.9-.3-.1-.5-.1-.7.2-.2.3-.8.9-1 1.1-.2.2-.4.2-.7.1-.3-.1-1.2-.4-2.3-1.4-.8-.7-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.5-.5.3-.5c.1-.2.1-.4 0-.5l-.9-2.1c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.3 5.2 4.6.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.7-.7 1.9-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4Z"/></svg>`}else{a.innerHTML=`<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 4h20v16H2V4Zm2 2v.5l8 5.3 8-5.3V6H4Zm16 12V8.9l-8 5.3-8-5.3V18h16Z"/></svg>`}return a};'
            .'const nav=document.querySelector(".nav-links");if(nav){const old=nav.querySelector("a:last-child");if(old){const label=old.textContent.trim();const wrap=document.createElement("span");wrap.className="unifco-nav-contact";const btn=document.createElement("button");btn.type="button";btn.className="unifco-nav-contact-toggle";btn.textContent=label;btn.setAttribute("aria-expanded","false");const menu=document.createElement("span");menu.className="unifco-nav-contact-menu";menu.append(icon("wa",wa),icon("mail",mail));wrap.append(btn,menu);old.replaceWith(wrap);btn.addEventListener("click",e=>{e.stopPropagation();const open=wrap.classList.toggle("open");btn.setAttribute("aria-expanded",open?"true":"false")});document.addEventListener("click",()=>{wrap.classList.remove("open");btn.setAttribute("aria-expanded","false")});menu.addEventListener("click",e=>e.stopPropagation())}}'
            .'const footer=document.querySelector("#contact .footer-grid>div:last-child");if(footer){footer.querySelector(".footer-contact")?.remove();footer.querySelector("p")?.remove();const icons=document.createElement("div");icons.className="unifco-contact-icons";icons.append(icon("wa",wa),icon("mail",mail));footer.appendChild(icons)}'
            .'})();</script>';
    }

    private function floatingContactPresentation(): string
    {
        return '<style data-unifco-public-contact>'
            .'.unifco-contact-fab{position:fixed;right:18px;bottom:18px;z-index:9998;display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}'
            .'.unifco-contact-fab a{display:inline-flex;align-items:center;gap:7px;padding:10px 13px;border-radius:999px;text-decoration:none;font:700 12px Inter,Arial,sans-serif;box-shadow:0 8px 24px rgba(3,31,76,.18)}'
            .'.unifco-contact-fab .wa{background:#147a48;color:#fff}.unifco-contact-fab .mail{background:#06275c;color:#fff}'
            .'@media(max-width:600px){.unifco-contact-fab{left:12px;right:12px;bottom:12px}.unifco-contact-fab a{flex:1;justify-content:center;min-width:0;font-size:11px}}'
            .'</style>'
            .'<div class="unifco-contact-fab" data-unifco-public-contact>'
            .'<a class="wa" href="'.e(UnifcoContact::whatsappUrl('Hello UNIFCO, I would like to inquire about your services.')).'" target="_blank" rel="noopener">WhatsApp '.e(UnifcoContact::WHATSAPP_DISPLAY).'</a>'
            .'<a class="mail" href="'.e(UnifcoContact::mailto()).'">'.e(UnifcoContact::EMAIL).'</a>'
            .'</div>';
    }
}
