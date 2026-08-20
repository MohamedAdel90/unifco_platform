<?php

namespace App\Http\Controllers;

use App\Models\PublicServiceRequest;
use App\Services\PublicRequestPipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function home(Request $request): Response
    {
        $requested = $request->query('lang');
        if (in_array($requested, ['ar', 'en'], true)) {
            $request->session()->put('public_locale', $requested);
        }

        $locale = in_array($requested, ['ar', 'en'], true)
            ? $requested
            : $request->session()->get('public_locale', 'ar');

        $html = view($locale === 'en' ? 'public.home-en' : 'public.home', compact('locale'))->render();
        $cairo = '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><style id="unifco-cairo-font">html,body,button,input,select,textarea{font-family:"Cairo",Tahoma,Arial,sans-serif}</style>';

        if ($locale === 'ar') {
            $html = str_replace(
                'حلول متكاملة لإدارة المرافق والصيانة وأنظمة MEP والطاقة والخدمات الفنية، من التخطيط وحتى التنفيذ والمتابعة والتقارير.',
                'حلول متكاملة لإدارة المرافق وصيانة الأنظمة الفنية مع متابعة رقمية وفرق متخصصة تضمن استمرارية التشغيل بأعلى كفاءة.',
                $html
            );
            $html = str_replace('متابعة رقمية وتقارير</span>', 'متابعة رقمية وتقارير شفافة</span>', $html);

            $heroRelease = <<<'CSS'
<style id="unifco-hero-release-20260821-v9">
html,body{overflow-x:hidden!important}
.top{background:#fff!important;border-bottom:1px solid #e8edf3!important;box-shadow:none!important}
.nav{min-height:112px!important;direction:ltr!important;gap:24px!important;max-width:100%!important;flex-wrap:nowrap!important}
.logo{width:205px!important;height:78px!important;object-fit:contain!important;margin:0!important;flex:0 0 auto!important}
.nav-links{direction:rtl!important;gap:30px!important;margin:auto!important;min-width:0!important}
.nav-link{font-size:14px!important;font-weight:800!important;color:#071f4d!important;position:relative!important;padding:44px 0 38px!important;white-space:nowrap!important}
.nav-link:first-child:after{content:"";position:absolute;left:12%;right:12%;bottom:24px;height:4px;background:#e20b24;border-radius:4px}
.nav-actions{direction:rtl!important;gap:12px!important;flex:0 0 auto!important}
.nav-actions .btn{min-height:54px!important;padding:12px 20px!important;border-radius:8px!important;font-size:13px!important;white-space:nowrap!important}
.lang{min-height:54px!important;display:flex!important;align-items:center!important;padding:10px 14px!important;border:1px solid #cfd7e2!important;font-size:12px!important;color:#071f4d!important;white-space:nowrap!important}
.hero{position:relative!important;height:clamp(640px,49.45vw,827px)!important;min-height:640px!important;overflow:hidden!important;color:#fff!important;background-image:linear-gradient(90deg,rgba(4,22,46,.98) 0%,rgba(4,22,46,.94) 22%,rgba(4,22,46,.78) 39%,rgba(4,22,46,.42) 55%,rgba(4,22,46,.10) 72%,rgba(4,22,46,0) 100%),url("/images/unifco-hero-workers-v7.jpg?v=20260821-9")!important;background-size:cover!important;background-position:center!important;background-repeat:no-repeat!important}
.hero .hero-grid{position:relative!important;display:grid!important;grid-template-columns:minmax(0,47%) minmax(0,53%)!important;direction:ltr!important;width:min(1495px,89%)!important;height:100%!important;min-height:0!important;margin:0 auto!important;align-items:center!important}
.hero .hero-copy{grid-column:1!important;position:relative!important;left:auto!important;right:auto!important;top:auto!important;transform:none!important;width:100%!important;max-width:650px!important;min-height:0!important;padding:0!important;margin:0!important;display:block!important;direction:rtl!important;text-align:right!important;background:transparent!important;z-index:5!important;visibility:visible!important;opacity:1!important}
.hero .eyebrow{font-size:20px!important;font-weight:800!important;letter-spacing:.3px!important;margin-bottom:16px!important;text-align:left!important;direction:ltr!important;color:#fff!important;display:block!important}
.hero h1{font-size:clamp(50px,4.25vw,72px)!important;line-height:1.26!important;font-weight:900!important;letter-spacing:-1px!important;margin:0 0 22px!important;max-width:650px!important;color:#fff!important;display:block!important}
.hero p{font-size:17px!important;line-height:2!important;font-weight:500!important;color:#f1f5fa!important;max-width:625px!important;margin:0!important;display:block!important}
.hero .actions{display:flex!important;direction:ltr!important;gap:28px!important;flex-wrap:nowrap!important;margin-top:30px!important;justify-content:flex-start!important}
.hero .actions .btn{width:205px!important;min-height:68px!important;border-radius:8px!important;font-size:17px!important;font-weight:800!important;box-shadow:none!important;padding:14px 20px!important}
.hero .actions .btn.red{background:#e20b24!important;border-color:#e20b24!important;color:#fff!important}
.hero .actions .btn.red:after{content:"✈";font-size:22px;margin-left:12px;transform:rotate(-25deg)}
.hero .actions .btn.light{background:rgba(3,20,43,.28)!important;color:#fff!important;border:2px solid rgba(255,255,255,.88)!important}
.hero .actions .btn.light:before{content:"←";font-size:22px;margin-right:10px}
.hero .hero-proof{display:flex!important;direction:ltr!important;gap:38px!important;flex-wrap:nowrap!important;margin-top:72px!important;align-items:center!important}
.hero .proof{display:flex!important;direction:rtl!important;align-items:center!important;gap:12px!important;font-size:13px!important;font-weight:800!important;color:#fff!important;min-width:160px!important;line-height:1.55!important}
.hero .proof i{display:grid!important;place-items:center!important;flex:0 0 48px!important;width:48px!important;height:48px!important;border:2px solid rgba(255,255,255,.9)!important;border-radius:50%!important;font-style:normal!important;font-size:20px!important;color:#fff!important;background:transparent!important}
.hero .hero-grid>div:last-child:not(.hero-copy){display:none!important}
@media(max-width:1200px){.nav-links{gap:18px!important}.nav-link{font-size:12px!important}.logo{width:175px!important}.hero .hero-grid{grid-template-columns:minmax(0,52%) minmax(0,48%)!important}.hero .hero-proof{gap:18px!important}.hero .proof{min-width:135px!important;font-size:11px!important}}
@media(max-width:1080px){.nav{min-height:82px!important}.logo{width:160px!important;height:62px!important}.hero{height:650px!important;min-height:650px!important;background-position:62% center!important}.hero .hero-grid{width:92%!important;grid-template-columns:minmax(0,58%) minmax(0,42%)!important}.hero h1{font-size:50px!important}.hero .hero-proof{margin-top:52px!important}}
@media(max-width:800px){.hero{height:610px!important;min-height:610px!important;background-position:67% center!important}.hero:after{content:"";position:absolute;inset:0;background:rgba(4,22,46,.24);z-index:1}.hero .hero-grid{grid-template-columns:1fr!important;z-index:2!important}.hero .hero-copy{grid-column:1!important;width:100%!important;max-width:590px!important}.hero h1{font-size:44px!important}.hero p{font-size:15px!important}.hero .hero-proof{gap:14px!important}.hero .proof{min-width:0!important;flex:1!important;font-size:10px!important}.hero .proof i{flex-basis:40px!important;width:40px!important;height:40px!important;font-size:16px!important}}
@media(max-width:560px){.hero{height:650px!important;min-height:650px!important;background-position:72% center!important}.hero .eyebrow{font-size:15px!important}.hero h1{font-size:36px!important}.hero .actions{gap:10px!important}.hero .actions .btn{width:auto!important;flex:1!important;min-height:56px!important;font-size:14px!important}.hero .hero-proof{margin-top:44px!important;gap:10px!important}.hero .proof{display:block!important;text-align:center!important;font-size:9px!important}.hero .proof i{margin:0 auto 8px!important}.logo{width:140px!important}.nav{min-height:74px!important}}
</style>
CSS;
            $html = str_replace('</head>', $heroRelease.'</head>', $html);
        }

        return response(str_replace('</head>', $cairo.'</head>', $html))
            ->header('X-UNIFCO-Release', 'hero-20260821-9')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function quote(): View { return view('public.request', ['type' => 'QUOTATION']); }
    public function emergency(): View { return view('public.request', ['type' => 'EMERGENCY_MAINTENANCE']); }

    public function store(Request $request, PublicRequestPipelineService $pipeline): RedirectResponse
    {
        $data = $request->validate([
            'request_type' => ['required','in:QUOTATION,EMERGENCY_MAINTENANCE'],
            'service_category' => ['required','string','max:120'],
            'subject' => ['required','string','max:180'],
            'details' => ['required','string','max:5000'],
            'site_city' => ['nullable','string','max:120'],
            'company_name' => ['required','string','max:180'],
            'commercial_registration' => ['required','string','max:60'],
            'email' => ['required','email','max:180'],
            'mobile' => ['required','string','max:32'],
        ]);

        $data['reference_no'] = ($data['request_type'] === 'QUOTATION' ? 'RFQ-' : 'EMR-').now()->format('Ymd').'-'.strtoupper(Str::random(6));
        $data['urgency'] = $data['request_type'] === 'EMERGENCY_MAINTENANCE' ? 'EMERGENCY' : 'NORMAL';
        $data['submitted_at'] = now();
        $data['status'] = 'NEW';

        $record = PublicServiceRequest::create($data);
        $pipeline->convert($record);

        return redirect()->route('public.request.received', $record->reference_no);
    }

    public function received(string $reference): View
    {
        $record = PublicServiceRequest::where('reference_no', $reference)->firstOrFail();
        return view('public.received', compact('record'));
    }

    public function adminIndex(): View
    {
        return view('public.admin-requests', [
            'requests' => PublicServiceRequest::latest('submitted_at')->limit(250)->get(),
        ]);
    }
}
