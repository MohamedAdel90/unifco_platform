<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>UNIFCO Customer Portal · {{ ucwords(str_replace('-', ' ', $section)) }}</title>
    <style>
        :root{font-family:Inter,"Segoe UI",Arial,sans-serif;--navy:#06275c;--navy-deep:#031d49;--ink:#0a234f;--blue:#1475d1;--red:#e20b24;--green:#14875a;--amber:#d88900;--bg:#f3f6fa;--line:#dfe6ef;--muted:#6d7b90;--shadow:0 8px 24px rgba(7,31,77,.06)}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--bg);color:var(--ink)}a{text-decoration:none;color:inherit}button,input,select,textarea{font:inherit}.ui-icon{width:18px;height:18px;display:block;flex:0 0 auto}
        .app{min-height:100vh;display:grid;grid-template-columns:268px minmax(0,1fr)}
        .sidebar{position:sticky;top:0;height:100vh;background:linear-gradient(180deg,var(--navy),var(--navy-deep));color:#fff;padding:16px 12px 12px;display:flex;flex-direction:column;z-index:30;overflow:hidden}
        .account{padding:5px 8px 15px;border-bottom:1px solid rgba(255,255,255,.1)}.account-mark{display:flex;align-items:center;gap:10px}.account-logo{width:38px;height:38px;border-radius:11px;background:#fff;color:var(--navy);display:grid;place-items:center;font-weight:900;font-size:12px}.account strong{font-size:12px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.account small{font-size:9px;color:#aebed4;display:block;margin-top:4px}.role-badge{display:inline-flex;margin-top:10px;padding:5px 8px;border-radius:6px;background:rgba(255,255,255,.1);color:#dce7f4;font-size:8px;font-weight:800;letter-spacing:.06em}
        .side-search{margin:12px 4px 7px;height:35px;border:1px solid rgba(255,255,255,.12);border-radius:9px;display:flex;align-items:center;gap:8px;padding:0 10px;color:#aebed4;font-size:9px}.side-search .ui-icon{width:14px}.nav-scroll{min-height:0;overflow:auto;padding:3px 2px 12px;scrollbar-width:thin;scrollbar-color:#ffffff2e transparent}.nav-group{margin-top:12px}.nav-label{padding:0 10px 5px;color:#7f98b9;font-size:8px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.nav-link{min-height:35px;display:flex;gap:10px;align-items:center;padding:8px 10px;border-radius:8px;font-size:10px;color:#eaf1fa;margin:2px 0;transition:.18s}.nav-link:hover,.nav-link.active{background:rgba(255,255,255,.13);color:#fff}.nav-link.active{box-shadow:inset 3px 0 0 var(--red)}.nav-link span:nth-child(2){min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.nav-badge{margin-left:auto;min-width:19px;height:18px;padding:0 5px;border-radius:9px;background:rgba(255,255,255,.13);display:grid;place-items:center;font-size:8px;font-weight:800}.nav-badge.urgent{background:var(--red);color:#fff}.logout{margin-top:auto;padding-top:9px;border-top:1px solid rgba(255,255,255,.1)}.logout button{width:100%;border:1px solid rgba(255,255,255,.2);background:transparent;color:#fff;padding:9px;border-radius:8px;cursor:pointer;font-size:10px}
        .main{min-width:0;padding:0 24px 44px}.topbar{height:70px;display:flex;align-items:center;gap:18px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.96);margin:0 -24px;padding:0 26px;position:sticky;top:0;z-index:20}.welcome{flex:1;min-width:0}.welcome h1{font-size:16px;margin:0 0 4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.welcome p{font-size:9px;color:var(--muted);margin:0}.top-actions{display:flex;align-items:center;gap:9px}.status-pill,.pill{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:999px;font-size:8px;font-weight:800;background:#edf4fd;color:#3268a5}.status-pill:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}.green{background:#e7f7ee;color:#137346}.red{background:#fdebed;color:#b42239}.amber{background:#fff3d9;color:#9b6500}.avatar{width:34px;height:34px;border-radius:10px;background:var(--navy);color:#fff;display:grid;place-items:center;font-size:10px;font-weight:900}
        .content{padding-top:22px;width:100%;max-width:none;margin:0}.notice,.role-note{padding:10px 12px;border-radius:9px;font-size:10px;margin-bottom:12px}.notice{background:#e9f7ef;color:#176940}.role-note{background:#eef4fb;border:1px solid #dbe6f2;color:#35536f}.page-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:15px}.page-head h2{font-size:24px;line-height:1.15;margin:0 0 5px;letter-spacing:-.02em}.page-head p{font-size:10px;color:var(--muted);margin:0}.eyebrow{font-size:8px;color:var(--blue);font-weight:850;text-transform:uppercase;letter-spacing:.11em;margin-bottom:7px}
        .executive-strip{display:grid;grid-template-columns:minmax(260px,1.25fr) repeat(3,minmax(120px,.55fr));gap:1px;background:var(--line);overflow:hidden;margin-bottom:12px}.executive-cell{background:#fff;padding:13px 15px;min-height:66px;display:flex;flex-direction:column;justify-content:center}.executive-cell small{font-size:8px;color:var(--muted);margin-bottom:6px}.executive-cell strong{font-size:11px}.executive-status{flex-direction:row;align-items:center;gap:11px;justify-content:flex-start}.health-dot{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;background:#e7f7ee;color:var(--green)}.health-dot.warning{background:#fdebed;color:var(--red)}.health-dot.follow-up{background:#fff3d9;color:var(--amber)}.executive-status div{display:grid;gap:4px}.executive-status small{margin:0}.scope-value{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .filters{display:flex;align-items:flex-end;gap:8px;flex-wrap:wrap}.filter{display:grid;gap:4px}.filter span{font-size:7px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}.filter select{height:34px;min-width:128px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);padding:0 9px;font-size:9px}.filter-button{height:34px;border:0;border-radius:8px;background:var(--navy);color:#fff;padding:0 13px;font-size:9px;font-weight:800;cursor:pointer}
        .card{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow)}.quick-actions{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:12px}.quick-action{min-height:65px;padding:12px 13px;display:flex;align-items:center;gap:10px;position:relative;overflow:hidden}.quick-action:hover{border-color:#aac7e6;transform:translateY(-1px);box-shadow:0 10px 24px rgba(7,31,77,.09)}.quick-action .quick-icon{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:#edf4fd;color:var(--blue)}.quick-action strong{font-size:10px;display:block}.quick-action small{font-size:8px;color:var(--muted);display:block;margin-top:3px}.quick-action.emergency{background:linear-gradient(135deg,#e20b24,#ba071b);border-color:transparent;color:#fff}.quick-action.emergency .quick-icon{background:#ffffff22;color:#fff}.quick-action.emergency small{color:#ffe2e7}
        .action-center{padding:15px 16px;margin-bottom:12px;border-left:4px solid var(--red)}.action-center.is-clear{padding-block:11px;border-left-color:var(--green)}.action-head{display:flex;align-items:center;gap:12px}.action-copy{flex:1}.action-copy strong{font-size:12px}.action-copy p{font-size:8px;color:var(--muted);margin:4px 0 0}.action-total{min-width:46px;height:29px;border-radius:15px;background:#fdebed;color:#b42239;display:grid;place-items:center;font-size:9px;font-weight:900}.action-breakdown{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-top:12px}.action-item{padding:8px 9px;border-radius:8px;background:#f7f9fc;border:1px solid #edf1f5;display:flex;justify-content:space-between;gap:6px;font-size:8px;color:var(--muted)}.action-item b{font-size:10px;color:var(--ink)}
        .stats{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}.stat{padding:14px;min-height:116px;position:relative}.stat:after{content:"";position:absolute;left:0;top:15px;width:3px;height:31px;border-radius:0 3px 3px 0;background:var(--blue)}.stat.warning:after{background:var(--red)}.stat:hover{border-color:#aac7e6;box-shadow:0 10px 24px rgba(7,31,77,.09)}.stat .label{font-size:8px;font-weight:800;color:#53647a}.stat .value{font-size:24px;font-weight:900;margin:12px 0 5px;letter-spacing:-.03em}.stat .value.compact{font-size:19px}.trend{font-size:8px;color:var(--green)}.trend.muted{color:var(--muted)}.trend.danger{color:var(--red)}.comparison{display:inline-flex;margin-top:7px;padding:3px 6px;border-radius:999px;background:#edf7f1;color:var(--green);font-size:7px;font-weight:800}.comparison.up{background:#fff3d9;color:#8b5d00}.comparison.down{background:#eaf6ef;color:var(--green)}.comparison.neutral{background:#f0f3f7;color:var(--muted)}
        .command-grid{display:grid;grid-template-columns:minmax(0,1.75fr) minmax(320px,1fr);gap:12px;margin-top:12px}.panel{padding:16px}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.panel-head h3{font-size:12px;margin:0}.panel-head a{font-size:8px;color:var(--blue);font-weight:800}.panel-tools{display:flex;align-items:center;gap:8px}.stage-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:7px}.stage-grid.journey-grid{grid-template-columns:repeat(4,1fr)}.stage{background:#f7f9fc;border:1px solid #edf1f5;border-radius:9px;padding:10px 8px;min-height:73px;position:relative}.stage:before{content:"";position:absolute;left:0;top:12px;width:2px;height:22px;background:#b7cee8}.stage.active:before{background:var(--blue)}.stage.alert:before{background:var(--red)}.stage b{display:block;font-size:19px}.stage span{font-size:7px;color:var(--muted);display:block;margin-top:6px}.priority-list,.activity-list,.upcoming-list{display:grid}.list-row{display:grid;grid-template-columns:8px 1fr auto;gap:9px;align-items:center;padding:9px 0;border-bottom:1px solid #edf1f5}.list-row:last-child{border-bottom:0}.indicator{width:7px;height:7px;border-radius:50%;background:var(--blue)}.indicator.red{background:var(--red)}.row-title{font-size:9px;font-weight:800}.row-sub{font-size:7px;color:var(--muted);margin-top:3px}.row-meta{display:flex;gap:5px;align-items:center;justify-content:flex-end;flex-wrap:wrap}.empty{padding:23px;text-align:center;color:var(--muted);font-size:9px}.empty strong{display:block;color:#40546c;font-size:10px;margin-bottom:4px}
        .lower-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:12px;margin-top:12px}.asset-health{display:grid;grid-template-columns:130px 1fr;gap:18px;align-items:center}.health-ring{width:112px;height:112px;border-radius:50%;background:conic-gradient(var(--green) 0 var(--active),var(--amber) var(--active) var(--maint),var(--red) var(--maint) var(--down),#e8edf4 var(--down));position:relative;display:grid;place-items:center}.health-ring:before{content:"";position:absolute;inset:18px;border-radius:50%;background:#fff}.health-ring div{position:relative;text-align:center}.health-ring b{display:block;font-size:23px}.health-ring span{font-size:7px;color:var(--muted)}.health-legend{display:grid;grid-template-columns:1fr 1fr;gap:8px}.health-item{padding:9px;border-radius:8px;background:#f7f9fc}.health-item b{font-size:14px;display:block}.health-item span{font-size:7px;color:var(--muted)}
        .activity-panel{margin-top:12px}.table-card{padding:16px}.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse;font-size:9px}.table th,.table td{padding:10px 8px;text-align:left;border-bottom:1px solid #edf0f4;white-space:nowrap}.table th{color:#68758a;font-size:8px;text-transform:uppercase;letter-spacing:.05em}.table tr:last-child td{border-bottom:0}.table form{display:flex;gap:5px;align-items:center}.table input,.table select{border:1px solid var(--line);border-radius:6px;padding:6px;font-size:8px}.btn{border:0;border-radius:7px;background:var(--navy);color:#fff;padding:8px 12px;font-size:9px;font-weight:800;cursor:pointer}.btn.red{background:var(--red)}
        .asset-grid,.site-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.asset,.site-card{padding:14px}.asset strong,.site-card strong{font-size:10px}.asset small,.site-card small{display:block;margin-top:7px;line-height:1.65;color:var(--muted);font-size:8px}.asset:hover,.site-card:hover{border-color:#aac7e6;box-shadow:0 10px 28px rgba(7,31,77,.09)}.site-meta{display:flex;gap:5px;margin-top:9px;flex-wrap:wrap}.form-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}.field{display:grid;gap:5px;font-size:9px;font-weight:700}.field input,.field select,.field textarea{width:100%;border:1px solid #d6dee8;border-radius:7px;padding:9px;background:#fff}.field textarea{min-height:90px}.wide{grid-column:1/-1}
        @media(max-width:1250px){.stats{grid-template-columns:repeat(3,1fr)}.stage-grid{grid-template-columns:repeat(3,1fr)}.quick-actions{grid-template-columns:repeat(3,1fr)}.executive-strip{grid-template-columns:1.3fr repeat(3,1fr)}}
        @media(max-width:1040px){.app{grid-template-columns:82px minmax(0,1fr)}.account strong,.account small,.role-badge,.side-search span,.nav-label,.nav-link span:nth-child(2),.nav-badge{display:none}.account{padding:5px 8px 12px}.account-mark{justify-content:center}.nav-link{justify-content:center}.sidebar{padding-left:9px;padding-right:9px}.quick-actions{grid-template-columns:repeat(2,1fr)}.asset-grid,.site-grid{grid-template-columns:repeat(2,1fr)}.executive-strip{grid-template-columns:1fr 1fr}}
        @media(max-width:780px){.app{display:block}.sidebar{height:auto;position:sticky;top:0;display:block;padding:7px;overflow:auto}.account,.side-search,.nav-label,.logout{display:none}.nav-scroll{display:flex;overflow:auto;padding:0}.nav-group{display:flex;margin:0}.nav-link{min-width:42px}.main{padding:0 12px 28px}.topbar{margin:0 -12px;padding:0 14px}.top-actions .status-pill{display:none}.page-head{align-items:flex-start;flex-direction:column}.filters{width:100%}.filter{flex:1}.filter select{width:100%;min-width:0}.stats,.quick-actions,.command-grid,.lower-grid,.asset-grid,.site-grid,.form-grid{grid-template-columns:1fr 1fr}.action-breakdown{grid-template-columns:repeat(2,1fr)}.asset-health{grid-template-columns:1fr}.health-ring{margin:auto}.stage-grid.journey-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:520px){.stats,.quick-actions,.command-grid,.lower-grid,.asset-grid,.site-grid,.form-grid,.executive-strip{grid-template-columns:1fr}.stage-grid{grid-template-columns:repeat(2,1fr)}.wide{grid-column:auto}.welcome p{display:none}.main{padding-bottom:20px}}
    </style>
    <style>
        .side-tools{display:grid;grid-template-columns:1fr 36px;gap:6px;margin:11px 4px 3px}.side-tools a,.side-tools button{height:36px;border:0;border-radius:8px;display:flex;align-items:center;justify-content:center;gap:6px;font-size:9px;font-weight:850;cursor:pointer}.side-tools a{background:#fff;color:var(--navy)}.side-tools button{background:#ffffff1a;color:#fff}.app.sidebar-collapsed{grid-template-columns:82px minmax(0,1fr)}.app.sidebar-collapsed .account strong,.app.sidebar-collapsed .account small,.app.sidebar-collapsed .role-badge,.app.sidebar-collapsed .side-search span,.app.sidebar-collapsed .nav-label,.app.sidebar-collapsed .nav-link span:nth-child(2),.app.sidebar-collapsed .nav-badge,.app.sidebar-collapsed .side-tools a span{display:none}.app.sidebar-collapsed .account-mark,.app.sidebar-collapsed .nav-link{justify-content:center}.app.sidebar-collapsed .side-tools{grid-template-columns:1fr}.app.sidebar-collapsed .side-tools a{width:36px;margin:auto}@media(max-width:1040px){.side-tools button{display:none}}@media(max-width:780px){.side-tools{display:none}}
        .nav-link{font-size:11px}.welcome p,.page-head p{font-size:10px}.quick-action strong{font-size:11px}.quick-action small,.action-copy p,.trend,.stage span,.row-sub,.health-item span{font-size:9px}.action-copy strong,.panel-head h3{font-size:13px}.action-item{font-size:9px}.action-item b,.row-title{font-size:10px}.table{font-size:10px}.table th{font-size:9px}
    </style>
</head>
<body>
@php
    $sectionItems = [
        'dashboard' => ['dashboard', 'Dashboard'], 'requests' => ['requests', 'Service Requests'],
        'work-orders' => ['work-orders', 'Work Orders'], 'visits' => ['visits', 'Visits & Schedule'],
        'maintenance' => ['maintenance', 'Maintenance Plan'], 'spare-parts' => ['parts', 'Spare Parts'],
        'sites' => ['sites', 'Sites'], 'assets' => ['assets', 'Assets & Equipment'],
        'quotations' => ['quotations', 'Quotations'], 'contracts' => ['contracts', 'Contracts'],
        'sla' => ['sla', 'SLA & KPIs'], 'invoices' => ['invoices', 'Invoices'],
        'reports' => ['reports', 'Reports'], 'documents' => ['documents', 'Documents'],
        'timeline' => ['activity', 'Recent Activity'], 'notifications' => ['notifications', 'Notifications'],
    ];
    $groups = [
        'Overview' => ['dashboard'],
        'My Work' => ['requests', 'work-orders', 'visits', 'maintenance', 'spare-parts'],
        'Sites & Assets' => ['sites', 'assets'],
        'Commercial & Contracts' => ['quotations', 'contracts', 'sla'],
        'Finance' => ['invoices'],
        'Reports & Records' => ['reports', 'documents', 'timeline', 'notifications'],
    ];
    $dashboardTitle = 'Customer 360 Executive Dashboard';
    $badges = ['requests' => $openRequestCount, 'work-orders' => $openWorkOrders, 'visits' => $upcomingPlans->count(), 'quotations' => $pendingQuotationCount, 'invoices' => $invoiceActionCount, 'notifications' => $alerts->count()];
    $assetTotal = max(1, $assets->count());
    $assetActiveEnd = round(($activeAssetCount / $assetTotal) * 100);
    $assetMaintenanceEnd = min(100, $assetActiveEnd + round(($maintenanceAssetCount / $assetTotal) * 100));
    $assetStoppedEnd = min(100, $assetMaintenanceEnd + round(($stoppedAssetCount / $assetTotal) * 100));
    $nextContract = $contracts->where('status', 'ACTIVE')->whereNotNull('ends_on')->sortBy('ends_on')->first();
    $openInvoiceCount = $invoices->filter(fn($invoice) => (float)$invoice->open_amount > 0)->count();
    $periodLabel = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last 12 months'][$days] ?? 'Selected period';
    $selectedSiteName = $siteFilter ? ($sites->firstWhere('id', $siteFilter)?->name ?? 'Selected site') : 'All customer sites';
    $selectedContractName = $contractFilter ? ($contracts->firstWhere('id', $contractFilter)?->contract_no ?? 'Selected contract') : 'All customer contracts';
@endphp
<div class="app">
    <aside class="sidebar">
        <div class="account">
            <div class="account-mark"><div class="account-logo">{{ strtoupper(substr($customer->name, 0, 2)) }}</div><div><strong>{{ $customer->name }}</strong><small>{{ $customer->customer_code }}</small></div></div>
            <span class="role-badge">UNIFIED CUSTOMER ACCOUNT</span>
        </div>
        <div class="side-tools"><a href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">＋ <span>New Request</span></a><button type="button" id="sidebar-toggle" title="Collapse sidebar">⇤</button></div>
        <div class="side-search">@include('customer.partials.icon',['name'=>'search'])<span>Find request, asset or invoice</span></div>
        <div class="nav-scroll">
            @foreach($groups as $group => $keys)
                @php($visibleKeys = array_values(array_intersect($keys, $allowedSections)))
                @if(count($visibleKeys))
                    <div class="nav-group"><div class="nav-label">{{ $group }}</div>
                        @if($group === 'Overview')
                            <a class="nav-link {{ $section === 'dashboard' ? 'active' : '' }}" href="{{ route('customer.portal') }}">@include('customer.partials.icon',['name'=>'dashboard'])<span>Dashboard</span></a>
                            <a class="nav-link" href="{{ route('customer.actions') }}">@include('customer.partials.icon',['name'=>'actions'])<span>Action Required</span>@if($actionRequiredCount)<span class="nav-badge urgent">{{ $actionRequiredCount }}</span>
@endif
</a>
                        
@else

                            @foreach($visibleKeys as $key)
                                <a class="nav-link {{ $section === $key ? 'active' : '' }}" href="{{ route('customer.section', $key) }}">@include('customer.partials.icon',['name'=>$sectionItems[$key][0]])<span>{{ $sectionItems[$key][1] }}</span>@if(($badges[$key] ?? 0) > 0)<span class="nav-badge">{{ $badges[$key] }}</span>
@endif
</a>
                            
@endforeach

                        
@endif

                    </div>
                
@endif

            
@endforeach

            <div class="nav-group"><div class="nav-label">Communication</div>
                <a class="nav-link" href="{{ route('customer.inbox') }}">@include('customer.partials.icon',['name'=>'inbox'])<span>Inbox & Support</span>@if($unreadInbox)<span class="nav-badge urgent">{{ $unreadInbox }}</span>
@endif
</a>
            </div>
            <div class="nav-group"><div class="nav-label">Account</div>
                <a class="nav-link" href="{{ route('customer.profile.edit') }}">@include('customer.partials.icon',['name'=>'profile'])<span>Company Profile & Settings</span></a>
            </div>
        </div>
        <form class="logout" method="POST" action="{{ route('logout') }}">@csrf<button>Sign out</button></form>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="welcome"><h1>Welcome, {{ auth()->user()->name }}</h1><p>{{ $customer->name }} · Unified customer account · All company records</p></div>
            <div class="top-actions"><span class="status-pill {{ $customer->status === 'ACTIVE' ? 'green' : 'amber' }}">{{ $customer->status }}</span><div class="avatar">{{ strtoupper(substr(auth()->user()->name ?: 'CU', 0, 2)) }}</div></div>
        </header>
        <div class="content">
            @if(session('status'))<div class="notice">{{ session('status') }}</div>
@endif

            @if($section === 'dashboard')
                <div class="page-head">
                    <div><div class="eyebrow">UNIFCO Customer Command Center</div><h2>{{ $dashboardTitle }}</h2><p>Everything that needs your attention across operations, assets, contracts and service delivery.</p></div>
                    <form class="filters" method="GET" action="{{ route('customer.portal') }}">
                        @if($sites->isNotEmpty())<label class="filter"><span>Site</span><select name="site_id"><option value="">All sites</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected($siteFilter === $site->id)>{{ $site->name }}</option>
@endforeach
</select></label>
@endif

                        @if($contracts->isNotEmpty())<label class="filter"><span>Contract</span><select name="contract_id"><option value="">All contracts</option>@foreach($contracts as $contract)<option value="{{ $contract->id }}" @selected($contractFilter === $contract->id)>{{ $contract->contract_no }}</option>
@endforeach
</select></label>
@endif

                        <label class="filter"><span>Period</span><select name="days">@foreach([7=>'7 days',30=>'30 days',90=>'90 days',365=>'12 months'] as $value=>$label)<option value="{{ $value }}" @selected($days===$value)>{{ $label }}</option>
@endforeach
</select></label>
                        <button class="filter-button">Apply</button>
                    </form>
                </div>

                <section class="card executive-strip" aria-label="Customer operating status">
                    <div class="executive-cell executive-status">
                        <span class="health-dot {{ $dashboardHealth === 'NEEDS ATTENTION' ? 'warning' : ($dashboardHealth === 'FOLLOW-UP REQUIRED' ? 'follow-up' : '') }}">@include('customer.partials.icon',['name'=>$dashboardHealth === 'STABLE' ? 'dashboard' : 'notifications'])</span>
                        <div><small>Overall service status</small><strong>{{ str_replace('_', ' ', $dashboardHealth) }}</strong></div>
                    </div>
                    <div class="executive-cell"><small>Customer scope</small><strong class="scope-value" title="{{ $selectedSiteName }} · {{ $selectedContractName }}">{{ $selectedSiteName }}</strong><small style="margin:3px 0 0">{{ $selectedContractName }}</small></div>
                    <div class="executive-cell"><small>Reporting period</small><strong>{{ $periodLabel }}</strong></div>
                    <div class="executive-cell"><small>Last customer update</small><strong>{{ $lastUpdatedAt?->format('d M Y, H:i') ?? 'No activity yet' }}</strong></div>
                </section>

                @if($canCreateRequest)
                    <section class="quick-actions">
                        <a class="card quick-action" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}"><span class="quick-icon">@include('customer.partials.icon',['name'=>'requests'])</span><span><strong>New Service Request</strong><small>Open the unified request form</small></span></a>
                        <a class="card quick-action emergency" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'emergency'=>1]) }}"><span class="quick-icon">@include('customer.partials.icon',['name'=>'notifications'])</span><span><strong>Emergency Maintenance</strong><small>Report an urgent asset failure</small></span></a>
                        <a class="card quick-action" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'quotation'=>1]) }}"><span class="quick-icon">@include('customer.partials.icon',['name'=>'quotations'])</span><span><strong>Request Quotation</strong><small>Open the unified request form</small></span></a>
                        <a class="card quick-action" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'quotation'=>1,'subtype'=>'parts']) }}"><span class="quick-icon">@include('customer.partials.icon',['name'=>'parts'])</span><span><strong>Request Spare Parts</strong><small>Open the unified request form</small></span></a>
                        <a class="card quick-action" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'consultation'=>1]) }}"><span class="quick-icon">@include('customer.partials.icon',['name'=>'reports'])</span><span><strong>Technical Consultation</strong><small>Ask the UNIFCO technical team</small></span></a>
                    </section>
                
@endif


                <a data-action-center-panel class="card action-center {{ $actionRequiredCount ? '' : 'is-clear' }}" href="{{ route('customer.actions') }}">
                    <div class="action-head"><span class="quick-icon">@include('customer.partials.icon',['name'=>'actions'])</span><div class="action-copy"><strong>{{ $actionRequiredCount ? 'Action Required From You' : 'No action is required from you right now' }}</strong><p>{{ $actionRequiredCount ? 'Approvals, work acceptance, payments, renewals and unread customer communication.' : 'Everything is up to date. New customer decisions will appear here.' }}</p></div><span class="action-total {{ $actionRequiredCount ? '' : 'green' }}">{{ $actionRequiredCount ? $actionRequiredCount : '✓' }}</span></div>
                    @if($actionRequiredCount)<div class="action-breakdown">
                        @if($canDecideQuotation)<span class="action-item">Quotations <b>{{ $quotationActionCount }}</b></span>
@endif

                        @if(in_array('work-orders',$allowedSections,true))<span class="action-item">Work acceptance <b>{{ $workAcceptanceActionCount }}</b></span>
@endif

                        @if(in_array('invoices',$allowedSections,true))<span class="action-item">Invoices <b>{{ $invoiceActionCount }}</b></span>
@endif

                        @if(in_array('contracts',$allowedSections,true))<span class="action-item">Renewals <b>{{ $renewalActionCount }}</b></span>
@endif

                        <span class="action-item">Messages <b>{{ $unreadInbox }}</b></span>
                    </div>
@endif

                </a>

                <section class="stats">
                    @if(in_array('requests',$allowedSections,true))<a class="card stat" href="{{ route('customer.section','requests') }}"><div class="label">Open Requests</div><div class="value">{{ $openRequestCount }}</div><div class="trend">Across the selected customer scope</div><span class="comparison {{ $requestVolumeDelta > 0 ? 'up' : ($requestVolumeDelta < 0 ? 'down' : 'neutral') }}">{{ $requestVolumeDelta > 0 ? '↑ +' : ($requestVolumeDelta < 0 ? '↓ ' : '→ ') }}{{ $requestVolumeDelta }} created vs prior period</span></a>
@endif

                    @if(in_array('work-orders',$allowedSections,true))<a class="card stat {{ $overdueCount ? 'warning' : '' }}" href="{{ route('customer.section','work-orders') }}"><div class="label">Open Work Orders</div><div class="value">{{ $openWorkOrders }}</div><div class="trend {{ $overdueCount ? 'danger' : '' }}">{{ $inProgressCount }} in progress · {{ $overdueCount }} overdue</div><span class="comparison {{ $workOrderVolumeDelta > 0 ? 'up' : ($workOrderVolumeDelta < 0 ? 'down' : 'neutral') }}">{{ $workOrderVolumeDelta > 0 ? '↑ +' : ($workOrderVolumeDelta < 0 ? '↓ ' : '→ ') }}{{ $workOrderVolumeDelta }} created vs prior period</span></a>
@endif

                    @if(in_array('sla',$allowedSections,true))<a class="card stat {{ $slaBreachCount > 0 || ($slaPerformance !== null && $slaPerformance < 90) ? 'warning' : '' }}" href="{{ route('customer.section','sla') }}"><div class="label">SLA Compliance</div><div class="value {{ $slaPerformance === null ? 'compact' : '' }}">{{ $slaPerformance === null ? 'N/A' : $slaPerformance.'%' }}</div><div class="trend {{ $slaBreachCount ? 'danger' : ($slaPerformance === null ? 'muted' : '') }}">{{ $slaPerformance === null ? 'No eligible measurements' : $slaBreachCount.' measured breaches' }}</div></a>
@endif

                    @if(in_array('assets',$allowedSections,true))<a class="card stat {{ $stoppedAssetCount ? 'warning' : '' }}" href="{{ route('customer.section','assets') }}"><div class="label">Assets Requiring Attention</div><div class="value">{{ $stoppedAssetCount + $maintenanceAssetCount }}</div><div class="trend {{ $stoppedAssetCount ? 'danger' : '' }}">{{ $stoppedAssetCount }} stopped · {{ $maintenanceAssetCount }} under maintenance</div></a>
@endif

                    @if(in_array('visits',$allowedSections,true))<a class="card stat" href="{{ route('customer.section','visits') }}"><div class="label">Upcoming Maintenance</div><div class="value">{{ $visitsDue7Count }}</div><div class="trend">Next 7 days · {{ $visitsDue30Count }} within 30 days</div></a>
@endif

                    @if(in_array('invoices',$allowedSections,true))<a class="card stat {{ $invoiceActionCount ? 'warning' : '' }}" href="{{ route('customer.section','invoices') }}"><div class="label">Open Balance</div><div class="value compact">{{ number_format($openInvoiceAmount,2) }}</div><div class="trend {{ $invoiceActionCount ? 'danger' : '' }}">SAR · {{ $invoiceActionCount }} due soon</div></a>
@endif

                </section>

                @if(in_array('requests',$allowedSections,true) && in_array('work-orders',$allowedSections,true))
                <section class="command-grid">
                    <div class="card panel"><div class="panel-head"><h3>Service Request Journey</h3><div class="panel-tools">@if($requestStageCounts['overdue'])<span class="pill red">{{ $requestStageCounts['overdue'] }} SLA overdue</span>
@endif
<a href="{{ route('customer.section','requests') }}">View all requests →</a></div></div><div class="stage-grid journey-grid">
                        <div class="stage {{ $requestStageCounts['new'] ? 'active' : '' }}"><b>{{ $requestStageCounts['new'] }}</b><span>New</span></div>
                        <div class="stage {{ $requestStageCounts['review'] ? 'active' : '' }}"><b>{{ $requestStageCounts['review'] }}</b><span>Under review</span></div>
                        <div class="stage {{ $requestStageCounts['assigned'] ? 'active' : '' }}"><b>{{ $requestStageCounts['assigned'] }}</b><span>Assigned</span></div>
                        <div class="stage {{ $requestStageCounts['scheduled'] ? 'active' : '' }}"><b>{{ $requestStageCounts['scheduled'] }}</b><span>Visit scheduled</span></div>
                        <div class="stage {{ $requestStageCounts['dispatch'] ? 'active' : '' }}"><b>{{ $requestStageCounts['dispatch'] }}</b><span>Team dispatched</span></div>
                        <div class="stage {{ $requestStageCounts['progress'] ? 'active' : '' }}"><b>{{ $requestStageCounts['progress'] }}</b><span>In progress</span></div>
                        <div class="stage {{ $requestStageCounts['customer'] ? 'alert' : '' }}"><b>{{ $requestStageCounts['customer'] }}</b><span>Awaiting customer</span></div>
                        <div class="stage"><b>{{ $requestStageCounts['closed'] }}</b><span>Completed</span></div>
                    </div></div>
                    <div class="card panel"><div class="panel-head"><h3>Priority Today</h3><a href="{{ route('customer.section','work-orders') }}">Open work orders →</a></div><div class="priority-list">@forelse($criticalWorkOrders as $workOrder)@php($lateDays = $workOrder->planned_start && $workOrder->planned_start->isPast() ? max(1, (int) ceil($workOrder->planned_start->diffInDays(now()))) : 0)<a class="list-row" href="{{ route('customer.work-orders.show',$workOrder) }}"><i class="indicator red"></i><div><div class="row-title">{{ $workOrder->work_order_no }} · {{ $workOrder->asset?->name }}</div><div class="row-sub">{{ $workOrder->asset?->site?->name ?: 'Site not assigned' }} · {{ $workOrder->planned_start?->format('d M, H:i') ?: 'Not scheduled' }}</div></div><span class="row-meta">@if($lateDays)<span class="pill red">{{ $lateDays }}d overdue</span>
@else
<span class="pill {{ in_array(strtoupper((string)$workOrder->priority),['EMERGENCY','CRITICAL']) ? 'red' : 'amber' }}">{{ str_replace('_',' ',$workOrder->priority) }}</span>
@endif
</span></a>
@empty
<div class="empty"><strong>No urgent or overdue work</strong>There are no high-priority or overdue work orders in this period.</div>
@endforelse
</div></div>
                </section>
                
@else

                <section class="command-grid">
                    <div class="card panel"><div class="panel-head"><h3>Commercial Overview</h3><a href="{{ route('customer.section','quotations') }}">View quotations →</a></div><div class="stage-grid"><div class="stage"><b>{{ $pendingQuotationCount }}</b><span>Pending quotations</span></div><div class="stage"><b>{{ $activeContractCount }}</b><span>Active contracts</span></div><div class="stage"><b>{{ $renewalActionCount }}</b><span>Renewals due</span></div></div></div>
                    <div class="card panel"><div class="panel-head"><h3>Financial Attention</h3><a href="{{ route('customer.section','invoices') }}">View invoices →</a></div><div class="priority-list"><div class="list-row"><i class="indicator {{ $invoiceActionCount?'red':'' }}"></i><div><div class="row-title">{{ $invoiceActionCount }} invoices require attention</div><div class="row-sub">Open balance {{ number_format($openInvoiceAmount,2) }} SAR</div></div></div></div></div>
                </section>
                
@endif


                <section class="lower-grid">
                    @if(in_array('assets',$allowedSections,true))<div class="card panel"><div class="panel-head"><h3>Asset Health</h3><a href="{{ route('customer.asset-health') }}">Asset 360 →</a></div><div class="asset-health"><div class="health-ring" style="--active:{{ $assetActiveEnd }}%;--maint:{{ $assetMaintenanceEnd }}%;--down:{{ $assetStoppedEnd }}%"><div><b>{{ $assets->count() }}</b><span>Visible assets</span></div></div><div class="health-legend"><div class="health-item"><b>{{ $activeAssetCount }}</b><span>Operational</span></div><div class="health-item"><b>{{ $maintenanceAssetCount }}</b><span>Maintenance</span></div><div class="health-item"><b>{{ $stoppedAssetCount }}</b><span>Stopped</span></div><div class="health-item"><b>{{ $criticalAssetCount }}</b><span>Critical assets</span></div><div class="health-item"><b>{{ $warrantyExpiringCount }}</b><span>Warranty ≤ 60 days</span></div><div class="health-item"><b>{{ $sites->count() }}</b><span>Authorized sites</span></div></div></div></div>
@endif

                    <div class="card panel"><div class="panel-head"><h3>Upcoming Visits & Maintenance</h3>@if(in_array('visits',$allowedSections,true))<a href="{{ route('customer.section','visits') }}">Open schedule →</a>
@endif
</div><div class="upcoming-list">@forelse($upcomingPlans as $plan)<div class="list-row"><i class="indicator"></i><div><div class="row-title">{{ $plan->name }}</div><div class="row-sub">{{ $plan->asset?->site?->name ?: 'Site not assigned' }} · {{ $plan->asset?->name }}</div></div><span class="pill">{{ $plan->next_due_date?->format('d M') }}</span></div>
@empty
<div class="empty"><strong>No scheduled visits</strong>There is no preventive maintenance in the selected period.</div>
@endforelse
</div></div>
                </section>

                <section class="lower-grid">
                    <div class="card panel"><div class="panel-head"><h3>Contracts & SLA</h3><a href="{{ route('customer.section','contracts') }}">Open contracts →</a></div><div class="health-legend"><div class="health-item"><b>{{ $activeContractCount }}</b><span>Active contracts</span></div><div class="health-item"><b>{{ $nextContract?->ends_on?->format('d M Y') ?: '—' }}</b><span>Nearest expiry</span></div><div class="health-item"><b>{{ $slaPerformance===null ? 'N/A' : $slaPerformance.'%' }}</b><span>Measured SLA</span></div><div class="health-item"><b>{{ $renewalActionCount }}</b><span>Renewals due</span></div></div></div>
                    <div class="card panel"><div class="panel-head"><h3>Financial Summary</h3><a href="{{ route('customer.section','invoices') }}">Open finance →</a></div><div class="health-legend"><div class="health-item"><b>{{ number_format($openInvoiceAmount,2) }}</b><span>Open balance · SAR</span></div><div class="health-item"><b>{{ $openInvoiceCount }}</b><span>Open invoices</span></div><div class="health-item"><b>{{ $invoiceActionCount }}</b><span>Due soon</span></div><div class="health-item"><b>{{ $pendingQuotationCount }}</b><span>Pending quotations</span></div></div></div>
                </section>

                <section class="card panel activity-panel"><div class="panel-head"><h3>Recent Relationship Activity</h3><a href="{{ route('customer.section','timeline') }}">Full timeline →</a></div><div class="activity-list">@forelse($timeline->take(6) as $event)<div class="list-row"><i class="indicator"></i><div><div class="row-title">{{ $event->title }}</div><div class="row-sub">{{ str_replace('_',' ',$event->event_type) }} · {{ $event->created_at?->format('d M Y, H:i') }}</div></div></div>
@empty
<div class="empty"><strong>No recent activity</strong>Customer-visible updates will appear here.</div>
@endforelse
</div></section>
            
@endif


            @if($section === 'requests')
                @php($reqTotal=$requests->count())
                @php($reqEmergency=$requests->filter(fn($r)=>strtoupper((string)$r->priority)==='EMERGENCY')->count())
                @php($reqOpen=$requests->filter(fn($r)=>!in_array(strtoupper((string)$r->status),['COMPLETED','CLOSED','CANCELLED'],true))->count())
                @php($reqCompleted=$requests->filter(fn($r)=>in_array(strtoupper((string)$r->status),['COMPLETED','CLOSED'],true))->count())
                @php($reqProgress=$requests->filter(fn($r)=>in_array(strtoupper((string)$r->status),['IN_PROGRESS','ASSIGNED'],true))->count())
                <div class="page-head request-page-head"><div><div class="eyebrow">Customer 360 &nbsp;›&nbsp; Service Requests</div><h2>Service Requests</h2><p>Track every customer request, priority, current stage and next action.</p></div><a class="btn red request-new" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">+ &nbsp; New Service Request</a></div>
                <section class="request-kpis">
                    <div class="card request-kpi"><span class="request-kpi-icon">@include('customer.partials.icon',['name'=>'requests'])</span><div><b>{{ $reqTotal }}</b><small>All Requests</small></div></div>
                    <div class="card request-kpi"><span class="request-kpi-icon amber">◷</span><div><b>{{ $reqOpen }}</b><small>Open</small></div></div>
                    <div class="card request-kpi"><span class="request-kpi-icon red">!</span><div><b>{{ $reqEmergency }}</b><small>Emergency</small></div></div>
                    <div class="card request-kpi"><span class="request-kpi-icon amber">◷</span><div><b>{{ $reqOpen }}</b><small>Overdue</small></div></div>
                    <div class="card request-kpi"><span class="request-kpi-icon">@include('customer.partials.icon',['name'=>'account'])</span><div><b>0</b><small>Awaiting Customer</small></div></div>
                    <div class="card request-kpi"><span class="request-kpi-icon">@include('customer.partials.icon',['name'=>'work-orders'])</span><div><b>{{ $reqProgress }}</b><small>In Progress</small></div></div>
                </section>
                @if($reqEmergency)
                <div class="card request-attention"><span class="request-attention-icon">!</span><div><strong>Needs Attention</strong><small>{{ $reqEmergency }} urgent request{{ $reqEmergency===1?'':'s' }} require attention</small></div><span>›</span></div>
                @endif
                <div class="card request-tools"><label class="request-search"><input id="request-search" type="search" placeholder="Search request no., subject, site or service..."></label><button type="button" class="request-filter-toggle" id="request-filter-toggle">☷</button><div class="request-filter-fields" id="request-filter-fields"><label class="filter"><span>Type</span><select id="request-type"><option value="">All Types</option>@foreach($requests->pluck('request_type')->filter()->unique()->sort() as $type)<option value="{{ strtolower($type) }}">{{ $type }}</option>@endforeach</select></label><label class="filter"><span>Priority</span><select id="request-priority"><option value="">All Priorities</option><option>EMERGENCY</option><option>HIGH</option><option>NORMAL</option></select></label><label class="filter"><span>Status</span><select id="request-status"><option value="">All Statuses</option>@foreach($requests->pluck('status')->filter()->unique()->sort() as $status)<option value="{{ strtoupper($status) }}">{{ $status }}</option>@endforeach</select></label><button type="button" class="filter-button" id="request-reset">Reset</button></div></div>
                <div class="request-tabs"><button class="active" data-request-tab="">All ({{ $reqTotal }})</button><button data-request-tab="open">Open ({{ $reqOpen }})</button><button data-request-tab="emergency">Emergency ({{ $reqEmergency }})</button><button data-request-tab="completed">Completed ({{ $reqCompleted }})</button></div>
                <div class="card table-card request-desktop"><div class="table-wrap"><table class="table"><thead><tr><th>Request</th><th>Type</th><th>Subject</th><th>Priority</th><th>Stage</th><th>Status</th><th>Created</th></tr></thead><tbody>@forelse($requests as $item)<tr data-request-row data-search="{{ strtolower($item->request_no.' '.$item->request_type.' '.$item->subject.' '.$item->status.' '.$item->priority) }}" data-type="{{ strtolower((string)$item->request_type) }}" data-priority="{{ strtoupper((string)$item->priority) }}" data-status="{{ strtoupper((string)$item->status) }}"><td>{{ $item->request_no }}</td><td>{{ $item->request_type }}</td><td>{{ $item->subject }}</td><td><span class="pill {{ in_array($item->priority,['EMERGENCY','HIGH'])?'red':'' }}">{{ $item->priority }}</span></td><td>{{ $item->workflow_stage }}</td><td>{{ $item->status }}</td><td>{{ $item->created_at?->format('Y-m-d') }}</td></tr>@empty<tr><td colspan="7">No requests found.</td></tr>@endforelse</tbody></table></div></div>
                <div class="request-mobile">@forelse($requests as $item)<article class="card request-card" data-request-row data-search="{{ strtolower($item->request_no.' '.$item->request_type.' '.$item->subject.' '.$item->status.' '.$item->priority) }}" data-type="{{ strtolower((string)$item->request_type) }}" data-priority="{{ strtoupper((string)$item->priority) }}" data-status="{{ strtoupper((string)$item->status) }}"><div class="request-card-top"><span class="request-kpi-icon">@include('customer.partials.icon',['name'=>'requests'])</span><div><strong>{{ $item->request_no }}</strong><h3>{{ $item->subject ?: str_replace('_',' ',$item->request_type) }}</h3></div><span class="pill {{ in_array($item->priority,['EMERGENCY','HIGH'])?'red':'' }}">{{ str_replace('_',' ',$item->status) }}</span></div><div class="request-card-meta"><span>{{ str_replace('_',' ',$item->request_type) }}</span><span>{{ str_replace('_',' ',$item->workflow_stage) }}</span><span>{{ $item->created_at?->format('d M Y · H:i') }}</span></div></article>@empty<div class="card empty">No requests found.</div>@endforelse</div>
                <script>document.addEventListener('DOMContentLoaded',()=>{const q=document.getElementById('request-search'),type=document.getElementById('request-type'),priority=document.getElementById('request-priority'),status=document.getElementById('request-status'),reset=document.getElementById('request-reset'),toggle=document.getElementById('request-filter-toggle'),fields=document.getElementById('request-filter-fields'),tabs=[...document.querySelectorAll('[data-request-tab]')],rows=[...document.querySelectorAll('[data-request-row]')];let tab='';const apply=()=>{const s=(q?.value||'').toLowerCase(),ty=(type?.value||'').toLowerCase(),p=(priority?.value||'').toUpperCase(),st=(status?.value||'').toUpperCase();rows.forEach(r=>{let ok=(!s||r.dataset.search.includes(s))&&(!ty||r.dataset.type===ty)&&(!p||r.dataset.priority===p)&&(!st||r.dataset.status===st);if(tab==='open')ok=ok&&!['COMPLETED','CLOSED','CANCELLED'].includes(r.dataset.status);if(tab==='emergency')ok=ok&&r.dataset.priority==='EMERGENCY';if(tab==='completed')ok=ok&&['COMPLETED','CLOSED'].includes(r.dataset.status);r.style.display=ok?'':'none'})};[q,type,priority,status].forEach(el=>el?.addEventListener(el===q?'input':'change',apply));reset?.addEventListener('click',()=>{q.value='';type.value='';priority.value='';status.value='';apply()});toggle?.addEventListener('click',()=>fields?.classList.toggle('open'));tabs.forEach(b=>b.addEventListener('click',()=>{tabs.forEach(x=>x.classList.remove('active'));b.classList.add('active');tab=b.dataset.requestTab;apply()}))});</script>
                <style>
                .request-kpis{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:9px;margin-bottom:10px}.request-kpi{padding:12px;display:flex;align-items:center;gap:9px}.request-kpi-icon{width:36px;height:36px;border-radius:11px;background:#eaf3ff;color:var(--blue);display:grid;place-items:center;flex:0 0 auto;font-weight:900}.request-kpi-icon.red{background:#fdebed;color:var(--red)}.request-kpi-icon.amber{background:#fff3d9;color:#d88900}.request-kpi-icon .ui-icon{width:18px;height:18px}.request-kpi b{font-size:18px;display:block}.request-kpi small{font-size:7.5px;color:var(--muted)}.request-attention{display:flex;align-items:center;gap:10px;padding:11px 14px;border:1px solid #ffc3cb;background:#fff7f8;margin-bottom:10px}.request-attention-icon{width:34px;height:34px;border:1px solid #ff7183;border-radius:50%;display:grid;place-items:center;color:var(--red);font-weight:900}.request-attention div{flex:1}.request-attention strong,.request-attention small{display:block}.request-attention small{font-size:8px;color:#ad4553;margin-top:2px}.request-tools{padding:9px;display:flex;gap:7px;margin-bottom:8px}.request-search{flex:1}.request-search input{width:100%;height:34px;border:1px solid var(--line);border-radius:8px;padding:0 11px;font-size:9px}.request-filter-toggle{display:none;width:36px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--navy)}.request-filter-fields{display:flex;gap:7px}.request-tabs{display:flex;gap:6px;margin:0 0 9px;overflow:auto}.request-tabs button{border:0;background:#eaf0f8;color:#4d607a;border-radius:8px;padding:8px 12px;font-size:8px;font-weight:800;white-space:nowrap}.request-tabs button.active{background:var(--navy);color:#fff}.request-mobile{display:none}
                @media(max-width:900px){.request-kpis{grid-template-columns:repeat(3,1fr)}}
                @media(max-width:780px){.request-page-head{flex-direction:column;align-items:stretch}.request-new{width:100%;text-align:center}.request-kpis{grid-template-columns:repeat(3,1fr)}.request-tools{flex-wrap:wrap}.request-filter-toggle{display:block}.request-filter-fields{display:none;width:100%;grid-template-columns:1fr 1fr}.request-filter-fields.open{display:grid}.request-desktop{display:none}.request-mobile{display:grid;gap:7px}.request-card{padding:10px}.request-card-top{display:grid;grid-template-columns:auto 1fr auto;gap:8px;align-items:start}.request-card-top strong{font-size:9px;color:var(--blue)}.request-card-top h3{font-size:9px;margin:4px 0 0;line-height:1.3}.request-card-meta{display:flex;gap:6px;justify-content:space-between;border-top:1px solid var(--line);padding-top:7px;margin-top:8px;color:var(--muted);font-size:7px}}
                @media(max-width:520px){.request-page-head{gap:9px;margin-bottom:10px}.request-page-head h2{font-size:27px}.request-page-head p{font-size:8px}.request-new{height:36px;padding:9px}.request-kpis{grid-template-columns:repeat(3,minmax(0,1fr));gap:6px}.request-kpi{padding:7px 5px;min-height:66px;gap:5px;justify-content:center;flex-direction:column;text-align:center}.request-kpi-icon{width:29px;height:29px;border-radius:9px}.request-kpi b{font-size:16px}.request-kpi small{font-size:6.8px;line-height:1.1}.request-attention{padding:9px 10px}.request-attention-icon{width:30px;height:30px}.request-tools{padding:7px}.request-search input{height:32px;font-size:8px}.request-tabs button{padding:7px 10px}.request-card{padding:9px}.request-card-top .request-kpi-icon{width:31px;height:31px}.request-card-top .pill{font-size:6.5px;padding:5px 7px}.request-card-meta{font-size:6.8px}}
                </style>
            
@endif


            @if($section === 'work-orders')
                @php($woTotal=$workOrders->count())
                @php($woProgress=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),['IN_PROGRESS','ASSIGNED'],true))->count())
                @php($woCompleted=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),['COMPLETED','CLOSED'],true))->count())
                @php($woOpen=$woTotal-$woCompleted)
                <div class="page-head wo-page-head"><div><div class="eyebrow">Customer 360 &nbsp;›&nbsp; Work Orders</div><h2>Work Orders</h2><p>Track execution records across your customer sites and assets.</p></div><a class="btn red wo-request" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">+ &nbsp; Request Service</a></div>
                <section class="wo-kpis"><div class="card wo-kpi"><b>{{ $woOpen }}</b><small>Open</small></div><div class="card wo-kpi"><b>{{ $woProgress }}</b><small>In Progress</small></div><div class="card wo-kpi overdue"><b>{{ $woOpen }}</b><small>Overdue</small></div><div class="card wo-kpi done"><b>{{ $woCompleted }}</b><small>Completed</small></div></section>
                <div class="wo-mobile-toolbar"><div class="wo-tabs"><span class="active">All <b>{{ $woTotal }}</b></span><span>Open <b>{{ $woOpen }}</b></span><span>In Progress <b>{{ $woProgress }}</b></span><span>Overdue <b>{{ $woOpen }}</b></span></div></div>
                <form class="card panel filters wo-filters" method="GET" action="{{ route('customer.section','work-orders') }}" style="margin-bottom:12px">
                    <label class="filter"><span>Search</span><input name="q" value="{{ $searchFilter }}" placeholder="Work order or asset" style="height:34px;min-width:190px;border:1px solid var(--line);border-radius:8px;padding:0 9px;font-size:9px"></label>
                    <label class="filter"><span>Site</span><select name="site_id"><option value="">All sites</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected($siteFilter===$site->id)>{{ $site->name }}</option>
@endforeach
</select></label>
                    <label class="filter"><span>Contract</span><select name="contract_id"><option value="">All contracts</option>@foreach($contracts as $contract)<option value="{{ $contract->id }}" @selected($contractFilter===$contract->id)>{{ $contract->contract_no }}</option>
@endforeach
</select></label>
                    <label class="filter"><span>Priority</span><select name="priority"><option value="">All priorities</option>@foreach(['NORMAL','HIGH','EMERGENCY'] as $value)<option value="{{ $value }}" @selected($priorityFilter===$value)>{{ str_replace('_',' ',$value) }}</option>
@endforeach
</select></label>
                    <label class="filter"><span>Status</span><select name="status"><option value="">All statuses</option>@foreach(['OPEN','ASSIGNED','IN_PROGRESS','COMPLETED','CLOSED'] as $value)<option value="{{ $value }}" @selected($statusFilter===$value)>{{ str_replace('_',' ',$value) }}</option>
@endforeach
</select></label>
                    <button class="filter-button">Apply</button><a class="btn" href="{{ route('customer.section','work-orders') }}" style="height:34px;padding:8px 13px;background:#edf3fb;color:var(--navy)">Reset</a>
                </form>
                <div class="card table-card wo-desktop"><div class="table-wrap"><table class="table"><thead><tr><th>Work Order</th><th>Asset</th><th>Site</th><th>Type</th><th>Priority</th><th>Status</th><th>Planned</th></tr></thead><tbody>@forelse($workOrders as $item)<tr><td><a class="pill" href="{{ route('customer.work-orders.show',$item) }}">{{ $item->work_order_no }}</a></td><td>{{ $item->asset?->asset_code }} · {{ $item->asset?->name }}</td><td>{{ $item->asset?->site?->name ?: '—' }}</td><td>{{ $item->maintenance_type }}</td><td>{{ $item->priority }}</td><td>{{ $item->status }}</td><td>{{ $item->planned_start?->format('Y-m-d H:i') ?: '—' }}</td></tr>
@empty
<tr><td colspan="7">No work orders in scope.</td></tr>
@endforelse
</tbody></table></div></div>
                <div class="wo-mobile-list">@forelse($workOrders as $item)<a class="card wo-card" href="{{ route('customer.work-orders.show',$item) }}"><div class="wo-card-top"><span class="wo-doc">@include('customer.partials.icon',['name'=>'work-orders'])</span><div><strong>{{ $item->work_order_no }}</strong><div class="wo-asset">{{ $item->asset?->asset_code }} — {{ $item->asset?->name }}</div></div><span class="pill {{ in_array(strtoupper((string)$item->status),['OVERDUE'])?'red':'' }}">{{ str_replace('_',' ',$item->status) }}</span></div><div class="wo-card-meta"><span>⌖ {{ $item->asset?->site?->name ?: 'Site not assigned' }}</span><span>▣ {{ $item->planned_start?->format('d M Y') ?: 'No planned date' }}</span></div></a>@empty<div class="card empty">No work orders in scope.</div>@endforelse</div>
                <style>.wo-kpis,.wo-mobile-toolbar,.wo-mobile-list{display:none}@media(max-width:780px){.wo-page-head{flex-direction:column;align-items:stretch}.wo-request{text-align:center;width:100%}.wo-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:7px;margin-bottom:8px}.wo-kpi{padding:10px;text-align:center}.wo-kpi b{display:block;font-size:19px;color:var(--navy)}.wo-kpi small{font-size:7px;color:var(--muted)}.wo-kpi.overdue b{color:var(--red)}.wo-kpi.done b{color:#15955f}.wo-mobile-toolbar{display:block;margin-bottom:8px;overflow:auto}.wo-tabs{display:flex;gap:5px;min-width:max-content}.wo-tabs span{background:#edf2f8;border-radius:9px;padding:8px 11px;font-size:7px;font-weight:800;color:#52647c}.wo-tabs span.active{background:var(--navy);color:#fff}.wo-tabs b{background:#fff;color:var(--navy);border-radius:50%;padding:3px 5px;margin-left:4px}.wo-filters{padding:7px;display:grid;grid-template-columns:1fr auto;gap:6px}.wo-filters .filter:first-child{grid-column:1/2}.wo-filters .filter:not(:first-child){display:none}.wo-filters .filter-button{height:34px;font-size:0;width:38px;padding:0}.wo-filters .filter-button:after{content:'☷';font-size:15px}.wo-filters>a{display:none}.wo-desktop{display:none}.wo-mobile-list{display:grid;gap:7px}.wo-card{padding:10px;text-decoration:none;color:inherit}.wo-card-top{display:grid;grid-template-columns:auto 1fr auto;gap:8px;align-items:start}.wo-doc{width:32px;height:32px;border-radius:9px;background:#eaf3ff;color:var(--blue);display:grid;place-items:center}.wo-doc .ui-icon{width:17px;height:17px}.wo-card strong{font-size:9px;color:var(--blue)}.wo-asset{font-size:8px;font-weight:800;margin-top:4px;line-height:1.3}.wo-card .pill{font-size:6.5px;padding:5px 7px}.wo-card-meta{display:flex;gap:12px;flex-wrap:wrap;border-top:1px solid var(--line);margin-top:8px;padding-top:7px;font-size:7px;color:var(--muted)}}@media(max-width:520px){.wo-page-head h2{font-size:27px}.wo-page-head p{font-size:8px}.wo-request{height:36px;padding:9px}.wo-kpis{grid-template-columns:repeat(2,1fr)}.wo-kpi{padding:8px;min-height:54px}.wo-filters{margin-bottom:8px!important}}</style>
                <div class="card panel" style="margin-top:12px;display:flex;align-items:center;justify-content:space-between;gap:14px"><div><h3 style="margin-bottom:5px">Need another service?</h3><div class="row-sub">Use the approved unified request form for maintenance, emergencies, quotations, spare parts and technical consultation.</div></div><a class="btn red" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">Request Service</a></div>
            
@endif


            @if($section === 'sites')
                <div class="page-head"><div><h2>Sites</h2><p>Authorized locations, reception contacts and operational coverage.</p></div></div>
                <div class="site-grid">@forelse($sites as $site)<a class="card site-card" href="{{ route('customer.portal',['site_id'=>$site->id]) }}"><strong>{{ $site->site_code }} · {{ $site->name }}</strong><small>{{ $site->city ?: 'City not specified' }}<br>{{ $site->address ?: 'Address not specified' }}<br>Reception: {{ $site->contact_name ?: '—' }} · {{ $site->contact_mobile ?: '—' }}</small><div class="site-meta"><span class="pill {{ $site->status==='ACTIVE'?'green':'amber' }}">{{ $site->status }}</span><span class="pill">{{ $assets->where('customer_site_id',$site->id)->count() }} assets</span></div></a>
@empty
<div class="card empty">No sites in your authorized scope.</div>
@endforelse
</div>
            
@endif


            @if($section === 'assets')
                <div class="page-head"><div><h2>Assets & Equipment</h2><p>Every registered asset and equipment item belonging to this customer.</p></div><a class="btn" href="{{ route('customer.asset-health') }}">Open Asset Health</a></div>
                <div class="asset-grid">@forelse($assets as $asset)<a class="card asset" href="{{ route('customer.asset.show',$asset) }}"><strong>{{ $asset->asset_code }} · {{ $asset->name }}</strong><small>{{ $asset->site?->name ?: 'No site' }}<br>{{ $asset->location_code ?: 'Location not assigned' }}<br>Operational status: {{ $asset->operational_status ?: $asset->status }}<br>Health: {{ $asset->health_score !== null ? $asset->health_score.'%' : 'Not measured' }}</small></a>
@empty
<div class="card empty">No assets in your scope.</div>
@endforelse
</div>
            
@endif


            @if($section === 'visits')
                <div class="page-head"><div><h2>Visits & Schedule</h2><p>Upcoming preventive work and completed technician visits.</p></div></div>
                <section class="lower-grid"><div class="card panel"><div class="panel-head"><h3>Upcoming Maintenance</h3></div>@forelse($upcomingPlans as $plan)<div class="list-row"><i class="indicator"></i><div><div class="row-title">{{ $plan->name }}</div><div class="row-sub">{{ $plan->asset?->site?->name }} · {{ $plan->asset?->name }}</div></div><span class="pill">{{ $plan->next_due_date?->format('Y-m-d') }}</span></div>
@empty
<div class="empty">No upcoming visits.</div>
@endforelse
</div><div class="card panel"><div class="panel-head"><h3>Completed Visits</h3></div>@forelse($visitReports->take(6) as $report)<div class="list-row"><i class="indicator"></i><div><div class="row-title">{{ $report->report_no }} · {{ $report->visit_type }}</div><div class="row-sub">{{ $report->technician_name ?: 'UNIFCO team' }}</div></div><a class="pill" href="{{ route('customer.visits.pdf',$report) }}">PDF</a></div>
@empty
<div class="empty">No completed visits.</div>
@endforelse
</div></section>
            
@endif


            @if($section === 'maintenance')
                <div class="page-head"><div><h2>Maintenance Plan</h2><p>Preventive plans for all customer assets.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Plan</th><th>Asset</th><th>Site</th><th>Frequency</th><th>Next Due</th><th>Status</th></tr></thead><tbody>@forelse($plans as $plan)<tr><td>{{ $plan->plan_no }} · {{ $plan->name }}</td><td>{{ $plan->asset?->asset_code }}</td><td>{{ $plan->asset?->site?->name ?: '—' }}</td><td>{{ $plan->frequency_type }} / {{ $plan->frequency_value }}</td><td>{{ $plan->next_due_date?->format('Y-m-d') }}</td><td>{{ $plan->status }}</td></tr>
@empty
<tr><td colspan="6">No maintenance plans in scope.</td></tr>
@endforelse
</tbody></table></div></div>
            
@endif


            @if($section === 'spare-parts')
                @php($partsActivityCount = $materials->count())
                @php($partsTotalQty = $materials->sum(fn($p)=>(float)$p->quantity))
                @php($partsWorkOrders = $materials->pluck('work_order_no')->filter()->unique()->count())
                @php($partsAssets = $materials->pluck('asset_code')->filter()->unique()->count())
                @php($partsTop = $materials->groupBy('item_name')->map(fn($items)=>$items->sum(fn($i)=>(float)$i->quantity))->sortDesc()->take(5))
                @php($partsMax = max(1,(float)($partsTop->max() ?? 1)))
                <div class="page-head parts-page-head">
                    <div class="parts-title-wrap"><span class="parts-title-icon">@include('customer.partials.icon',['name'=>'spare-parts'])</span><div><h2>Spare Parts</h2><p>Request spare parts, track issued items and review related work orders and asset usage.</p></div></div>
                    <a class="btn red parts-request-btn" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'quotation'=>1,'subtype'=>'parts']) }}">+ &nbsp; Request Spare Parts</a>
                </div>

                <section class="parts-kpis">
                    <div class="card parts-kpi"><span class="parts-kpi-icon blue">@include('customer.partials.icon',['name'=>'spare-parts'])</span><div><b>{{ $partsActivityCount }}</b><strong>Parts Activity</strong><small>{{ $partsActivityCount ? 'Recorded issued items' : 'No activity yet' }}</small></div></div>
                    <div class="card parts-kpi"><span class="parts-kpi-icon orange">◫</span><div><b>{{ rtrim(rtrim(number_format($partsTotalQty,4,'.',''),'0'),'.') ?: '0' }}</b><strong>Total Quantity</strong><small>Across all recorded parts</small></div></div>
                    <div class="card parts-kpi"><span class="parts-kpi-icon green">@include('customer.partials.icon',['name'=>'work-orders'])</span><div><b>{{ $partsWorkOrders }}</b><strong>Related Work Orders</strong><small>Unique work orders</small></div></div>
                    <div class="card parts-kpi"><span class="parts-kpi-icon red">@include('customer.partials.icon',['name'=>'assets'])</span><div><b>{{ $partsAssets }}</b><strong>Assets Served</strong><small>Unique customer assets</small></div></div>
                </section>

                <section class="parts-main-grid">
                    <div class="card parts-requests-panel">
                        <div class="parts-panel-head">
                            <div><h3>@include('customer.partials.icon',['name'=>'spare-parts']) <span>Parts Activity</span></h3><small>{{ $partsActivityCount }} record{{ $partsActivityCount===1?'':'s' }}</small></div>
                            <div class="parts-head-actions"><button type="button" class="parts-tool-btn" id="parts-filter-toggle">Filters</button></div>
                        </div>
                        <div class="parts-tabs">
                            <button class="active" type="button">All ({{ $partsActivityCount }})</button>
                            <button type="button">Work Orders ({{ $partsWorkOrders }})</button>
                            <button type="button">Assets ({{ $partsAssets }})</button>
                        </div>
                        <div class="parts-search-row">
                            <label class="parts-search"><span>@include('customer.partials.icon',['name'=>'search'])</span><input id="parts-search" type="search" placeholder="Search by part name, work order, or asset..."></label>
                        </div>
                        @if($materials->isEmpty())
                            <div class="parts-empty"><span class="parts-empty-icon">@include('customer.partials.icon',['name'=>'spare-parts'])</span><strong>No spare-parts activity yet</strong><p>Issued parts and related work orders will appear here automatically.</p></div>
                        @else
                            <div class="parts-table-wrap"><table class="table parts-table"><thead><tr><th>#</th><th>Part</th><th>Description</th><th>Work Order</th><th>Asset</th><th>Quantity</th><th>Date</th><th>Actions</th></tr></thead><tbody>
                            @foreach($materials as $part)
                                <tr data-part-row data-search="{{ strtolower($part->item_code.' '.$part->item_name.' '.$part->work_order_no.' '.$part->asset_code.' '.$part->asset_name) }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td><div class="parts-part-cell"><span class="parts-mini-icon">@include('customer.partials.icon',['name'=>'spare-parts'])</span><div><strong>{{ $part->item_code }}</strong><small>{{ $part->item_name }}</small></div></div></td>
                                    <td>{{ $part->item_name }}</td>
                                    <td><span class="parts-link">{{ $part->work_order_no ?: '—' }}</span></td>
                                    <td>{{ $part->asset_code }}<small>{{ $part->asset_name }}</small></td>
                                    <td><span class="pill amber">{{ rtrim(rtrim(number_format((float)$part->quantity,4,'.',''),'0'),'.') }} {{ $part->uom }}</span></td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($part->created_at)->format('d M Y') }}</td>
                                    <td><button class="parts-more" type="button" aria-label="More">⋮</button></td>
                                </tr>
                            @endforeach
                            </tbody></table></div>
                            <div class="parts-footer"><span>Showing 1 to {{ $partsActivityCount }} of {{ $partsActivityCount }} entries</span><span class="parts-pages"><b>1</b></span></div>
                        @endif
                    </div>

                    <aside class="parts-side-stack">
                        <div class="card parts-side-card"><div class="parts-panel-head"><h3>Activity Summary</h3></div><div class="parts-donut-wrap"><div class="parts-donut" style="--activity:{{ min(100,$partsActivityCount?100:0) }}%"><div><b>{{ $partsActivityCount }}</b><small>Total records</small></div></div><div class="parts-summary-list"><span><i class="dot blue"></i>Related work orders <b>{{ $partsWorkOrders }}</b></span><span><i class="dot green"></i>Assets served <b>{{ $partsAssets }}</b></span><span><i class="dot orange"></i>Total quantity <b>{{ rtrim(rtrim(number_format($partsTotalQty,2,'.',''),'0'),'.') }}</b></span></div></div></div>
                        <div class="card parts-side-card"><div class="parts-panel-head"><h3>Quick Actions</h3></div><div class="parts-quick-grid"><a class="parts-quick danger" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'quotation'=>1,'subtype'=>'parts']) }}">@include('customer.partials.icon',['name'=>'spare-parts'])<span><b>Request Spare Parts</b><small>Create a new request</small></span></a><a class="parts-quick" href="{{ route('customer.section','work-orders') }}">@include('customer.partials.icon',['name'=>'work-orders'])<span><b>View Work Orders</b><small>Check work order status</small></span></a><a class="parts-quick" href="{{ route('customer.section','documents') }}">@include('customer.partials.icon',['name'=>'documents'])<span><b>Document Library</b><small>View part catalogs & specs</small></span></a><a class="parts-quick" href="{{ route('customer.inbox') }}">@include('customer.partials.icon',['name'=>'inbox'])<span><b>Contact Support</b><small>Get help from our team</small></span></a></div></div>
                    </aside>
                </section>

                <section class="parts-bottom-grid">
                    <div class="card parts-side-card"><div class="parts-panel-head"><h3>Recent Activity</h3><span class="pill">Latest</span></div><div class="parts-activity-list">@forelse($materials->take(3) as $part)<div><i class="activity-dot"></i><span><b>{{ $part->item_name }}</b><small>{{ $part->work_order_no }} · {{ $part->asset_code }}</small></span><time>{{ \Illuminate\Support\Carbon::parse($part->created_at)->diffForHumans() }}</time></div>@empty<div class="parts-empty small">No recent activity.</div>@endforelse</div></div>
                    <div class="card parts-side-card"><div class="parts-panel-head"><h3>Top Requested Parts</h3><span class="pill">By quantity</span></div><div class="parts-bars">@forelse($partsTop as $name=>$qty)<div><span>{{ $name }}</span><i><b style="width:{{ min(100,($qty/$partsMax)*100) }}%"></b></i><strong>{{ rtrim(rtrim(number_format((float)$qty,2,'.',''),'0'),'.') }}</strong></div>@empty<div class="parts-empty small">No part usage data.</div>@endforelse</div></div>
                    <div class="card parts-side-card"><div class="parts-panel-head"><h3>Coverage</h3></div><div class="parts-coverage"><div><b>{{ $partsWorkOrders }}</b><span>Work orders with parts activity</span></div><div><b>{{ $partsAssets }}</b><span>Assets with parts activity</span></div><div><b>{{ $partsActivityCount }}</b><span>Part activity records</span></div><div><b>{{ rtrim(rtrim(number_format($partsTotalQty,2,'.',''),'0'),'.') }}</b><span>Total quantity recorded</span></div></div><p class="parts-note">Availability, delivery and supplier lead-time data are shown only when they are recorded in the operational source data.</p></div>
                </section>

                <script>document.addEventListener('DOMContentLoaded',()=>{const q=document.getElementById('parts-search'),rows=[...document.querySelectorAll('[data-part-row]')];q?.addEventListener('input',()=>{const s=q.value.toLowerCase();rows.forEach(row=>row.style.display=!s||row.dataset.search.includes(s)?'':'none')})});</script>
                <style>
                    .parts-title-wrap{display:flex;align-items:center;gap:12px}.parts-title-icon{width:44px;height:44px;border-radius:12px;background:#fff1f2;color:var(--red);display:grid;place-items:center;box-shadow:0 4px 14px rgba(13,45,91,.08)}.parts-title-icon .ui-icon{width:23px;height:23px}.parts-request-btn{padding-inline:18px}.parts-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:12px}.parts-kpi{display:flex;align-items:center;gap:14px;padding:16px;min-height:104px}.parts-kpi-icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;font-size:22px;flex:0 0 auto}.parts-kpi-icon .ui-icon{width:24px;height:24px}.parts-kpi-icon.blue{background:#eef6ff;color:#1475d1}.parts-kpi-icon.orange{background:#fff4e8;color:#f59e0b}.parts-kpi-icon.green{background:#ebf9f3;color:#0aa06e}.parts-kpi-icon.red{background:#fff0f2;color:#e20b24}.parts-kpi b{display:block;font-size:28px;line-height:1}.parts-kpi strong{display:block;font-size:11px;margin-top:5px}.parts-kpi small{display:block;font-size:8px;color:var(--muted);margin-top:5px}.parts-main-grid{display:grid;grid-template-columns:minmax(0,2.1fr) minmax(320px,.85fr);gap:12px}.parts-requests-panel,.parts-side-card{padding:14px}.parts-panel-head{display:flex;justify-content:space-between;align-items:center;gap:12px}.parts-panel-head h3{font-size:12px;margin:0;display:flex;align-items:center;gap:7px}.parts-panel-head h3 .ui-icon{width:17px;height:17px}.parts-panel-head small{font-size:8px;color:var(--muted)}.parts-tool-btn{height:32px;border:1px solid var(--line);background:#fff;border-radius:8px;padding:0 12px;font-size:9px;font-weight:800}.parts-tabs{display:flex;gap:18px;border-bottom:1px solid var(--line);margin-top:10px;overflow:auto}.parts-tabs button{background:none;border:0;padding:10px 0;font-size:9px;color:var(--muted);white-space:nowrap}.parts-tabs button.active{color:var(--blue);font-weight:900;border-bottom:2px solid var(--blue)}.parts-search-row{padding:10px 0}.parts-search{height:34px;border:1px solid var(--line);border-radius:8px;display:flex;align-items:center;gap:7px;padding:0 10px}.parts-search .ui-icon{width:15px;height:15px;color:var(--muted)}.parts-search input{border:0;outline:0;width:100%;font-size:9px;background:transparent}.parts-table-wrap{overflow:auto}.parts-table{min-width:860px}.parts-table th{font-size:7px}.parts-table td{font-size:8px;vertical-align:middle}.parts-table td small{display:block;color:var(--muted);margin-top:3px}.parts-part-cell{display:flex;align-items:center;gap:8px}.parts-mini-icon{width:28px;height:28px;border-radius:8px;background:#eef6ff;color:var(--blue);display:grid;place-items:center}.parts-mini-icon .ui-icon{width:14px;height:14px}.parts-link{color:var(--blue);font-weight:800}.parts-more{border:0;background:none;font-size:17px;color:var(--muted)}.parts-footer{display:flex;justify-content:space-between;align-items:center;font-size:8px;color:var(--muted);padding-top:10px}.parts-pages b{display:grid;place-items:center;width:28px;height:28px;border-radius:7px;background:var(--blue);color:#fff}.parts-side-stack{display:grid;gap:12px}.parts-donut-wrap{display:grid;grid-template-columns:150px 1fr;gap:12px;align-items:center;padding-top:12px}.parts-donut{width:120px;height:120px;border-radius:50%;background:conic-gradient(#ff9418 0 72%,#0aa06e 72% 90%,#1475d1 90% 98%,#e20b24 98% 100%);display:grid;place-items:center;position:relative}.parts-donut:after{content:"";position:absolute;inset:18px;background:#fff;border-radius:50%}.parts-donut>div{position:relative;z-index:1;text-align:center}.parts-donut b{display:block;font-size:24px}.parts-donut small{font-size:8px;color:var(--muted)}.parts-summary-list{display:grid;gap:11px}.parts-summary-list span{display:grid;grid-template-columns:8px 1fr auto;gap:8px;align-items:center;font-size:8px}.dot{width:8px;height:8px;border-radius:50%;display:block}.dot.blue{background:#1475d1}.dot.green{background:#0aa06e}.dot.orange{background:#ff9418}.parts-quick-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px}.parts-quick{display:flex;align-items:flex-start;gap:9px;text-decoration:none;color:var(--ink);background:#f7faff;border-radius:9px;padding:11px}.parts-quick.danger{background:#fff1f2;color:#d80d26}.parts-quick .ui-icon{width:18px;height:18px;flex:0 0 auto}.parts-quick b{display:block;font-size:8px}.parts-quick small{display:block;font-size:7px;color:var(--muted);margin-top:3px}.parts-bottom-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px}.parts-activity-list{display:grid;margin-top:8px}.parts-activity-list>div{display:grid;grid-template-columns:9px 1fr auto;gap:8px;align-items:center;padding:8px 0;border-bottom:1px solid var(--line)}.activity-dot{width:8px;height:8px;border-radius:50%;background:#0aa06e}.parts-activity-list b{display:block;font-size:8px}.parts-activity-list small,.parts-activity-list time{display:block;font-size:7px;color:var(--muted)}.parts-bars{display:grid;gap:10px;margin-top:12px}.parts-bars>div{display:grid;grid-template-columns:110px 1fr 28px;gap:8px;align-items:center;font-size:8px}.parts-bars i{height:7px;background:#edf2f7;border-radius:99px;overflow:hidden}.parts-bars i b{display:block;height:100%;background:#1475d1;border-radius:inherit}.parts-coverage{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px}.parts-coverage div{background:#f7f9fc;border-radius:8px;padding:10px}.parts-coverage b{display:block;font-size:16px}.parts-coverage span{display:block;font-size:7px;color:var(--muted);margin-top:4px}.parts-note{font-size:7px;line-height:1.5;color:var(--muted);background:#eef6ff;border-radius:8px;padding:9px;margin:8px 0 0}.parts-empty{min-height:180px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;color:var(--muted)}.parts-empty.small{min-height:80px}.parts-empty-icon{width:48px;height:48px;border-radius:14px;background:#eef6ff;color:var(--blue);display:grid;place-items:center}.parts-empty-icon .ui-icon{width:24px;height:24px}.parts-empty strong{font-size:11px;color:var(--ink);margin:10px 0 4px}.parts-empty p{font-size:8px}
                    @media(max-width:1180px){.parts-main-grid{grid-template-columns:1fr}.parts-side-stack{grid-template-columns:1fr 1fr}.parts-bottom-grid{grid-template-columns:1fr 1fr}.parts-bottom-grid>*:last-child{grid-column:1/-1}}
                    @media(max-width:780px){.parts-kpis{grid-template-columns:1fr 1fr}.parts-side-stack,.parts-bottom-grid{grid-template-columns:1fr}.parts-bottom-grid>*:last-child{grid-column:auto}.parts-donut-wrap{grid-template-columns:120px 1fr}.parts-donut{width:105px;height:105px}.parts-page-head{align-items:flex-start}.parts-request-btn{width:100%;justify-content:center;margin-top:8px}.parts-table{min-width:720px}}
                    @media(max-width:520px){.parts-kpis{gap:8px}.parts-kpi{padding:11px;min-height:92px;align-items:flex-start}.parts-kpi-icon{width:38px;height:38px;border-radius:10px}.parts-kpi b{font-size:21px}.parts-kpi strong{font-size:9px}.parts-kpi small{font-size:7px}.parts-quick-grid{grid-template-columns:1fr}.parts-title-icon{width:38px;height:38px}.parts-title-wrap{align-items:flex-start}}
                </style>
@endif


            @if($section === 'quotations')
                @php($quotationCount = $quotations->count())
                @php($quotationDraft = $quotations->filter(fn($q)=>strtoupper((string)$q->status)==='DRAFT')->count())
                @php($quotationAwaiting = $quotations->filter(fn($q)=>in_array(strtoupper((string)$q->status),['SENT','UNDER_REVIEW','REVISION_REQUESTED'],true))->count())
                @php($quotationApproved = $quotations->filter(fn($q)=>in_array(strtoupper((string)$q->status),['APPROVED','ACCEPTED'],true))->count())
                @php($quotationRejected = $quotations->filter(fn($q)=>strtoupper((string)$q->status)==='REJECTED')->count())
                @php($quotationTypes = $quotations->groupBy(fn($q)=>trim((string)($q->quotation_type ?? $q->type ?? 'General Quotation'))))
                @php($quoteDateMin = $quotations->min('created_at'))
                @php($quoteDateMax = $quotations->max('updated_at'))
                @php($quotePalette = ['#1475d1','#f59e0b','#7056e8','#15a979','#ef3b67','#e20b24'])
                @php($quoteGradientParts = [])
                @php($quoteCursor = 0)
                @foreach($quotationTypes as $type => $items)
                    @php($slice = $quotationCount ? ($items->count() / $quotationCount) * 100 : 0)
                    @php($color = $quotePalette[$loop->index % count($quotePalette)])
                    @php($quoteGradientParts[] = $color.' '.$quoteCursor.'% '.($quoteCursor+$slice).'%')
                    @php($quoteCursor += $slice)
                @endforeach
                <div class="page-head quote-page-head">
                    <div class="quote-title-wrap"><span class="quote-title-icon">@include('customer.partials.icon',['name'=>'quotations'])</span><div><h2>Quotations</h2><p>Commercial proposals for your customer account. Review quotations, download documents and take action.</p></div></div>
                    <a class="btn quote-request" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'quotation'=>1]) }}">+ &nbsp; Request Quotation</a>
                </div>
                <section class="quote-kpis">
                    <div class="card quote-kpi"><span class="quote-kpi-icon">@include('customer.partials.icon',['name'=>'quotations'])</span><div><b>{{ $quotationCount }}</b><strong>Total Quotations</strong><small>All quotations in your account</small></div></div>
                    <div class="card quote-kpi draft"><span class="quote-kpi-icon">✎</span><div><b>{{ $quotationDraft }}</b><strong>Draft</strong><small>Being prepared</small></div></div>
                    <div class="card quote-kpi waiting"><span class="quote-kpi-icon">◷</span><div><b>{{ $quotationAwaiting }}</b><strong>Awaiting Decision</strong><small>Require your action</small></div></div>
                    <div class="card quote-kpi approved"><span class="quote-kpi-icon">✓</span><div><b>{{ $quotationApproved }}</b><strong>Approved</strong><small>Accepted quotations</small></div></div>
                    <div class="card quote-kpi rejected"><span class="quote-kpi-icon">×</span><div><b>{{ $quotationRejected }}</b><strong>Rejected</strong><small>Not accepted</small></div></div>
                </section>
                <div class="card quote-tools">
                    <label class="quote-search"><span class="quote-search-icon">@include('customer.partials.icon',['name'=>'search'])</span><input id="quote-search" type="search" placeholder="Search quotations by number, type or related request..."></label>
                    <label class="filter quote-desktop-filter"><select id="quote-status"><option value="">All Statuses</option><option>DRAFT</option><option>SENT</option><option>UNDER_REVIEW</option><option>REVISION_REQUESTED</option><option>APPROVED</option><option>REJECTED</option></select></label>
                    <label class="filter quote-desktop-filter"><select id="quote-type"><option value="">All Types</option>@foreach($quotationTypes->keys()->sort() as $type)<option value="{{ strtolower($type) }}">{{ $type }}</option>@endforeach</select></label>
                    <div class="quote-date-box quote-desktop-filter">▣ <span>{{ $quoteDateMin?->format('d M Y') ?: '—' }} - {{ $quoteDateMax?->format('d M Y') ?: '—' }}</span></div>
                    <button type="button" class="quote-sort quote-desktop-filter">⇅ <span>Sort by<br><b>Newest First</b></span></button>
                    <button type="button" class="quote-mobile-filter-btn">Filters</button>
                </div>
                @if($quotations->isEmpty())
                    <div class="card quote-empty"><span class="quote-kpi-icon">@include('customer.partials.icon',['name'=>'quotations'])</span><strong>No quotations found</strong><p>Your commercial proposals will appear here when they are created.</p><a class="btn" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'quotation'=>1]) }}">Request Quotation</a></div>
                @else
                    <div class="card table-card quote-desktop">
                        <div class="table-wrap"><table class="table quote-table"><thead><tr><th>#</th><th>Quotation</th><th>Type / Related Request</th><th>Revision</th><th>Value</th><th>Updated</th><th>Status</th><th>Customer Action</th><th>Actions</th></tr></thead><tbody>@foreach($quotations as $quotation)<tr data-quote-row data-search="{{ strtolower($quotation->quotation_no.' '.($quotation->quotation_type ?? $quotation->type ?? 'general quotation').' '.$quotation->status) }}" data-status="{{ strtoupper((string)$quotation->status) }}" data-type="{{ strtolower(trim((string)($quotation->quotation_type ?? $quotation->type ?? 'General Quotation'))) }}"><td>{{ $loop->iteration }}</td><td><strong>{{ $quotation->quotation_no }} ›</strong><small>Created on {{ $quotation->created_at?->format('d M Y') }}</small></td><td><b>{{ $quotation->quotation_type ?? $quotation->type ?? 'General Quotation' }}</b></td><td>R{{ $quotation->revision_no }}</td><td>{{ number_format((float)$quotation->amount,2) }} {{ $quotation->currency }}</td><td>{{ $quotation->updated_at?->format('d M Y') }}<small>{{ $quotation->updated_at?->format('h:i A') }}</small></td><td><span class="pill">{{ $quotation->status }}</span></td><td>@if($canDecideQuotation && in_array($quotation->status,['SENT','UNDER_REVIEW','REVISION_REQUESTED']))<form class="quote-decision" method="POST" action="{{ route('customer.quotations.decision',$quotation) }}">@csrf<select name="decision"><option value="APPROVE">Approve</option><option value="REVISION">Revision</option><option value="REJECT">Reject</option></select><input name="notes" placeholder="Notes"><button class="btn">Submit</button></form>@else<span class="pill">{{ $readOnly ? 'Read only' : 'No action required' }}</span>@endif</td><td><div class="quote-row-actions"><button type="button" title="View">◉</button><button type="button" title="Download">⇩</button><button type="button" title="More">⋮</button></div></td></tr>@endforeach</tbody></table></div>
                        <div class="quote-footer"><span>Showing 1 to {{ $quotationCount }} of {{ $quotationCount }} quotations</span><span class="quote-page-controls">‹ <b>1</b> ›</span></div>
                    </div>
                    <div class="quote-mobile">@foreach($quotations as $quotation)<article class="card quote-card" data-quote-row data-search="{{ strtolower($quotation->quotation_no.' '.($quotation->quotation_type ?? $quotation->type ?? 'general quotation').' '.$quotation->status) }}" data-status="{{ strtoupper((string)$quotation->status) }}" data-type="{{ strtolower(trim((string)($quotation->quotation_type ?? $quotation->type ?? 'General Quotation'))) }}"><div class="quote-card-top"><span class="quote-kpi-icon">@include('customer.partials.icon',['name'=>'quotations'])</span><div><strong>{{ $quotation->quotation_no }}</strong><small>{{ $quotation->quotation_type ?? $quotation->type ?? 'General Quotation' }}</small><em>{{ $quotation->created_at?->format('d M Y') }} &nbsp; {{ $quotation->updated_at?->format('h:i A') }}</em></div><span class="quote-card-arrow">›</span></div><div class="quote-card-status"><span class="pill">{{ $quotation->status }}</span></div></article>@endforeach<div class="quote-mobile-pager"><button>‹</button><strong>1 of {{ max(1,$quotationCount) }}</strong><button>›</button></div></div>
                    <section class="quote-insights">
                        <div class="card panel quote-type-panel"><div class="panel-head"><h3>Quotations by Type</h3></div><div class="quote-type-chart"><div class="quote-donut" style="background:conic-gradient({{ implode(',', $quoteGradientParts) }});"><span><b>{{ $quotationCount }}</b><small>Total</small></span></div><div class="quote-legend">@foreach($quotationTypes as $type=>$items)<div><i style="background:{{ $quotePalette[$loop->index % count($quotePalette)] }}"></i><span>{{ $type }}</span><b>{{ $items->count() }}</b><small>{{ $quotationCount ? round(($items->count()/$quotationCount)*100) : 0 }}%</small></div>@endforeach</div></div></div>
                        <div class="card panel"><div class="panel-head"><h3>Recent Quotations</h3><a href="#">View All</a></div>@foreach($quotations->sortByDesc('updated_at')->take(5) as $quotation)<div class="list-row"><i class="indicator"></i><div><div class="row-title">{{ $quotation->quotation_no }}</div><div class="row-sub">{{ $quotation->updated_at?->format('d M Y') }}</div></div><span class="pill">{{ ucfirst(strtolower((string)$quotation->status)) }}</span></div>@endforeach</div>
                        <div class="card panel"><div class="panel-head"><h3>Quick Actions</h3></div><a class="list-row" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'quotation'=>1]) }}"><i class="indicator"></i><div><div class="row-title">Request a New Quotation</div><div class="row-sub">Submit a new request for a quotation.</div></div><span>›</span></a><div class="list-row"><i class="indicator"></i><div><div class="row-title">Download Multiple</div><div class="row-sub">Select and download quotations.</div></div><span>›</span></div><a class="list-row" href="{{ route('customer.inbox') }}"><i class="indicator"></i><div><div class="row-title">Need Help?</div><div class="row-sub">Contact our support team.</div></div><span>›</span></a></div>
                    </section>
                @endif
                <script>document.addEventListener('DOMContentLoaded',()=>{const q=document.getElementById('quote-search'),s=document.getElementById('quote-status'),t=document.getElementById('quote-type'),rows=[...document.querySelectorAll('[data-quote-row]')];const apply=()=>{const text=(q?.value||'').toLowerCase(),status=(s?.value||'').toUpperCase(),type=(t?.value||'').toLowerCase();rows.forEach(row=>row.style.display=(!text||row.dataset.search.includes(text))&&(!status||row.dataset.status===status)&&(!type||row.dataset.type===type)?'':'none')};q?.addEventListener('input',apply);s?.addEventListener('change',apply);t?.addEventListener('change',apply)});</script>
                <style>
                @media(min-width:781px){body:has(.quote-page-head) .content{max-width:none;width:100%;margin:0}.quote-page-head{margin-bottom:13px}.quote-title-wrap{display:flex;align-items:flex-start;gap:12px}.quote-title-icon{width:39px;height:39px;border-radius:9px;background:#edf4fd;color:var(--blue);display:grid;place-items:center}.quote-title-icon .ui-icon{width:22px;height:22px}.quote-page-head h2{font-size:27px;margin:0 0 4px}.quote-page-head p{font-size:9px}.quote-request{min-width:165px;padding:10px 15px;font-size:9px}.quote-kpis{gap:10px;margin-bottom:12px}.quote-kpi{padding:13px 14px;min-height:98px;gap:11px}.quote-kpi-icon{width:42px;height:42px;border-radius:13px;font-size:22px}.quote-kpi-icon .ui-icon{width:21px;height:21px}.quote-kpi b{font-size:21px}.quote-kpi strong{font-size:9px;margin:5px 0 3px}.quote-kpi small{font-size:7px}.quote-tools{padding:9px;gap:8px;margin-bottom:12px;grid-template-columns:minmax(320px,1.75fr) minmax(130px,.6fr) minmax(130px,.6fr) minmax(180px,.8fr) minmax(130px,.55fr)}.quote-search input,.quote-tools .filter select{height:36px;font-size:9px}.quote-date-box,.quote-sort{height:36px}.quote-desktop{padding:12px}.quote-table{font-size:9px}.quote-table th{font-size:8px}.quote-table th,.quote-table td{padding:9px 7px}.quote-table td strong{font-size:9px}.quote-table td small{font-size:7px}.quote-footer{padding:10px 12px;font-size:8px}.quote-insights{gap:10px;margin-top:10px}.quote-insights .panel{padding:13px}.quote-insights .panel-head{margin-bottom:11px}.quote-insights .panel-head h3{font-size:11px}.quote-insights .list-row{padding:7px 0}}
                .quote-page-head h2{display:flex;align-items:center;gap:8px}.quote-kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px}.quote-kpi{display:flex;align-items:flex-start}.quote-kpi-icon{background:#eaf3ff;color:var(--blue);display:grid;place-items:center;flex:0 0 auto;font-weight:800}.quote-kpi b{line-height:1;display:block}.quote-kpi strong{display:block}.quote-kpi small{display:block;color:var(--muted);line-height:1.35}.quote-kpi.draft .quote-kpi-icon{background:#fff4df;color:#d88a00}.quote-kpi.waiting .quote-kpi-icon,.quote-kpi.rejected .quote-kpi-icon{background:#fdebed;color:var(--red)}.quote-kpi.approved .quote-kpi-icon{background:#e8f8ee;color:#10a75a}.quote-tools{display:grid}.quote-search{position:relative}.quote-search-icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#5a6d87}.quote-search-icon .ui-icon{width:16px;height:16px}.quote-search input{width:100%;border:1px solid var(--line);border-radius:8px;padding:0 11px 0 34px}.quote-tools .filter select{width:100%}.quote-date-box{border:1px solid var(--line);border-radius:8px;background:#fff;display:flex;align-items:center;gap:8px;padding:0 10px;font-size:8px;color:var(--ink)}.quote-sort{border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);display:flex;align-items:center;justify-content:center;gap:7px;font-size:8px}.quote-sort span{text-align:left;line-height:1.2}.quote-sort b{font-size:8px}.quote-mobile-filter-btn{display:none}.quote-table td strong{display:block;color:#006bd6}.quote-table td small{display:block;color:var(--muted);margin-top:3px}.quote-row-actions{display:flex;gap:4px}.quote-row-actions button{border:0;background:transparent;color:var(--navy);font-weight:900;cursor:pointer}.quote-footer{display:flex;justify-content:space-between;align-items:center;color:var(--muted);border-top:1px solid var(--line)}.quote-page-controls{display:flex;gap:8px;align-items:center}.quote-page-controls b{background:#0d63c9;color:#fff;border-radius:6px;padding:7px 11px}.quote-mobile{display:none}.quote-insights{display:grid;grid-template-columns:1.05fr 1fr 1fr}.quote-type-chart{display:grid;grid-template-columns:130px 1fr;gap:14px;align-items:center}.quote-donut{width:112px;height:112px;border-radius:50%;position:relative;display:grid;place-items:center}.quote-donut:after{content:'';position:absolute;inset:19px;background:#fff;border-radius:50%}.quote-donut span{position:relative;z-index:1;text-align:center}.quote-donut b{display:block;font-size:22px}.quote-donut small{font-size:7px;color:var(--muted)}.quote-legend{display:grid;gap:6px}.quote-legend>div{display:grid;grid-template-columns:8px 1fr 20px 28px;gap:6px;align-items:center;font-size:7px}.quote-legend i{width:7px;height:7px;border-radius:50%}.quote-legend small{color:var(--muted)}.quote-empty{min-height:260px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:25px}.quote-empty strong{font-size:14px;margin:12px 0 5px}.quote-empty p{font-size:9px;color:var(--muted);margin:0 0 14px}.quote-card{padding:12px}.quote-card-top{display:grid;grid-template-columns:auto 1fr auto;gap:9px;align-items:start}.quote-card-top strong{display:block;color:#006bd6;font-size:10px}.quote-card-top small,.quote-card-top em{display:block;color:var(--muted);font-size:8px;margin-top:3px;font-style:normal}.quote-card-arrow{font-size:18px;color:var(--navy)}.quote-card-status{display:flex;justify-content:flex-end;margin-top:-18px}.quote-mobile-pager{display:flex;align-items:center;justify-content:space-between;padding:8px 4px}.quote-mobile-pager button{width:34px;height:32px;border:1px solid var(--line);background:#fff;border-radius:7px}.quote-mobile-pager strong{font-size:9px}
                @media(max-width:1050px){.quote-kpis{grid-template-columns:repeat(3,1fr)}.quote-insights{grid-template-columns:1fr 1fr}.quote-insights>div:last-child{grid-column:1/-1}}
                @media(max-width:780px){.quote-page-head{flex-direction:column;align-items:stretch}.quote-title-wrap{display:flex;gap:10px;align-items:flex-start}.quote-title-icon{width:36px;height:36px;border-radius:9px;background:#edf4fd;color:var(--blue);display:grid;place-items:center}.quote-title-icon .ui-icon{width:20px;height:20px}.quote-request{width:100%;text-align:center}.quote-kpis{grid-template-columns:1fr 1fr}.quote-tools{grid-template-columns:1fr auto auto;gap:7px}.quote-search{grid-column:1/-1}.quote-desktop-filter{display:none!important}.quote-mobile-filter-btn{display:block;border:1px solid var(--line);background:#fff;border-radius:8px;padding:0 13px;font-size:8px;font-weight:800}.quote-mobile-filter-btn:before{content:'▽ ';}.quote-desktop{display:none}.quote-mobile{display:grid;gap:8px}.quote-insights{display:none}.quote-tools:after{content:'⇅';display:grid;place-items:center;width:40px;border:1px solid var(--line);border-radius:8px;background:#fff}.quote-tools:before{content:'▣  {{ $quoteDateMin?->format('d M Y') ?: '—' }}\A{{ $quoteDateMax?->format('d M Y') ?: '—' }}';white-space:pre;display:flex;align-items:center;justify-content:center;grid-column:1/2;border:1px solid var(--line);border-radius:8px;background:#fff;font-size:7px;text-align:center;padding:5px}.quote-mobile-filter-btn{grid-column:2/3}.quote-tools:after{grid-column:3/4}.quote-kpi{min-height:74px;padding:9px 10px;gap:8px}.quote-kpi-icon{width:34px;height:34px;border-radius:10px}.quote-kpi b{font-size:18px}.quote-kpi strong{font-size:8px}.quote-kpi small{display:none}.quote-card{padding:10px}.quote-card-status .pill{font-size:7px}}
                @media(max-width:520px){.quote-page-head h2{font-size:25px}.quote-page-head p{font-size:8px;line-height:1.45}.quote-request{height:36px;padding:9px 12px}.quote-kpis{gap:7px}.quote-tools{padding:8px;margin-bottom:9px}.quote-search input{height:34px;font-size:8px}.quote-mobile{gap:7px}}
                </style>
            
@endif


            @if($section === 'contracts')
                <div class="page-head"><div><h2>Contracts</h2><p>All contracts and service coverage for this customer account.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Contract</th><th>Title</th><th>Period</th><th>Status</th><th></th></tr></thead><tbody>@forelse($contracts as $contract)<tr><td>{{ $contract->contract_no }}</td><td>{{ $contract->title }}</td><td>{{ $contract->starts_on?->format('Y-m-d') }} — {{ $contract->ends_on?->format('Y-m-d') }}</td><td>{{ $contract->status }}</td><td><a class="pill" href="{{ route('customer.contracts.pdf',$contract) }}">PDF</a></td></tr>
@empty
<tr><td colspan="5">No contracts in scope.</td></tr>
@endforelse
</tbody></table></div></div>
            
@endif


            @if($section === 'invoices')
                @php($invoiceTotal = $invoices->sum(fn($i)=>(float)$i->amount))
                @php($invoiceOpen = $invoices->sum(fn($i)=>(float)$i->open_amount))
                @php($invoicePaid = max(0,$invoiceTotal-$invoiceOpen))
                @php($invoiceOverdue = $invoices->filter(fn($i)=>$i->due_date && $i->due_date->isPast() && (float)$i->open_amount>0)->sum(fn($i)=>(float)$i->open_amount))
                @php($invoiceOpenCount = $invoices->filter(fn($i)=>(float)$i->open_amount>0)->count())
                <div class="page-head"><div><div class="eyebrow">Customer Finance</div><h2>Invoices & Payments</h2><p>View invoices, balances, due dates and payment status for your customer account.</p></div></div>
                <section class="stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:12px">
                    <div class="card stat warning"><div class="label">Total Outstanding</div><div class="value compact">SAR {{ number_format($invoiceOpen,2) }}</div><div class="trend muted">{{ $invoiceOpenCount }} open invoice{{ $invoiceOpenCount===1?'':'s' }}</div></div>
                    <div class="card stat"><div class="label">Paid Amount</div><div class="value compact">SAR {{ number_format($invoicePaid,2) }}</div><div class="trend muted">Across visible invoices</div></div>
                    <div class="card stat warning"><div class="label">Overdue Amount</div><div class="value compact">SAR {{ number_format($invoiceOverdue,2) }}</div><div class="trend {{ $invoiceOverdue>0?'danger':'muted' }}">{{ $invoiceOverdue>0?'Action may be required':'No overdue balance' }}</div></div>
                    <div class="card stat"><div class="label">Open Invoices</div><div class="value">{{ $invoiceOpenCount }}</div><div class="trend muted">{{ $invoices->count() }} total invoice{{ $invoices->count()===1?'':'s' }}</div></div>
                </section>
                <div class="card panel" style="margin-bottom:12px">
                    <div class="filters"><label class="filter" style="flex:1"><span>Search invoices</span><input id="invoice-search" placeholder="Invoice number, status or amount" style="height:34px;width:100%;min-width:190px;border:1px solid var(--line);border-radius:8px;padding:0 10px;font-size:9px"></label><label class="filter"><span>Status</span><select id="invoice-status"><option value="">All statuses</option><option>OPEN</option><option>PAID</option><option>OVERDUE</option><option>PARTIALLY PAID</option></select></label><button class="filter-button" type="button" id="invoice-reset">Reset</button></div>
                </div>
                <div class="card table-card">
                    <div class="table-wrap"><table class="table" id="invoice-table"><thead><tr><th>Invoice #</th><th>Issue Date</th><th>Due Date</th><th>Amount</th><th>Balance Due</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                    @forelse($invoices as $invoice)<tr data-invoice-row data-search="{{ strtolower($invoice->document_no.' '.$invoice->status.' '.$invoice->amount.' '.$invoice->open_amount) }}" data-status="{{ strtoupper($invoice->status) }}"><td><strong>{{ $invoice->document_no }}</strong></td><td>{{ $invoice->document_date?->format('d M Y') ?: '—' }}</td><td>{{ $invoice->due_date?->format('d M Y') ?: '—' }}</td><td>{{ number_format((float)$invoice->amount,2) }} {{ $invoice->currency }}</td><td>{{ number_format((float)$invoice->open_amount,2) }} {{ $invoice->currency }}</td><td><span class="pill {{ (float)$invoice->open_amount<=0?'green':($invoice->due_date && $invoice->due_date->isPast()?'red':'amber') }}">{{ $invoice->status }}</span></td><td><a class="pill" href="{{ route('customer.invoices.pdf',$invoice) }}">View / PDF</a></td></tr>
                    
@empty
<tr><td colspan="7"><div class="empty" style="padding:42px 20px"><strong style="font-size:14px;color:var(--ink)">No invoices found</strong><div style="margin:7px 0 16px">You don't have any invoices yet. They will appear here once issued.</div><a class="btn" href="{{ route('customer.inbox') }}">Contact Support</a></div></td></tr>
@endforelse

                    </tbody></table></div>
                </div>
                <section class="lower-grid">
                    <div class="card panel"><div class="panel-head"><h3>Payment Summary</h3></div><div class="health-legend"><div class="health-item"><b>SAR {{ number_format($invoiceTotal,2) }}</b><span>Total invoiced</span></div><div class="health-item"><b>SAR {{ number_format($invoicePaid,2) }}</b><span>Recorded paid amount</span></div></div></div>
                    <div class="card panel"><div class="panel-head"><h3>Need Help?</h3></div><div class="row-sub" style="margin-bottom:12px">Contact UNIFCO support for questions about invoices or account balances.</div><a class="btn" href="{{ route('customer.inbox') }}">Contact Support</a></div>
                </section>
                <script>document.addEventListener('DOMContentLoaded',()=>{const q=document.getElementById('invoice-search'),s=document.getElementById('invoice-status'),r=document.getElementById('invoice-reset'),rows=[...document.querySelectorAll('[data-invoice-row]')];const apply=()=>{const text=(q?.value||'').toLowerCase(),status=(s?.value||'').toUpperCase();rows.forEach(row=>row.style.display=(!text||row.dataset.search.includes(text))&&(!status||row.dataset.status===status)?'':'none')};q?.addEventListener('input',apply);s?.addEventListener('change',apply);r?.addEventListener('click',()=>{q.value='';s.value='';apply()})});</script>
                <style>@media(max-width:780px){body:has(#invoice-table) .stats{grid-template-columns:1fr 1fr!important}#invoice-table th:nth-child(2),#invoice-table td:nth-child(2),#invoice-table th:nth-child(4),#invoice-table td:nth-child(4){display:none}}@media(max-width:520px){body:has(#invoice-table) .stats{grid-template-columns:1fr!important}.filters .filter{flex-basis:100%}.filters .filter-button{width:100%}#invoice-table{min-width:560px}}</style>
            
@endif


            @if($section === 'sla')
                @php($slaWorkTotal = max(0, $preventiveCount + $correctiveCount))
                @php($preventiveShare = $slaWorkTotal ? round(($preventiveCount / $slaWorkTotal) * 100) : 0)
                @php($correctiveShare = $slaWorkTotal ? 100 - $preventiveShare : 0)
                <div class="page-head sla-page-head"><div><div class="eyebrow">Service Performance</div><h2>SLA & KPIs</h2><p>Measured response and resolution performance across the complete customer account.</p></div></div>
                <section class="sla-kpi-grid">
                    <div class="card sla-kpi"><span class="sla-kpi-icon">@include('customer.partials.icon',['name'=>'sla'])</span><div><div class="label">Measured SLA</div><div class="value">{{ $slaPerformance===null?'N/A':$slaPerformance.'%' }}</div><div class="trend muted">{{ $slaPerformance===null?'No eligible measurements':'Response and resolution checks' }}</div></div></div>
                    <div class="card sla-kpi"><span class="sla-kpi-icon">@include('customer.partials.icon',['name'=>'maintenance'])</span><div><div class="label">Preventive Work</div><div class="value">{{ $preventiveCount }}</div><div class="trend muted">Scheduled maintenance</div></div></div>
                    <div class="card sla-kpi"><span class="sla-kpi-icon">@include('customer.partials.icon',['name'=>'work-orders'])</span><div><div class="label">Corrective Work</div><div class="value">{{ $correctiveCount }}</div><div class="trend muted">Reactive maintenance</div></div></div>
                    <div class="card sla-kpi overdue"><span class="sla-kpi-icon">@include('customer.partials.icon',['name'=>'notifications'])</span><div><div class="label">Overdue Work</div><div class="value">{{ $overdueCount }}</div><div class="trend danger">{{ $overdueCount ? 'Requires attention' : 'No overdue work' }}</div></div></div>
                </section>
                <section class="sla-insights">
                    <div class="card panel sla-trend-card"><div class="panel-head"><h3>SLA Performance Trend</h3><span class="pill">Current account</span></div>
                        @if($slaPerformance===null)<div class="sla-empty-chart"><div class="sla-bars"><i></i><i></i><i></i><i></i></div><strong>No SLA data available</strong><span>SLA measurements will appear here once eligible requests are available.</span></div>
                        
@else
<div class="sla-score"><strong>{{ $slaPerformance }}%</strong><span>Measured SLA performance</span><div class="sla-meter"><i style="width:{{ min(100,max(0,$slaPerformance)) }}%"></i></div></div>
@endif

                    </div>
                    <div class="card panel"><div class="panel-head"><h3>Work Orders by Type</h3><strong>{{ $slaWorkTotal }} total</strong></div>
                        <div class="sla-mix"><div class="sla-mix-bar"><i style="width:{{ $preventiveShare }}%"></i><b style="width:{{ $correctiveShare }}%"></b></div><div class="sla-legend"><span><i></i>Preventive Work <b>{{ $preventiveCount }} · {{ $preventiveShare }}%</b></span><span><i class="corrective"></i>Corrective Work <b>{{ $correctiveCount }} · {{ $correctiveShare }}%</b></span><span><i class="overdue-dot"></i>Overdue Work <b>{{ $overdueCount }}</b></span></div></div>
                    </div>
                </section>
                <style>
                    .sla-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.sla-kpi{min-height:132px;padding:17px;display:flex;gap:13px;align-items:flex-start}.sla-kpi-icon{width:40px;height:40px;border-radius:12px;background:#eaf3ff;color:var(--blue);display:grid;place-items:center;flex:0 0 auto}.sla-kpi-icon .ui-icon{width:21px;height:21px}.sla-kpi .label{font-size:9px;font-weight:850;color:#53647a}.sla-kpi .value{font-size:27px;font-weight:900;margin:10px 0 5px;letter-spacing:-.04em}.sla-kpi.overdue .sla-kpi-icon{background:#fdebed;color:var(--red)}.sla-kpi.overdue .value{color:var(--red)}.sla-insights{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(280px,.85fr);gap:12px;margin-top:12px}.sla-empty-chart{min-height:220px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;border-radius:10px;background:linear-gradient(180deg,#fbfcfe,#f7f9fc);color:var(--muted)}.sla-empty-chart strong{font-size:12px;color:#40546c;margin:12px 0 5px}.sla-empty-chart span{font-size:9px;max-width:320px;line-height:1.6}.sla-bars{height:40px;display:flex;align-items:flex-end;gap:4px}.sla-bars i{display:block;width:7px;border-radius:4px;background:#c8d7e8}.sla-bars i:nth-child(1){height:14px}.sla-bars i:nth-child(2){height:23px}.sla-bars i:nth-child(3){height:31px}.sla-bars i:nth-child(4){height:39px}.sla-score{min-height:220px;display:flex;flex-direction:column;justify-content:center}.sla-score>strong{font-size:40px}.sla-score>span{font-size:9px;color:var(--muted);margin:4px 0 18px}.sla-meter{height:10px;background:#edf1f5;border-radius:999px;overflow:hidden}.sla-meter i{display:block;height:100%;background:var(--blue);border-radius:inherit}.sla-mix{padding:12px 2px}.sla-mix-bar{height:12px;border-radius:999px;background:#edf1f5;overflow:hidden;display:flex;margin:20px 0 25px}.sla-mix-bar i{background:var(--blue)}.sla-mix-bar b{background:var(--navy)}.sla-legend{display:grid;gap:13px}.sla-legend span{display:grid;grid-template-columns:9px 1fr auto;gap:8px;align-items:center;font-size:9px;color:var(--muted)}.sla-legend span>i{width:8px;height:8px;border-radius:50%;background:var(--blue)}.sla-legend span>i.corrective{background:var(--navy)}.sla-legend span>i.overdue-dot{background:var(--red)}.sla-legend b{color:var(--ink)}
                    @media(max-width:1000px){.sla-kpi-grid{grid-template-columns:1fr 1fr}.sla-insights{grid-template-columns:1fr}}
                    @media(max-width:520px){.sla-page-head{margin-bottom:12px}.sla-page-head h2{font-size:27px}.sla-kpi-grid{grid-template-columns:1fr 1fr;gap:8px}.sla-kpi{min-height:132px;padding:12px;gap:8px;flex-direction:column}.sla-kpi-icon{width:34px;height:34px;border-radius:10px}.sla-kpi-icon .ui-icon{width:18px;height:18px}.sla-kpi .label{font-size:8px}.sla-kpi .value{font-size:24px;margin:6px 0 3px}.sla-kpi .trend{font-size:8px;line-height:1.35}.sla-insights{gap:8px;margin-top:8px}.sla-insights .panel{padding:13px}.sla-empty-chart,.sla-score{min-height:170px}.sla-legend span{font-size:8px}}
                </style>
            
@endif


            @if($section === 'timeline')
                <div class="page-head"><div><h2>Recent Activity</h2><p>Customer-visible transaction and relationship history.</p></div></div>
                <div class="card panel">@forelse($timeline as $event)<div class="list-row"><i class="indicator"></i><div><div class="row-title">{{ $event->title }}</div><div class="row-sub">{{ str_replace('_',' ',$event->event_type) }} · {{ $event->created_at?->format('Y-m-d H:i') }}</div></div></div>
@empty
<div class="empty">No timeline activity.</div>
@endforelse
</div>
            
@endif


            @if($section === 'reports')
                @php($reportCount = $visitReports->count())
                @php($reportThisMonth = $visitReports->filter(fn($r) => $r->visit_date && $r->visit_date->isCurrentMonth())->count())
                @php($awaitingReview = $visitReports->filter(fn($r) => blank($r->customer_acknowledgement))->count())
                @php($completedReportCount = max(0,$reportCount-$awaitingReview))
                @php($reportTypes = $visitReports->groupBy(fn($r) => trim((string)($r->visit_type ?: 'Other'))))
                @php($visibleReports = $visitReports->take(5))
                <div class="reports-shell">
                    <div class="reports-utility">
                        <div class="reports-global-search">@include('customer.partials.icon',['name'=>'search'])<input type="search" placeholder="Search anything..."></div>
                        <div class="reports-utility-actions"><span>EN</span><span class="reports-bell">@include('customer.partials.icon',['name'=>'notifications'])<b>{{ $alerts->count() }}</b></span><span class="reports-user">{{ strtoupper(substr(auth()->user()->name ?: 'UN',0,2)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>Customer Admin</small></div></div>
                    </div>

                    <div class="reports-breadcrumb"><span>Home</span><b>›</b><strong>Reports</strong></div>

                    <div class="reports-hero">
                        <div class="reports-title-wrap"><span class="reports-title-icon">@include('customer.partials.icon',['name'=>'reports'])</span><div><h2>Reports</h2><p>Technical reports across all customer sites and assets.</p></div></div>
                        <div class="reports-head-actions">
                            <button class="reports-action reports-date" type="button">▣ <span>01 Jan 2026 – {{ now()->format('d M Y') }}</span></button>
                            <a class="reports-action reports-export" href="#reports-table">⇩ <span>Export Report</span></a>
                            <a class="reports-action reports-generate" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">＋ <span>Generate Report</span></a>
                        </div>
                    </div>

                    <section class="reports-kpis">
                        <article class="reports-kpi reports-kpi-blue"><span class="reports-kpi-icon">@include('customer.partials.icon',['name'=>'reports'])</span><div><small>Total Reports</small><b>{{ $reportCount }}</b><em>All reports</em></div></article>
                        <article class="reports-kpi reports-kpi-green"><span class="reports-kpi-icon">@include('customer.partials.icon',['name'=>'visits'])</span><div><small>This Month</small><b>{{ $reportThisMonth }}</b><em>Generated this month</em></div></article>
                        <article class="reports-kpi reports-kpi-purple"><span class="reports-kpi-icon">✓</span><div><small>Completed Visits</small><b>{{ $completedReportCount }}</b><em>With final reports</em></div></article>
                        <article class="reports-kpi reports-kpi-orange"><span class="reports-kpi-icon">◷</span><div><small>Awaiting Review</small><b>{{ $awaitingReview }}</b><em>Pending customer review</em></div></article>
                    </section>

                    <section class="reports-filter-card">
                        <div class="reports-filter-grid">
                            <label class="reports-search-box"><span class="sr-only">Search reports</span>@include('customer.partials.icon',['name'=>'search'])<input id="reports-search" type="search" placeholder="Search reports, request number, site, asset or technician..."></label>
                            <label><span>Report Type</span><select id="reports-type"><option value="">All Types</option>@foreach($reportTypes->keys()->sort() as $type)<option value="{{ strtolower($type) }}">{{ $type }}</option>@endforeach</select></label>
                            <label><span>Site</span><select id="reports-site"><option value="">All Sites</option>@foreach($sites as $site)<option value="{{ $site->id }}">{{ $site->name }}</option>@endforeach</select></label>
                            <label><span>Asset</span><select id="reports-asset"><option value="">All Assets</option>@foreach($assets as $asset)<option value="{{ $asset->id }}">{{ $asset->name }}</option>@endforeach</select></label>
                            <label><span>Technician</span><select id="reports-tech"><option value="">All Technicians</option>@foreach($visitReports->pluck('technician_name')->filter()->unique()->sort() as $tech)<option value="{{ strtolower($tech) }}">{{ $tech }}</option>@endforeach</select></label>
                            <label><span>Status</span><select id="reports-status"><option value="">All Statuses</option><option value="completed">Completed</option><option value="under-review">Under Review</option></select></label>
                            <button type="button" class="reports-reset" id="reports-reset">↻ Reset</button>
                            <button type="button" class="reports-apply">⌁ Apply Filters</button>
                        </div>
                    </section>

                    @if($visitReports->isEmpty())
                        <section class="reports-empty-card">
                            <span class="reports-empty-icon">@include('customer.partials.icon',['name'=>'reports'])</span>
                            <strong>No reports available yet</strong>
                            <p>Technical reports will appear here automatically after completed service visits and maintenance activity.</p>
                        </section>
                    @else
                        <section class="reports-table-card" id="reports-table">
                            <div class="reports-table-wrap">
                                <table class="reports-table">
                                    <thead><tr><th>#</th><th>Report No.</th><th>Related Request</th><th>Report</th><th>Site / Asset</th><th>Type</th><th>Technician</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                                    <tbody>
                                    @foreach($visibleReports as $report)
                                        @php($reportAsset = $assets->firstWhere('id',$report->asset_id))
                                        @php($reportWorkOrder = $workOrders->firstWhere('id',$report->work_order_id))
                                        @php($reportStatus = blank($report->customer_acknowledgement) ? 'Under Review' : 'Completed')
                                        <tr data-report-row data-search="{{ strtolower($report->report_no.' '.$report->visit_type.' '.$report->technician_name.' '.($reportAsset?->name ?? '').' '.($reportAsset?->site?->name ?? '')) }}" data-type="{{ strtolower(trim((string)($report->visit_type ?: 'Other'))) }}" data-site="{{ $reportAsset?->customer_site_id }}" data-asset="{{ $report->asset_id }}" data-tech="{{ strtolower((string)$report->technician_name) }}" data-status="{{ $reportStatus==='Completed'?'completed':'under-review' }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td><strong class="report-link">{{ $report->report_no }}</strong></td>
                                            <td><span class="report-link">{{ $reportWorkOrder?->work_order_no ?: '—' }}</span></td>
                                            <td><div class="report-name-cell"><span>@include('customer.partials.icon',['name'=>'documents'])</span><strong>{{ $report->visit_type ?: 'Technical Report' }}</strong></div></td>
                                            <td><strong>{{ $reportAsset?->site?->name ?: '—' }}</strong><small>{{ $reportAsset?->name ?: '—' }}</small></td>
                                            <td><span class="report-type-chip">{{ $report->visit_type ?: 'Technical' }}</span></td>
                                            <td>{{ $report->technician_name ?: '—' }}</td>
                                            <td>{{ $report->visit_date?->format('d M Y') ?: '—' }}<small>{{ $report->created_at?->format('h:i A') }}</small></td>
                                            <td><span class="report-status {{ $reportStatus==='Completed'?'done':'review' }}">{{ $reportStatus }}</span></td>
                                            <td><div class="report-actions"><a href="{{ route('customer.visits.pdf',$report) }}" title="View">◉</a><a href="{{ route('customer.visits.pdf',$report) }}" title="Download">⇩</a><button type="button" title="More">⋮</button></div></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="reports-pagination"><span>Showing 1 to {{ $visibleReports->count() }} of {{ $reportCount }} reports</span><label><select><option>5 per page</option></select></label><div><button>«</button><button>‹</button><button class="active">1</button><button>2</button><button>3</button><button>›</button><button>»</button></div></div>
                        </section>

                        <section class="reports-mobile-list">
                            @foreach($visibleReports as $report)
                                @php($reportAsset = $assets->firstWhere('id',$report->asset_id))
                                @php($reportStatus = blank($report->customer_acknowledgement) ? 'Under Review' : 'Completed')
                                <article class="report-mobile-card" data-report-row data-search="{{ strtolower($report->report_no.' '.$report->visit_type.' '.$report->technician_name.' '.($reportAsset?->name ?? '')) }}" data-type="{{ strtolower(trim((string)($report->visit_type ?: 'Other'))) }}" data-site="{{ $reportAsset?->customer_site_id }}" data-asset="{{ $report->asset_id }}" data-tech="{{ strtolower((string)$report->technician_name) }}" data-status="{{ $reportStatus==='Completed'?'completed':'under-review' }}">
                                    <span class="report-mobile-icon">@include('customer.partials.icon',['name'=>'documents'])</span>
                                    <div><strong>{{ $report->report_no }}</strong><b>{{ $report->visit_type ?: 'Technical Report' }}</b><small>{{ $reportAsset?->site?->name ?: '—' }} · {{ $report->visit_date?->format('d M Y') ?: '—' }}</small></div>
                                    <a href="{{ route('customer.visits.pdf',$report) }}">◉</a>
                                    <span class="report-status {{ $reportStatus==='Completed'?'done':'review' }}">{{ $reportStatus }}</span>
                                </article>
                            @endforeach
                            <div class="reports-mobile-pagination"><button>‹</button><span>1 of {{ max(1,(int)ceil($reportCount/5)) }}</span><button>›</button></div>
                        </section>

                        <section class="reports-insights">
                            <article class="reports-insight-card">
                                <div class="reports-insight-head"><h3>Reports by Type</h3><select><option>This Year</option></select></div>
                                <div class="reports-donut-wrap"><div class="reports-donut"><span><b>{{ $reportCount }}</b><small>Total</small></span></div><div class="reports-legend">@foreach($reportTypes->take(5) as $type=>$items)<div><i></i><span>{{ $type }}</span><b>{{ $items->count() }}</b><small>{{ $reportCount?round(($items->count()/$reportCount)*100):0 }}%</small></div>@endforeach</div></div>
                            </article>
                            <article class="reports-insight-card">
                                <div class="reports-insight-head"><h3>Reports by Site</h3><select><option>This Year</option></select></div>
                                <div class="reports-site-bars">@foreach($sites->take(6) as $site)@php($siteCount=$visitReports->filter(fn($r)=>($assets->firstWhere('id',$r->asset_id)?->customer_site_id)===$site->id)->count())<div><span>{{ $site->name }}</span><i><b style="width:{{ $reportCount?max(5,round(($siteCount/$reportCount)*100)):0 }}%"></b></i><strong>{{ $siteCount }}</strong></div>@endforeach</div>
                            </article>
                            <article class="reports-insight-card">
                                <div class="reports-insight-head"><h3>Recent Reports</h3><a href="#reports-table">View All</a></div>
                                <div class="reports-recent">@foreach($visitReports->take(5) as $report)<div><strong>{{ $report->report_no }}</strong><span>{{ $report->visit_date?->format('d M Y') }}</span><em class="{{ blank($report->customer_acknowledgement)?'review':'done' }}">{{ blank($report->customer_acknowledgement)?'Under Review':'Completed' }}</em></div>@endforeach</div>
                            </article>
                        </section>

                        <section class="reports-footnote"><span class="reports-foot-icon">@include('customer.partials.icon',['name'=>'documents'])</span><div><strong>Reports are generated automatically after visit completion.</strong><p>You can also request a custom report for a specific period, site or asset.</p></div><a href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">Request Custom Report</a></section>
                    @endif
                </div>

                <script>
                document.addEventListener('DOMContentLoaded',()=>{
                    const q=document.getElementById('reports-search'),type=document.getElementById('reports-type'),site=document.getElementById('reports-site'),asset=document.getElementById('reports-asset'),tech=document.getElementById('reports-tech'),status=document.getElementById('reports-status'),reset=document.getElementById('reports-reset'),rows=[...document.querySelectorAll('[data-report-row]')];
                    const apply=()=>{const s=(q?.value||'').toLowerCase();rows.forEach(row=>{const ok=(!s||row.dataset.search.includes(s))&&(!type?.value||row.dataset.type===type.value)&&(!site?.value||row.dataset.site===site.value)&&(!asset?.value||row.dataset.asset===asset.value)&&(!tech?.value||row.dataset.tech===tech.value)&&(!status?.value||row.dataset.status===status.value);row.style.display=ok?'':'none'})};
                    [q,type,site,asset,tech,status].forEach(el=>{el?.addEventListener(el===q?'input':'change',apply)});
                    reset?.addEventListener('click',()=>{if(q)q.value='';[type,site,asset,tech,status].forEach(el=>{if(el)el.value=''});apply()});
                });
                </script>
                <style>
                    @media(min-width:781px){.topbar{display:none}.content{padding-top:0}.main{padding-top:0}}
                    .reports-shell{width:100%;padding:0 0 24px}.reports-utility{height:70px;margin:0 -24px 18px;padding:0 24px;display:flex;align-items:center;justify-content:space-between;background:#fff;border-bottom:1px solid var(--line)}.reports-global-search{width:min(360px,36vw);height:38px;border:1px solid var(--line);border-radius:8px;display:flex;align-items:center;gap:9px;padding:0 12px;background:#f8fbff}.reports-global-search input{border:0;outline:0;background:transparent;width:100%;font-size:10px}.reports-utility-actions{display:flex;align-items:center;gap:10px}.reports-utility-actions>span:not(.reports-bell):not(.reports-user){font-size:9px;font-weight:800;padding:6px 9px;background:#f4f7fb;border-radius:7px}.reports-bell{position:relative;display:grid;place-items:center;width:34px;height:34px}.reports-bell b{position:absolute;right:1px;top:0;background:var(--red);color:#fff;border-radius:50%;font-size:7px;min-width:15px;height:15px;display:grid;place-items:center}.reports-user{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;background:var(--navy);color:#fff;font-size:9px;font-weight:900}.reports-utility-actions div{display:grid}.reports-utility-actions strong{font-size:9px}.reports-utility-actions small{font-size:8px;color:var(--muted)}
                    .reports-breadcrumb{display:flex;gap:10px;align-items:center;font-size:9px;color:var(--muted);margin-bottom:14px}.reports-breadcrumb strong{color:var(--ink)}
                    .reports-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}.reports-title-wrap{display:flex;align-items:center;gap:12px}.reports-title-icon{width:42px;height:42px;display:grid;place-items:center;color:#0870db}.reports-title-icon .ui-icon{width:30px;height:30px}.reports-title-wrap h2{margin:0 0 4px;font-size:28px}.reports-title-wrap p{margin:0;color:var(--muted);font-size:10px}.reports-head-actions{display:flex;gap:9px;align-items:center}.reports-action{height:39px;border:1px solid var(--line);border-radius:8px;padding:0 12px;display:inline-flex;align-items:center;gap:7px;font-size:9px;font-weight:800;background:#fff;color:var(--navy)}.reports-generate{background:var(--navy);color:#fff;border-color:var(--navy)}.reports-date{font-weight:700}
                    .reports-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}.reports-kpi{background:#fff;border:1px solid var(--line);border-radius:10px;padding:15px;display:grid;grid-template-columns:52px 1fr;gap:12px;align-items:center;min-height:96px;box-shadow:var(--shadow)}.reports-kpi-icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;font-size:23px}.reports-kpi-blue .reports-kpi-icon{background:#eaf3ff;color:#0870db}.reports-kpi-green .reports-kpi-icon{background:#e7f8f1;color:#138b63}.reports-kpi-purple .reports-kpi-icon{background:#f0e9ff;color:#7446d7}.reports-kpi-orange .reports-kpi-icon{background:#fff0e7;color:#e36a10}.reports-kpi small,.reports-kpi em{display:block}.reports-kpi small{font-size:9px;font-weight:800}.reports-kpi b{font-size:24px;line-height:1;display:block;margin:5px 0}.reports-kpi em{font-size:8px;color:var(--muted);font-style:normal}
                    .reports-filter-card{background:#fff;border:1px solid var(--line);border-radius:10px;padding:14px;margin-bottom:16px;box-shadow:var(--shadow)}.reports-filter-grid{display:grid;grid-template-columns:minmax(260px,2fr) repeat(3,minmax(130px,.8fr));gap:10px;align-items:end}.reports-filter-grid label{display:grid;gap:5px}.reports-filter-grid label>span:not(.sr-only){font-size:8px;font-weight:800;color:#4e637f}.reports-filter-grid select,.reports-filter-grid input{height:36px;border:1px solid #d8e1ec;border-radius:7px;background:#fff;padding:0 10px;font-size:9px;outline:0}.reports-search-box{position:relative}.reports-search-box .ui-icon{position:absolute;left:10px;bottom:9px;width:16px;height:16px;color:#526b89}.reports-search-box input{padding-left:34px;width:100%}.reports-filter-grid label:nth-of-type(5),.reports-filter-grid label:nth-of-type(6){grid-column:span 1}.reports-reset,.reports-apply{height:36px;border-radius:7px;border:1px solid var(--line);font-size:9px;font-weight:800;cursor:pointer}.reports-reset{background:#fff;color:var(--navy)}.reports-apply{background:var(--navy);color:#fff;border-color:var(--navy)}
                    .reports-table-card,.reports-empty-card{background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:var(--shadow)}.reports-table-wrap{overflow:auto}.reports-table{width:100%;border-collapse:collapse;font-size:9px}.reports-table th,.reports-table td{padding:10px 9px;border-bottom:1px solid #e8edf3;text-align:left;vertical-align:middle;white-space:nowrap}.reports-table th{background:#f8fafc;color:#4d617d;font-size:8px}.reports-table td small{display:block;color:var(--muted);font-size:7px;margin-top:3px}.report-link{color:#0868d6}.report-name-cell{display:flex;align-items:center;gap:7px}.report-name-cell>span{color:#0870db}.report-type-chip,.report-status{display:inline-flex;padding:5px 8px;border-radius:6px;font-size:8px}.report-type-chip{background:#eaf3ff;color:#075fc3}.report-status.done{background:#e5f7ed;color:#177447}.report-status.review{background:#fff1d9;color:#9d6700}.report-actions{display:flex;align-items:center;gap:7px}.report-actions a,.report-actions button{border:0;background:transparent;color:var(--navy);font-size:14px;padding:2px;cursor:pointer}.reports-pagination{display:flex;align-items:center;gap:12px;padding:10px 12px;font-size:8px}.reports-pagination>span{margin-right:auto;color:#526b89}.reports-pagination select{height:30px;border:1px solid var(--line);border-radius:6px;font-size:8px}.reports-pagination>div{display:flex;gap:4px}.reports-pagination button{width:28px;height:28px;border:1px solid var(--line);background:#fff;border-radius:5px;font-size:8px}.reports-pagination button.active{background:var(--navy);color:#fff}
                    .reports-empty-card{min-height:360px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}.reports-empty-icon{width:58px;height:58px;border-radius:15px;background:#eaf3ff;color:#0870db;display:grid;place-items:center}.reports-empty-icon .ui-icon{width:27px;height:27px}.reports-empty-card strong{font-size:14px;margin-top:13px}.reports-empty-card p{font-size:9px;color:var(--muted);max-width:460px}
                    .reports-mobile-list{display:none}.reports-insights{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:12px}.reports-insight-card{background:#fff;border:1px solid var(--line);border-radius:10px;padding:14px;box-shadow:var(--shadow)}.reports-insight-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:13px}.reports-insight-head h3{margin:0;font-size:12px}.reports-insight-head select{height:30px;border:1px solid var(--line);border-radius:6px;font-size:8px}.reports-insight-head a{font-size:8px;color:#0868d6;font-weight:800}.reports-donut-wrap{display:flex;align-items:center;gap:14px}.reports-donut{width:120px;height:120px;border-radius:50%;background:conic-gradient(#0b70f2 0 37%,#ef7d15 37% 62%,#7446d7 62% 78%,#14a09b 78% 92%,#ed334b 92% 100%);display:grid;place-items:center;position:relative}.reports-donut:after{content:"";position:absolute;inset:24px;border-radius:50%;background:#fff}.reports-donut span{z-index:1;text-align:center}.reports-donut b{display:block;font-size:20px}.reports-donut small{font-size:8px}.reports-legend{flex:1;display:grid;gap:7px}.reports-legend div{display:grid;grid-template-columns:8px 1fr auto auto;gap:6px;font-size:8px;align-items:center}.reports-legend i{width:7px;height:7px;border-radius:50%;background:#0b70f2}.reports-legend small{color:var(--muted)}.reports-site-bars{display:grid;gap:9px}.reports-site-bars>div{display:grid;grid-template-columns:120px 1fr 25px;gap:8px;align-items:center;font-size:8px}.reports-site-bars i{height:9px;border-radius:9px;background:#edf2f7;overflow:hidden}.reports-site-bars i b{display:block;height:100%;background:linear-gradient(90deg,#2677ef,#86b5ff);border-radius:inherit}.reports-recent{display:grid}.reports-recent>div{display:grid;grid-template-columns:1fr 82px auto;gap:8px;align-items:center;padding:7px 0;border-bottom:1px solid #edf1f5;font-size:8px}.reports-recent strong{color:#0868d6}.reports-recent em{font-style:normal;padding:4px 6px;border-radius:5px}.reports-recent em.done{background:#e5f7ed;color:#177447}.reports-recent em.review{background:#fff1d9;color:#9d6700}
                    .reports-footnote{margin-top:12px;background:#eaf3ff;border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:11px}.reports-foot-icon{width:40px;height:40px;border-radius:50%;background:#2477ef;color:#fff;display:grid;place-items:center}.reports-footnote div{flex:1}.reports-footnote strong{font-size:9px}.reports-footnote p{font-size:8px;color:#526b89;margin:3px 0 0}.reports-footnote>a{background:var(--navy);color:#fff;border-radius:7px;padding:9px 14px;font-size:8px;font-weight:800}.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
                    @media(max-width:1200px){.reports-filter-grid{grid-template-columns:minmax(240px,1.5fr) repeat(2,minmax(130px,.7fr))}.reports-filter-grid label:nth-of-type(4),.reports-filter-grid label:nth-of-type(5),.reports-filter-grid label:nth-of-type(6){grid-column:auto}.reports-kpis{grid-template-columns:1fr 1fr}.reports-insights{grid-template-columns:1fr 1fr}.reports-insights article:last-child{grid-column:1/-1}}
                    @media(max-width:780px){.topbar{display:flex}.reports-shell{padding-bottom:10px}.reports-utility,.reports-breadcrumb{display:none}.reports-hero{display:block}.reports-title-wrap h2{font-size:24px}.reports-title-wrap p{font-size:10px;line-height:1.5}.reports-head-actions{display:grid;grid-template-columns:1fr 1fr;margin-top:14px}.reports-date{grid-column:1/-1}.reports-action{justify-content:center;height:42px}.reports-kpis{grid-template-columns:1fr 1fr;gap:8px}.reports-kpi{min-height:88px;padding:11px;grid-template-columns:40px 1fr;gap:8px}.reports-kpi-icon{width:38px;height:38px;border-radius:11px}.reports-kpi b{font-size:22px}.reports-filter-card{padding:0;border:0;box-shadow:none;background:transparent}.reports-filter-grid{display:block}.reports-filter-grid>*{display:none!important}.reports-filter-grid:before{content:"⌁  Filters";display:flex;align-items:center;justify-content:space-between;height:44px;background:#fff;border:1px solid var(--line);border-radius:9px;padding:0 12px;font-size:10px;font-weight:800}.reports-table-card,.reports-insights,.reports-footnote{display:none}.reports-mobile-list{display:grid;gap:8px}.report-mobile-card{background:#fff;border:1px solid var(--line);border-radius:9px;padding:11px;display:grid;grid-template-columns:40px 1fr auto;gap:8px;align-items:start;position:relative}.report-mobile-icon{width:38px;height:38px;border-radius:10px;background:#eaf3ff;color:#0870db;display:grid;place-items:center}.report-mobile-card>div{display:grid;gap:3px}.report-mobile-card>div strong{font-size:9px;color:#0868d6}.report-mobile-card>div b{font-size:9px}.report-mobile-card>div small{font-size:7.5px;color:var(--muted)}.report-mobile-card>a{color:var(--navy);font-size:15px}.report-mobile-card>.report-status{grid-column:2/4;justify-self:end}.reports-mobile-pagination{display:flex;align-items:center;justify-content:space-between;padding:10px}.reports-mobile-pagination button{width:34px;height:34px;border:1px solid var(--line);background:#fff;border-radius:7px}.reports-mobile-pagination span{font-size:9px}.reports-empty-card{min-height:280px}.reports-title-icon{width:34px;height:34px}.reports-title-icon .ui-icon{width:26px;height:26px}}
                    @media(max-width:520px){.reports-kpi small{font-size:8px}.reports-kpi em{font-size:7px}.reports-kpi{grid-template-columns:35px 1fr}.reports-kpi-icon{width:34px;height:34px}.reports-head-actions{gap:8px}.reports-action{font-size:8px;padding:0 8px}}
                </style>

            @endif


            @if($section === 'documents')
                @php($documentCount = $attachments->count())
                @php($documentTypes = $attachments->groupBy(fn($a) => strtolower((string)$a->attachment_type)))
                <div class="page-head document-page-head"><div><div class="eyebrow">Customer Records</div><h2>Document Library</h2><p>All customer-visible files associated with contracts, assets and service activity.</p></div></div>
                <section class="document-summary">
                    <div class="card document-stat"><span>@include('customer.partials.icon',['name'=>'documents'])</span><div><b>{{ $documentCount }}</b><small>All Documents</small></div></div>
                    <div class="card document-stat"><span>@include('customer.partials.icon',['name'=>'contracts'])</span><div><b>{{ $documentTypes->filter(fn($v,$k)=>str_contains($k,'contract'))->flatten()->count() }}</b><small>Contracts</small></div></div>
                    <div class="card document-stat"><span>@include('customer.partials.icon',['name'=>'reports'])</span><div><b>{{ $documentTypes->filter(fn($v,$k)=>str_contains($k,'report'))->flatten()->count() }}</b><small>Reports</small></div></div>
                    <div class="card document-stat"><span>@include('customer.partials.icon',['name'=>'quotations'])</span><div><b>{{ $documentTypes->filter(fn($v,$k)=>str_contains($k,'quotation'))->flatten()->count() }}</b><small>Quotations</small></div></div>
                </section>
                <div class="card panel document-tools"><label class="document-search"><span>Search documents</span><input id="document-search" type="search" placeholder="File name or document type..."></label><label class="filter"><span>Type</span><select id="document-type"><option value="">All types</option>@foreach($attachments->pluck('attachment_type')->filter()->unique()->sort() as $type)<option value="{{ strtolower($type) }}">{{ $type }}</option>@endforeach</select></label><button type="button" class="filter-button" id="document-reset">Reset</button></div>
                @if($attachments->isEmpty())
                    <div class="card document-empty"><span class="document-empty-icon">@include('customer.partials.icon',['name'=>'documents'])</span><strong>No documents available yet</strong><p>Your contracts, service reports, quotations and other customer documents will appear here automatically.</p><a class="btn" href="{{ route('customer.inbox') }}">Contact Support</a></div>
                @else
                    <div class="card table-card document-desktop"><div class="table-wrap"><table class="table"><thead><tr><th>File</th><th>Type</th><th>Created</th><th>Actions</th></tr></thead><tbody>@foreach($attachments as $attachment)<tr data-document-row data-search="{{ strtolower($attachment->original_name.' '.$attachment->attachment_type) }}" data-type="{{ strtolower($attachment->attachment_type) }}"><td><strong>{{ $attachment->original_name }}</strong></td><td><span class="pill">{{ $attachment->attachment_type }}</span></td><td>{{ $attachment->created_at?->format('d M Y') }}</td><td><a class="pill" href="{{ route('customer.attachments.download',$attachment->id) }}">Download</a></td></tr>@endforeach</tbody></table></div></div>
                    <div class="document-mobile">@foreach($attachments as $attachment)<article class="card document-card" data-document-row data-search="{{ strtolower($attachment->original_name.' '.$attachment->attachment_type) }}" data-type="{{ strtolower($attachment->attachment_type) }}"><div class="document-card-head"><span class="document-file-icon">@include('customer.partials.icon',['name'=>'documents'])</span><div><strong>{{ $attachment->original_name }}</strong><span class="pill">{{ $attachment->attachment_type }}</span></div></div><small>{{ $attachment->created_at?->format('d M Y') }}</small><a class="btn" href="{{ route('customer.attachments.download',$attachment->id) }}">Download</a></article>@endforeach</div>
                @endif
                <script>document.addEventListener('DOMContentLoaded',()=>{const q=document.getElementById('document-search'),t=document.getElementById('document-type'),r=document.getElementById('document-reset'),rows=[...document.querySelectorAll('[data-document-row]')];const apply=()=>{const s=(q?.value||'').toLowerCase(),type=(t?.value||'').toLowerCase();rows.forEach(row=>row.style.display=(!s||row.dataset.search.includes(s))&&(!type||row.dataset.type===type)?'':'none')};q?.addEventListener('input',apply);t?.addEventListener('change',apply);r?.addEventListener('click',()=>{q.value='';t.value='';apply()})});</script>
                <style>
                    .document-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:12px}.document-stat{padding:15px;display:flex;align-items:center;gap:11px}.document-stat>span,.document-file-icon,.document-empty-icon{width:38px;height:38px;border-radius:10px;background:#eaf3ff;color:var(--blue);display:grid;place-items:center;flex:0 0 auto}.document-stat .ui-icon,.document-file-icon .ui-icon,.document-empty-icon .ui-icon{width:20px;height:20px}.document-stat b{display:block;font-size:20px}.document-stat small{display:block;color:var(--muted);font-size:9px;margin-top:2px}.document-tools{display:flex;align-items:end;gap:10px;margin-bottom:12px}.document-search{flex:1}.document-search span{display:block;font-size:8px;color:var(--muted);font-weight:800;margin-bottom:5px}.document-search input{width:100%;height:34px;border:1px solid var(--line);border-radius:8px;padding:0 10px;font-size:9px}.document-empty{min-height:270px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:30px}.document-empty-icon{width:54px;height:54px;border-radius:15px}.document-empty-icon .ui-icon{width:27px;height:27px}.document-empty strong{font-size:14px;margin:14px 0 5px}.document-empty p{max-width:440px;color:var(--muted);font-size:9px;line-height:1.6;margin:0 0 15px}.document-mobile{display:none}.document-card{padding:13px}.document-card-head{display:flex;gap:10px;align-items:flex-start}.document-card-head>div{min-width:0}.document-card-head strong{display:block;font-size:10px;overflow-wrap:anywhere;margin-bottom:6px}.document-card>small{display:block;color:var(--muted);font-size:8px;margin:9px 0}.document-card .btn{width:100%;justify-content:center}
                    @media(max-width:780px){.document-summary{grid-template-columns:1fr 1fr}.document-tools{align-items:stretch;flex-wrap:wrap}.document-search{flex-basis:100%}.document-tools .filter{flex:1}.document-desktop{display:none}.document-mobile{display:grid;gap:8px}.document-empty{min-height:220px}}
                    @media(max-width:520px){.document-page-head h2{font-size:27px}.document-summary{gap:8px}.document-stat{padding:11px}.document-stat>span{width:34px;height:34px}.document-stat b{font-size:17px}.document-tools{padding:11px}.document-tools .filter-button{min-width:72px}}
                </style>
@endif


            @if($section === 'notifications')
                @include('customer.partials.notifications-workspace', ['alerts' => $alerts])
            @endif

        </div>
    </main>
</div>
<script>(()=>{const app=document.querySelector('.app'),button=document.getElementById('sidebar-toggle');if(!app||!button)return;const key='unifco-customer-sidebar-collapsed';if(localStorage.getItem(key)==='1')app.classList.add('sidebar-collapsed');button.addEventListener('click',()=>{app.classList.toggle('sidebar-collapsed');localStorage.setItem(key,app.classList.contains('sidebar-collapsed')?'1':'0');button.textContent=app.classList.contains('sidebar-collapsed')?'⇥':'⇤'})})();</script>
</body>
</html>
