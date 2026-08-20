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
<style id="unifco-hero-release-20260821-v8">
.top{background:#fff!important;border-bottom:1px solid #e8edf3!important;box-shadow:none!important}
.nav{min-height:112px!important;direction:ltr!important;gap:28px!important}
.logo{width:218px!important;height:82px!important;object-fit:contain!important;margin:0!important;flex:0 0 auto!important}
.nav-links{direction:rtl!important;gap:38px!important;margin:auto!important}
.nav-link{font-size:15px!important;font-weight:800!important;color:#071f4d!important;position:relative!important;padding:44px 0 38px!important}
.nav-link:first-child:after{content:"";position:absolute;left:12%;right:12%;bottom:24px;height:4px;background:#e20b24;border-radius:4px}
.nav-actions{direction:rtl!important;gap:16px!important}
.nav-actions .btn{min-height:56px!important;padding:12px 24px!important;border-radius:8px!important;font-size:14px!important}
.lang{min-height:56px!important;display:flex!important;align-items:center!important;padding:10px 15px!important;border:1px solid #cfd7e2!important;font-size:13px!important;color:#071f4d!important}
.hero{position:relative!important;height:clamp(640px,49.45vw,827px)!important;min-height:640px!important;display:block!important;overflow:hidden!important;color:#fff!important;background-image:linear-gradient(90deg,rgba(4,22,46,.98) 0%,rgba(4,22,46,.95) 20%,rgba(4,22,46,.80) 38%,rgba(4,22,46,.48) 54%,rgba(4,22,46,.12) 72%,rgba(4,22,46,0) 100%),url("/images/unifco-hero-workers-v7.jpg?v=20260821-8")!important;background-size:cover!important;background-position:center!important;background-repeat:no-repeat!important}
.hero .hero-grid{position:relative!important;display:block!important;width:min(1495px,89%)!important;height:100%!important;min-height:0!important;margin:0 auto!important}
.hero .hero-copy{position:absolute!important;left:0!important;right:auto!important;top:50%!important;transform:translateY(-50%)!important;width:43%!important;max-width:650px!important;min-height:0!important;padding:0!important;margin:0!important;display:block!important;direction:rtl!important;text-align:right!important;background:transparent!important;z-index:3!important}
.hero .eyebrow{font-size:20px!important;font-weight:800!important;letter-spacing:.3px!important;margin-bottom:16px!important;text-align:left!important;direction:ltr!important;color:#fff!important}
.hero h1{font-size:clamp(50px,4.25vw,72px)!important;line-height:1.26!important;font-weight:900!important;letter-spacing:-1px!important;margin:0 0 22px!important;max-width:650px!important;color:#fff!important}
.hero p{font-size:17px!important;line-height:2!important;font-weight:500!important;color:#f1f5fa!important;max-width:625px!important;margin:0!important}
.hero .actions{display:flex!important;direction:ltr!important;gap:28px!important;flex-wrap:nowrap!important;margin-top:30px!important;justify-content:flex-start!important}
.hero .actions .btn{width:205px!important;min-height:68px!important;border-radius:8px!important;font-size:17px!important;font-weight:800!important;box-shadow:none!important;padding:14px 20px!important}
.hero .actions .btn.red{background:#e20b24!important;border-color:#e20b24!important;color:#fff!important}
.hero .actions .btn.red:after{content:"✈";font-size:22px;margin-left:12px;transform:rotate(-25deg)}
.hero .actions .btn.light{background:rgba(3,20,43,.28)!important;color:#fff!important;border:2px solid rgba(255,255,255,.88)!important}
.hero .actions .btn.light:before{content:"←";font-size:22px;margin-right:10px}
.hero .hero-proof{display:flex!important;direction:ltr!important;gap:46px!important;flex-wrap:nowrap!important;margin-top:82px!important;align-items:center!important}
.hero .proof{display:flex!important;direction:rtl!important;align-items:center!important;gap:13px!important;font-size:14px!important;font-weight:800!important;color:#fff!important;min-width:175px!important;line-height:1.55!important}
.hero .proof i{display:block!important;flex:0 0 52px!important;width:52px!important;height:52px!important;border:0!important;border-radius:0!important;font-size:0!important;background-position:center!important;background-repeat:no-repeat!important;background-size:50px 50px!important}
.hero .proof:nth-child(1) i{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='52' height='52' viewBox='0 0 52 52' fill='none' stroke='white' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M9 29v-5a17 17 0 0 1 34 0v5'/%3E%3Cpath d='M9 27H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h5V27H9Z'/%3E%3Cpath d='M43 27h3a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3h-5V27h2Z'/%3E%3Cpath d='M42 42c-2 4-6 6-11 6h-4'/%3E%3Ccircle cx='24' cy='48' r='2'/%3E%3C/svg%3E")!important}
.hero .proof:nth-child(2) i{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='52' height='52' viewBox='0 0 52 52' fill='none' stroke='white' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='6' y='9' width='40' height='36' rx='3'/%3E%3Cpath d='M15 5v9M37 5v9M6 18h40'/%3E%3Cpath d='m17 31 6 6 13-14'/%3E%3C/svg%3E")!important}
.hero .proof:nth-child(3) i{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='52' height='52' viewBox='0 0 52 52' fill='none' stroke='white' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 45h40'/%3E%3Crect x='9' y='31' width='7' height='11'/%3E%3Crect x='23' y='24' width='7' height='18'/%3E%3Crect x='37' y='16' width='7' height='26'/%3E%3Cpath d='m9 25 12-9 9 4 14-13'/%3E%3Cpath d='M37 7h7v7'/%3E%3C/svg%3E")!important}
.hero .hero-grid>div:last-child{display:none!important}
@media(max-width:1200px){.nav-links{gap:22px!important}.nav-link{font-size:13px!important}.logo{width:185px!important}.hero .hero-copy{width:49%!important}.hero .hero-proof{gap:22px!important}.hero .proof{min-width:150px!important;font-size:12px!important}}
@media(max-width:1080px){.nav{min-height:82px!important}.logo{width:165px!important;height:64px!important}.hero{height:650px!important;min-height:650px!important;background-position:62% center!important}.hero .hero-grid{width:92%!important}.hero .hero-copy{width:58%!important;max-width:610px!important}.hero h1{font-size:52px!important}.hero .hero-proof{margin-top:55px!important}}
@media(max-width:800px){.hero{height:610px!important;min-height:610px!important;background-position:67% center!important}.hero:after{content:"";position:absolute;inset:0;background:rgba(4,22,46,.22);z-index:1}.hero .hero-copy{width:100%!important;max-width:590px!important;z-index:2!important}.hero h1{font-size:44px!important}.hero p{font-size:15px!important}.hero .hero-proof{gap:18px!important}.hero .proof{min-width:0!important;flex:1!important;font-size:11px!important}.hero .proof i{flex-basis:42px!important;width:42px!important;height:42px!important;background-size:40px 40px!important}}
@media(max-width:560px){.hero{height:650px!important;min-height:650px!important;background-position:72% center!important}.hero .hero-copy{top:48%!important}.hero .eyebrow{font-size:15px!important}.hero h1{font-size:36px!important}.hero .actions{gap:10px!important}.hero .actions .btn{width:auto!important;flex:1!important;min-height:56px!important;font-size:14px!important}.hero .hero-proof{margin-top:48px!important;gap:12px!important}.hero .proof{display:block!important;text-align:center!important;font-size:10px!important}.hero .proof i{margin:0 auto 8px!important}.logo{width:145px!important}.nav{min-height:74px!important}}
</style>
CSS;
            $html = str_replace('</head>', $heroRelease.'</head>', $html);
        }

        return response(str_replace('</head>', $cairo.'</head>', $html))
            ->header('X-UNIFCO-Release', 'hero-20260821-8')
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
