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
        if ($locale === 'ar') {
            $html = str_replace(
                'تم حذف قطاع الضيافة وتوجيه القطاعات نحو المواقع التي تعتمد على التشغيل والصيانة واستمرارية الطاقة.',
                'تغطي خدماتنا القطاعات التي تعتمد على جاهزية المرافق واستمرارية الطاقة الكهربائية.',
                $html
            );
        }

        $cairo = '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><style id="unifco-cairo-font">html,body,button,input,select,textarea{font-family:"Cairo",Tahoma,Arial,sans-serif}</style>';

        return response(str_replace('</head>', $cairo.'</head>', $html))
            ->header('X-UNIFCO-Release', 'home-electrical-20260821-12')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function quote(): View { return view('public.request', ['type' => 'QUOTATION']); }
    public function emergency(): View { return view('public.request', ['type' => 'EMERGENCY_MAINTENANCE']); }

    public function store(Request $request, PublicRequestPipelineService $pipeline): RedirectResponse
    {
        $isEmergency = $request->input('request_type') === 'EMERGENCY_MAINTENANCE';

        $data = $request->validate([
            'request_type' => ['required','in:QUOTATION,EMERGENCY_MAINTENANCE'],
            'service_category' => ['required','string','max:120'],
            'subject' => ['required','string','max:180'],
            'details' => ['required','string','max:5000'],
            'site_city' => [$isEmergency ? 'required' : 'nullable','string','max:120'],
            'site_address' => [$isEmergency ? 'required' : 'nullable','string','max:500'],
            'latitude' => [$isEmergency ? 'required' : 'nullable','numeric','between:-90,90'],
            'longitude' => [$isEmergency ? 'required' : 'nullable','numeric','between:-180,180'],
            'requested_date' => [$isEmergency ? 'required' : 'nullable','date','after_or_equal:today'],
            'requested_time' => [$isEmergency ? 'required' : 'nullable','date_format:H:i'],
            'equipment_image' => [$isEmergency ? 'required' : 'nullable','image','mimes:jpg,jpeg,png,webp','max:8192'],
            'supporting_images' => ['nullable','array','max:6'],
            'supporting_images.*' => ['image','mimes:jpg,jpeg,png,webp','max:8192'],
            'company_name' => ['required','string','max:180'],
            'responsible_person' => [$isEmergency ? 'required' : 'nullable','string','max:180'],
            'commercial_registration' => ['required','string','max:60'],
            'email' => ['required','email','max:180'],
            'mobile' => ['required','string','max:32'],
        ]);

        unset($data['equipment_image'], $data['supporting_images']);

        if ($request->hasFile('equipment_image')) {
            $data['equipment_image_path'] = $request->file('equipment_image')->store('public-requests/equipment', 'public');
        }

        if ($request->hasFile('supporting_images')) {
            $data['supporting_image_paths'] = collect($request->file('supporting_images'))
                ->map(fn ($image) => $image->store('public-requests/supporting', 'public'))
                ->values()
                ->all();
        }

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
