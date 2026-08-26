@extends('layouts.app')
@section('title',$profile['title'].' · UNIFCO')
@section('heading',$profile['title'])
@section('content')
<style>
.role-hero{background:linear-gradient(135deg,#071f4d,#123d72);color:#fff;border-radius:16px;padding:20px;display:flex;justify-content:space-between;gap:18px;align-items:center}.role-hero h2{margin:5px 0;font-size:23px}.role-hero p{margin:0;color:#ced9e8;font-size:11px}.role-chip{display:inline-flex;padding:5px 9px;border-radius:999px;background:#ffffff18;font-size:9px;font-weight:900}.role-user{font-size:10px;color:#dbe5f1;text-align:right}.role-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:14px 0}.role-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px}.role-kpi span{display:block;font-size:9px;color:#758399;font-weight:750}.role-kpi b{display:block;font-size:26px;color:#071f4d;margin-top:7px}.role-grid{display:grid;grid-template-columns:1.35fr .65fr;gap:12px}.role-box{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px}.role-box h3{margin:0 0 12px;font-size:14px;color:#071f4d}.approval-card,.queue-card{border:1px solid #e7ebf1;border-radius:10px;padding:12px;margin-bottom:8px}.approval-head,.queue-head{display:flex;justify-content:space-between;gap:10px}.approval-card b,.queue-card b{font-size:11px}.approval-card small,.queue-card small{display:block;color:#748197;font-size:9px;margin-top:4px}.approval-meta{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}.badge{display:inline-flex;padding:4px 7px;border-radius:999px;font-size:8px;font-weight:850;background:#edf3fb;color:#275f9c}.badge.red{background:#fde8ec;color:#b51e38}.badge.amber{background:#fff3df;color:#986000}.badge.green{background:#e8f7ee;color:#167448}.role-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.empty-role{padding:18px;text-align:center;color:#7d899a;font-size:10px}.decision{padding:9px 0;border-bottom:1px solid #edf1f5}.decision:last-child{border-bottom:0}.decision b{font-size:10px}.decision small{display:block;color:#7d899a;font-size:8px;margin-top:3px}.decision-form{margin-top:10px;padding-top:9px;border-top:1px dashed #e2e8f0}.decision-form textarea{width:100%;min-height:54px;font-size:10px;margin-bottom:7px}.decision-buttons{display:flex;gap:6px;flex-wrap:wrap}.decision-buttons button{border:0;border-radius:7px;padding:7px 10px;font-size:9px;font-weight:800;cursor:pointer}.approve{background:#e6f7ed;color:#126f42}.return{background:#fff1d9;color:#915c00}.reject{background:#fde8ec;color:#ad2037}.queue-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.queue-card{margin:0}.queue-card a{display:inline-flex;margin-top:8px;font-size:9px;font-weight:800;color:#1e315b}.section-gap{margin-top:12px}@media(max-width:1000px){.role-grid{grid-template-columns:1fr}.queue-grid{grid-template-columns:1fr}.role-kpis{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.role-hero{align-items:flex-start;flex-direction:column}.role-user{text-align:left}.role-kpis{grid-template-columns:1fr}}
</style>
<section class="role-hero">
  <div><span class="role-chip">{{ $profile['accent'] }}</span><h2>{{ $profile['title'] }}</h2><p>{{ $profile['subtitle'] }}</p></div>
  <div class="role-user"><b>{{ $user->name }}</b><br>{{ $user->email }}<br><span>{{ str_replace('_',' ',$role) }}</span></div>
</section>
<section class="role-kpis">
@foreach($metrics as $metric)
<div class="role-kpi"><span>{{ $metric['label'] }}</span><b>@if($metric['money']??false){{ number_format((float)$metric['value'],2) }} <small>SAR</small>@else{{ number_format((float)$metric['value'],0) }}@endif</b></div>
@endforeach
</section>

<section class="role-box">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px"><h3>{{ $profile['queue'] }}</h3><span class="badge">{{ $workQueue->count() }} active items</span></div>
  <div class="queue-grid">
    @forelse($workQueue as $item)
      <div class="queue-card"><div class="queue-head"><div><span class="badge">{{ $item['type'] }}</span><b style="display:block;margin-top:7px">{{ $item['title'] }}</b><small>{{ $item['meta'] }}</small></div><span class="badge amber">{{ $item['state'] }}</span></div><a href="{{ $item['url'] }}">Open related workspace →</a></div>
    @empty<div class="empty-role" style="grid-column:1/-1">No active work items for this role right now.</div>@endforelse
  </div>
</section>

<div class="role-grid section-gap">
<section class="role-box">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px"><h3>My Pending Approvals</h3><div><span class="badge">Waiting later: {{ $waitingApprovals }}</span> @if($breachedApprovals)<span class="badge red">SLA breached: {{ $breachedApprovals }}</span>@endif</div></div>
  @forelse($pendingApprovals as $approval)
    @php($sr=$approval->entity_type===\App\Models\ServiceRequest::class ? $serviceRequests->get($approval->entity_id) : null)
    <div class="approval-card">
      <div class="approval-head"><div><b>{{ str_replace('_',' ',$approval->action) }}</b><small>{{ class_basename($approval->entity_type) }} #{{ $approval->entity_id }} @if($sr) · {{ $sr->request_no }} · {{ $sr->subject }} @endif</small></div><span class="badge {{ $approval->due_at && $approval->due_at->isPast()?'red':'amber' }}">{{ $approval->due_at ? $approval->due_at->diffForHumans() : 'No deadline' }}</span></div>
      @if($sr)
        <div class="approval-meta"><span class="badge">{{ $sr->request_type }}</span><span class="badge">{{ $sr->priority }}</span><span class="badge">{{ str_replace('_',' ',$sr->eligibility) }}</span><span class="badge">{{ $sr->company_name }}</span></div>
        <small style="margin-top:8px">{{ \Illuminate\Support\Str::limit($sr->details,220) }}</small>
      @endif
      <form class="decision-form" method="POST" action="{{ route('workflow.approvals.decide',$approval) }}">@csrf
        <textarea name="note" placeholder="Decision note. Required for Return or Reject."></textarea>
        <div class="decision-buttons">
          <button class="approve" name="decision" value="APPROVED">Approve</button>
          <button class="return" name="decision" value="RETURNED">Return for correction</button>
          <button class="reject" name="decision" value="REJECTED">Reject</button>
        </div>
      </form>
    </div>
  @empty<div class="empty-role">No approvals are waiting for this role right now.</div>@endforelse
  <div class="role-actions"><a class="btn" href="{{ route('workflow.approvals.index') }}">Open My Approval Inbox</a></div>
</section>
<section class="role-box"><h3>Recent Decisions</h3>@forelse($recentDecisions as $decision)<div class="decision"><b>{{ str_replace('_',' ',$decision->action) }} · {{ $decision->status }}</b><small>{{ $decision->decided_at?->format('Y-m-d H:i') }} · {{ $decision->decision_note ?: 'No note' }}</small></div>@empty<div class="empty-role">No decisions recorded for this role yet.</div>@endforelse</section>
</div>
@endsection
