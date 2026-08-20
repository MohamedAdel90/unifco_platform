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
            $heroRelease = '<style id="unifco-hero-release-20260821-v7">.hero{position:relative!important;min-height:590px!important;display:block!important;overflow:hidden!important;background:linear-gradient(90deg,rgba(5,20,42,.98) 0%,rgba(5,20,42,.94) 23%,rgba(5,20,42,.72) 42%,rgba(5,20,42,.25) 62%,rgba(5,20,42,.04) 78%,rgba(5,20,42,0) 100%),url("/images/unifco-hero-workers-v7.jpg?v=20260821-7") 72% center/cover no-repeat!important}.hero .hero-grid{position:relative!important;display:block!important;min-height:590px!important;width:min(1280px,92%)!important;margin:0 auto!important}.hero .hero-copy{position:absolute!important;left:0!important;right:auto!important;top:50%!important;transform:translateY(-50%)!important;width:46%!important;max-width:590px!important;display:flex!important;flex-direction:column!important;justify-content:center!important;direction:rtl!important;text-align:right!important;z-index:5!important;padding:24px 0!important;background:transparent!important}.hero .hero-grid>div:last-child{display:none!important}.hero .hero-copy h1,.hero .hero-copy h2,.hero .hero-copy p,.hero .hero-copy div{position:relative!important;z-index:6!important}@media(max-width:1080px){.hero{min-height:560px!important;background-position:68% center!important}.hero .hero-grid{min-height:560px!important;width:92%!important}.hero .hero-copy{width:52%!important;max-width:560px!important;left:0!important}}@media(max-width:800px){.hero{min-height:560px!important;background-position:70% center!important}.hero:before{content:"";position:absolute;inset:0;background:rgba(5,20,42,.44);z-index:1}.hero .hero-copy{position:relative!important;left:auto!important;top:auto!important;transform:none!important;width:100%!important;max-width:620px!important;min-height:560px!important;padding:54px 3%!important;margin:0!important}}@media(max-width:520px){.hero{min-height:520px!important;background-position:73% center!important}.hero .hero-grid{min-height:520px!important}.hero .hero-copy{min-height:520px!important;padding:42px 2%!important}}</style>';
            $html = str_replace('</head>', $heroRelease.'</head>', $html);
        }

        return response(str_replace('</head>', $cairo.'</head>', $html))
            ->header('X-UNIFCO-Release', 'hero-20260821-7')
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
