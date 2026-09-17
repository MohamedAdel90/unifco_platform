@php
    $brandingSetting = \App\Models\BrandingSetting::query()->latest('id')->first();
    $customerLogoPath = $customer->logo_path ?? null;
    $customerLogoUrl = $customerLogoPath
        ? (str_starts_with($customerLogoPath, 'http') ? $customerLogoPath : asset('storage/'.ltrim(str_replace('public/', '', $customerLogoPath), '/')))
        : null;
    $unifcoLogoPath = $brandingSetting?->logo_path;
    $unifcoLogoUrl = $unifcoLogoPath
        ? (str_starts_with($unifcoLogoPath, 'http') ? $unifcoLogoPath : asset('storage/'.ltrim(str_replace('public/', '', $unifcoLogoPath), '/')))
        : asset('images/unifco-logo.webp');
    $recentRequests = collect($requests ?? [])->sortByDesc('created_at')->take(2);
    $recentWorkOrders = collect($workOrders ?? [])->sortByDesc('updated_at')->take(2);
    $recentItems = $recentRequests->map(fn($x) => ['type'=>'request','title'=>'Service request created','ref'=>$x->request_no ?? $x->service_request_no ?? ('Request #'.$x->id),'time'=>optional($x->created_at)->diffForHumans()])
        ->concat($recentWorkOrders->map(fn($x) => ['type'=>'work','title'=>'Work order updated','ref'=>$x->work_order_no ?? ('Work order #'.$x->id),'time'=>optional($x->updated_at)->diffForHumans()]))
        ->take(4);
@endphp
<style>
/* Approved customer dashboard branding/layout bridge. */
#dashboard-sidebar-template{display:none}.sidebar .ui-icon{width:18px;height:18px;display:block;flex:0 0 auto}.sidebar .nav-scroll{scrollbar-width:thin;scrollbar-color:#ffffff2e transparent}.sidebar .nav-link{transition:.18s}.sidebar .nav-link:hover,.sidebar .nav-link.active{background:rgba(255,255,255,.13);color:#fff}.sidebar .nav-link span:nth-child(2){min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.sidebar .nav-badge{background:rgba(255,255,255,.13)}.sidebar .nav-badge.urgent{background:var(--red);color:#fff}.sidebar .logout button,.sidebar .side-tools button{cursor:pointer}.sidebar .customer-brand{height:92px;display:grid;place-items:center;padding:7px 10px 12px;border-bottom:1px solid #ffffff1a}.sidebar .customer-brand img{max-width:118px;max-height:70px;object-fit:contain;filter:drop-shadow(0 2px 6px #00163155)}.sidebar .customer-brand .customer-fallback{width:58px;height:58px;border-radius:14px;background:#fff;color:var(--navy);display:grid;place-items:center;font-weight:900;font-size:18px}.sidebar .side-tools{grid-template-columns:1fr 38px;margin:10px 2px 5px}.sidebar .side-tools a{height:38px}.sidebar .side-tools button{height:38px}.sidebar .nav-scroll{padding-top:2px}.app.sidebar-collapsed{grid-template-columns:76px minmax(0,1fr)}.app.sidebar-collapsed .sidebar{padding-inline:9px}.app.sidebar-collapsed .customer-brand img{max-width:50px;max-height:48px}.app.sidebar-collapsed .customer-brand .customer-fallback{width:44px;height:44px}.app.sidebar-collapsed .side-tools{grid-template-columns:1fr}.app.sidebar-collapsed .side-tools a{display:none}.app.sidebar-collapsed .side-tools button{width:100%}.app.sidebar-collapsed .nav-label,.app.sidebar-collapsed .nav-link span:nth-child(2),.app.sidebar-collapsed .nav-badge{display:none}.app.sidebar-collapsed .nav-link{justify-content:center;padding-inline:0}.app.sidebar-collapsed .logout button{font-size:0}.app.sidebar-collapsed .logout button:after{content:'↪';font-size:14px}
/* Approved dashboard top area. */
.topbar .welcome h1{font-size:18px}.topbar .welcome h1 .customer-welcome-name{display:none}.customer-hero{grid-template-columns:minmax(540px,1.15fr) minmax(520px,1.5fr)!important;min-height:118px!important}.customer-info{padding:14px 18px!important;display:block!important}.customer-info .brand-box{display:none!important}.customer-copy h1{font-size:18px!important;margin:0 0 10px!important}.customer-copy .identity{gap:10px!important}.customer-copy .identity>span:first-child,.customer-copy .identity>span:nth-child(2){display:none!important}.contact-line{margin-top:12px!important;gap:16px!important}.contact-line>span:last-child{display:none!important}.hero-visual{min-height:118px}.hero-message{right:210px!important;top:33px!important;width:180px!important;font-size:15px!important}.unifco-hero-logo{position:absolute;z-index:4;right:22px;top:18px;width:145px;height:82px;border-left:1px solid #ffffff88;padding-left:18px;display:flex;align-items:center;justify-content:center}.unifco-hero-logo img{position:static!important;width:auto!important;height:auto!important;max-width:120px!important;max-height:70px!important;object-fit:contain!important;opacity:1!important;filter:drop-shadow(0 2px 5px rgba(0,0,0,.25))}.hero-stats{display:none!important}.bottom-row{grid-template-columns:1.05fr 2.35fr!important}.bottom-row .support{display:none!important}.approved-lower-grid{display:grid;grid-template-columns:1fr 1fr 1.12fr 1.12fr;gap:10px;margin-top:10px}.approved-card{background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow);padding:12px 13px;min-height:178px}.approved-head{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:9px}.approved-head h3{font-size:11px;margin:0}.approved-head a{font-size:8px;color:var(--blue);font-weight:700}.approved-empty{height:120px;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;color:var(--muted);font-size:8px}.approved-empty b{font-size:10px;color:var(--navy);margin-bottom:5px}.approved-list{display:grid}.approved-item{display:grid;grid-template-columns:24px 1fr auto;gap:8px;align-items:center;padding:8px 0;border-bottom:1px solid #edf1f6}.approved-item:last-child{border-bottom:0}.approved-item i{width:24px;height:24px;border-radius:7px;background:#edf6ff;color:var(--blue);display:grid;place-items:center;font-style:normal;font-size:11px}.approved-item strong{display:block;font-size:8.5px}.approved-item small{display:block;font-size:7px;color:var(--muted);margin-top:2px}.approved-item time{font-size:7px;color:var(--muted);white-space:nowrap}.approved-amount{font-size:8px;font-weight:800;white-space:nowrap}.approved-status{display:block;color:var(--amber);font-size:7px;margin-top:2px;text-align:right}.support-link{display:flex;align-items:center;justify-content:space-between;border:1px solid #e8eef6;border-radius:8px;padding:9px;margin-bottom:6px;font-size:8px}.support-link:last-child{margin-bottom:0}.support-link b{display:block}.support-link small{display:block;color:var(--muted);margin-top:2px}@media(max-width:1500px){.approved-lower-grid{grid-template-columns:1fr 1fr}.customer-hero{grid-template-columns:1fr 1fr!important}}@media(max-width:900px){.approved-lower-grid{grid-template-columns:1fr}.customer-hero{grid-template-columns:1fr!important}.unifco-hero-logo{display:none}.hero-message{right:24px!important}.bottom-row{grid-template-columns:1fr!important}}
</style>

<template id="dashboard-sidebar-template">
    <div class="customer-brand">
        @if($customerLogoUrl)
            <img src="{{ $customerLogoUrl }}" alt="{{ $customer->name }} logo">
        @else
            <div class="customer-fallback">{{ strtoupper(substr($customer->name,0,2)) }}</div>
        @endif
    </div>
    <div class="side-tools">
        <a href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">＋ <span>New Request</span></a>
        <button type="button" id="sidebar-toggle" title="Collapse sidebar">⇤</button>
    </div>
    <div class="nav-scroll">
        @php
            $dashboardSectionItems = [
                'requests' => ['requests', 'Service Requests'], 'work-orders' => ['work-orders', 'Work Orders'],
                'visits' => ['visits', 'Visits & Schedule'], 'maintenance' => ['maintenance', 'Maintenance Plan'],
                'spare-parts' => ['parts', 'Spare Parts'], 'sites' => ['sites', 'Sites'],
                'assets' => ['assets', 'Assets & Equipment'], 'quotations' => ['quotations', 'Quotations'],
                'contracts' => ['contracts', 'Contracts'], 'sla' => ['sla', 'SLA & KPIs'],
                'invoices' => ['invoices', 'Invoices & Payments'], 'reports' => ['reports', 'Reports'],
                'documents' => ['documents', 'Document Library'], 'timeline' => ['activity', 'Recent Activity'],
                'notifications' => ['notifications', 'Notifications'],
            ];
            $dashboardGroups = [
                'Overview' => ['dashboard'], 'My Work' => ['requests', 'work-orders', 'visits', 'maintenance', 'spare-parts'],
                'Sites & Assets' => ['sites', 'assets'], 'Commercial & Contracts' => ['quotations', 'contracts', 'sla'],
                'Finance' => ['invoices'], 'Reports & Records' => ['reports', 'documents', 'timeline', 'notifications'],
            ];
            $dashboardBadges = ['requests'=>$openRequestCount,'work-orders'=>$openWorkOrders,'visits'=>$upcomingPlans->count(),'quotations'=>$pendingQuotationCount,'invoices'=>$invoiceActionCount,'notifications'=>$alerts->count()];
        @endphp
        @foreach($dashboardGroups as $group => $keys)
            @php($visibleKeys = array_values(array_filter($keys, fn($key) => in_array($key, $allowedSections, true))))
            @if(count($visibleKeys))
                <div class="nav-group"><div class="nav-label">{{ $group }}</div>
                    @if($group === 'Overview')
                        <a class="nav-link active" href="{{ route('customer.portal') }}">@include('customer.partials.icon',['name'=>'dashboard'])<span>Dashboard</span></a>
                        <a class="nav-link" href="{{ route('customer.actions') }}">@include('customer.partials.icon',['name'=>'actions'])<span>Action Required</span>@if($actionRequiredCount)<span class="nav-badge urgent">{{ $actionRequiredCount }}</span>@endif</a>
                    @else
                        @foreach($visibleKeys as $key)
                            <a class="nav-link" href="{{ route('customer.section', $key) }}">@include('customer.partials.icon',['name'=>$dashboardSectionItems[$key][0]])<span>{{ $dashboardSectionItems[$key][1] }}</span>@if(($dashboardBadges[$key] ?? 0) > 0)<span class="nav-badge">{{ $dashboardBadges[$key] }}</span>@endif</a>
                        @endforeach
                    @endif
                </div>
            @endif
        @endforeach
        <div class="nav-group"><div class="nav-label">Communication & Account</div>
            <a class="nav-link" href="{{ route('customer.inbox') }}">@include('customer.partials.icon',['name'=>'inbox'])<span>Inbox & Support</span>@if($unreadInbox)<span class="nav-badge urgent">{{ $unreadInbox }}</span>@endif</a>
            <a class="nav-link" href="{{ route('customer.profile.edit') }}">@include('customer.partials.icon',['name'=>'profile'])<span>Company Profile & Settings</span></a>
        </div>
    </div>
    <form class="logout" method="POST" action="{{ route('logout') }}">@csrf<button>Sign out</button></form>
</template>

<template id="approved-dashboard-lower-template">
<section class="approved-lower-grid">
    <div class="approved-card">
        <div class="approved-head"><h3>Upcoming Visits</h3><a href="{{ route('customer.section','visits') }}">View all visits →</a></div>
        @forelse(collect($upcomingPlans ?? [])->take(3) as $visit)
            <div class="approved-item"><i>□</i><div><strong>{{ $visit->title ?? $visit->visit_type ?? 'Scheduled visit' }}</strong><small>{{ $visit->scheduled_for ?? $visit->planned_date ?? $visit->visit_date ?? 'Upcoming' }}</small></div><time>›</time></div>
        @empty
            <div class="approved-empty"><b>No upcoming visits</b><span>You have no scheduled visits in the next period.</span></div>
        @endforelse
    </div>
    <div class="approved-card">
        <div class="approved-head"><h3>Recent Activity</h3><a href="{{ route('customer.section','timeline') }}">View all activity →</a></div>
        <div class="approved-list">
            @foreach($recentItems as $item)
                <div class="approved-item"><i>{{ $item['type']==='work'?'⚙':'▤' }}</i><div><strong>{{ $item['title'] }}</strong><small>{{ $item['ref'] }}</small></div><time>{{ $item['time'] }}</time></div>
            @endforeach
            @if($recentItems->isEmpty())<div class="approved-empty"><b>No recent activity</b></div>@endif
        </div>
    </div>
    <div class="approved-card">
        <div class="approved-head"><h3>Pending Approvals / Quotations</h3><a href="{{ route('customer.section','quotations') }}">View all →</a></div>
        <div class="approved-list">
            @forelse(collect($quotations ?? [])->filter(fn($q)=>in_array(strtoupper((string)($q->status ?? '')),['PENDING','SENT','AWAITING_APPROVAL','SUBMITTED']))->take(3) as $q)
                <div class="approved-item"><i>▤</i><div><strong>{{ $q->quotation_no ?? ('Quotation #'.$q->id) }}</strong><small>{{ $q->title ?? $q->subject ?? 'Quotation awaiting action' }}</small></div><div><span class="approved-amount">SAR {{ number_format((float)($q->total_amount ?? $q->amount ?? 0),2) }}</span><span class="approved-status">Pending Approval</span></div></div>
            @empty
                <div class="approved-empty"><b>No pending approvals</b><span>Items requiring your decision will appear here.</span></div>
            @endforelse
        </div>
    </div>
    <div class="approved-card">
        <div class="approved-head"><h3>Messages & Support</h3><a href="{{ route('customer.inbox') }}">Contact support →</a></div>
        <a class="support-link" href="{{ route('customer.inbox') }}"><span><b>Need Help?</b><small>Get in touch with our support team.</small></span><span>›</span></a>
        <a class="support-link" href="{{ route('customer.section','documents') }}"><span><b>Document Library</b><small>Browse customer-visible files and records.</small></span><span>›</span></a>
        <a class="support-link" href="{{ route('customer.inbox') }}"><span><b>Raise a Query</b><small>Send us a message.</small></span><span>›</span></a>
    </div>
</section>
</template>
<script>
(()=>{
    const app=document.querySelector('.app');
    const sidebar=document.querySelector('.sidebar');
    const template=document.getElementById('dashboard-sidebar-template');
    if(!app||!sidebar||!template)return;
    sidebar.innerHTML=template.innerHTML;
    const welcome=document.querySelector('.topbar .welcome h1');
    if(welcome) welcome.textContent='Welcome back,';
    const heroVisual=document.querySelector('.hero-visual');
    if(heroVisual && !heroVisual.querySelector('.unifco-hero-logo')){
        const logo=document.createElement('div');logo.className='unifco-hero-logo';logo.innerHTML='<img src="{{ $unifcoLogoUrl }}" alt="UNIFCO">';heroVisual.appendChild(logo);
    }
    const bottom=document.querySelector('.bottom-row');
    const lowerTemplate=document.getElementById('approved-dashboard-lower-template');
    if(bottom&&lowerTemplate&&!document.querySelector('.approved-lower-grid')) bottom.insertAdjacentHTML('afterend',lowerTemplate.innerHTML);
    const button=document.getElementById('sidebar-toggle');
    if(!button)return;
    const key='unifco-customer-sidebar-collapsed';
    if(localStorage.getItem(key)==='1') app.classList.add('sidebar-collapsed');
    const sync=()=>{button.textContent=app.classList.contains('sidebar-collapsed')?'⇥':'⇤'};sync();
    button.addEventListener('click',()=>{app.classList.toggle('sidebar-collapsed');localStorage.setItem(key,app.classList.contains('sidebar-collapsed')?'1':'0');sync();});
})();
</script>
