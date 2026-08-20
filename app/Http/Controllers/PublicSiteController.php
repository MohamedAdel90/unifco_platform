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
            $heroRelease = '<style id="unifco-hero-release-20260821-v5">.hero{min-height:590px!important;aspect-ratio:auto!important;background:#071d3a!important;overflow:hidden!important}.hero .hero-grid{display:grid!important;grid-template-columns:48% 52%!important;direction:ltr!important;min-height:590px!important;width:100%!important;max-width:none!important}.hero .hero-copy{grid-column:1!important;display:flex!important;flex-direction:column!important;justify-content:center!important;direction:rtl!important;text-align:right!important;position:relative!important;z-index:3!important;padding:54px 8% 54px 10%!important;background:linear-gradient(90deg,#071d3a 0%,#092a55 100%)!important}.hero .hero-grid>div:last-child{grid-column:2!important;display:block!important;min-height:590px!important;background-color:#0a2b57!important;background-image:url("/images/unifco-facility-hero.jpg?v=20260821-5")!important;background-size:auto 145%!important;background-position:right bottom!important;background-repeat:no-repeat!important;position:relative!important}.hero .hero-grid>div:last-child:before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(9,42,85,.9) 0%,rgba(9,42,85,.28) 20%,rgba(9,42,85,0) 46%)}@media(max-width:1080px){.hero .hero-grid{grid-template-columns:1fr!important}.hero .hero-copy{grid-column:1!important;padding:48px 5%!important}.hero .hero-grid>div:last-child{grid-column:1!important;min-height:360px!important;background-size:auto 135%!important;background-position:right bottom!important}}@media(max-width:700px){.hero{min-height:0!important}.hero .hero-copy{padding:38px 4%!important}.hero .hero-grid>div:last-child{min-height:300px!important;background-size:auto 125%!important}}@media(max-width:450px){.hero .hero-grid>div:last-child{min-height:250px!important;background-size:auto 118%!important}}</style>';
            $html = str_replace('</head>', $heroRelease.'</head>', $html);
        }

        return response(str_replace('</head>', $cairo.'</head>', $html))
            ->header('X-UNIFCO-Release', 'hero-20260821-5')
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
