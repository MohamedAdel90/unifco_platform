<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicRequestBottomTicketPresentation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $requestRoutes = [
            'public.quote',
            'public.request-service',
            'public.emergency',
            'public.current-maintenance',
        ];

        if (! $request->routeIs(...$requestRoutes) || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();

        $styles = <<<'HTML'
<style id="unifco-request-bottom-ticket-v1">
.request-bottom-actions-row{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:20px!important;width:100%!important;margin:18px 0 24px!important;padding-top:18px!important;border-top:1px solid #eef2f7!important;direction:ltr!important}
.request-bottom-actions-row .request-actions-left{display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:9px!important;flex:0 0 auto!important;direction:ltr!important;order:1!important}
.request-bottom-actions-row .request-ticket-note{order:2!important;flex:1 1 auto!important;width:auto!important;max-width:none!important;margin:0!important;padding:10px 14px!important;min-height:46px!important;display:flex!important;align-items:center!important;justify-content:flex-start!important;background:#eef5ff!important;border:1px solid #cfe0f6!important;border-right:4px solid #08295d!important;border-radius:9px!important;color:#24456f!important;font-size:10.5px!important;font-weight:800!important;line-height:1.8!important;text-align:right!important;direction:rtl!important}
.request-bottom-actions-row .request-ticket-note strong{color:#e3132c!important}
.request-bottom-actions-row .submit,.request-bottom-actions-row button[type="submit"],.request-bottom-actions-row input[type="submit"]{width:150px!important;min-width:150px!important;height:46px!important;min-height:46px!important;margin:0!important;border-radius:8px!important;font-size:12px!important;padding:0 16px!important}
.request-bottom-actions-row .cancel-request-btn{height:46px!important;min-width:92px!important;padding:0 16px!important;border-radius:8px!important;border:1px solid #cbd5e1!important;background:#fff!important;color:#52657e!important;text-decoration:none!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;font-size:11px!important;font-weight:900!important;box-shadow:none!important}
.request-bottom-actions-row .cancel-request-btn:hover{background:#f7f9fc!important;border-color:#9fb0c4!important;color:#17345e!important}
.request-bottom-actions-row .ticket-promise-inline{display:none!important}
body.request-ticket-bottom-ready>.request-ticket-note,main>.request-ticket-note,.request-clarity-hero+.request-ticket-note{display:none!important}
@media(max-width:760px){.request-bottom-actions-row{flex-direction:column!important;align-items:stretch!important;direction:rtl!important}.request-bottom-actions-row .request-ticket-note{order:1!important;width:100%!important}.request-bottom-actions-row .request-actions-left{order:2!important;width:100%!important;direction:rtl!important}.request-bottom-actions-row .request-actions-left>*{flex:1 1 0!important;width:auto!important;min-width:0!important}}
</style>
HTML;

        $homeUrl = route('public.home');
        $script = <<<HTML
<script id="unifco-request-bottom-ticket-script-v1">
(()=>{
  const routesFormSelector='form[action*="service-requests"], form[action*="request"]';

  const createTicketNote=()=>{
    const note=document.createElement('div');
    note.className='request-ticket-note';
    note.innerHTML='✓ بعد تسجيل الطلب بنجاح، يصدر النظام <strong>رقم تذكرة فريد</strong> للطلب وفق آلية الترقيم المعتمدة، ويُعرض للعميل في صفحة تأكيد الاستلام.';
    return note;
  };

  const enhanceForm=(form)=>{
    if(!form || form.dataset.bottomTicketReady==='1') return;
    const submit=form.querySelector('.submit, button[type="submit"], input[type="submit"]');
    if(!submit) return;
    form.dataset.bottomTicketReady='1';

    let row=form.querySelector('.submit-reference-row, .request-bottom-actions-row');
    if(!row){
      row=document.createElement('div');
      row.className='request-bottom-actions-row';
      const anchor=submit.closest('.actions,.form-actions,.submit-row') || submit;
      anchor.parentNode.insertBefore(row, anchor);
    } else {
      row.classList.add('request-bottom-actions-row');
    }

    let actions=row.querySelector('.submit-actions,.request-actions-left');
    if(!actions){
      actions=document.createElement('div');
      actions.className='request-actions-left';
      row.insertBefore(actions,row.firstChild);
    } else {
      actions.classList.add('request-actions-left');
    }

    if(!actions.contains(submit)) actions.appendChild(submit);

    let cancel=actions.querySelector('.cancel-request-btn');
    if(!cancel){
      cancel=document.createElement('a');
      cancel.className='cancel-request-btn';
      cancel.href='{$homeUrl}';
      cancel.textContent='إلغاء';
      cancel.setAttribute('aria-label','إلغاء الطلب والعودة للرئيسية');
      actions.appendChild(cancel);
    }

    let note=document.querySelector('.request-ticket-note:not(.request-bottom-actions-row .request-ticket-note)');
    if(!note) note=createTicketNote();
    row.appendChild(note);

    row.querySelectorAll('.ticket-promise-inline').forEach(el=>el.remove());
    document.body.classList.add('request-ticket-bottom-ready');
  };

  const init=()=>{
    const forms=[...document.querySelectorAll(routesFormSelector)];
    if(!forms.length){
      const fallback=[...document.querySelectorAll('form')].filter(f=>f.querySelector('button[type="submit"],input[type="submit"],.submit'));
      fallback.forEach(enhanceForm);
    } else forms.forEach(enhanceForm);
  };

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',()=>{init();setTimeout(init,80)}); else {init();setTimeout(init,80)}
})();
</script>
HTML;

        $html = str_replace('</head>', $styles.'</head>', $html);
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
