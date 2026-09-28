@extends('layouts.app')
@section('title','Public Requests · UNIFCO')
@section('heading','Public Requests')
@section('content')
<div class="card">
    <div class="page-head">
        <div><h2 style="margin:0">Website Requests</h2><p class="muted">Guest quotation and maintenance submissions.</p></div>
        <a class="btn secondary" href="{{ route('public.home') }}" target="_blank">Open public site</a>
    </div>
    <table>
        <thead><tr><th>Reference</th><th>Type</th><th>Company</th><th>Service</th><th>Contact</th><th>Requested</th><th>Status</th><th>Submitted</th></tr></thead>
        <tbody>
        @forelse($requests as $r)
            @php
                // Historic intake rows can contain an invalid date. Reading the
                // Eloquent date cast would throw and break the entire inbox.
                $rawRequestedDate = (string) $r->getRawOriginal('requested_date');
                $requestedDate = preg_match('/^\d{4}-\d{2}-\d{2}(?:\s|$)/', $rawRequestedDate)
                    ? substr($rawRequestedDate, 0, 10) : null;
                $rawSubmittedAt = (string) $r->getRawOriginal('submitted_at');
                $submittedAt = preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $rawSubmittedAt)
                    ? substr($rawSubmittedAt, 0, 16) : '—';
                $requestType = match (strtoupper((string) $r->request_type)) {
                    'EMERGENCY_MAINTENANCE' => 'Emergency Maintenance',
                    'MAINTENANCE', 'SERVICE_REQUEST' => 'Maintenance',
                    'CONSULTATION' => 'Consultation',
                    default => 'Quotation',
                };
            @endphp
            <tr>
                <td><strong>{{ $r->reference_no }}</strong><br><small>{{ $r->subject }}</small></td>
                <td>{{ $requestType }}</td>
                <td>{{ $r->company_name }}<br><small>CR: {{ $r->commercial_registration }}</small></td>
                <td>{{ $r->service_category }}@if($r->site_city)<br><small>{{ $r->site_city }}</small>@endif</td>
                <td>@if($r->responsible_person)<strong>{{ $r->responsible_person }}</strong><br>@endif{{ $r->email }}<br>{{ $r->mobile }}</td>
                <td>{{ $requestedDate ? $requestedDate.' '.$r->requested_time : '—' }}</td>
                <td><span class="pill">{{ $r->status }}</span></td>
                <td>{{ $submittedAt }}</td>
            </tr>
            <tr><td colspan="8" class="muted">
                <div style="display:grid;gap:5px">
                    <div>{{ $r->details }}</div>
                    @if($r->site_address)<div><strong>Location:</strong> {{ $r->site_address }}
                        @if($r->latitude && $r->longitude)<a href="https://www.openstreetmap.org/?mlat={{ $r->latitude }}&mlon={{ $r->longitude }}#map=18/{{ $r->latitude }}/{{ $r->longitude }}" target="_blank">Open map</a>@endif
                    </div>@endif
                    @if($r->equipment_image_path)<div><strong>Equipment image:</strong> <a href="{{ asset('storage/'.$r->equipment_image_path) }}" target="_blank">View</a></div>@endif
                    @if($r->supporting_image_paths)<div><strong>Supporting images:</strong>
                        @foreach($r->supporting_image_paths as $index => $path)<a href="{{ asset('storage/'.$path) }}" target="_blank">Image {{ $index + 1 }}</a>@if(!$loop->last) · @endif @endforeach
                    </div>@endif
                </div>
            </td></tr>
        @empty
            <tr><td colspan="8">No public requests yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
