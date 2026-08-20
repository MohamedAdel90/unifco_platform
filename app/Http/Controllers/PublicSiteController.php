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
            $heroRelease = '<style id="unifco-hero-release-20260821-v6">.hero{min-height:590px!important;display:flex!important;align-items:center!important;overflow:hidden!important;background:linear-gradient(90deg,rgba(7,26,52,.96) 0%,rgba(7,26,52,.88) 28%,rgba(7,26,52,.58) 48%,rgba(7,26,52,.14) 70%,rgba(7,26,52,.02) 100%),url("/images/unifco-hero-workers-v7.jpg?v=20260821-6") center/cover no-repeat!important}.hero .hero-grid{display:grid!important;grid-template-columns:52% 48%!important;direction:ltr!important;min-height:590px!important;width:min(1280px,92%)!important;margin:auto!important}.hero .hero-copy{grid-column:1!important;display:flex!important;flex-direction:column!important;justify-content:center!important;direction:rtl!important;text-align:right!important;position:relative!important;z-index:3!important;padding:58px 8% 58px 0!important;background:transparent!important}.hero .hero-grid>div:last-child{grid-column:2!important;display:block!important;min-height:590px!important;background:none!important}.hero .hero-grid>div:last-child:before{display:none!important}@media(max-width:1080px){.hero{background-position:62% center!important}.hero .hero-grid{grid-template-columns:1fr!important;min-height:560px!important}.hero .hero-copy{grid-column:1!important;max-width:650px!important;padding:48px 4%!important}.hero .hero-grid>div:last-child{display:none!important}}@media(max-width:700px){.hero{min-height:540px!important;background-position:68% center!important}.hero .hero-grid{min-height:540px!important}.hero .hero-copy{padding:38px 3%!important;background:linear-gradient(90deg,rgba(7,26,52,.88),rgba(7,26,52,.28))!important}}@media(max-width:450px){.hero{min-height:510px!important;background-position:72% center!important}}</style>';
            $html = str_replace('</head>', $heroRelease.'</head>', $html);
        }

        return response(str_replace('</head>', $cairo.'</head>', $html))
            ->header('X-UNIFCO-Release', 'hero-20260821-6')
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
