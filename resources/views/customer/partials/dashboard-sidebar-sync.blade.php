<style>
/* Dashboard-only bridge: render the exact same customer navigation model/styles used by the other customer pages. */
#dashboard-sidebar-template{display:none}.sidebar .ui-icon{width:18px;height:18px;display:block;flex:0 0 auto}.sidebar .side-search .ui-icon{width:14px}.sidebar .side-tools{grid-template-columns:1fr 38px 38px}.sidebar .side-tools .side-alert{height:38px;border-radius:8px;background:var(--red);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:900}.sidebar .nav-scroll{scrollbar-width:thin;scrollbar-color:#ffffff2e transparent}.sidebar .nav-link{transition:.18s}.sidebar .nav-link:hover,.sidebar .nav-link.active{background:rgba(255,255,255,.13);color:#fff}.sidebar .nav-link span:nth-child(2){min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.sidebar .nav-badge{background:rgba(255,255,255,.13)}.sidebar .nav-badge.urgent{background:var(--red);color:#fff}.sidebar .logout button{cursor:pointer}.sidebar .side-tools button{cursor:pointer}.app.sidebar-collapsed{grid-template-columns:76px minmax(0,1fr)}.app.sidebar-collapsed .sidebar{padding-inline:9px}.app.sidebar-collapsed .account-mark>div:last-child,.app.sidebar-collapsed .role-badge,.app.sidebar-collapsed .side-tools a span,.app.sidebar-collapsed .side-search span,.app.sidebar-collapsed .nav-label,.app.sidebar-collapsed .nav-link span:nth-child(2),.app.sidebar-collapsed .nav-badge{display:none}.app.sidebar-collapsed .account{padding-inline:4px}.app.sidebar-collapsed .account-mark{justify-content:center}.app.sidebar-collapsed .side-tools{grid-template-columns:1fr}.app.sidebar-collapsed .side-tools a{display:none}.app.sidebar-collapsed .side-tools button{width:100%}.app.sidebar-collapsed .side-search{justify-content:center;padding:0}.app.sidebar-collapsed .nav-link{justify-content:center;padding-inline:0}.app.sidebar-collapsed .logout button{font-size:0}.app.sidebar-collapsed .logout button:after{content:'↪';font-size:14px}
</style>

<template id="dashboard-sidebar-template">
    <div class="account">
        <div class="account-mark"><div class="account-logo">{{ strtoupper(substr($customer->name, 0, 2)) }}</div><div><strong>{{ $customer->name }}</strong><small>{{ $customer->customer_code }}</small></div></div>
        <span class="role-badge">UNIFIED CUSTOMER ACCOUNT</span>
    </div>
    <div class="side-tools">
        <a href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">＋ <span>New Request</span></a>
        <a class="side-alert" href="{{ route('customer.actions') }}" title="Action Required">!</a>
        <button type="button" id="sidebar-toggle" title="Collapse sidebar">⇤</button>
    </div>
    <div class="side-search">@include('customer.partials.icon',['name'=>'search'])<span>Request, asset, invoice or contract...</span></div>
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
                'Overview' => ['dashboard'],
                'My Work' => ['requests', 'work-orders', 'visits', 'maintenance', 'spare-parts'],
                'Sites & Assets' => ['sites', 'assets'],
                'Commercial & Contracts' => ['quotations', 'contracts', 'sla'],
                'Finance' => ['invoices'],
                'Reports & Records' => ['reports', 'documents', 'timeline', 'notifications'],
            ];
            $dashboardBadges = [
                'requests' => $openRequestCount, 'work-orders' => $openWorkOrders,
                'visits' => $upcomingPlans->count(), 'quotations' => $pendingQuotationCount,
                'invoices' => $invoiceActionCount, 'notifications' => $alerts->count(),
            ];
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
<script>
(()=>{
    const app=document.querySelector('.app');
    const sidebar=document.querySelector('.sidebar');
    const template=document.getElementById('dashboard-sidebar-template');
    if(!app||!sidebar||!template)return;
    sidebar.innerHTML=template.innerHTML;
    const button=document.getElementById('sidebar-toggle');
    if(!button)return;
    const key='unifco-customer-sidebar-collapsed';
    if(localStorage.getItem(key)==='1') app.classList.add('sidebar-collapsed');
    const sync=()=>{button.textContent=app.classList.contains('sidebar-collapsed')?'⇥':'⇤'};
    sync();
    button.addEventListener('click',()=>{
        app.classList.toggle('sidebar-collapsed');
        localStorage.setItem(key,app.classList.contains('sidebar-collapsed')?'1':'0');
        sync();
    });
})();
</script>
