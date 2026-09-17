@php
    $activeSection = 'visits';
    $pageTitle = 'Visits & Schedule';
    $pageDescription = 'Upcoming visits, completed work and customer-visible visit reports in one place.';
    $upcoming = collect($upcomingPlans ?? [])->sortBy('next_due_date');
    $reports = collect($visitReports ?? [])->sortByDesc('visit_date');
    $today = now()->startOfDay();
    $weekEnd = now()->copy()->addDays(7)->endOfDay();
    $monthEnd = now()->copy()->addDays(30)->endOfDay();
    $dueThisWeek = $upcoming->filter(fn($p) => $p->next_due_date && $p->next_due_date->between($today, $weekEnd))->count();
    $dueThisMonth = $upcoming->filter(fn($p) => $p->next_due_date && $p->next_due_date->between($today, $monthEnd))->count();
    $overdueVisits = $upcoming->filter(fn($p) => $p->next_due_date && $p->next_due_date->lt($today))->count();
    $todayVisits = $upcoming->filter(fn($p) => $p->next_due_date && $p->next_due_date->isSameDay($today));
    $calendarStart = now()->copy()->startOfMonth();
    $calendarDays = $calendarStart->daysInMonth;
    $calendarOffset = $calendarStart->dayOfWeek;
@endphp

@push('styles')
<style>
.visits-page{display:grid;gap:14px}.visits-page .portal-page-head{align-items:center;margin-bottom:0}.visit-title{display:flex;align-items:center;gap:12px}.visit-title-icon{width:48px;height:48px;border-radius:14px;background:#edf5ff;color:var(--blue);display:grid;place-items:center}.visit-title-icon .ui-icon{width:25px;height:25px}.visit-head-actions{display:flex;gap:8px;flex-wrap:wrap}.visit-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.visit-kpi{padding:16px;display:grid;grid-template-columns:44px 1fr;gap:12px;align-items:center;min-height:96px}.visit-kpi-icon{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;background:#edf5ff;color:var(--blue)}.visit-kpi-icon.green{background:#e8f8f0;color:var(--green)}.visit-kpi-icon.amber{background:#fff4db;color:var(--amber)}.visit-kpi-icon.red{background:#fdebed;color:var(--red)}.visit-kpi-icon .ui-icon{width:21px;height:21px}.visit-kpi small{font-size:8px;color:var(--muted);font-weight:800}.visit-kpi strong{display:block;font-size:25px;line-height:1;margin:5px 0}.visit-kpi span{font-size:8px;color:var(--muted)}.visit-main-grid{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(360px,.95fr);gap:12px}.visit-card{padding:16px}.visit-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:13px}.visit-card-head h3{font-size:13px;margin:0}.visit-card-head a{font-size:8px;color:var(--blue);font-weight:800}.visit-tabs{display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap}.visit-tab{padding:6px 10px;border-radius:999px;background:#f3f6fa;color:#6b7a90;font-size:8px;font-weight:800}.visit-tab.active{background:#e9f3ff;color:var(--blue)}.visit-list{display:grid}.visit-row{display:grid;grid-template-columns:58px minmax(0,1fr) auto;gap:12px;align-items:center;padding:11px 0;border-bottom:1px solid #edf1f5}.visit-row:last-child{border-bottom:0}.visit-date{height:54px;border-radius:10px;background:#f5f8fc;border:1px solid #e5ecf4;display:grid;place-items:center;text-align:center}.visit-date b{font-size:17px;line-height:1}.visit-date span{font-size:7px;color:var(--muted);margin-top:3px}.visit-row h4{font-size:10px;margin:0 0 5px}.visit-meta{display:flex;gap:10px;flex-wrap:wrap;font-size:8px;color:var(--muted)}.visit-meta span{display:flex;align-items:center;gap:4px}.visit-dot{width:6px;height:6px;border-radius:50%;background:var(--blue)}.visit-row-actions{display:flex;align-items:center;gap:7px}.visit-status{padding:5px 8px;border-radius:999px;background:#eaf3ff;color:var(--blue);font-size:8px;font-weight:800;white-space:nowrap}.visit-status.green{background:#e8f8f0;color:var(--green)}.visit-status.red{background:#fdebed;color:var(--red)}.visit-empty{padding:38px 20px;text-align:center;color:var(--muted);font-size:9px}.visit-empty strong{display:block;color:var(--ink);font-size:12px;margin-bottom:5px}.calendar-shell{padding:16px}.calendar-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}.calendar-top h3{font-size:13px;margin:0}.calendar-month{font-size:11px;font-weight:850}.calendar-grid{display:grid;grid-template-columns:repeat(7,1fr);border:1px solid #e7edf4;border-radius:10px;overflow:hidden}.calendar-weekday{padding:7px;text-align:center;background:#f7f9fc;color:var(--muted);font-size:7px;font-weight:800}.calendar-day{min-height:54px;padding:7px;border-top:1px solid #edf1f5;border-inline-end:1px solid #edf1f5;font-size:8px;position:relative;background:#fff}.calendar-day:nth-child(7n){border-inline-end:0}.calendar-day.blank{background:#fafbfd}.calendar-day.today{background:#edf6ff;color:var(--blue);font-weight:900}.calendar-badges{display:flex;gap:3px;position:absolute;bottom:7px;left:7px}.calendar-badges i{width:5px;height:5px;border-radius:50%;background:var(--blue)}.calendar-badges i.completed{background:var(--green)}.calendar-legend{display:flex;gap:14px;flex-wrap:wrap;margin-top:10px;font-size:8px;color:var(--muted)}.calendar-legend span{display:flex;align-items:center;gap:5px}.calendar-legend i{width:7px;height:7px;border-radius:50%;background:var(--blue)}.calendar-legend i.green{background:var(--green)}.right-stack{display:grid;gap:12px}.today-row{display:grid;grid-template-columns:66px 1fr auto;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid #edf1f5}.today-row:last-child{border-bottom:0}.today-time{font-size:9px;font-weight:900;color:var(--blue)}.today-row strong{font-size:9px}.today-row small{display:block;margin-top:3px;color:var(--muted);font-size:7px}.quick-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.quick-link{min-height:48px;border:1px solid #e3eaf2;border-radius:10px;background:#f8fbff;color:var(--blue);display:flex;align-items:center;gap:8px;padding:10px;font-size:9px;font-weight:800}.quick-link.red{background:#fff4f6;color:var(--red)}.quick-link .ui-icon{width:18px;height:18px}.visit-lower-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}.history-row,.report-row{display:grid;grid-template-columns:30px 1fr auto;gap:9px;align-items:center;padding:9px 0;border-bottom:1px solid #edf1f5}.history-row:last-child,.report-row:last-child{border-bottom:0}.history-icon,.report-icon{width:30px;height:30px;border-radius:9px;background:#eef6ff;color:var(--blue);display:grid;place-items:center}.history-icon.green{background:#e9f8f1;color:var(--green)}.history-icon .ui-icon,.report-icon .ui-icon{width:15px;height:15px}.history-row strong,.report-row strong{font-size:9px}.history-row small,.report-row small{font-size:7px;color:var(--muted);display:block;margin-top:3px}.report-download{font-size:8px;color:var(--blue);font-weight:800}.coverage-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.coverage-box{padding:12px;border:1px solid #edf1f5;background:#f8fafc;border-radius:10px}.coverage-box strong{display:block;font-size:19px}.coverage-box span{font-size:8px;color:var(--muted)}
@media(max-width:1200px){.visit-kpis{grid-template-columns:1fr 1fr}.visit-main-grid{grid-template-columns:1fr}.visit-lower-grid{grid-template-columns:1fr 1fr}}
@media(max-width:760px){.visit-kpis,.visit-lower-grid{grid-template-columns:1fr}.visit-row{grid-template-columns:52px 1fr}.visit-row-actions{grid-column:2}.calendar-day{min-height:45px}.visit-head-actions{width:100%}.visit-head-actions .portal-btn{flex:1}}
</style>
@endpush

@include('customer.partials.portal-shell-open')

<div class="visits-page">
    <div class="portal-page-head">
        <div class="visit-title">
            <div class="visit-title-icon">@include('customer.partials.icon',['name'=>'visits'])</div>
            <div><h2>Visits & Schedule</h2><p>Manage upcoming maintenance visits, review visit history and download customer-visible reports.</p></div>
        </div>
        <div class="visit-head-actions no-print">
            <a class="portal-btn soft" href="{{ route('customer.section','maintenance') }}">Maintenance Plan</a>
            <a class="portal-btn" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">＋ Schedule a Visit</a>
        </div>
    </div>

    <section class="visit-kpis">
        <div class="portal-card visit-kpi"><div class="visit-kpi-icon">@include('customer.partials.icon',['name'=>'visits'])</div><div><small>Upcoming Visits</small><strong>{{ $upcoming->count() }}</strong><span>{{ $dueThisWeek }} within 7 days</span></div></div>
        <div class="portal-card visit-kpi"><div class="visit-kpi-icon green">@include('customer.partials.icon',['name'=>'reports'])</div><div><small>Completed Visits</small><strong>{{ $reports->count() }}</strong><span>Customer-visible visit history</span></div></div>
        <div class="portal-card visit-kpi"><div class="visit-kpi-icon amber">@include('customer.partials.icon',['name'=>'maintenance'])</div><div><small>Due This Month</small><strong>{{ $dueThisMonth }}</strong><span>Next 30 days</span></div></div>
        <div class="portal-card visit-kpi"><div class="visit-kpi-icon red">@include('customer.partials.icon',['name'=>'actions'])</div><div><small>Requires Attention</small><strong>{{ $overdueVisits }}</strong><span>{{ $overdueVisits ? 'Overdue maintenance visits' : 'No overdue visits' }}</span></div></div>
    </section>

    <section class="visit-main-grid">
        <div class="portal-card visit-card">
            <div class="visit-card-head"><h3>Upcoming Visits</h3><a href="{{ route('customer.section','maintenance') }}">View maintenance plan →</a></div>
            <div class="visit-tabs"><span class="visit-tab active">All ({{ $upcoming->count() }})</span><span class="visit-tab">This Week ({{ $dueThisWeek }})</span><span class="visit-tab">Next 30 Days ({{ $dueThisMonth }})</span><span class="visit-tab">Overdue ({{ $overdueVisits }})</span></div>
            <div class="visit-list">
                @forelse($upcoming->take(7) as $plan)
                    @php($due=$plan->next_due_date)
                    <div class="visit-row">
                        <div class="visit-date"><div><b>{{ $due?->format('d') ?? '—' }}</b><span>{{ $due?->format('M Y') ?? 'Not set' }}</span></div></div>
                        <div><h4>{{ $plan->name ?? 'Scheduled maintenance visit' }}</h4><div class="visit-meta"><span><i class="visit-dot"></i>{{ $plan->asset?->site?->name ?: 'Site not assigned' }}</span><span>{{ $plan->asset?->name ?: 'Asset not assigned' }}</span></div></div>
                        <div class="visit-row-actions"><span class="visit-status {{ $due && $due->lt($today) ? 'red' : '' }}">{{ $due && $due->lt($today) ? 'Overdue' : 'Scheduled' }}</span></div>
                    </div>
                @empty
                    <div class="visit-empty"><strong>No upcoming visits</strong>Your preventive maintenance schedule is currently clear.</div>
                @endforelse
            </div>
        </div>

        <div class="right-stack">
            <div class="portal-card calendar-shell">
                <div class="calendar-top"><h3>Visit Calendar</h3><span class="calendar-month">{{ now()->format('M Y') }}</span></div>
                <div class="calendar-grid">
                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)<div class="calendar-weekday">{{ $day }}</div>@endforeach
                    @for($i=0;$i<$calendarOffset;$i++)<div class="calendar-day blank"></div>@endfor
                    @for($day=1;$day<=$calendarDays;$day++)
                        @php
                            $date=$calendarStart->copy()->day($day);
                            $hasUpcoming=$upcoming->contains(fn($p)=>$p->next_due_date && $p->next_due_date->isSameDay($date));
                            $hasReport=$reports->contains(fn($r)=>isset($r->visit_date) && $r->visit_date && $r->visit_date->isSameDay($date));
                        @endphp
                        <div class="calendar-day {{ $date->isToday() ? 'today' : '' }}">{{ $day }}@if($hasUpcoming||$hasReport)<div class="calendar-badges">@if($hasUpcoming)<i></i>@endif @if($hasReport)<i class="completed"></i>@endif</div>@endif</div>
                    @endfor
                </div>
                <div class="calendar-legend"><span><i></i>Scheduled</span><span><i class="green"></i>Completed</span></div>
            </div>

            <div class="portal-card visit-card">
                <div class="visit-card-head"><h3>Today's Schedule</h3><span class="portal-pill">{{ $todayVisits->count() }} visits</span></div>
                @forelse($todayVisits->take(4) as $plan)
                    <div class="today-row"><div class="today-time">Today</div><div><strong>{{ $plan->name }}</strong><small>{{ $plan->asset?->site?->name ?: 'Site not assigned' }}</small></div><span class="visit-status">Scheduled</span></div>
                @empty
                    <div class="visit-empty"><strong>No visits today</strong>There are no scheduled customer visits for today.</div>
                @endforelse
            </div>

            <div class="portal-card visit-card no-print">
                <div class="visit-card-head"><h3>Quick Actions</h3></div>
                <div class="quick-grid"><a class="quick-link" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">@include('customer.partials.icon',['name'=>'visits'])<span>Schedule a Visit</span></a><a class="quick-link red" href="{{ route('customer.section','work-orders') }}">@include('customer.partials.icon',['name'=>'work-orders'])<span>View Work Orders</span></a><a class="quick-link" href="{{ route('customer.section','spare-parts') }}">@include('customer.partials.icon',['name'=>'parts'])<span>Request Spare Parts</span></a><a class="quick-link" href="{{ route('customer.inbox') }}">@include('customer.partials.icon',['name'=>'inbox'])<span>Contact Support</span></a></div>
            </div>
        </div>
    </section>

    <section class="visit-lower-grid">
        <div class="portal-card visit-card">
            <div class="visit-card-head"><h3>Recent Visits</h3><a href="{{ route('customer.section','timeline') }}">View activity →</a></div>
            @forelse($reports->take(5) as $report)
                <div class="history-row"><div class="history-icon green">@include('customer.partials.icon',['name'=>'visits'])</div><div><strong>{{ $report->visit_type ?: 'Completed visit' }}</strong><small>{{ $report->report_no }} · {{ $report->technician_name ?: 'UNIFCO team' }}</small></div><span class="portal-pill green">Completed</span></div>
            @empty<div class="visit-empty"><strong>No completed visits yet</strong>Completed visit history will appear here.</div>@endforelse
        </div>

        <div class="portal-card visit-card">
            <div class="visit-card-head"><h3>Visit Reports</h3><a href="{{ route('customer.section','reports') }}">View reports →</a></div>
            @forelse($reports->take(5) as $report)
                <div class="report-row"><div class="report-icon">@include('customer.partials.icon',['name'=>'documents'])</div><div><strong>{{ $report->report_no }} · {{ $report->visit_type }}</strong><small>{{ $report->visit_date?->format('d M Y') ?? 'Completed visit' }}</small></div><a class="report-download" href="{{ route('customer.visits.pdf',$report) }}">Download</a></div>
            @empty<div class="visit-empty"><strong>No reports available</strong>Customer-visible reports will appear after completed visits.</div>@endforelse
        </div>

        <div class="portal-card visit-card">
            <div class="visit-card-head"><h3>Maintenance Coverage</h3><a href="{{ route('customer.section','assets') }}">View assets →</a></div>
            <div class="coverage-grid"><div class="coverage-box"><strong>{{ collect($sites ?? [])->count() }}</strong><span>Customer Sites</span></div><div class="coverage-box"><strong>{{ collect($assets ?? [])->count() }}</strong><span>Assets & Equipment</span></div><div class="coverage-box"><strong>{{ $dueThisMonth }}</strong><span>Visits Due in 30 Days</span></div><div class="coverage-box"><strong>{{ $reports->count() }}</strong><span>Completed Visit Reports</span></div></div>
        </div>
    </section>
</div>

@include('customer.partials.portal-shell-close')
