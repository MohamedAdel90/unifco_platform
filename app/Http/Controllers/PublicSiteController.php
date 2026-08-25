<?php

namespace App\Http\Controllers;

use App\Models\PublicServiceRequest;
use App\Services\PublicRequestPipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PublicSiteController extends Controller
{
    public function home(Request $request): Response
    {
        $requested = $request->query('lang');
        if (in_array($requested, ['ar', 'en'], true)) $request->session()->put('public_locale', $requested);
        $locale = in_array($requested, ['ar', 'en'], true) ? $requested : $request->session()->get('public_locale', 'ar');
        $html = view($locale === 'en' ? 'public.home-en' : 'public.home', compact('locale'))->render();
        if ($locale === 'ar') {
            $html = str_replace('تم حذف قطاع الضيافة وتوجيه القطاعات نحو المواقع التي تعتمد على التشغيل والصيانة واستمرارية الطاقة.','تغطي خدماتنا القطاعات التي تعتمد على جاهزية المرافق واستمرارية الطاقة الكهربائية.',$html);
            $html = str_replace('<div class="kicker">خدماتنا</div><h2>خدمات كهربائية وصيانة وإدارة مرافق متكاملة</h2><p>من أنظمة الطاقة الحرجة والصيانة المتخصصة إلى إدارة الأصول والمرافق</p>','<h2>خدماتنا</h2><p>من أنظمة الطاقة الحرجة والصيانة المتخصصة إلى إدارة الأصول والمرافق</p>',$html);
        }
        $cairo = '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"><style id="unifco-cairo-font">html,body,button,input,select,textarea{font-family:"Cairo",Tahoma,Arial,sans-serif}</style>';
        return response(str_replace('</head>', $cairo.'</head>', $html))->header('X-UNIFCO-Release', 'home-electrical-20260821-12')->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function quote(): View { return view('public.request', ['type' => 'QUOTATION']); }
    public function emergency(): View { return view('public.request', ['type' => 'EMERGENCY_MAINTENANCE']); }

    public function store(Request $request, PublicRequestPipelineService $pipeline): RedirectResponse
    {
        $data = $request->validate([
            'request_intent' => ['required','in:QUOTATION,SERVICE_REQUEST,CONSULTATION'],
            'request_subtype' => ['required','in:SPARE_PARTS_QUOTE,MAINTENANCE_CONTRACT_QUOTE,ROUTINE_MAINTENANCE,URGENT_MAINTENANCE,TECHNICAL_CONSULTATION'],
            'site_name' => ['required','string','max:180'],
            'company_name' => ['required','string','max:180'],
            'responsible_person' => ['required','string','max:180'],
            'mobile' => ['required','string','max:32'],
            'email' => ['required','email','max:180'],
            'site_area' => ['required','string','max:120'],
            'site_city' => ['required','string','max:120'],
            'site_address' => ['nullable','string','max:500'],
            'latitude' => ['nullable','numeric','between:-90,90'],
            'longitude' => ['nullable','numeric','between:-180,180'],
            'asset_type' => ['required','string','max:120'],
            'equipment_brand' => ['nullable','string','max:120'],
            'equipment_model' => ['nullable','string','max:120'],
            'service_category' => ['required','string','max:120'],
            'service_other' => ['nullable','string','max:180'],
            'urgency' => ['required','in:NORMAL,PRIORITY,URGENT,EMERGENCY'],
            'requested_date' => ['required','date','after_or_equal:today'],
            'requested_time' => ['required','date_format:H:i'],
            'details' => ['required','string','max:5000'],
            'equipment_photos' => ['nullable','array','max:6'],
            'equipment_photos.*' => ['image','mimes:jpg,jpeg,png,webp','max:8192'],
            'problem_photos' => ['nullable','array','max:6'],
            'problem_photos.*' => ['image','mimes:jpg,jpeg,png,webp','max:8192'],
            'previous_reports' => ['nullable','array','max:5'],
            'previous_reports.*' => ['file','mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png','max:12288'],
        ]);

        $subtypeIntent = [
            'SPARE_PARTS_QUOTE' => 'QUOTATION',
            'MAINTENANCE_CONTRACT_QUOTE' => 'QUOTATION',
            'ROUTINE_MAINTENANCE' => 'SERVICE_REQUEST',
            'URGENT_MAINTENANCE' => 'SERVICE_REQUEST',
            'TECHNICAL_CONSULTATION' => 'CONSULTATION',
        ];
        abort_unless($subtypeIntent[$data['request_subtype']] === $data['request_intent'], 422, 'Request type and subtype do not match.');

        $data['request_type'] = match ($data['request_subtype']) {
            'SPARE_PARTS_QUOTE','MAINTENANCE_CONTRACT_QUOTE' => 'QUOTATION',
            'URGENT_MAINTENANCE' => 'EMERGENCY_MAINTENANCE',
            'ROUTINE_MAINTENANCE' => 'SERVICE_REQUEST',
            default => 'CONSULTATION',
        };
        $data['service_family'] = strtoupper($data['asset_type']) === 'HVAC' ? 'MEP' : 'ELECTRICAL';
        $data['subject'] = trim((($data['service_other'] ?? null) ?: $data['service_category']).' - '.$data['site_name']);
        if ($data['request_subtype'] === 'URGENT_MAINTENANCE') $data['urgency'] = 'EMERGENCY';

        unset($data['equipment_photos'], $data['problem_photos'], $data['previous_reports']);
        if ($request->hasFile('equipment_photos')) $data['equipment_photo_paths'] = collect($request->file('equipment_photos'))->map(fn($f)=>$f->store('public-requests/equipment','public'))->values()->all();
        if ($request->hasFile('problem_photos')) $data['problem_photo_paths'] = collect($request->file('problem_photos'))->map(fn($f)=>$f->store('public-requests/problems','public'))->values()->all();
        if ($request->hasFile('previous_reports')) $data['previous_report_paths'] = collect($request->file('previous_reports'))->map(fn($f)=>$f->store('public-requests/reports','public'))->values()->all();

        $record = DB::transaction(function () use ($data) {
            $counter = DB::table('public_request_counters')->where('key','public_request_ticket')->lockForUpdate()->first();
            if (! $counter) {
                DB::table('public_request_counters')->insert(['key'=>'public_request_ticket','last_number'=>926000000,'created_at'=>now(),'updated_at'=>now()]);
                $next = 926000001;
            } else {
                $next = ((int) $counter->last_number) + 1;
            }
            DB::table('public_request_counters')->where('key','public_request_ticket')->update(['last_number'=>$next,'updated_at'=>now()]);
            $code = match ($data['request_subtype']) {
                'SPARE_PARTS_QUOTE' => 'UNQ-',
                'MAINTENANCE_CONTRACT_QUOTE' => 'UNM-',
                'ROUTINE_MAINTENANCE' => 'UNRM-',
                'URGENT_MAINTENANCE' => 'UNUM-',
                default => 'UNC-',
            };
            $payload = $data + ['ticket_serial'=>$next,'reference_no'=>$code.$next,'submitted_at'=>now(),'status'=>'NEW'];
            return PublicServiceRequest::create($payload);
        });

        // Ticket issuance is the customer-facing transaction boundary. Downstream
        // CRM/work-order conversion must never turn a successfully issued ticket
        // into a 500 response. Conversion failures are logged for operations and
        // can be retried independently while the customer still receives the receipt.
        try {
            $pipeline->convert($record);
        } catch (Throwable $exception) {
            Log::error('Public request pipeline conversion failed after ticket issuance.', [
                'public_service_request_id' => $record->id,
                'reference_no' => $record->reference_no,
                'request_type' => $record->request_type,
                'request_subtype' => $record->request_subtype,
                'exception' => $exception,
            ]);
        }

        return redirect()->route('public.request.received', ['reference'=>$record->reference_no,'lang'=>$request->input('lang','ar')]);
    }

    public function received(string $reference): View
    {
        $record = PublicServiceRequest::where('reference_no', $reference)->firstOrFail();
        return view('public.received', compact('record'));
    }

    public function adminIndex(): View
    {
        return view('public.admin-requests', ['requests' => PublicServiceRequest::latest('submitted_at')->limit(250)->get()]);
    }
}
