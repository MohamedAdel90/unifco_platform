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
            $heroRelease = '<style id="unifco-hero-release-20260821-v4">.hero{min-height:590px!important;aspect-ratio:auto!important;background:linear-gradient(90deg,#071d3a 0%,#0a2b57 48%,#0a2b57 100%)!important;overflow:hidden!important}.hero .hero-grid{display:grid!important;grid-template-columns:46% 54%!important;direction:ltr!important;min-height:590px!important}.hero .hero-copy{display:block!important;direction:rtl!important;text-align:right!important;align-self:center!important;position:relative!important;z-index:2!important;padding:58px 6% 58px 0!important}.hero .hero-grid>div:last-child{display:block!important;min-height:590px!important;background-image:url("/images/unifco-facility-hero.jpg?v=20260821-4")!important;background-size:200% auto!important;background-position:100% 100%!important;background-repeat:no-repeat!important;position:relative!important}.hero .hero-grid>div:last-child:before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#0a2b57 0%,rgba(10,43,87,.48) 22%,rgba(10,43,87,0) 52%)}@media(max-width:1080px){.hero .hero-grid{grid-template-columns:1fr!important}.hero .hero-copy{padding:52px 4%!important}.hero .hero-grid>div:last-child{min-height:360px!important;background-size:190% auto!important;background-position:100% 100%!important}}@media(max-width:700px){.hero{min-height:0!important}.hero .hero-grid>div:last-child{min-height:300px!important;background-size:205% auto!important}.hero .hero-copy{padding:42px 3%!important}}@media(max-width:450px){.hero .hero-grid>div:last-child{min-height:250px!important;background-size:220% auto!important}}</style>';
            $html = str_replace('</head>', $heroRelease.'</head>', $html);
        }

        return response(str_replace('</head>', $cairo.'</head>', $html))
            ->header('X-UNIFCO-Release', 'hero-20260821-4')
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
