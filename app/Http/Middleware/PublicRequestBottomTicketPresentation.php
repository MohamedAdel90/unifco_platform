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

        $isRequestPage = $request->routeIs(...$requestRoutes)
            || $request->is('request-service*')
            || $request->is('request-quote*')
            || $request->is('emergency*');

        if (! $isRequestPage || ! method_exists($response, 'getContent') || ! method_exists($response, 'setContent')) {
            return $response;
        }

        $html = (string) $response->getContent();
        $homeUrl = route('public.home');
        $ticketNote = '<div class="request-ticket-note">✓ بعد تسجيل الطلب بنجاح، يصدر النظام <strong>رقم تذكرة فريد</strong> للطلب وفق آلية الترقيم المعتمدة، ويُعرض للعميل في صفحة تأكيد الاستلام.</div>';
        $cancel = '<a class="cancel-request-btn" href="'.$homeUrl.'" aria-label="إلغاء الطلب والعودة للرئيسية">إلغاء</a>';

        $styles = <<<'HTML'
<style id="unifco-request-bottom-ticket-v4">
.request-bottom-actions-row{
    display:flex!important;
    align-items:center!important;
    justify-content:space-between!important;
    gap:16px!important;
    width:100%!important;
    margin:16px 0 22px!important;
    padding:14px 0 0!important;
    border-top:1px solid #e9eef5!important;
    direction:ltr!important;
}
.request-bottom-actions-row .request-actions-left{
    display:flex!important;
    align-items:center!important;
    justify-content:flex-start!important;
    gap:9px!important;
    flex:0 0 auto!important;
    direction:ltr!important;
    order:1!important;
}
.request-bottom-actions-row .request-ticket-note{
    order:2!important;
    flex:1 1 auto!important;
    width:auto!important;
    max-width:none!important;
    min-width:0!important;
    min-height:46px!important;
    margin:0!important;
    padding:9px 14px!important;
    display:flex!important;
    align-items:center!important;
    justify-content:flex-start!important;
    background:#eef5ff!important;
    border:1px solid #cfe0f6!important;
    border-right:4px solid #08295d!important;
    border-radius:8px!important;
    color:#24456f!important;
    font-size:10px!important;
    font-weight:800!important;
    line-height:1.65!important;
    text-align:right!important;
    direction:rtl!important;
    box-shadow:none!important;
}
.request-bottom-actions-row .request-ticket-note strong{color:#e3132c!important;font-weight:900!important}
.request-bottom-actions-row .submit,
.request-bottom-actions-row .emergency-submit,
.request-bottom-actions-row button[type="submit"],
.request-bottom-actions-row input[type="submit"]{
    width:150px!important;
    min-width:150px!important;
    max-width:150px!important;
    height:46px!important;
    min-height:46px!important;
    margin:0!important;
    padding:0 13px!important;
    border:0!important;
    border-radius:7px!important;
    background:#e3132c!important;
    color:#fff!important;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    text-align:center!important;
    font-size:11px!important;
    font-weight:900!important;
    line-height:1.35!important;
    box-shadow:none!important;
    white-space:normal!important;
}
.request-bottom-actions-row .cancel-request-btn{
    width:88px!important;
    min-width:88px!important;
    height:46px!important;
    padding:0 12px!important;
    border-radius:7px!important;
    border:1px solid #cbd5e1!important;
    background:#fff!important;
    color:#52657e!important;
    text-decoration:none!important;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    font-size:10.5px!important;
    font-weight:900!important;
    box-shadow:none!important;
}
.request-bottom-actions-row .cancel-request-btn:hover{background:#f7f9fc!important;border-color:#9fb0c4!important;color:#17345e!important}
.request-bottom-actions-row .ticket-promise-inline{display:none!important}
body.request-ticket-bottom-ready>.request-ticket-note,
main>.request-ticket-note,
.request-clarity-hero+.request-ticket-note{display:none!important}
.emergency-submit-row.request-bottom-actions-row{margin-top:16px!important}
@media(max-width:760px){
    .request-bottom-actions-row{flex-direction:column!important;align-items:stretch!important;direction:rtl!important;gap:10px!important}
    .request-bottom-actions-row .request-ticket-note{order:1!important;width:100%!important}
    .request-bottom-actions-row .request-actions-left{order:2!important;width:100%!important;direction:rtl!important}
    .request-bottom-actions-row .request-actions-left>*{flex:1 1 0!important;width:auto!important;max-width:none!important;min-width:0!important}
}
</style>
HTML;
        $html = str_replace('</head>', $styles.'</head>', $html);

        $routinePattern = '/(<p\s+class="privacy"[^>]*>.*?<\/p>)\s*(<button\s+class="submit"[^>]*type="submit"[^>]*>.*?<\/button>)/si';
        $html = preg_replace_callback($routinePattern, function ($m) use ($ticketNote, $cancel) {
            return $m[1].'<div class="request-bottom-actions-row"><div class="request-actions-left">'.$m[2].$cancel.'</div>'.$ticketNote.'</div>';
        }, $html, 1) ?? $html;

        $emergencyPattern = '/<div\s+class="emergency-submit-row"[^>]*>\s*(<button\s+class="emergency-submit"[^>]*type="submit"[^>]*>.*?<\/button>)\s*(?:<button\s+class="emergency-cancel"[^>]*>.*?<\/button>)?\s*<\/div>/si';
        $html = preg_replace_callback($emergencyPattern, function ($m) use ($ticketNote, $cancel) {
            return '<div class="emergency-submit-row request-bottom-actions-row"><div class="request-actions-left">'.$m[1].$cancel.'</div>'.$ticketNote.'</div>';
        }, $html, 1) ?? $html;

        if (str_contains($html, 'request-bottom-actions-row')) {
            $html = preg_replace('/<div\s+class="request-ticket-note"[^>]*>.*?<\/div>(?=\s*<main|\s*<section|\s*<div\s+class="request-selector)/si', '', $html, 1) ?? $html;
        }

        $script = <<<HTML
<script id="unifco-request-bottom-ticket-script-v4">
(()=>{
  const noteHtml='✓ بعد تسجيل الطلب بنجاح، يصدر النظام <strong>رقم تذكرة فريد</strong> للطلب وفق آلية الترقيم المعتمدة، ويُعرض للعميل في صفحة تأكيد الاستلام.';
  const homeUrl='{$homeUrl}';
  const makeNote=()=>{const n=document.createElement('div');n.className='request-ticket-note';n.innerHTML=noteHtml;return n};
  const makeCancel=()=>{const a=document.createElement('a');a.className='cancel-request-btn';a.href=homeUrl;a.textContent='إلغاء';a.setAttribute('aria-label','إلغاء الطلب والعودة للرئيسية');return a};

  const normalizeRow=(row)=>{
    if(!row)return;
    row.classList.add('request-bottom-actions-row');
    const submit=row.querySelector('.emergency-submit,.submit,button[type="submit"],input[type="submit"]');
    if(!submit)return;
    row.querySelectorAll('.emergency-cancel,.cancel-request-btn').forEach(el=>el.remove());
    let actions=row.querySelector('.request-actions-left,.submit-actions');
    if(!actions){actions=document.createElement('div');actions.className='request-actions-left';row.prepend(actions)}
    actions.classList.add('request-actions-left');
    if(!actions.contains(submit))actions.appendChild(submit);
    actions.appendChild(makeCancel());
    let note=row.querySelector('.request-ticket-note');
    if(!note){note=makeNote();row.appendChild(note)}
    row.querySelectorAll('.ticket-promise-inline').forEach(el=>el.remove());
    document.body.classList.add('request-ticket-bottom-ready');
  };

  const fallbackForm=(form)=>{
    if(!form)return;
    const submit=form.querySelector('.emergency-submit,.submit,button[type="submit"],input[type="submit"]');
    if(!submit)return;
    let row=submit.closest('.request-bottom-actions-row,.submit-reference-row,.emergency-submit-row,.form-actions,.actions');
    if(!row){row=document.createElement('div');submit.parentNode.insertBefore(row,submit);row.appendChild(submit)}
    normalizeRow(row);
  };

  const init=()=>{
    document.querySelectorAll('.request-bottom-actions-row,.submit-reference-row,.emergency-submit-row').forEach(normalizeRow);
    document.querySelectorAll('form').forEach(form=>{
      const submit=form.querySelector('.emergency-submit,.submit,button[type="submit"],input[type="submit"]');
      if(submit&&!form.querySelector('.request-bottom-actions-row'))fallbackForm(form);
    });
  };
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>{init();setTimeout(init,120)});else{init();setTimeout(init,120)}
})();
</script>
HTML;
        $html = str_replace('</body>', $script.'</body>', $html);
        $response->setContent($html);

        return $response;
    }
}
