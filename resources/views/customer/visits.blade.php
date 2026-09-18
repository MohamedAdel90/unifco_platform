@php
    $activeSection = 'visits';
    $pageTitle = 'Visits & Schedule';
    $pageDescription = 'Manage upcoming maintenance visits, view history, and stay informed about scheduled service activity.';

    $upcoming = collect($upcomingPlans ?? [])->sortBy('next_due_date')->values();
    $reports = collect($visitReports ?? [])->sortByDesc('visit_date')->values();
    $today = now()->startOfDay();
    $weekEnd = now()->copy()->addDays(7)->endOfDay();
    $nextWeekEnd = now()->copy()->addDays(14)->endOfDay();

    $dueThisWeek = $upcoming->filter(fn($p) => $p->next_due_date && $p->next_due_date->between($today, $weekEnd))->count();
    $dueNextWeek = $upcoming->filter(fn($p) => $p->next_due_date && $p->next_due_date->gt($weekEnd) && $p->next_due_date->lte($nextWeekEnd))->count();
    $overdueVisits = $upcoming->filter(fn($p) => $p->next_due_date && $p->next_due_date->lt($today))->count();
    $todayVisits = $upcoming->filter(fn($p) => $p->next_due_date && $p->next_due_date->isSameDay($today));
    $inProgress = $upcoming->filter(fn($p) => in_array(strtolower((string)($p->status ?? '')), ['in progress','in_progress','active']))->count();

    $calendarStart = now()->copy()->startOfMonth();
    $calendarDays = $calendarStart->daysInMonth;
    $calendarOffset = $calendarStart->dayOfWeek;

    $technicians = $reports
        ->filter(fn($r) => filled($r->technician_name ?? null))
        ->unique(fn($r) => strtolower(trim((string)$r->technician_name)))
        ->take(4)
        ->values();
@endphp

@push('styles')
<style>
.visits-page{display:grid;gap:12px}.visits-page .portal-page-head{align-items:center;margin-bottom:0}.visit-title{display:flex;align-items:center;gap:12px}.visit-title-icon{width:48px;height:48px;border-radius:14px;background:#edf5ff;color:var(--blue);display:grid;place-items:center;border:1px solid #dce9f8}.visit-title-icon .ui-icon{width:25px;height:25px}.visit-head-actions{display:flex;gap:8px;flex-wrap:wrap}.visit-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.visit-kpi{padding:16px;display:grid;grid-template-columns:44px 1fr;gap:12px;align-items:center;min-height:96px}.visit-kpi-icon{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;background:#eaf4ff;color:var(--blue)}.visit-kpi-icon.green{background:#e8f8f0;color:var(--green)}.visit-kpi-icon.red{background:#fdebed;color:var(--red)}.visit-kpi-icon.amber{background:#fff4db;color:var(--amber)}.visit-kpi-icon .ui-icon{width:22px;height:22px}.visit-kpi small{font-size:8px;color:var(--muted);font-weight:800}.visit-kpi strong{display:block;font-size:25px;line-height:1;margin:5px 0}.visit-kpi span{font-size:8px;color:var(--muted)}.visit-kpi .positive{color:var(--green)}.visit-kpi .danger{color:var(--red)}
.visit-command-grid{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(360px,.9fr) minmax(330px,.85fr);gap:12px}.visit-card{padding:16px}.visit-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:13px}.visit-card-head h3{font-size:13px;margin:0;display:flex;align-items:center;gap:7px}.visit-card-head h3 .ui-icon{width:17px;height:17px}.visit-card-head a{font-size:8px;color:var(--blue);font-weight:800}.visit-tabs{display:grid;grid-template-columns:repeat(4,1fr);border-bottom:1px solid #edf1f5;margin-bottom:3px}.visit-tab{padding:9px 8px;text-align:center;font-size:8px;color:#6b7a90;font-weight:800;border-bottom:2px solid transparent}.visit-tab.active{color:var(--blue);border-bottom-color:var(--blue);background:#f3f8ff}.visit-list{display:grid}.visit-row{display:grid;grid-template-columns:58px minmax(0,1fr) auto 18px;gap:12px;align-items:center;padding:11px 0;border-bottom:1px solid #edf1f5}.visit-row:last-child{border-bottom:0}.visit-date{height:55px;border-radius:10px;background:#f4f8fd;border:1px solid #e2eaf3;display:grid;place-items:center;text-align:center}.visit-date b{font-size:17px;line-height:1}.visit-date span{font-size:7px;color:var(--muted);margin-top:3px}.visit-row h4{font-size:10px;margin:0 0 5px}.visit-meta{display:flex;gap:12px;flex-wrap:wrap;font-size:8px;color:var(--muted)}.visit-meta span{display:flex;align-items:center;gap:4px}.visit-status{padding:5px 8px;border-radius:999px;background:#eaf3ff;color:var(--blue);font-size:8px;font-weight:800;white-space:nowrap}.visit-status.green{background:#e8f8f0;color:var(--green)}.visit-status.red{background:#fdebed;color:var(--red)}.visit-status.amber{background:#fff4db;color:var(--amber)}.visit-more{color:var(--muted);font-weight:900}.visit-empty{padding:38px 20px;text-align:center;color:var(--muted);font-size:9px}.visit-empty strong{display:block;color:var(--ink);font-size:12px;margin-bottom:5px}
.calendar-shell{padding:16px}.calendar-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}.calendar-top h3{font-size:13px;margin:0;display:flex;align-items:center;gap:7px}.calendar-top h3 .ui-icon{width:17px;height:17px}.calendar-month{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:850}.calendar-today{border:1px solid #dfe6ef;border-radius:7px;padding:5px 11px;color:var(--blue);font-size:8px;font-weight:800;background:#fff}.calendar-grid{display:grid;grid-template-columns:repeat(7,1fr);border:1px solid #e7edf4;border-radius:10px;overflow:hidden}.calendar-weekday{padding:7px;text-align:center;background:#f7f9fc;color:var(--muted);font-size:7px;font-weight:800}.calendar-day{min-height:49px;padding:7px;border-top:1px solid #edf1f5;border-inline-end:1px solid #edf1f5;font-size:8px;position:relative;background:#fff;text-align:center}.calendar-day:nth-child(7n){border-inline-end:0}.calendar-day.blank{background:#fafbfd}.calendar-day.today{color:#fff;font-weight:900}.calendar-day.today:before{content:"";position:absolute;inset:7px;border-radius:50%;background:var(--blue);z-index:0}.calendar-day>span{position:relative;z-index:1}.calendar-badges{display:flex;gap:3px;position:absolute;bottom:7px;left:50%;transform:translateX(-50%);z-index:2}.calendar-badges i{width:5px;height:5px;border-radius:50%;background:var(--blue)}.calendar-badges i.completed{background:var(--green)}.calendar-badges i.overdue{background:var(--red)}.calendar-day.today .calendar-badges i{box-shadow:0 0 0 1px #fff}.calendar-legend{display:flex;gap:13px;flex-wrap:wrap;margin-top:10px;font-size:8px;color:var(--muted)}.calendar-legend span{display:flex;align-items:center;gap:5px}.calendar-legend i{width:7px;height:7px;border-radius:50%;background:var(--blue)}.calendar-legend i.green{background:var(--green)}.calendar-legend i.red{background:var(--red)}
.today-row{display:grid;grid-template-columns:65px 1fr auto;gap:10px;align-items:center;padding:11px 0;border-bottom:1px solid #edf1f5}.today-row:last-child{border-bottom:0}.today-time{font-size:9px;font-weight:900;color:var(--blue)}.today-row strong{font-size:9px}.today-row small{display:block;margin-top:3px;color:var(--muted);font-size:7px}.right-stack{display:grid;gap:12px}.quick-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.quick-link{min-height:51px;border:1px solid #e3eaf2;border-radius:10px;background:#f7fbff;color:var(--blue);display:flex;align-items:center;gap:8px;padding:10px;font-size:9px;font-weight:800}.quick-link.red{background:#fff5f6;color:var(--red)}.quick-link .ui-icon{width:18px;height:18px}
.visit-lower-grid{display:grid;grid-template-columns:1.05fr 1.05fr 1fr;gap:12px}.history-row,.report-row,.tech-row{display:grid;grid-template-columns:32px 1fr auto;gap:9px;align-items:center;padding:9px 0;border-bottom:1px solid #edf1f5}.history-row:last-child,.report-row:last-child,.tech-row:last-child{border-bottom:0}.history-icon,.report-icon,.tech-avatar{width:32px;height:32px;border-radius:50%;background:#eef6ff;color:var(--blue);display:grid;place-items:center;font-weight:900;font-size:8px}.history-icon{border-radius:9px}.history-icon.green{background:#e9f8f1;color:var(--green)}.history-icon .ui-icon,.report-icon .ui-icon{width:15px;height:15px}.history-row strong,.report-row strong,.tech-row strong{font-size:9px}.history-row small,.report-row small,.tech-row small{font-size:7px;color:var(--muted);display:block;margin-top:3px}.report-download{font-size:8px;color:var(--blue);font-weight:800;border:1px solid #dfe7f0;padding:5px 9px;border-radius:7px}.tech-state{font-size:7px;padding:4px 7px;border-radius:999px;background:#e8f8f0;color:var(--green);font-weight:800}.tech-empty{padding:24px 12px;text-align:center;color:var(--muted);font-size:8px}
@media(max-width:1350px){.visit-command-grid{grid-template-columns:1.2fr .9fr}.visit-command-grid>.right-stack{grid-column:1/-1;grid-template-columns:1fr 1fr}.visit-lower-grid{grid-template-columns:1fr 1fr}}@media(max-width:900px){.visit-kpis{grid-template-columns:1fr 1fr}.visit-command-grid{grid-template-columns:1fr}.visit-command-grid>.right-stack{grid-column:auto;grid-template-columns:1fr}.visit-lower-grid{grid-template-columns:1fr}}@media(max-width:620px){.visit-kpis{grid-template-columns:1fr}.visit-row{grid-template-columns:52px 1fr}.visit-row .visit-status,.visit-row .visit-more{grid-column:2}.visit-tabs{grid-template-columns:1fr 1fr}.quick-grid{grid-template-columns:1fr}.visit-head-actions{width:100%}.visit-head-actions .portal-btn{flex:1}}
</style>
@endpush

@include('customer.partials.portal-shell-open')

<div class="visits-page">
    <div class="portal-page-head">
        <div class="visit-title">
            <div class="visit-title-icon">@include('customer.partials.icon',['name'=>'visits'])</div>
            <div><h2>Visits & Schedule</h2><p>Manage your upcoming maintenance visits, view history, and stay informed about scheduled service activity.</p></div>
        </div>
        <div class="visit-head-actions no-print">
            <a class="portal-btn" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">@include('customer.partials.icon',['name'=>'visits']) Schedule a Visit</a>
        </div>
    </div>

    <section class="visit-kpis">
        <div class="portal-card visit-kpi"><div class="visit-kpi-icon">@include('customer.partials.icon',['name'=>'visits'])</div><div><small>Upcoming Visits</small><strong>{{ $upcoming->count() }}</strong><span class="positive">↑ {{ $dueThisWeek }} this week</span></div></div>
        <div class="portal-card visit-kpi"><div class="visit-kpi-icon green">@include('customer.partials.icon',['name'=>'reports'])</div><div><small>Completed Visits</small><strong>{{ $reports->count() }}</strong><span>Customer-visible visit history</span></div></div>
        <div class="portal-card visit-kpi"><div class="visit-kpi-icon red">@include('customer.partials.icon',['name'=>'work-orders'])</div><div><small>In Progress</small><strong>{{ $inProgress }}</strong><span>Active scheduled work</span></div></div>
        <div class="portal-card visit-kpi"><div class="visit-kpi-icon amber">@include('customer.partials.icon',['name'=>'actions'])</div><div><small>Requires Attention</small><strong>{{ $overdueVisits }}</strong><span class="{{ $overdueVisits ? 'danger' : '' }}">{{ $overdueVisits ? 'Needs follow-up' : 'No overdue visits' }}</span></div></div>
    </section>

    <section class="visit-command-grid">
        <div class="portal-card visit-card">
            <div class="visit-card-head"><h3>@include('customer.partials.icon',['name'=>'visits']) Upcoming Visits</h3><a href="{{ route('customer.section','maintenance') }}">View all visits →</a></div>
            <div class="visit-tabs"><span class="visit-tab active">All ({{ $upcoming->count() }})</span><span class="visit-tab">This Week ({{ $dueThisWeek }})</span><span class="visit-tab">Next Week ({{ $dueNextWeek }})</span><span class="visit-tab">Overdue ({{ $overdueVisits }})</span></div>
            <div class="visit-list">
                @forelse($upcoming->take(5) as $plan)
                    @php($due=$plan->next_due_date)
                    <div class="visit-row">
                        <div class="visit-date"><div><b>{{ $due?->format('d') ?? '—' }}</b><span>{{ $due?->format('M Y') ?? 'Not set' }}</span></div></div>
                        <div><h4>{{ $plan->name ?? 'Scheduled maintenance visit' }}</h4><div class="visit-meta"><span>◷ {{ $plan->preferred_time ?? 'Scheduled time' }}</span><span>⌖ {{ $plan->asset?->site?->name ?: 'Site not assigned' }}</span></div></div>
                        <span class="visit-status {{ $due && $due->lt($today) ? 'red' : '' }}">{{ $due && $due->lt($today) ? 'Overdue' : 'Scheduled' }}</span>
                        <span class="visit-more">⋮</span>
                    </div>
                @empty
                    <div class="visit-empty"><strong>No upcoming visits</strong>Your preventive maintenance schedule is currently clear.</div>
                @endforelse
            </div>
        </div>

        <div class="portal-card calendar-shell">
            <div class="calendar-top"><h3>@include('customer.partials.icon',['name'=>'visits']) Calendar</h3><div class="calendar-month">{{ now()->format('M Y') }} <span class="calendar-today">Today</span></div></div>
            <div class="calendar-grid">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $weekday)
                    <div class="calendar-weekday">{{ $weekday }}</div>
                @endforeach
                @foreach(array_fill(0, $calendarOffset, null) as $blank)
                    <div class="calendar-day blank"></div>
                @endforeach
                @foreach(range(1, $calendarDays) as $calendarDay)
                    @php
                        $date = $calendarStart->copy()->day($calendarDay);
                        $hasUpcoming = $upcoming->contains(fn ($plan) => $plan->next_due_date && $plan->next_due_date->isSameDay($date));
                        $hasReport = $reports->contains(fn ($report) => $report->visit_date && $report->visit_date->isSameDay($date));
                        $hasOverdue = $upcoming->contains(fn ($plan) => $plan->next_due_date && $plan->next_due_date->isSameDay($date) && $plan->next_due_date->lt($today));
                    @endphp
                    <div class="calendar-day {{ $date->isToday() ? 'today' : '' }}">
                        <span>{{ $calendarDay }}</span>
                        @if($hasUpcoming || $hasReport || $hasOverdue)
                            <div class="calendar-badges">
                                @if($hasUpcoming)<i></i>@endif
                                @if($hasReport)<i class="completed"></i>@endif
                                @if($hasOverdue)<i class="overdue"></i>@endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="calendar-legend"><span><i></i>Scheduled</span><span><i class="green"></i>Completed</span><span><i class="red"></i>Requires Attention</span></div>
        </div>

        <div class="right-stack">
            <div class="portal-card visit-card">
                <div class="visit-card-head"><h3>@include('customer.partials.icon',['name'=>'visits']) Today's Schedule</h3><span class="portal-pill">{{ $todayVisits->count() }} Visits</span></div>
                @forelse($todayVisits->take(4) as $plan)
                    <div class="today-row"><div class="today-time">{{ $plan->preferred_time ?? 'Today' }}</div><div><strong>{{ $plan->name }}</strong><small>{{ $plan->asset?->site?->name ?: 'Site not assigned' }}</small></div><span class="visit-status">Scheduled</span></div>
                @empty
                    <div class="visit-empty"><strong>No visits today</strong>There are no scheduled customer visits for today.</div>
                @endforelse
            </div>
            <div class="portal-card visit-card no-print">
                <div class="visit-card-head"><h3>@include('customer.partials.icon',['name'=>'actions']) Quick Actions</h3></div>
                <div class="quick-grid">
                    <a class="quick-link" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">@include('customer.partials.icon',['name'=>'visits'])<span>Schedule a Visit</span></a>
                    <a class="quick-link red" href="{{ route('customer.section','work-orders') }}">@include('customer.partials.icon',['name'=>'work-orders'])<span>View Work Orders</span></a>
                    <a class="quick-link" href="{{ route('customer.section','spare-parts') }}">@include('customer.partials.icon',['name'=>'parts'])<span>Request Spare Parts</span></a>
                    <a class="quick-link" href="{{ route('customer.inbox') }}">@include('customer.partials.icon',['name'=>'inbox'])<span>Contact Support</span></a>
                </div>
            </div>
        </div>
    </section>

    <section class="visit-lower-grid">
        <div class="portal-card visit-card">
            <div class="visit-card-head"><h3>@include('customer.partials.icon',['name'=>'visits']) Recent Visits</h3><a href="{{ route('customer.section','timeline') }}">View all activity →</a></div>
            @forelse($reports->take(4) as $report)
                <div class="history-row"><div class="history-icon green">@include('customer.partials.icon',['name'=>'visits'])</div><div><strong>{{ $report->visit_type ?: 'Completed visit' }}</strong><small>{{ $report->report_no }} · {{ $report->visit_date?->format('d M Y') ?? 'Completed' }}</small></div><span class="portal-pill green">Completed</span></div>
            @empty<div class="visit-empty"><strong>No completed visits yet</strong>Completed visit history will appear here.</div>@endforelse
        </div>

        <div class="portal-card visit-card">
            <div class="visit-card-head"><h3>@include('customer.partials.icon',['name'=>'users']) Technician Schedule</h3><span></span></div>
            @forelse($technicians as $report)
                @php($name=trim((string)$report->technician_name))
                <div class="tech-row"><div class="tech-avatar">{{ strtoupper(collect(preg_split('/\s+/', $name))->take(2)->map(fn($n)=>substr($n,0,1))->implode('')) }}</div><div><strong>{{ $name }}</strong><small>{{ $report->visit_type ?: 'UNIFCO Technician' }}</small></div><span class="tech-state">Recent Visit</span></div>
            @empty
                <div class="tech-empty">Technician assignment details will appear when available on completed visit reports.</div>
            @endforelse
        </div>

        <div class="portal-card visit-card">
            <div class="visit-card-head"><h3>@include('customer.partials.icon',['name'=>'documents']) Visit Reports</h3><a href="{{ route('customer.section','reports') }}">View reports →</a></div>
            @forelse($reports->take(4) as $report)
                <div class="report-row"><div class="report-icon">@include('customer.partials.icon',['name'=>'documents'])</div><div><strong>{{ $report->visit_type ?: 'Visit Report' }}</strong><small>{{ $report->report_no }} · {{ $report->visit_date?->format('d M Y') ?? 'Completed visit' }}</small></div><a class="report-download" href="{{ route('customer.visits.pdf',$report) }}">Download</a></div>
            @empty<div class="visit-empty"><strong>No reports available</strong>Customer-visible reports will appear after completed visits.</div>@endforelse
        </div>
    </section>
</div>

@include('customer.partials.portal-shell-close')
