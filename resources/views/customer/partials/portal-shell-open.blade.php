@php
    $locale = isset($locale) && in_array($locale, ['ar','en'], true)
        ? $locale
        : request()->session()->get('customer_portal_locale', request('lang') === 'ar' ? 'ar' : 'en');
    $isArabic = $locale === 'ar';
    $activeSection = $activeSection ?? ($section ?? 'dashboard');
    $pageTitle = $pageTitle ?? 'Customer 360';
    $pageDescription = $pageDescription ?? ($isArabic ? 'جميع بيانات وأعمال الشركة في حساب عميل موحد.' : 'All company records and services in one unified customer account.');
    $navGroups = [
        ($isArabic ? 'الرئيسية' : 'Overview') => [
            ['dashboard', 'dashboard', $isArabic ? 'لوحة التحكم' : 'Dashboard', route('customer.portal', ['lang'=>$locale])],
            ['actions', 'actions', $isArabic ? 'الإجراءات المطلوبة' : 'Action Required', route('customer.actions', ['lang'=>$locale])],
        ],
        ($isArabic ? 'أعمالي' : 'My Work') => [
            ['requests', 'requests', $isArabic ? 'طلبات الخدمة' : 'Service Requests', route('customer.section', ['section'=>'requests','lang'=>$locale])],
            ['work-orders', 'work-orders', $isArabic ? 'أوامر العمل' : 'Work Orders', route('customer.section', ['section'=>'work-orders','lang'=>$locale])],
            ['visits', 'visits', $isArabic ? 'الزيارات والمواعيد' : 'Visits & Schedule', route('customer.section', ['section'=>'visits','lang'=>$locale])],
            ['maintenance', 'maintenance', $isArabic ? 'خطة الصيانة' : 'Maintenance Plan', route('customer.section', ['section'=>'maintenance','lang'=>$locale])],
            ['spare-parts', 'parts', $isArabic ? 'قطع الغيار' : 'Spare Parts', route('customer.section', ['section'=>'spare-parts','lang'=>$locale])],
        ],
        ($isArabic ? 'المواقع والأصول' : 'Sites & Assets') => [
            ['sites', 'sites', $isArabic ? 'المواقع' : 'Sites', route('customer.section', ['section'=>'sites','lang'=>$locale])],
            ['assets', 'assets', $isArabic ? 'الأصول والمعدات' : 'Assets & Equipment', route('customer.section', ['section'=>'assets','lang'=>$locale])],
        ],
        ($isArabic ? 'التجاري والعقود' : 'Commercial & Contracts') => [
            ['quotations', 'quotations', $isArabic ? 'عروض الأسعار' : 'Quotations', route('customer.section', ['section'=>'quotations','lang'=>$locale])],
            ['contracts', 'contracts', $isArabic ? 'العقود' : 'Contracts', route('customer.section', ['section'=>'contracts','lang'=>$locale])],
            ['sla', 'sla', $isArabic ? 'مؤشرات SLA وKPI' : 'SLA & KPIs', route('customer.section', ['section'=>'sla','lang'=>$locale])],
        ],
        ($isArabic ? 'المالية' : 'Finance') => [
            ['invoices', 'invoices', $isArabic ? 'الفواتير والدفعات' : 'Invoices & Payments', route('customer.section', ['section'=>'invoices','lang'=>$locale])],
        ],
        ($isArabic ? 'التقارير والمستندات' : 'Reports & Records') => [
            ['reports', 'reports', $isArabic ? 'التقارير' : 'Reports', route('customer.section', ['section'=>'reports','lang'=>$locale])],
            ['documents', 'documents', $isArabic ? 'مكتبة المستندات' : 'Document Library', route('customer.section', ['section'=>'documents','lang'=>$locale])],
            ['timeline', 'activity', $isArabic ? 'النشاط الأخير' : 'Recent Activity', route('customer.section', ['section'=>'timeline','lang'=>$locale])],
            ['notifications', 'notifications', $isArabic ? 'الإشعارات' : 'Notifications', route('customer.section', ['section'=>'notifications','lang'=>$locale])],
        ],
        ($isArabic ? 'التواصل والحساب' : 'Communication & Account') => [
            ['inbox', 'inbox', $isArabic ? 'صندوق الوارد والدعم' : 'Inbox & Support', route('customer.inbox', ['lang'=>$locale])],
            ['profile', 'profile', 'Company Profile & Settings', route('customer.profile.edit', ['lang'=>$locale])],
        ],
    ];
    $navBadges = $navBadges ?? [
        'actions' => $actionRequiredCount ?? $total ?? null,
        'requests' => $openRequestCount ?? null,
        'work-orders' => $openWorkOrders ?? null,
        'notifications' => isset($alerts) ? $alerts->count() : null,
        'inbox' => $unreadInbox ?? $unread ?? null,
    ];
@endphp
<!doctype html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>UNIFCO Customer Portal · {{ $pageTitle }}</title>
    <style>
        :root{font-family:Inter,"Segoe UI",Arial,sans-serif;--navy:#06275c;--navy-deep:#031d49;--ink:#0a234f;--blue:#1475d1;--red:#e20b24;--green:#14875a;--amber:#d88900;--bg:#f3f6fa;--line:#dfe6ef;--muted:#6d7b90;--shadow:0 8px 24px rgba(7,31,77,.06)}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--bg);color:var(--ink)}a{text-decoration:none;color:inherit}button,input,select,textarea{font:inherit}.ui-icon{width:18px;height:18px;display:block;flex:0 0 auto}
        .portal-app{min-height:100vh;display:grid;grid-template-columns:172px minmax(0,1fr)}.portal-sidebar{position:sticky;top:0;height:100vh;background:linear-gradient(180deg,var(--navy),var(--navy-deep));color:#fff;padding:12px 9px 10px;display:flex;flex-direction:column;z-index:30;overflow:hidden}.portal-account{padding:4px 6px 10px;border-bottom:1px solid #ffffff1a}.portal-account-row{display:flex;align-items:center;gap:7px}.portal-logo{width:32px;height:32px;border-radius:9px;background:#fff;color:var(--navy);display:grid;place-items:center;font-weight:900;font-size:11px}.portal-account strong{font-size:10px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.portal-account small{font-size:9px;color:#aebed4;display:block;margin-top:3px}.portal-role{display:inline-flex;margin-top:9px;padding:5px 8px;border-radius:6px;background:#ffffff1a;color:#dce7f4;font-size:8px;font-weight:800;letter-spacing:.05em}.portal-create{display:grid;grid-template-columns:1fr 32px 32px;gap:6px;margin:9px 2px 4px}.portal-create a,.portal-create button{height:32px;border:0;border-radius:8px;display:flex;align-items:center;justify-content:center;gap:7px;font-size:9px;font-weight:850;cursor:pointer}.portal-create .primary{background:#fff;color:var(--navy)}.portal-create .emergency{background:var(--red);color:#fff}.portal-create .collapse{background:#ffffff1a;color:#fff}.portal-search{margin:7px 2px 4px;height:30px;border:1px solid #ffffff1f;border-radius:9px;display:flex;align-items:center;gap:8px;padding:0 9px}.portal-search input{min-width:0;width:100%;border:0;background:transparent;color:#fff;font-size:9px;outline:0}.portal-search input::placeholder{color:#aebed4}.portal-nav{min-height:0;overflow:auto;padding:2px 2px 10px;scrollbar-width:thin;scrollbar-color:#ffffff2e transparent}.portal-group{margin-top:9px}.portal-group-title{padding:0 7px 4px;color:#7f98b9;font-size:8px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.portal-link{min-height:30px;display:flex;gap:10px;align-items:center;padding:6px 7px;border-radius:7px;font-size:9px;color:#eaf1fa;margin:2px 0}.portal-link:hover,.portal-link.active{background:#ffffff21;color:#fff}.portal-link.active{box-shadow:inset 3px 0 0 var(--red)}[dir=rtl] .portal-link.active{box-shadow:inset -3px 0 0 var(--red)}.portal-link span:nth-child(2){min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.portal-badge{margin-inline-start:auto;min-width:19px;height:18px;padding:0 5px;border-radius:9px;background:#ffffff20;display:grid;place-items:center;font-size:8px;font-weight:800}.portal-badge.urgent{background:var(--red)}.portal-footer{margin-top:auto;padding-top:8px;border-top:1px solid #ffffff1a}.portal-footer button{width:100%;border:1px solid #ffffff33;background:transparent;color:#fff;padding:9px;border-radius:8px;cursor:pointer;font-size:9px}.portal-app.sidebar-collapsed{grid-template-columns:70px minmax(0,1fr)}.portal-app.sidebar-collapsed .portal-account strong,.portal-app.sidebar-collapsed .portal-account small,.portal-app.sidebar-collapsed .portal-role,.portal-app.sidebar-collapsed .portal-group-title,.portal-app.sidebar-collapsed .portal-link span:nth-child(2),.portal-app.sidebar-collapsed .portal-badge,.portal-app.sidebar-collapsed .portal-search input,.portal-app.sidebar-collapsed .portal-create .primary span{display:none}.portal-app.sidebar-collapsed .portal-account-row,.portal-app.sidebar-collapsed .portal-link{justify-content:center}.portal-app.sidebar-collapsed .portal-create{grid-template-columns:1fr}.portal-app.sidebar-collapsed .portal-create a,.portal-app.sidebar-collapsed .portal-create button{width:38px;margin:auto}
        .portal-main{min-width:0;padding:0 24px 44px}.portal-topbar{height:70px;display:flex;align-items:center;gap:18px;border-bottom:1px solid var(--line);background:#fffffff5;margin:0 -24px;padding:0 26px;position:sticky;top:0;z-index:20}.portal-heading{flex:1;min-width:0}.portal-heading h1{font-size:16px;margin:0 0 4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.portal-heading p{font-size:9px;color:var(--muted);margin:0}.portal-status{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:999px;font-size:8px;font-weight:800;background:#e7f7ee;color:#137346}.portal-status:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}.portal-avatar{width:34px;height:34px;border-radius:10px;background:var(--navy);color:#fff;display:grid;place-items:center;font-size:10px;font-weight:900}.portal-content{padding-top:22px;max-width:1500px;margin:auto}.portal-breadcrumb{display:flex;gap:6px;align-items:center;color:var(--muted);font-size:9px;margin-bottom:10px}.portal-breadcrumb a{color:var(--blue);font-weight:800}.portal-page-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:15px}.portal-page-head h2{font-size:24px;line-height:1.15;margin:0 0 5px}.portal-page-head p{font-size:10px;color:var(--muted);margin:0}.portal-card{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow)}.portal-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:0;border-radius:8px;background:var(--navy);color:#fff;padding:9px 13px;font-size:9px;font-weight:850;cursor:pointer}.portal-btn.red{background:var(--red)}.portal-btn.green{background:var(--green)}.portal-btn.soft{background:#edf3fb;color:var(--navy)}.portal-pill{display:inline-flex;align-items:center;padding:5px 8px;border-radius:999px;font-size:8px;font-weight:800;background:#edf4fd;color:#3268a5}.portal-pill.green{background:#e7f7ee;color:#137346}.portal-pill.red{background:#fdebed;color:#b42239}.portal-pill.amber{background:#fff3d9;color:#9b6500}.portal-notice{padding:11px 13px;background:#e9f7ef;color:#176940;border-radius:9px;margin-bottom:12px;font-size:10px}
        .portal-table-wrap{overflow:auto}.portal-table{width:100%;border-collapse:collapse;font-size:10px}.portal-table th,.portal-table td{padding:11px 9px;text-align:start;border-bottom:1px solid #edf0f4;white-space:nowrap}.portal-table th{color:#68758a;font-size:8px;text-transform:uppercase;letter-spacing:.04em}.portal-table tr:last-child td{border-bottom:0}.portal-empty{padding:30px;text-align:center;color:var(--muted);font-size:10px}.portal-empty strong{display:block;color:var(--ink);font-size:12px;margin-bottom:5px}.portal-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}.portal-panel{padding:16px}.portal-panel h3{font-size:13px;margin:0 0 13px}.portal-kv{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.portal-kv>div{border:1px solid #edf1f5;border-radius:9px;padding:11px;min-width:0}.portal-kv small{display:block;color:var(--muted);font-size:8px;text-transform:uppercase;margin-bottom:5px}.portal-kv strong{font-size:10px;display:block;word-break:break-word}
        @media(max-width:1040px){.portal-app{grid-template-columns:82px minmax(0,1fr)}.portal-account strong,.portal-account small,.portal-role,.portal-group-title,.portal-link span:nth-child(2),.portal-badge,.portal-search input,.portal-create .primary span{display:none}.portal-account-row,.portal-link{justify-content:center}.portal-create{grid-template-columns:1fr}.portal-create a{width:38px}.portal-sidebar{padding-inline:9px}}
        @media(max-width:760px){.portal-app{display:block}.portal-sidebar{height:auto;position:sticky;top:0;display:flex;flex-direction:row;align-items:center;padding:7px;overflow:auto}.portal-account,.portal-create,.portal-search,.portal-group-title,.portal-footer{display:none}.portal-nav{display:flex;overflow:auto;padding:0}.portal-group{display:flex;margin:0}.portal-link{min-width:42px}.portal-main{padding:0 12px 28px}.portal-topbar{margin:0 -12px;padding:0 14px}.portal-page-head{align-items:flex-start;flex-direction:column}.portal-grid-2{grid-template-columns:1fr}}
        @media(max-width:520px){.portal-status{display:none}.portal-kv{grid-template-columns:1fr}.portal-content{padding-top:14px}.portal-page-head h2{font-size:21px}}
        @media print{.portal-sidebar,.portal-topbar,.portal-breadcrumb,.no-print{display:none!important}.portal-app{display:block}.portal-main{padding:0}.portal-content{max-width:none;padding:0}body{background:#fff}}
    </style>
    @stack('styles')
</head>
<body>
<div class="portal-app">
    <aside class="portal-sidebar" id="customer-sidebar">
        <div class="portal-account"><div class="portal-account-row"><div class="portal-logo">{{ strtoupper(substr($customer->name, 0, 2)) }}</div><div><strong>{{ $customer->name }}</strong><small>{{ $customer->customer_code }}</small></div></div><span class="portal-role">{{ $isArabic ? 'حساب العميل الموحد' : 'UNIFIED CUSTOMER ACCOUNT' }}</span></div>
        <div class="portal-create"><a class="primary" href="{{ route('public.request-service', ['customer' => $customer->customer_code]) }}">＋ <span>{{ $isArabic ? 'إنشاء طلب' : 'New Request' }}</span></a><a class="emergency" title="{{ $isArabic ? 'بلاغ طارئ' : 'Emergency Request' }}" href="{{ route('public.request-service', ['customer' => $customer->customer_code, 'emergency' => 1]) }}">!</a><button class="collapse" id="portal-sidebar-toggle" type="button" title="Collapse sidebar">⇤</button></div>
        <form class="portal-search" method="GET" action="{{ route('customer.search', ['lang'=>$locale]) }}">@include('customer.partials.icon',['name'=>'search'])<input name="q" aria-label="{{ $isArabic ? 'بحث عام' : 'Global search' }}" placeholder="{{ $isArabic ? 'طلب، أصل، فاتورة أو عقد' : 'Request, asset, invoice or contract' }}"></form>
        <nav class="portal-nav">
            @foreach($navGroups as $group => $items)
                <div class="portal-group"><div class="portal-group-title">{{ $group }}</div>
                    @foreach($items as [$key,$icon,$label,$url])
                        <a class="portal-link {{ $activeSection === $key ? 'active' : '' }}" href="{{ $url }}">@include('customer.partials.icon',['name'=>$icon])<span>{{ $label }}</span>@if(isset($navBadges[$key]) && (int)$navBadges[$key] > 0)<span class="portal-badge {{ in_array($key,['actions','notifications'],true) ? 'urgent' : '' }}">{{ $navBadges[$key] }}</span>@endif</a>
                    @endforeach
                </div>
            @endforeach
        </nav>
        <form class="portal-footer" method="POST" action="{{ route('logout') }}">@csrf<button>{{ $isArabic ? 'تسجيل الخروج' : 'Sign out' }}</button></form>
    </aside>
    <main class="portal-main">
        <header class="portal-topbar"><div class="portal-heading"><h1>{{ $pageTitle }}</h1><p>{{ $customer->name }} · {{ $pageDescription }}</p></div><span class="portal-status">{{ $customer->status }}</span><div class="portal-avatar">{{ strtoupper(substr(auth()->user()->name ?: $customer->name,0,2)) }}</div></header>
        <div class="portal-content">
            <div class="portal-breadcrumb"><a href="{{ route('customer.portal', ['lang'=>$locale]) }}">{{ $isArabic ? 'لوحة العميل' : 'Customer 360' }}</a><span>›</span><span>{{ $pageTitle }}</span></div>
            @if(session('status'))<div class="portal-notice">{{ session('status') }}</div>@endif
