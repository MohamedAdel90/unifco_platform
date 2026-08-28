@extends('layouts.app')

@section('title', $asset->asset_code.' · Warranty & Insurance')
@section('heading', 'Warranty & Insurance')

@section('content')
<style>
.card{background:#fff;border:1px solid #e1e6ee;border-radius:12px;padding:16px;margin-bottom:12px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.form{display:grid;grid-template-columns:1fr 1fr;gap:7px}.form input,.form select,.form textarea{padding:8px;border:1px solid #d7e0eb;border-radius:6px}.wide{grid-column:1/-1}.btn{border:0;border-radius:7px;background:#06275c;color:#fff;padding:8px 10px;font-weight:800}.row{padding:9px 0;border-bottom:1px solid #edf1f5;font-size:12px}.pill{display:inline-flex;padding:4px 8px;border-radius:999px;background:#edf3fb;color:#285f99;font-size:10px;font-weight:800}.warn{background:#fff4dc;color:#8b5c00}.bad{background:#ffeaea;color:#963434}.notice{background:#e9f7ef;color:#176940;padding:10px;border-radius:8px;margin-bottom:12px}@media(max-width:800px){.grid,.form{grid-template-columns:1fr}.wide{grid-column:auto}}
</style>

@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
<div class="card">
    <a href="{{ route('asset-master.show',$asset) }}">← Asset 360</a>
    <h2>{{ $asset->asset_code }} · {{ $asset->name }}</h2>
    <span class="pill">Phase D · EAM-05</span>
    @if($expiringSoon->isNotEmpty()) <span class="pill warn">{{ $expiringSoon->count() }} expiring within 30 days</span> @endif
    @if($expired->isNotEmpty()) <span class="pill bad">{{ $expired->count() }} expired</span> @endif
</div>

<div class="grid">
<section class="card">
<h3>Add Warranty / Insurance Coverage</h3>
<form class="form" method="POST" action="{{ route('asset-master.coverage.store',$asset) }}">@csrf
<select name="coverage_type" required><option value="WARRANTY">Warranty</option><option value="INSURANCE">Insurance</option></select>
<input name="provider" required placeholder="Provider / insurer">
<input name="reference_no" placeholder="Policy / warranty reference">
<input type="date" name="starts_at" required>
<input type="date" name="expires_at" required>
<input type="number" step="0.01" min="0" name="coverage_amount" placeholder="Coverage amount">
<input name="currency" maxlength="3" placeholder="SAR">
<textarea class="wide" name="scope" placeholder="Coverage scope / exclusions"></textarea>
<button class="btn wide">Record Coverage</button>
</form>
</section>

<section class="card">
<h3>Coverage Register & Expiry Alerts</h3>
@forelse($coverages as $coverage)
<div class="row">
    <b>{{ $coverage->coverage_type }}</b> · {{ $coverage->provider }} · {{ $coverage->reference_no ?: 'No reference' }}<br>
    {{ $coverage->starts_at->format('Y-m-d') }} → {{ $coverage->expires_at->format('Y-m-d') }} · {{ $coverage->status }}
    @if($coverage->isExpired()) <span class="pill bad">EXPIRED</span> @elseif($coverage->expiresSoon()) <span class="pill warn">EXPIRING SOON</span> @endif
    <details><summary>Renew / Claim / History</summary>
        <form class="form" method="POST" action="{{ route('asset-master.coverage.renew',[$asset,$coverage]) }}" style="margin-top:8px">@csrf
            <input type="date" name="starts_at" required><input type="date" name="expires_at" required>
            <input name="reference_no" placeholder="Renewal reference"><input type="number" step="0.01" name="coverage_amount" placeholder="Coverage amount">
            <button class="btn wide">Renew Coverage</button>
        </form>
        <form class="form" method="POST" action="{{ route('asset-master.coverage.claims.store',[$asset,$coverage]) }}" style="margin-top:8px">@csrf
            <input name="claim_no" placeholder="Claim number"><input type="date" name="claim_date" required>
            <input type="number" step="0.01" min="0" name="claimed_amount" placeholder="Claimed amount"><textarea name="description" required placeholder="Claim description"></textarea>
            <button class="btn wide">Submit Claim</button>
        </form>
        @foreach($coverage->claims as $claim)
            <div class="row">Claim {{ $claim->claim_no ?: '#'.$claim->id }} · {{ $claim->status }} · {{ $claim->claim_date->format('Y-m-d') }}
            @if($canApprove && $claim->status==='SUBMITTED')
                <form method="POST" action="{{ route('asset-master.coverage.claims.review',[$asset,$claim]) }}">@csrf
                    <input type="number" step="0.01" name="approved_amount" placeholder="Approved amount"><input name="resolution_notes" placeholder="Review notes">
                    <button class="btn" name="decision" value="APPROVE">Approve</button><button class="btn" name="decision" value="REJECT">Reject</button>
                </form>
            @endif
            </div>
        @endforeach
    </details>
</div>
@empty <p>No warranty or insurance coverage recorded.</p> @endforelse
</section>
</div>
@endsection
