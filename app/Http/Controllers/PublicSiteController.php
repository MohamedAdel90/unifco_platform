<?php

namespace App\Http\Controllers;

use App\Models\PublicServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function home(): View
    {
        return view('public.home');
    }

    public function quote(): View
    {
        return view('public.request', ['type' => 'QUOTATION']);
    }

    public function emergency(): View
    {
        return view('public.request', ['type' => 'EMERGENCY_MAINTENANCE']);
    }

    public function store(Request $request): RedirectResponse
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
