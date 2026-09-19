<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>UNIFCO Customer Portal · {{ ucwords(str_replace('-', ' ', $section)) }}</title>
    <style>
        :root{font-family:Inter,"Segoe UI",Arial,sans-serif;--navy:#06275c;--navy-deep:#031d49;--ink:#0a234f;--blue:#1475d1;--red:#e20b24;--green:#14875a;--amber:#d88900;--bg:#f3f6fa;--line:#dfe6ef;--muted:#6d7b90;--shadow:0 8px 24px rgba(7,31,77,.06)}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--bg);color:var(--ink)}a{text-decoration:none;color:inherit}button,input,select,textarea{font:inherit}.ui-icon{width:18px;height:18px;display:block;flex:0 0 auto}
        .app{min-height:100vh;display:grid;grid-template-columns:264px minmax(0,1fr)}
        .sidebar{position:sticky;top:0;height:100vh;background:linear-gradient(180deg,var(--navy),var(--navy-deep));color:#fff;padding:16px 12px 12px;display:flex;flex-direction:column;z-index:30;overflow:hidden}
        .account{padding:5px 8px 15px;border-bottom:1px solid rgba(255,255,255,.1)}.account-mark{display:flex;align-items:center;gap:10px}.account-logo{width:38px;height:38px;border-radius:11px;background:#fff;color:var(--navy);display:grid;place-items:center;font-weight:900;font-size:12px}.account strong{font-size:12px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.account small{font-size:9px;color:#aebed4;display:block;margin-top:4px}.role-badge{display:inline-flex;margin-top:10px;padding:5px 8px;border-radius:6px;background:rgba(255,255,255,.1);color:#dce7f4;font-size:8px;font-weight:800;letter-spacing:.06em}
        .side-search{margin:12px 4px 7px;height:35px;border:1px solid rgba(255,255,255,.12);border-radius:9px;display:flex;align-items:center;gap:8px;padding:0 10px;color:#aebed4;font-size:9px}.side-search .ui-icon{width:14px}.nav-scroll{min-height:0;overflow:auto;padding:3px 2px 12px;scrollbar-width:thin;scrollbar-color:#ffffff2e transparent}.nav-group{margin-top:12px}.nav-label{padding:0 10px 5px;color:#7f98b9;font-size:8px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.nav-link{min-height:35px;display:flex;gap:10px;align-items:center;padding:8px 10px;border-radius:8px;font-size:10px;color:#eaf1fa;margin:2px 0;transition:.18s}.nav-link:hover,.nav-link.active{background:rgba(255,255,255,.13);color:#fff}.nav-link.active{box-shadow:inset 3px 0 0 var(--red)}.nav-link span:nth-child(2){min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.nav-badge{margin-left:auto;min-width:19px;height:18px;padding:0 5px;border-radius:9px;background:rgba(255,255,255,.13);display:grid;place-items:center;font-size:8px;font-weight:800}.nav-badge.urgent{background:var(--red);color:#fff}.logout{margin-top:auto;padding-top:9px;border-top:1px solid rgba(255,255,255,.1)}.logout button{width:100%;border:1px solid rgba(255,255,255,.2);background:transparent;color:#fff;padding:9px;border-radius:8px;cursor:pointer;font-size:10px}
        .main{min-width:0;padding:0 24px 44px}.topbar{height:70px;display:flex;align-items:center;gap:18px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.96);margin:0 -24px;padding:0 26px;position:sticky;top:0;z-index:20}.welcome{flex:1;min-width:0}.welcome h1{font-size:16px;margin:0 0 4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.welcome p{font-size:9px;color:var(--muted);margin:0}.top-actions{display:flex;align-items:center;gap:9px}.status-pill,.pill{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:999px;font-size:8px;font-weight:800;background:#edf4fd;color:#3268a5}.status-pill:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}.green{background:#e7f7ee;color:#137346}.red{background:#fdebed;color:#b42239}.amber{background:#fff3d9;color:#9b6500}.avatar{width:34px;height:34px;border-radius:10px;background:var(--navy);color:#fff;display:grid;place-items:center;font-size:10px;font-weight:900}
        .content{padding-top:22px;max-width:1500px;margin:auto}.notice,.role-note{padding:10px 12px;border-radius:9px;font-size:10px;margin-bottom:12px}.notice{background:#e9f7ef;color:#176940}.role-note{background:#eef4fb;border:1px solid #dbe6f2;color:#35536f}.page-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:15px}.page-head h2{font-size:24px;line-height:1.15;margin:0 0 5px;letter-spacing:-.02em}.page-head p{font-size:10px;color:var(--muted);margin:0}.eyebrow{font-size:8px;color:var(--blue);font-weight:850;text-transform:uppercase;letter-spacing:.11em;margin-bottom:7px}
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
                <div class="page-head"><div><h2>Service Requests</h2><p>All service requests belonging to this customer account.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Request</th><th>Type</th><th>Subject</th><th>Priority</th><th>Stage</th><th>Status</th><th>Created</th></tr></thead><tbody>@forelse($requests as $item)<tr><td>{{ $item->request_no }}</td><td>{{ $item->request_type }}</td><td>{{ $item->subject }}</td><td><span class="pill {{ in_array($item->priority,['EMERGENCY','HIGH'])?'red':'' }}">{{ $item->priority }}</span></td><td>{{ $item->workflow_stage }}</td><td>{{ $item->status }}</td><td>{{ $item->created_at?->format('Y-m-d') }}</td></tr>
@empty
<tr><td colspan="7">No requests found.</td></tr>
@endforelse
</tbody></table></div></div>
            
@endif


            @if($section === 'work-orders')
                <div class="page-head"><div><h2>Work Orders</h2><p>All execution records across the customer’s sites and assets.</p></div><a class="btn red" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">Request Service</a></div>
                <form class="card panel filters" method="GET" action="{{ route('customer.section','work-orders') }}" style="margin-bottom:12px">
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
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Work Order</th><th>Asset</th><th>Site</th><th>Type</th><th>Priority</th><th>Status</th><th>Planned</th></tr></thead><tbody>@forelse($workOrders as $item)<tr><td><a class="pill" href="{{ route('customer.work-orders.show',$item) }}">{{ $item->work_order_no }}</a></td><td>{{ $item->asset?->asset_code }} · {{ $item->asset?->name }}</td><td>{{ $item->asset?->site?->name ?: '—' }}</td><td>{{ $item->maintenance_type }}</td><td>{{ $item->priority }}</td><td>{{ $item->status }}</td><td>{{ $item->planned_start?->format('Y-m-d H:i') ?: '—' }}</td></tr>
@empty
<tr><td colspan="7">No work orders in scope.</td></tr>
@endforelse
</tbody></table></div></div>
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
                <div class="page-head"><div><h2>Spare Parts</h2><p>Parts issued against all customer assets and work orders.</p></div><a class="btn red" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'quotation'=>1,'subtype'=>'parts']) }}">Request Spare Parts</a></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Part</th><th>Description</th><th>Work Order</th><th>Asset</th><th>Quantity</th><th>Date</th></tr></thead><tbody>@forelse($materials as $part)<tr><td>{{ $part->item_code }}</td><td>{{ $part->item_name }}</td><td>{{ $part->work_order_no }}</td><td>{{ $part->asset_code }} · {{ $part->asset_name }}</td><td>{{ $part->quantity }} {{ $part->uom }}</td><td>{{ $part->created_at }}</td></tr>
@empty
<tr><td colspan="6">No spare-parts activity in your scope.</td></tr>
@endforelse
</tbody></table></div></div>
            
@endif


            @if($section === 'quotations')
                @php($quotationTotal = $quotations->count())
                @php($quotationDraft = $quotations->where('status','DRAFT')->count())
                @php($quotationAwaiting = $quotations->whereIn('status',['SENT','UNDER_REVIEW','REVISION_REQUESTED'])->count())
                @php($quotationApproved = $quotations->whereIn('status',['CUSTOMER_APPROVED','APPROVED','ACCEPTED'])->count())
                @php($quotationRejected = $quotations->whereIn('status',['CUSTOMER_REJECTED','REJECTED'])->count())
                <div class="page-head quotation-head"><div><div class="eyebrow">Commercial Workspace</div><h2>Quotations</h2><p>Commercial proposals for your customer account. Review quotations, documents and actions in one place.</p></div><a class="btn quotation-request" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'quotation'=>1]) }}">＋ Request Quotation</a></div>
                <section class="quotation-stats">
                    <div class="card quotation-stat"><span>@include('customer.partials.icon',['name'=>'quotations'])</span><div><b>{{ $quotationTotal }}</b><strong>Total Quotations</strong><small>All quotations in your account</small></div></div>
                    <div class="card quotation-stat draft"><span>@include('customer.partials.icon',['name'=>'requests'])</span><div><b>{{ $quotationDraft }}</b><strong>Draft</strong><small>Being prepared</small></div></div>
                    <div class="card quotation-stat awaiting"><span>@include('customer.partials.icon',['name'=>'actions'])</span><div><b>{{ $quotationAwaiting }}</b><strong>Awaiting Decision</strong><small>Require your action</small></div></div>
                    <div class="card quotation-stat approved"><span>✓</span><div><b>{{ $quotationApproved }}</b><strong>Approved</strong><small>Accepted quotations</small></div></div>
                    <div class="card quotation-stat rejected"><span>×</span><div><b>{{ $quotationRejected }}</b><strong>Rejected</strong><small>Not accepted</small></div></div>
                </section>
                <div class="card quotation-toolbar">
                    <label class="quotation-search">@include('customer.partials.icon',['name'=>'search'])<input id="quotation-search" type="search" placeholder="Search quotations by number, status or value..."></label>
                    <select id="quotation-status"><option value="">All Statuses</option><option value="DRAFT">Draft</option><option value="SENT">Sent</option><option value="UNDER_REVIEW">Under Review</option><option value="REVISION_REQUESTED">Revision Requested</option><option value="CUSTOMER_APPROVED">Approved</option><option value="CUSTOMER_REJECTED">Rejected</option></select>
                    <button type="button" id="quotation-reset">Reset</button>
                </div>
                <div class="card table-card quotation-table-card"><div class="table-wrap"><table class="table quotation-table"><thead><tr><th>#</th><th>Quotation</th><th>Revision</th><th>Value</th><th>Updated</th><th>Status</th><th>Customer Action</th></tr></thead><tbody>
                @forelse($quotations as $quotation)
                    @php($quotationStatus = strtoupper((string)$quotation->status))
                    <tr data-quotation-row data-status="{{ $quotationStatus }}" data-search="{{ strtolower($quotation->quotation_no.' '.$quotation->status.' '.$quotation->amount.' '.$quotation->currency) }}">
                        <td>{{ $loop->iteration }}</td>
                        <td><strong class="quotation-number">{{ $quotation->quotation_no }}</strong><small class="quotation-sub">Created {{ $quotation->quotation_date?->format('d M Y') ?? '—' }}</small></td>
                        <td>R{{ $quotation->revision_no }}</td>
                        <td><strong>{{ number_format((float)$quotation->amount,2) }} {{ $quotation->currency }}</strong></td>
                        <td>{{ $quotation->updated_at?->format('d M Y') ?? $quotation->quotation_date?->format('d M Y') ?? '—' }}</td>
                        <td><span class="pill {{ in_array($quotationStatus,['CUSTOMER_APPROVED','APPROVED','ACCEPTED'])?'green':(in_array($quotationStatus,['CUSTOMER_REJECTED','REJECTED'])?'red':(in_array($quotationStatus,['SENT','UNDER_REVIEW','REVISION_REQUESTED'])?'amber':'')) }}">{{ str_replace('_',' ',$quotationStatus) }}</span></td>
                        <td>@if($canDecideQuotation && in_array($quotation->status,['SENT','UNDER_REVIEW','REVISION_REQUESTED']))
                            <form method="POST" action="{{ route('customer.quotations.decision',$quotation) }}">@csrf<select name="decision"><option value="APPROVE">Approve</option><option value="REVISION">Request revision</option><option value="REJECT">Reject</option></select><input name="notes" placeholder="Notes"><button class="btn">Submit</button></form>
                        @else
                            <span class="pill">{{ $readOnly ? 'Read only' : 'No action required' }}</span>
                        @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty"><strong>No quotations found</strong>New commercial proposals will appear here.</div></td></tr>
                @endforelse
                </tbody></table></div><div class="quotation-footer"><span>Showing <b>{{ $quotationTotal }}</b> quotation{{ $quotationTotal===1?'':'s' }}</span><span>Customer commercial workspace</span></div></div>
                <style>
                    .quotation-head{align-items:center}.quotation-request{padding:11px 16px;box-shadow:0 8px 18px rgba(20,117,209,.18);background:var(--blue)}
                    .quotation-stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:12px}.quotation-stat{min-height:98px;padding:14px;display:flex;align-items:center;gap:11px}.quotation-stat>span{width:40px;height:40px;border-radius:11px;background:#eaf3ff;color:var(--blue);display:grid;place-items:center;font-size:22px;font-weight:900}.quotation-stat>span .ui-icon{width:20px}.quotation-stat.draft>span{background:#fff3df;color:#d88900}.quotation-stat.awaiting>span,.quotation-stat.rejected>span{background:#fdebed;color:var(--red)}.quotation-stat.approved>span{background:#e7f7ee;color:var(--green)}.quotation-stat b{font-size:21px;display:block;line-height:1}.quotation-stat strong{font-size:9px;display:block;margin-top:5px}.quotation-stat small{font-size:7px;color:var(--muted);display:block;margin-top:4px}
                    .quotation-toolbar{padding:11px;display:grid;grid-template-columns:minmax(280px,1fr) 180px auto;gap:9px;margin-bottom:12px}.quotation-toolbar select,.quotation-toolbar button,.quotation-search{height:38px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--ink)}.quotation-search{display:flex;align-items:center;gap:8px;padding:0 11px}.quotation-search .ui-icon{width:15px;color:var(--muted)}.quotation-search input{border:0;outline:0;width:100%;font-size:9px}.quotation-toolbar select{padding:0 10px;font-size:9px}.quotation-toolbar button{padding:0 14px;font-size:9px;font-weight:800;cursor:pointer}
                    .quotation-table-card{padding:0;overflow:hidden}.quotation-table th,.quotation-table td{padding:13px 12px}.quotation-table th:first-child,.quotation-table td:first-child{padding-left:18px}.quotation-number{color:var(--blue);font-size:10px}.quotation-sub{display:block;color:var(--muted);font-size:7px;margin-top:4px}.quotation-footer{display:flex;justify-content:space-between;padding:12px 18px;font-size:8px;color:var(--muted);border-top:1px solid #edf0f4}.quotation-footer b{color:var(--ink)}
                    @media(max-width:1100px){.quotation-stats{grid-template-columns:repeat(3,1fr)}}@media(max-width:780px){.quotation-stats{grid-template-columns:repeat(2,1fr)}.quotation-toolbar{grid-template-columns:1fr 1fr}.quotation-search{grid-column:1/-1}.quotation-table th:nth-child(1),.quotation-table td:nth-child(1),.quotation-table th:nth-child(5),.quotation-table td:nth-child(5){display:none}}@media(max-width:520px){.quotation-stats{grid-template-columns:1fr 1fr}.quotation-stat{min-height:90px;padding:10px}.quotation-toolbar{grid-template-columns:1fr}.quotation-search{grid-column:auto}.quotation-table th:nth-child(3),.quotation-table td:nth-child(3){display:none}.quotation-footer span:last-child{display:none}}
                </style>
                <script>document.addEventListener('DOMContentLoaded',()=>{const rows=[...document.querySelectorAll('[data-quotation-row]')],search=document.getElementById('quotation-search'),status=document.getElementById('quotation-status'),reset=document.getElementById('quotation-reset');const apply=()=>{const q=(search?.value||'').toLowerCase(),s=status?.value||'';rows.forEach(row=>row.style.display=(!q||row.dataset.search.includes(q))&&(!s||row.dataset.status===s)?'':'none')};search?.addEventListener('input',apply);status?.addEventListener('change',apply);reset?.addEventListener('click',()=>{if(search)search.value='';if(status)status.value='';apply()})});</script>
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
                <div class="page-head"><div><h2>Reports</h2><p>Technical reports across all customer sites and assets.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Report</th><th>Date</th><th>Type</th><th>Technician</th><th></th></tr></thead><tbody>@forelse($visitReports as $report)<tr><td>{{ $report->report_no }}</td><td>{{ $report->visit_date?->format('Y-m-d') }}</td><td>{{ $report->visit_type }}</td><td>{{ $report->technician_name }}</td><td><a class="pill" href="{{ route('customer.visits.pdf',$report) }}">PDF</a></td></tr>
@empty
<tr><td colspan="5">No reports found.</td></tr>
@endforelse
</tbody></table></div></div>
            
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
