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
        .filters{display:flex;align-items:flex-end;gap:8px;flex-wrap:wrap}.filter{display:grid;gap:4px}.filter span{font-size:7px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}.filter select{height:34px;min-width:128px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--ink);padding:0 9px;font-size:9px}.filter-button{height:34px;border:0;border-radius:8px;background:var(--navy);color:#fff;padding:0 13px;font-size:9px;font-weight:800;cursor:pointer}
        .card{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow)}.quick-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:12px}.quick-action{min-height:65px;padding:12px 13px;display:flex;align-items:center;gap:10px;position:relative;overflow:hidden}.quick-action .quick-icon{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:#edf4fd;color:var(--blue)}.quick-action strong{font-size:10px;display:block}.quick-action small{font-size:8px;color:var(--muted);display:block;margin-top:3px}.quick-action.emergency{background:linear-gradient(135deg,#e20b24,#ba071b);border-color:transparent;color:#fff}.quick-action.emergency .quick-icon{background:#ffffff22;color:#fff}.quick-action.emergency small{color:#ffe2e7}
        .action-center{padding:15px 16px;margin-bottom:12px;border-left:4px solid var(--red)}.action-head{display:flex;align-items:center;gap:12px}.action-copy{flex:1}.action-copy strong{font-size:12px}.action-copy p{font-size:8px;color:var(--muted);margin:4px 0 0}.action-total{min-width:46px;height:29px;border-radius:15px;background:#fdebed;color:#b42239;display:grid;place-items:center;font-size:9px;font-weight:900}.action-breakdown{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-top:12px}.action-item{padding:8px 9px;border-radius:8px;background:#f7f9fc;border:1px solid #edf1f5;display:flex;justify-content:space-between;gap:6px;font-size:8px;color:var(--muted)}.action-item b{font-size:10px;color:var(--ink)}
        .stats{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}.stat{padding:14px;min-height:103px;position:relative}.stat:after{content:"";position:absolute;left:0;top:15px;width:3px;height:28px;border-radius:0 3px 3px 0;background:var(--blue)}.stat.warning:after{background:var(--red)}.stat .label{font-size:8px;font-weight:800;color:#53647a}.stat .value{font-size:24px;font-weight:900;margin:12px 0 5px;letter-spacing:-.03em}.stat .value.compact{font-size:19px}.trend{font-size:8px;color:var(--green)}.trend.muted{color:var(--muted)}.trend.danger{color:var(--red)}
        .command-grid{display:grid;grid-template-columns:minmax(0,1.75fr) minmax(300px,1fr);gap:12px;margin-top:12px}.panel{padding:16px}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.panel-head h3{font-size:12px;margin:0}.panel-head a{font-size:8px;color:var(--blue);font-weight:800}.stage-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:7px}.stage{background:#f7f9fc;border:1px solid #edf1f5;border-radius:9px;padding:10px 8px;min-height:73px}.stage b{display:block;font-size:19px}.stage span{font-size:7px;color:var(--muted);display:block;margin-top:6px}.priority-list,.activity-list,.upcoming-list{display:grid}.list-row{display:grid;grid-template-columns:8px 1fr auto;gap:9px;align-items:center;padding:9px 0;border-bottom:1px solid #edf1f5}.list-row:last-child{border-bottom:0}.indicator{width:7px;height:7px;border-radius:50%;background:var(--blue)}.indicator.red{background:var(--red)}.row-title{font-size:9px;font-weight:800}.row-sub{font-size:7px;color:var(--muted);margin-top:3px}.empty{padding:23px;text-align:center;color:var(--muted);font-size:9px}.empty strong{display:block;color:#40546c;font-size:10px;margin-bottom:4px}
        .lower-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:12px;margin-top:12px}.asset-health{display:grid;grid-template-columns:130px 1fr;gap:18px;align-items:center}.health-ring{width:112px;height:112px;border-radius:50%;background:conic-gradient(var(--green) 0 var(--active),var(--amber) var(--active) var(--maint),var(--red) var(--maint) var(--down),#e8edf4 var(--down));position:relative;display:grid;place-items:center}.health-ring:before{content:"";position:absolute;inset:18px;border-radius:50%;background:#fff}.health-ring div{position:relative;text-align:center}.health-ring b{display:block;font-size:23px}.health-ring span{font-size:7px;color:var(--muted)}.health-legend{display:grid;grid-template-columns:1fr 1fr;gap:8px}.health-item{padding:9px;border-radius:8px;background:#f7f9fc}.health-item b{font-size:14px;display:block}.health-item span{font-size:7px;color:var(--muted)}
        .activity-panel{margin-top:12px}.table-card{padding:16px}.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse;font-size:9px}.table th,.table td{padding:10px 8px;text-align:left;border-bottom:1px solid #edf0f4;white-space:nowrap}.table th{color:#68758a;font-size:8px;text-transform:uppercase;letter-spacing:.05em}.table tr:last-child td{border-bottom:0}.table form{display:flex;gap:5px;align-items:center}.table input,.table select{border:1px solid var(--line);border-radius:6px;padding:6px;font-size:8px}.btn{border:0;border-radius:7px;background:var(--navy);color:#fff;padding:8px 12px;font-size:9px;font-weight:800;cursor:pointer}.btn.red{background:var(--red)}
        .asset-grid,.site-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.asset,.site-card{padding:14px}.asset strong,.site-card strong{font-size:10px}.asset small,.site-card small{display:block;margin-top:7px;line-height:1.65;color:var(--muted);font-size:8px}.asset:hover,.site-card:hover{border-color:#aac7e6;box-shadow:0 10px 28px rgba(7,31,77,.09)}.site-meta{display:flex;gap:5px;margin-top:9px;flex-wrap:wrap}.form-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}.field{display:grid;gap:5px;font-size:9px;font-weight:700}.field input,.field select,.field textarea{width:100%;border:1px solid #d6dee8;border-radius:7px;padding:9px;background:#fff}.field textarea{min-height:90px}.wide{grid-column:1/-1}
        @media(max-width:1250px){.stats{grid-template-columns:repeat(3,1fr)}.stage-grid{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:1040px){.app{grid-template-columns:82px minmax(0,1fr)}.account strong,.account small,.role-badge,.side-search span,.nav-label,.nav-link span:nth-child(2),.nav-badge{display:none}.account{padding:5px 8px 12px}.account-mark{justify-content:center}.nav-link{justify-content:center}.sidebar{padding-left:9px;padding-right:9px}.quick-actions{grid-template-columns:repeat(2,1fr)}.asset-grid,.site-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:780px){.app{display:block}.sidebar{height:auto;position:sticky;top:0;display:block;padding:7px;overflow:auto}.account,.side-search,.nav-label,.logout{display:none}.nav-scroll{display:flex;overflow:auto;padding:0}.nav-group{display:flex;margin:0}.nav-link{min-width:42px}.main{padding:0 12px 28px}.topbar{margin:0 -12px;padding:0 14px}.top-actions .status-pill{display:none}.page-head{align-items:flex-start;flex-direction:column}.filters{width:100%}.filter{flex:1}.filter select{width:100%;min-width:0}.stats,.quick-actions,.command-grid,.lower-grid,.asset-grid,.site-grid,.form-grid{grid-template-columns:1fr 1fr}.action-breakdown{grid-template-columns:repeat(2,1fr)}.asset-health{grid-template-columns:1fr}.health-ring{margin:auto}}
        @media(max-width:520px){.stats,.quick-actions,.command-grid,.lower-grid,.asset-grid,.site-grid,.form-grid{grid-template-columns:1fr}.stage-grid{grid-template-columns:repeat(2,1fr)}.wide{grid-column:auto}.welcome p{display:none}.main{padding-bottom:20px}}
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
    $dashboardTitle = match($portalRole) {
        'CUSTOMER_ADMIN' => 'Customer 360 Executive Dashboard',
        'SITE_MANAGER' => 'Site Operations Dashboard',
        'FINANCE' => 'Customer Finance Dashboard',
        default => 'Customer Read-Only Dashboard',
    };
    $badges = ['requests' => $openRequestCount, 'work-orders' => $openWorkOrders, 'visits' => $upcomingPlans->count(), 'quotations' => $pendingQuotationCount, 'invoices' => $invoiceActionCount, 'notifications' => $alerts->count()];
    $assetTotal = max(1, $assets->count());
    $assetActiveEnd = round(($activeAssetCount / $assetTotal) * 100);
    $assetMaintenanceEnd = min(100, $assetActiveEnd + round(($maintenanceAssetCount / $assetTotal) * 100));
    $assetStoppedEnd = min(100, $assetMaintenanceEnd + round(($stoppedAssetCount / $assetTotal) * 100));
@endphp
<div class="app">
    <aside class="sidebar">
        <div class="account">
            <div class="account-mark"><div class="account-logo">{{ strtoupper(substr($customer->name, 0, 2)) }}</div><div><strong>{{ $customer->name }}</strong><small>{{ $customer->customer_code }}</small></div></div>
            <span class="role-badge">{{ str_replace('_', ' ', $portalRole) }}{{ $readOnly ? ' · READ ONLY' : '' }}</span>
        </div>
        <div class="side-search">@include('customer.partials.icon',['name'=>'search'])<span>Find request, asset or invoice</span></div>
        <div class="nav-scroll">
            @foreach($groups as $group => $keys)
                @php($visibleKeys = array_values(array_filter($keys, fn($key) => in_array($key, $allowedSections, true))))
                @if(count($visibleKeys))
                    <div class="nav-group"><div class="nav-label">{{ $group }}</div>
                        @if($group === 'Overview')
                            <a class="nav-link {{ $section === 'dashboard' ? 'active' : '' }}" href="{{ route('customer.portal') }}">@include('customer.partials.icon',['name'=>'dashboard'])<span>Dashboard</span></a>
                            <a class="nav-link" href="{{ route('customer.actions') }}">@include('customer.partials.icon',['name'=>'actions'])<span>Action Required</span>@if($actionRequiredCount)<span class="nav-badge urgent">{{ $actionRequiredCount }}</span>@endif</a>
                        @else
                            @foreach($visibleKeys as $key)
                                <a class="nav-link {{ $section === $key ? 'active' : '' }}" href="{{ route('customer.section', $key) }}">@include('customer.partials.icon',['name'=>$sectionItems[$key][0]])<span>{{ $sectionItems[$key][1] }}</span>@if(($badges[$key] ?? 0) > 0)<span class="nav-badge">{{ $badges[$key] }}</span>@endif</a>
                            @endforeach
                        @endif
                    </div>
                @endif
            @endforeach
            <div class="nav-group"><div class="nav-label">Communication</div>
                <a class="nav-link" href="{{ route('customer.inbox') }}">@include('customer.partials.icon',['name'=>'inbox'])<span>Inbox & Support</span>@if($unreadInbox)<span class="nav-badge urgent">{{ $unreadInbox }}</span>@endif</a>
            </div>
            <div class="nav-group"><div class="nav-label">Account</div>
                @if($canManageUsers)<a class="nav-link" href="{{ route('customer.access.index') }}">@include('customer.partials.icon',['name'=>'users'])<span>Users &amp; Access</span></a>@endif
                <a class="nav-link" href="{{ route('customer.profile.edit') }}">@include('customer.partials.icon',['name'=>'profile'])<span>Company Profile</span></a>
            </div>
        </div>
        <form class="logout" method="POST" action="{{ route('logout') }}">@csrf<button>Sign out</button></form>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="welcome"><h1>Welcome, {{ auth()->user()->name }}</h1><p>{{ $customer->name }} · {{ str_replace('_', ' ', $portalRole) }} · Scope-aware customer workspace</p></div>
            <div class="top-actions"><span class="status-pill {{ $customer->status === 'ACTIVE' ? 'green' : 'amber' }}">{{ $customer->status }}</span><div class="avatar">{{ strtoupper(substr(auth()->user()->name ?: 'CU', 0, 2)) }}</div></div>
        </header>
        <div class="content">
            @if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
            @if($readOnly)<div class="role-note">Read-only access: you can view authorized customer data but cannot create requests or make commercial decisions.</div>
            @elseif($portalRole === 'SITE_MANAGER')<div class="role-note">Site-scoped access: operational data is limited to sites and assets assigned to your account.</div>
            @elseif($portalRole === 'FINANCE')<div class="role-note">Finance access: commercial, contract and financial information is available; maintenance execution actions are hidden.</div>@endif

            @if($section === 'dashboard')
                <div class="page-head">
                    <div><div class="eyebrow">UNIFCO Customer Command Center</div><h2>{{ $dashboardTitle }}</h2><p>Everything that needs your attention across operations, assets, contracts and service delivery.</p></div>
                    <form class="filters" method="GET" action="{{ route('customer.portal') }}">
                        @if($sites->isNotEmpty())<label class="filter"><span>Site</span><select name="site_id"><option value="">All sites</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected($siteFilter === $site->id)>{{ $site->name }}</option>@endforeach</select></label>@endif
                        @if($contracts->isNotEmpty())<label class="filter"><span>Contract</span><select name="contract_id"><option value="">All contracts</option>@foreach($contracts as $contract)<option value="{{ $contract->id }}" @selected($contractFilter === $contract->id)>{{ $contract->contract_no }}</option>@endforeach</select></label>@endif
                        <label class="filter"><span>Period</span><select name="days">@foreach([7=>'7 days',30=>'30 days',90=>'90 days',365=>'12 months'] as $value=>$label)<option value="{{ $value }}" @selected($days===$value)>{{ $label }}</option>@endforeach</select></label>
                        <button class="filter-button">Apply</button>
                    </form>
                </div>

                @if($canCreateRequest)
                    <section class="quick-actions">
                        <a class="card quick-action" href="{{ route('customer.section','work-orders') }}#request-service"><span class="quick-icon">@include('customer.partials.icon',['name'=>'requests'])</span><span><strong>New Service Request</strong><small>Routine maintenance or support</small></span></a>
                        <a class="card quick-action emergency" href="{{ route('customer.section','work-orders') }}?priority=EMERGENCY#request-service"><span class="quick-icon">@include('customer.partials.icon',['name'=>'notifications'])</span><span><strong>Emergency Maintenance</strong><small>Report an urgent asset failure</small></span></a>
                        <a class="card quick-action" href="{{ route('customer.section','work-orders') }}?service_category=Quotation#request-service"><span class="quick-icon">@include('customer.partials.icon',['name'=>'quotations'])</span><span><strong>Request Quotation</strong><small>Start a commercial request</small></span></a>
                        <a class="card quick-action" href="{{ route('customer.section','work-orders') }}?service_category=Spare%20Parts#request-service"><span class="quick-icon">@include('customer.partials.icon',['name'=>'parts'])</span><span><strong>Request Spare Parts</strong><small>Parts for an authorized asset</small></span></a>
                    </section>
                @endif

                <a data-action-center-panel class="card action-center" href="{{ route('customer.actions') }}">
                    <div class="action-head"><span class="quick-icon">@include('customer.partials.icon',['name'=>'actions'])</span><div class="action-copy"><strong>Action Required From You</strong><p>Approvals, work acceptance, payments, renewals and unread customer communication.</p></div><span class="action-total">{{ $actionRequiredCount }}</span></div>
                    <div class="action-breakdown">
                        @if($canDecideQuotation)<span class="action-item">Quotations <b>{{ $quotationActionCount }}</b></span>@endif
                        @if(in_array('work-orders',$allowedSections,true))<span class="action-item">Work acceptance <b>{{ $workAcceptanceActionCount }}</b></span>@endif
                        @if(in_array('invoices',$allowedSections,true))<span class="action-item">Invoices <b>{{ $invoiceActionCount }}</b></span>@endif
                        @if(in_array('contracts',$allowedSections,true))<span class="action-item">Renewals <b>{{ $renewalActionCount }}</b></span>@endif
                        <span class="action-item">Messages <b>{{ $unreadInbox }}</b></span>
                    </div>
                </a>

                <section class="stats">
                    @if(in_array('requests',$allowedSections,true))<a class="card stat" href="{{ route('customer.section','requests') }}"><div class="label">Open Requests</div><div class="value">{{ $openRequestCount }}</div><div class="trend">Within your selected scope</div></a>@endif
                    @if(in_array('work-orders',$allowedSections,true))<a class="card stat" href="{{ route('customer.section','work-orders') }}"><div class="label">Open Work Orders</div><div class="value">{{ $openWorkOrders }}</div><div class="trend">{{ $inProgressCount }} currently in progress</div></a>@endif
                    @if(in_array('sla',$allowedSections,true))<a class="card stat {{ $slaPerformance !== null && $slaPerformance < 90 ? 'warning' : '' }}" href="{{ route('customer.section','sla') }}"><div class="label">SLA Performance</div><div class="value {{ $slaPerformance === null ? 'compact' : '' }}">{{ $slaPerformance === null ? 'N/A' : $slaPerformance.'%' }}</div><div class="trend {{ $slaPerformance === null ? 'muted' : '' }}">{{ $slaPerformance === null ? 'No measured SLA in period' : 'Measured response & resolution' }}</div></a>@endif
                    @if(in_array('assets',$allowedSections,true))<a class="card stat {{ $stoppedAssetCount ? 'warning' : '' }}" href="{{ route('customer.section','assets') }}"><div class="label">Assets Requiring Attention</div><div class="value">{{ $stoppedAssetCount + $maintenanceAssetCount }}</div><div class="trend {{ $stoppedAssetCount ? 'danger' : '' }}">{{ $stoppedAssetCount }} stopped · {{ $maintenanceAssetCount }} under maintenance</div></a>@endif
                    @if(in_array('quotations',$allowedSections,true))<a class="card stat" href="{{ route('customer.section','quotations') }}"><div class="label">Pending Quotations</div><div class="value">{{ $pendingQuotationCount }}</div><div class="trend">Commercial decisions</div></a>@endif
                    @if(in_array('invoices',$allowedSections,true))<a class="card stat {{ $invoiceActionCount ? 'warning' : '' }}" href="{{ route('customer.section','invoices') }}"><div class="label">Open Balance</div><div class="value compact">{{ number_format($openInvoiceAmount,2) }}</div><div class="trend {{ $invoiceActionCount ? 'danger' : '' }}">SAR · {{ $invoiceActionCount }} due soon</div></a>@endif
                </section>

                @if(in_array('requests',$allowedSections,true) && in_array('work-orders',$allowedSections,true))
                <section class="command-grid">
                    <div class="card panel"><div class="panel-head"><h3>Service Request Journey</h3><a href="{{ route('customer.section','requests') }}">View all requests →</a></div><div class="stage-grid">
                        <div class="stage"><b>{{ $requestStageCounts['new'] }}</b><span>New</span></div><div class="stage"><b>{{ $requestStageCounts['review'] }}</b><span>Under review</span></div><div class="stage"><b>{{ $requestStageCounts['assigned'] }}</b><span>Assigned</span></div><div class="stage"><b>{{ $requestStageCounts['progress'] }}</b><span>In progress</span></div><div class="stage"><b>{{ $requestStageCounts['customer'] }}</b><span>Awaiting customer</span></div><div class="stage"><b>{{ $requestStageCounts['closed'] }}</b><span>Completed</span></div>
                    </div></div>
                    <div class="card panel"><div class="panel-head"><h3>Priority Today</h3><a href="{{ route('customer.section','work-orders') }}">Open work orders →</a></div><div class="priority-list">@forelse($criticalWorkOrders as $workOrder)<a class="list-row" href="{{ route('customer.work-orders.show',$workOrder) }}"><i class="indicator red"></i><div><div class="row-title">{{ $workOrder->work_order_no }} · {{ $workOrder->asset?->name }}</div><div class="row-sub">{{ str_replace('_',' ',$workOrder->priority) }} · {{ str_replace('_',' ',$workOrder->status) }}</div></div><span class="pill red">Review</span></a>@empty<div class="empty"><strong>No critical work</strong>No urgent or high-priority work orders in this period.</div>@endforelse</div></div>
                </section>
                @else
                <section class="command-grid">
                    <div class="card panel"><div class="panel-head"><h3>Commercial Overview</h3><a href="{{ route('customer.section','quotations') }}">View quotations →</a></div><div class="stage-grid"><div class="stage"><b>{{ $pendingQuotationCount }}</b><span>Pending quotations</span></div><div class="stage"><b>{{ $activeContractCount }}</b><span>Active contracts</span></div><div class="stage"><b>{{ $renewalActionCount }}</b><span>Renewals due</span></div></div></div>
                    <div class="card panel"><div class="panel-head"><h3>Financial Attention</h3><a href="{{ route('customer.section','invoices') }}">View invoices →</a></div><div class="priority-list"><div class="list-row"><i class="indicator {{ $invoiceActionCount?'red':'' }}"></i><div><div class="row-title">{{ $invoiceActionCount }} invoices require attention</div><div class="row-sub">Open balance {{ number_format($openInvoiceAmount,2) }} SAR</div></div></div></div></div>
                </section>
                @endif

                <section class="lower-grid">
                    @if(in_array('assets',$allowedSections,true))<div class="card panel"><div class="panel-head"><h3>Asset Health</h3><a href="{{ route('customer.asset-health') }}">Asset 360 →</a></div><div class="asset-health"><div class="health-ring" style="--active:{{ $assetActiveEnd }}%;--maint:{{ $assetMaintenanceEnd }}%;--down:{{ $assetStoppedEnd }}%"><div><b>{{ $assets->count() }}</b><span>Visible assets</span></div></div><div class="health-legend"><div class="health-item"><b>{{ $activeAssetCount }}</b><span>Operational</span></div><div class="health-item"><b>{{ $maintenanceAssetCount }}</b><span>Maintenance</span></div><div class="health-item"><b>{{ $stoppedAssetCount }}</b><span>Stopped</span></div><div class="health-item"><b>{{ $criticalAssetCount }}</b><span>Critical assets</span></div><div class="health-item"><b>{{ $warrantyExpiringCount }}</b><span>Warranty ≤ 60 days</span></div><div class="health-item"><b>{{ $sites->count() }}</b><span>Authorized sites</span></div></div></div></div>@endif
                    <div class="card panel"><div class="panel-head"><h3>Upcoming Visits & Maintenance</h3>@if(in_array('visits',$allowedSections,true))<a href="{{ route('customer.section','visits') }}">Open schedule →</a>@endif</div><div class="upcoming-list">@forelse($upcomingPlans as $plan)<div class="list-row"><i class="indicator"></i><div><div class="row-title">{{ $plan->name }}</div><div class="row-sub">{{ $plan->asset?->site?->name ?: 'Site not assigned' }} · {{ $plan->asset?->name }}</div></div><span class="pill">{{ $plan->next_due_date?->format('d M') }}</span></div>@empty<div class="empty"><strong>No scheduled visits</strong>There is no preventive maintenance in the selected period.</div>@endforelse</div></div>
                </section>

                <section class="card panel activity-panel"><div class="panel-head"><h3>Recent Relationship Activity</h3><a href="{{ route('customer.section','timeline') }}">Full timeline →</a></div><div class="activity-list">@forelse($timeline->take(6) as $event)<div class="list-row"><i class="indicator"></i><div><div class="row-title">{{ $event->title }}</div><div class="row-sub">{{ str_replace('_',' ',$event->event_type) }} · {{ $event->created_at?->format('d M Y, H:i') }}</div></div></div>@empty<div class="empty"><strong>No recent activity</strong>Customer-visible updates will appear here.</div>@endforelse</div></section>
            @endif

            @if($section === 'requests')
                <div class="page-head"><div><h2>Service Requests</h2><p>Requests visible within your assigned scope.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Request</th><th>Type</th><th>Subject</th><th>Priority</th><th>Stage</th><th>Status</th><th>Created</th></tr></thead><tbody>@forelse($requests as $item)<tr><td>{{ $item->request_no }}</td><td>{{ $item->request_type }}</td><td>{{ $item->subject }}</td><td><span class="pill {{ in_array($item->priority,['EMERGENCY','HIGH'])?'red':'' }}">{{ $item->priority }}</span></td><td>{{ $item->workflow_stage }}</td><td>{{ $item->status }}</td><td>{{ $item->created_at?->format('Y-m-d') }}</td></tr>@empty<tr><td colspan="7">No requests found.</td></tr>@endforelse</tbody></table></div></div>
            @endif

            @if($section === 'work-orders')
                <div class="page-head"><div><h2>Work Orders</h2><p>Execution records for assets within your scope.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Work Order</th><th>Asset</th><th>Site</th><th>Type</th><th>Priority</th><th>Status</th><th>Planned</th></tr></thead><tbody>@forelse($workOrders as $item)<tr><td><a class="pill" href="{{ route('customer.work-orders.show',$item) }}">{{ $item->work_order_no }}</a></td><td>{{ $item->asset?->asset_code }} · {{ $item->asset?->name }}</td><td>{{ $item->asset?->site?->name ?: '—' }}</td><td>{{ $item->maintenance_type }}</td><td>{{ $item->priority }}</td><td>{{ $item->status }}</td><td>{{ $item->planned_start?->format('Y-m-d H:i') ?: '—' }}</td></tr>@empty<tr><td colspan="7">No work orders in scope.</td></tr>@endforelse</tbody></table></div></div>
                @if($canCreateRequest)<div id="request-service" class="card table-card" style="margin-top:12px"><div class="panel-head"><h3>Request Service</h3><span class="pill">Unified request</span></div><form method="POST" action="{{ route('customer.requests.store') }}">@csrf<div class="form-grid"><label class="field">Contract<select name="service_contract_id"><option value="">—</option>@foreach($contracts as $contract)<option value="{{ $contract->id }}">{{ $contract->contract_no }}</option>@endforeach</select></label><label class="field">Asset<select name="asset_id"><option value="">—</option>@foreach($assets as $asset)<option value="{{ $asset->id }}">{{ $asset->asset_code }} · {{ $asset->name }}</option>@endforeach</select></label><label class="field">Priority<select name="priority"><option @selected(request('priority')==='NORMAL')>NORMAL</option><option @selected(request('priority')==='HIGH')>HIGH</option><option @selected(request('priority')==='EMERGENCY')>EMERGENCY</option></select></label><label class="field">Category<input name="service_category" value="{{ request('service_category') }}" required></label><label class="field">Subject<input name="subject" required></label><label class="field">Site / City<input name="site_city"></label><label class="field wide">Details<textarea name="details" required></textarea></label></div><button class="btn red" style="margin-top:10px">Submit Request</button></form></div>@endif
            @endif

            @if($section === 'sites')
                <div class="page-head"><div><h2>Sites</h2><p>Authorized locations, reception contacts and operational coverage.</p></div></div>
                <div class="site-grid">@forelse($sites as $site)<a class="card site-card" href="{{ route('customer.portal',['site_id'=>$site->id]) }}"><strong>{{ $site->site_code }} · {{ $site->name }}</strong><small>{{ $site->city ?: 'City not specified' }}<br>{{ $site->address ?: 'Address not specified' }}<br>Reception: {{ $site->contact_name ?: '—' }} · {{ $site->contact_mobile ?: '—' }}</small><div class="site-meta"><span class="pill {{ $site->status==='ACTIVE'?'green':'amber' }}">{{ $site->status }}</span><span class="pill">{{ $assets->where('customer_site_id',$site->id)->count() }} assets</span></div></a>@empty<div class="card empty">No sites in your authorized scope.</div>@endforelse</div>
            @endif

            @if($section === 'assets')
                <div class="page-head"><div><h2>Assets & Equipment</h2><p>Only assets assigned to your permitted sites or explicit asset scope.</p></div><a class="btn" href="{{ route('customer.asset-health') }}">Open Asset Health</a></div>
                <div class="asset-grid">@forelse($assets as $asset)<a class="card asset" href="{{ route('customer.asset.show',$asset) }}"><strong>{{ $asset->asset_code }} · {{ $asset->name }}</strong><small>{{ $asset->site?->name ?: 'No site' }}<br>{{ $asset->location_code ?: 'Location not assigned' }}<br>Operational status: {{ $asset->operational_status ?: $asset->status }}<br>Health: {{ $asset->health_score !== null ? $asset->health_score.'%' : 'Not measured' }}</small></a>@empty<div class="card empty">No assets in your scope.</div>@endforelse</div>
            @endif

            @if($section === 'visits')
                <div class="page-head"><div><h2>Visits & Schedule</h2><p>Upcoming preventive work and completed technician visits.</p></div></div>
                <section class="lower-grid"><div class="card panel"><div class="panel-head"><h3>Upcoming Maintenance</h3></div>@forelse($upcomingPlans as $plan)<div class="list-row"><i class="indicator"></i><div><div class="row-title">{{ $plan->name }}</div><div class="row-sub">{{ $plan->asset?->site?->name }} · {{ $plan->asset?->name }}</div></div><span class="pill">{{ $plan->next_due_date?->format('Y-m-d') }}</span></div>@empty<div class="empty">No upcoming visits.</div>@endforelse</div><div class="card panel"><div class="panel-head"><h3>Completed Visits</h3></div>@forelse($visitReports->take(6) as $report)<div class="list-row"><i class="indicator"></i><div><div class="row-title">{{ $report->report_no }} · {{ $report->visit_type }}</div><div class="row-sub">{{ $report->technician_name ?: 'UNIFCO team' }}</div></div><a class="pill" href="{{ route('customer.visits.pdf',$report) }}">PDF</a></div>@empty<div class="empty">No completed visits.</div>@endforelse</div></section>
            @endif

            @if($section === 'maintenance')
                <div class="page-head"><div><h2>Maintenance Plan</h2><p>Preventive plans for authorized assets.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Plan</th><th>Asset</th><th>Site</th><th>Frequency</th><th>Next Due</th><th>Status</th></tr></thead><tbody>@forelse($plans as $plan)<tr><td>{{ $plan->plan_no }} · {{ $plan->name }}</td><td>{{ $plan->asset?->asset_code }}</td><td>{{ $plan->asset?->site?->name ?: '—' }}</td><td>{{ $plan->frequency_type }} / {{ $plan->frequency_value }}</td><td>{{ $plan->next_due_date?->format('Y-m-d') }}</td><td>{{ $plan->status }}</td></tr>@empty<tr><td colspan="6">No maintenance plans in scope.</td></tr>@endforelse</tbody></table></div></div>
            @endif

            @if($section === 'spare-parts')
                <div class="page-head"><div><h2>Spare Parts</h2><p>Parts issued against authorized assets and work orders.</p></div>@if($canCreateRequest)<a class="btn red" href="{{ route('customer.section','work-orders') }}?service_category=Spare%20Parts#request-service">Request Spare Parts</a>@endif</div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Part</th><th>Description</th><th>Work Order</th><th>Asset</th><th>Quantity</th><th>Date</th></tr></thead><tbody>@forelse($materials as $part)<tr><td>{{ $part->item_code }}</td><td>{{ $part->item_name }}</td><td>{{ $part->work_order_no }}</td><td>{{ $part->asset_code }} · {{ $part->asset_name }}</td><td>{{ $part->quantity }} {{ $part->uom }}</td><td>{{ $part->created_at }}</td></tr>@empty<tr><td colspan="6">No spare-parts activity in your scope.</td></tr>@endforelse</tbody></table></div></div>
            @endif

            @if($section === 'quotations')
                <div class="page-head"><div><h2>Quotations</h2><p>Commercial proposals for your customer account.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Quotation</th><th>Revision</th><th>Value</th><th>Status</th><th>Decision</th></tr></thead><tbody>@forelse($quotations as $quotation)<tr><td>{{ $quotation->quotation_no }}</td><td>R{{ $quotation->revision_no }}</td><td>{{ number_format((float)$quotation->amount,2) }} {{ $quotation->currency }}</td><td>{{ $quotation->status }}</td><td>@if($canDecideQuotation && in_array($quotation->status,['SENT','UNDER_REVIEW','REVISION_REQUESTED']))<form method="POST" action="{{ route('customer.quotations.decision',$quotation) }}">@csrf<select name="decision"><option value="APPROVE">Approve</option><option value="REVISION">Request revision</option><option value="REJECT">Reject</option></select><input name="notes" placeholder="Notes"><button class="btn">Submit</button></form>@else<span class="pill">{{ $readOnly?'Read only':'No action required' }}</span>@endif</td></tr>@empty<tr><td colspan="5">No quotations found.</td></tr>@endforelse</tbody></table></div></div>
            @endif

            @if($section === 'contracts')
                <div class="page-head"><div><h2>Contracts</h2><p>Contracts assigned to your portal scope.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Contract</th><th>Title</th><th>Period</th><th>Status</th><th></th></tr></thead><tbody>@forelse($contracts as $contract)<tr><td>{{ $contract->contract_no }}</td><td>{{ $contract->title }}</td><td>{{ $contract->starts_on?->format('Y-m-d') }} — {{ $contract->ends_on?->format('Y-m-d') }}</td><td>{{ $contract->status }}</td><td><a class="pill" href="{{ route('customer.contracts.pdf',$contract) }}">PDF</a></td></tr>@empty<tr><td colspan="5">No contracts in scope.</td></tr>@endforelse</tbody></table></div></div>
            @endif

            @if($section === 'invoices')
                <div class="page-head"><div><h2>Invoices & Payments</h2><p>Financial documents for the customer account.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Invoice</th><th>Date</th><th>Due</th><th>Amount</th><th>Open</th><th>Status</th><th></th></tr></thead><tbody>@forelse($invoices as $invoice)<tr><td>{{ $invoice->document_no }}</td><td>{{ $invoice->document_date?->format('Y-m-d') }}</td><td>{{ $invoice->due_date?->format('Y-m-d') }}</td><td>{{ number_format((float)$invoice->amount,2) }} {{ $invoice->currency }}</td><td>{{ number_format((float)$invoice->open_amount,2) }}</td><td>{{ $invoice->status }}</td><td><a class="pill" href="{{ route('customer.invoices.pdf',$invoice) }}">PDF</a></td></tr>@empty<tr><td colspan="7">No invoices found.</td></tr>@endforelse</tbody></table></div></div>
            @endif

            @if($section === 'sla')
                <div class="page-head"><div><h2>SLA & KPIs</h2><p>Measured response and resolution performance within your authorized scope.</p></div></div>
                <section class="stats"><div class="card stat"><div class="label">Measured SLA</div><div class="value {{ $slaPerformance===null?'compact':'' }}">{{ $slaPerformance===null?'N/A':$slaPerformance.'%' }}</div><div class="trend muted">{{ $slaPerformance===null?'No eligible measurements':'Response and resolution checks' }}</div></div><div class="card stat"><div class="label">Preventive Work</div><div class="value">{{ $preventiveCount }}</div></div><div class="card stat"><div class="label">Corrective Work</div><div class="value">{{ $correctiveCount }}</div></div><div class="card stat warning"><div class="label">Overdue Work</div><div class="value">{{ $overdueCount }}</div></div></section>
            @endif

            @if($section === 'timeline')
                <div class="page-head"><div><h2>Recent Activity</h2><p>Customer-visible transaction and relationship history.</p></div></div>
                <div class="card panel">@forelse($timeline as $event)<div class="list-row"><i class="indicator"></i><div><div class="row-title">{{ $event->title }}</div><div class="row-sub">{{ str_replace('_',' ',$event->event_type) }} · {{ $event->created_at?->format('Y-m-d H:i') }}</div></div></div>@empty<div class="empty">No timeline activity.</div>@endforelse</div>
            @endif

            @if($section === 'reports')
                <div class="page-head"><div><h2>Reports</h2><p>Technical reports within your authorized asset scope.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Report</th><th>Date</th><th>Type</th><th>Technician</th><th></th></tr></thead><tbody>@forelse($visitReports as $report)<tr><td>{{ $report->report_no }}</td><td>{{ $report->visit_date?->format('Y-m-d') }}</td><td>{{ $report->visit_type }}</td><td>{{ $report->technician_name }}</td><td><a class="pill" href="{{ route('customer.visits.pdf',$report) }}">PDF</a></td></tr>@empty<tr><td colspan="5">No reports found.</td></tr>@endforelse</tbody></table></div></div>
            @endif

            @if($section === 'documents')
                <div class="page-head"><div><h2>Document Library</h2><p>Files associated with authorized assets and service activity.</p></div></div>
                <div class="card table-card"><div class="table-wrap"><table class="table"><thead><tr><th>Type</th><th>File</th><th>Created</th><th></th></tr></thead><tbody>@forelse($attachments as $attachment)<tr><td>{{ $attachment->attachment_type }}</td><td>{{ $attachment->original_name }}</td><td>{{ $attachment->created_at }}</td><td><a class="pill" href="{{ route('customer.attachments.download',$attachment->id) }}">Download</a></td></tr>@empty<tr><td colspan="4">No documents in scope.</td></tr>@endforelse</tbody></table></div></div>
            @endif

            @if($section === 'notifications')
                <div class="page-head"><div><h2>Notifications</h2><p>Operational, contractual and financial alerts relevant to your role.</p></div></div>
                <div class="site-grid">@forelse($alerts as $alert)<div class="card panel"><span class="pill {{ $alert->severity==='HIGH'?'red':'amber' }}">{{ $alert->type }}</span><h3>{{ $alert->title }}</h3><div class="row-sub">{{ $alert->due_date?->format('Y-m-d') }}</div></div>@empty<div class="card empty">No active alerts.</div>@endforelse</div>
            @endif
        </div>
    </main>
</div>
</body>
</html>
