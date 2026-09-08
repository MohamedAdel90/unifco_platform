@extends('layouts.app')
@section('title','Customers · UNIFCO')
@section('heading','Customers / العملاء')
@section('content')
@php
    $statusLabels=[
        'ACTIVE'=>['ar'=>'نشط','en'=>'Active','class'=>'active'],
        'BLOCKED'=>['ar'=>'معلّق','en'=>'Suspended','class'=>'blocked'],
    ];
    $onboardingLabels=[
        'DRAFT'=>['ar'=>'مسودة','en'=>'Draft','progress'=>25],
        'ONBOARDING'=>['ar'=>'قيد التهيئة','en'=>'Onboarding','progress'=>65],
        'PENDING'=>['ar'=>'قيد التهيئة','en'=>'Onboarding','progress'=>65],
        'COMPLETED'=>['ar'=>'مكتملة','en'=>'Completed','progress'=>100],
        'ACTIVE'=>['ar'=>'مكتملة','en'=>'Completed','progress'=>100],
    ];
@endphp
<style>
.customers-page{direction:ltr;color:#17243a}.customers-page *{box-sizing:border-box}html[dir="rtl"] .customers-page{direction:rtl}.customers-ar{display:none}html[dir="rtl"] .customers-en{display:none}html[dir="rtl"] .customers-ar{display:inline}.customers-page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px}.customers-page-head h1{margin:0 0 5px;color:#102b54;font-size:23px}.customers-page-head p{margin:0;color:#718096;font-size:11px}.customers-head-actions{display:flex;gap:8px;flex-wrap:wrap}.customers-page .customer-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:38px;padding:8px 12px;border:1px solid #d9e1ec;border-radius:8px;background:#fff;color:#253751;text-decoration:none;font-size:10px;font-weight:800;cursor:pointer}.customers-page .customer-btn:hover{background:#f5f8fc}.customers-page .customer-btn.primary{background:#ce122d;border-color:#ce122d;color:#fff}.customers-page .customer-btn.navy{background:#102b54;border-color:#102b54;color:#fff}.customers-page .customer-btn.icon-only{width:34px;height:34px;min-height:34px;padding:0;font-size:17px}.customer-import-input{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}.customers-metrics{display:grid;grid-template-columns:repeat(4,minmax(130px,1fr));gap:12px;margin-bottom:16px}.customers-metric{background:#fff;border:1px solid #e0e7f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:10px;box-shadow:0 3px 11px rgba(16,43,84,.035)}.customers-metric span{display:block;color:#718096;font-size:10px}.customers-metric strong{display:block;margin-top:3px;color:#102b54;font-size:23px}.customers-metric-icon{width:36px;height:36px;border-radius:9px;display:grid;place-items:center;background:#eaf0f9;color:#1b4b83;font-size:16px}.customers-metric-icon.red{background:#fff0f3;color:#ce122d}.customers-panel{background:#fff;border:1px solid #e0e7f0;border-radius:11px;overflow:visible;box-shadow:0 3px 11px rgba(16,43,84,.035)}.customers-filters{padding:13px;display:grid;grid-template-columns:minmax(230px,1.7fr) repeat(3,minmax(125px,.7fr)) auto;gap:8px;border-bottom:1px solid #e6ebf2}.customers-field{position:relative}.customers-field .search-icon{position:absolute;left:11px;top:10px;color:#718096;font-size:15px;pointer-events:none}html[dir="rtl"] .customers-field .search-icon{left:auto;right:11px}.customers-input,.customers-select{width:100%;min-height:38px;background:#fbfcfe;color:#253751;border:1px solid #d9e1ec;border-radius:8px;padding:7px 10px;font:inherit;font-size:10px}.customers-input{padding-left:34px}html[dir="rtl"] .customers-input{padding-left:10px;padding-right:34px}.customers-table-wrap{overflow-x:auto}.customers-table{width:100%;min-width:980px;border-collapse:collapse;margin:0;font-size:10px}.customers-table th{text-align:left;color:#66758c;background:#f8fafc;padding:10px 12px;font-size:9px;font-weight:800;white-space:nowrap}.customers-table td{text-align:left;padding:11px 12px;border-top:1px solid #edf1f6;vertical-align:middle}html[dir="rtl"] .customers-table th,html[dir="rtl"] .customers-table td{text-align:right}.customers-table tbody tr:hover{background:#f8fbff}.customer-identity{display:flex;align-items:center;gap:9px;min-width:155px}.customer-avatar{flex:0 0 auto;width:34px;height:34px;border-radius:9px;background:#e9f0fa;color:#173b6c;display:grid;place-items:center;font-weight:900;font-size:13px}.customer-identity a{color:#102f5e;text-decoration:none;font-weight:900}.customer-identity small,.customer-contact small,.customer-sector small{display:block;color:#8491a3;font-size:8px;margin-top:3px}.customer-ltr{direction:ltr;text-align:left!important;white-space:nowrap}.customer-code{color:#102b54;font-weight:900}.customer-status{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:5px 8px;font-size:8px;font-weight:900;white-space:nowrap}.customer-status:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}.customer-status.active{color:#087a50;background:#e5f7ef}.customer-status.blocked{color:#b2203a;background:#fde9ee}.customer-progress{min-width:112px}.customer-progress-label{display:flex;justify-content:space-between;gap:7px;margin-bottom:5px;color:#66758c;font-size:8px}.customer-progress-label b{color:#102b54}.customer-progress-track{height:5px;border-radius:99px;background:#e8edf4;overflow:hidden}.customer-progress-track i{display:block;height:100%;border-radius:99px;background:#1a5b9d}.customer-menu-wrap{position:relative}.customer-menu{position:absolute;z-index:12;top:39px;left:0;width:165px;padding:5px;background:#fff;border:1px solid #dce4ee;border-radius:8px;box-shadow:0 12px 28px rgba(25,45,75,.16)}html[dir="rtl"] .customer-menu{left:auto;right:0}.customer-menu[hidden]{display:none}.customer-menu a,.customer-menu button{display:block;width:100%;padding:8px;border:0;border-radius:6px;background:transparent;color:#253751;text-align:left;text-decoration:none;font:inherit;font-size:9px;font-weight:700;cursor:pointer}html[dir="rtl"] .customer-menu a,html[dir="rtl"] .customer-menu button{text-align:right}.customer-menu a:hover,.customer-menu button:hover{background:#f0f4f9}.customer-menu .danger{color:#ce122d}.customer-empty{padding:38px;text-align:center;color:#718096}.customer-empty b{display:block;color:#102b54;font-size:13px;margin-bottom:5px}.customers-footer{padding:12px 14px;border-top:1px solid #e6ebf2;display:flex;align-items:center;justify-content:space-between;gap:12px;color:#718096;font-size:9px}.customers-pagination{direction:ltr}.customers-pagination nav{margin:0}.customers-pagination svg{width:16px}.customers-pagination p{font-size:9px}.customers-pagination a,.customers-pagination span{font-size:9px}.customers-notice{margin-bottom:14px}.customers-errors{margin-bottom:14px;padding:11px 13px;border-radius:8px;background:#fdebed;color:#9f2438;font-size:10px}.customers-errors ul{margin:5px 0 0;padding-inline-start:18px}@media(max-width:1050px){.customers-filters{grid-template-columns:1fr 1fr}.customers-field{grid-column:1/-1}.customers-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.customers-page-head{align-items:flex-start;flex-direction:column}.customers-head-actions{width:100%}.customers-head-actions .customer-btn{flex:1}.customers-filters{grid-template-columns:1fr}.customers-field{grid-column:auto}.customers-metrics{grid-template-columns:1fr 1fr}.customers-footer{align-items:flex-start;flex-direction:column}}@media(max-width:420px){.customers-metrics{grid-template-columns:1fr}}
</style>

<div class="customers-page" id="customers-page">
    <div class="customers-page-head">
        <div>
            <h1><span class="customers-en">Customer Management</span><span class="customers-ar">إدارة العملاء</span></h1>
            <p><span class="customers-en">Manage customer profiles, contacts and onboarding status from one place.</span><span class="customers-ar">إدارة ملفات العملاء وبيانات التواصل وحالة التهيئة من مكان واحد.</span></p>
        </div>
        <div class="customers-head-actions">
            <a class="customer-btn" href="{{ route('crm.customers.export',request()->query()) }}">⇩ <span class="customers-en">Export CSV</span><span class="customers-ar">تصدير CSV</span></a>
            <form method="POST" action="{{ route('crm.customers.import') }}" enctype="multipart/form-data" id="customer-import-form">@csrf
                <label class="customer-btn" for="customer-import-file">⇧ <span class="customers-en">Import CSV</span><span class="customers-ar">استيراد CSV</span></label>
                <input class="customer-import-input" id="customer-import-file" name="file" type="file" accept=".csv,text/csv" required>
            </form>
            <a class="customer-btn primary" href="{{ route('crm.customers.create') }}">＋ <span class="customers-en">Add Customer</span><span class="customers-ar">إضافة عميل</span></a>
        </div>
    </div>

    @if(session('status'))<p class="notice customers-notice">{{ session('status') }}</p>@endif
    @if($errors->any())<div class="customers-errors"><b><span class="customers-en">The request could not be completed.</span><span class="customers-ar">تعذر إكمال الطلب.</span></b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="customers-metrics">
        <div class="customers-metric"><div><span><span class="customers-en">Total Customers</span><span class="customers-ar">إجمالي العملاء</span></span><strong>{{ $metrics['total'] }}</strong></div><div class="customers-metric-icon">◎</div></div>
        <div class="customers-metric"><div><span><span class="customers-en">Active Customers</span><span class="customers-ar">العملاء النشطون</span></span><strong>{{ $metrics['active'] }}</strong></div><div class="customers-metric-icon">✓</div></div>
        <div class="customers-metric"><div><span><span class="customers-en">In Onboarding</span><span class="customers-ar">قيد التهيئة</span></span><strong>{{ $metrics['onboarding'] }}</strong></div><div class="customers-metric-icon">◷</div></div>
        <div class="customers-metric"><div><span><span class="customers-en">Suspended Accounts</span><span class="customers-ar">حسابات معلّقة</span></span><strong>{{ $metrics['blocked'] }}</strong></div><div class="customers-metric-icon red">!</div></div>
    </div>

    <div class="customers-panel">
        <form class="customers-filters" method="GET" action="{{ route('crm.customers.index') }}">
            <label class="customers-field"><span class="search-icon">⌕</span><span class="sr-only">Search</span><input class="customers-input" name="q" value="{{ request('q') }}" data-placeholder-en="Search by name, customer ID, email or phone..." data-placeholder-ar="ابحث بالاسم أو رقم العميل أو البريد أو الجوال..."></label>
            <label><span class="sr-only">Status</span><select class="customers-select" name="status"><option value="">All statuses / كل الحالات</option><option value="ACTIVE" @selected(request('status')==='ACTIVE')>Active / نشط</option><option value="BLOCKED" @selected(request('status')==='BLOCKED')>Suspended / معلّق</option></select></label>
            <label><span class="sr-only">City</span><select class="customers-select" name="city"><option value="">All cities / كل المدن</option>@foreach($cities as $city)<option value="{{ $city }}" @selected(request('city')===$city)>{{ $city }}</option>@endforeach</select></label>
            <label><span class="sr-only">Industry</span><select class="customers-select" name="industry"><option value="">All sectors / كل القطاعات</option>@foreach($industries as $industry)<option value="{{ $industry }}" @selected(request('industry')===$industry)>{{ $industry }}</option>@endforeach</select></label>
            <div style="display:flex;gap:6px"><button class="customer-btn navy" type="submit"><span class="customers-en">Apply</span><span class="customers-ar">تطبيق</span></button><a class="customer-btn" href="{{ route('crm.customers.index') }}" aria-label="Reset filters">↻</a></div>
        </form>

        <div class="customers-table-wrap">
            <table class="customers-table">
                <thead><tr><th><span class="customers-en">Customer</span><span class="customers-ar">العميل</span></th><th><span class="customers-en">Customer ID</span><span class="customers-ar">رقم العميل</span></th><th><span class="customers-en">Sector / City</span><span class="customers-ar">القطاع / المدينة</span></th><th><span class="customers-en">Primary Contact</span><span class="customers-ar">مسؤول التواصل</span></th><th><span class="customers-en">Customer Status</span><span class="customers-ar">حالة العميل</span></th><th><span class="customers-en">Onboarding</span><span class="customers-ar">اكتمال التهيئة</span></th><th><span class="customers-en">Actions</span><span class="customers-ar">الإجراءات</span></th></tr></thead>
                <tbody>
                @forelse($customers as $c)
                    @php
                        $status=$statusLabels[strtoupper($c->status ?: 'ACTIVE')] ?? ['ar'=>$c->status,'en'=>$c->status,'class'=>'active'];
                        $onboarding=$onboardingLabels[strtoupper($c->onboarding_status ?: 'DRAFT')] ?? ['ar'=>$c->onboarding_status,'en'=>$c->onboarding_status,'progress'=>25];
                    @endphp
                    <tr>
                        <td><div class="customer-identity"><div class="customer-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($c->name,0,1)) }}</div><div><a href="{{ route('crm.customers.portal',$c) }}">{{ $c->name }}</a><small><span class="customers-en">Customer since</span><span class="customers-ar">عميل منذ</span> {{ $c->created_at?->format('Y') }}</small></div></div></td>
                        <td><span class="customer-code customer-ltr">{{ $c->customer_code }}</span></td>
                        <td><div class="customer-sector"><strong>{{ $c->industry ?: '—' }}</strong><small>{{ $c->city ?: '—' }}</small></div></td>
                        <td><div class="customer-contact"><strong>{{ $c->contact_name ?: '—' }}</strong><small class="customer-ltr">{{ $c->email ?: ($c->phone ?: '—') }}</small></div></td>
                        <td><span class="customer-status {{ $status['class'] }}"><span class="customers-en">{{ $status['en'] }}</span><span class="customers-ar">{{ $status['ar'] }}</span></span></td>
                        <td><div class="customer-progress"><div class="customer-progress-label"><span><span class="customers-en">{{ $onboarding['en'] }}</span><span class="customers-ar">{{ $onboarding['ar'] }}</span></span><b>{{ $onboarding['progress'] }}%</b></div><div class="customer-progress-track"><i style="width:{{ $onboarding['progress'] }}%"></i></div></div></td>
                        <td><div class="customer-menu-wrap"><button class="customer-btn icon-only customer-menu-toggle" type="button" aria-expanded="false" aria-label="Customer actions">⋯</button><div class="customer-menu" hidden>
                            <a href="{{ route('crm.customers.portal',$c) }}"><span class="customers-en">View customer center</span><span class="customers-ar">عرض مركز العميل</span></a>
                            <a href="{{ route('crm.customers.edit',$c) }}"><span class="customers-en">Edit customer</span><span class="customers-ar">تعديل البيانات</span></a>
                            @if($c->status==='ACTIVE')<form method="POST" action="{{ route('crm.customers.block',$c) }}" data-confirm-en="Suspend this customer?" data-confirm-ar="هل تريد تعليق هذا العميل؟">@csrf<button class="danger" type="submit"><span class="customers-en">Suspend customer</span><span class="customers-ar">تعليق العميل</span></button></form>@endif
                        </div></div></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="customer-empty"><b><span class="customers-en">No customers match these filters</span><span class="customers-ar">لا يوجد عملاء مطابقون للفلاتر</span></b><span><span class="customers-en">Change the filters or add a new customer.</span><span class="customers-ar">غيّر الفلاتر أو أضف عميلًا جديدًا.</span></span></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="customers-footer"><span><span class="customers-en">Showing</span><span class="customers-ar">عرض</span> {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} <span class="customers-en">of</span><span class="customers-ar">من أصل</span> {{ $customers->total() }}</span><div class="customers-pagination">{{ $customers->links() }}</div></div>
    </div>
</div>
<script>
(function(){
    var page=document.getElementById('customers-page');
    if(!page)return;
    var file=document.getElementById('customer-import-file'),form=document.getElementById('customer-import-form');
    if(file&&form)file.addEventListener('change',function(){if(this.files.length)form.submit()});
    function syncPlaceholder(){var ar=document.documentElement.dir==='rtl';page.querySelectorAll('[data-placeholder-en]').forEach(function(input){input.placeholder=ar?input.dataset.placeholderAr:input.dataset.placeholderEn})}
    syncPlaceholder();
    new MutationObserver(syncPlaceholder).observe(document.documentElement,{attributes:true,attributeFilter:['dir']});
    page.querySelectorAll('.customer-menu-toggle').forEach(function(toggle){toggle.addEventListener('click',function(event){event.stopPropagation();var menu=this.nextElementSibling;page.querySelectorAll('.customer-menu').forEach(function(other){if(other!==menu)other.hidden=true});menu.hidden=!menu.hidden;this.setAttribute('aria-expanded',menu.hidden?'false':'true')})});
    document.addEventListener('click',function(){page.querySelectorAll('.customer-menu').forEach(function(menu){menu.hidden=true})});
    page.querySelectorAll('form[data-confirm-en]').forEach(function(item){item.addEventListener('submit',function(event){var message=document.documentElement.dir==='rtl'?this.dataset.confirmAr:this.dataset.confirmEn;if(!window.confirm(message))event.preventDefault()})});
})();
</script>
@endsection
