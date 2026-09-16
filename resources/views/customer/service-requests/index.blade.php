@php
    $ar = app()->getLocale()==='ar';
    $labels = [
        'OPEN'=>[$ar?'مفتوح':'Open','green'],'CLOSED'=>[$ar?'مغلق':'Closed',''],'COMPLETED'=>[$ar?'مكتمل':'Completed','green'],'CANCELLED'=>[$ar?'ملغي':'Cancelled',''],
        'TRIAGE'=>[$ar?'فرز الطلب':'Triage',''],'SALES_REVIEW'=>[$ar?'مراجعة المبيعات':'Sales Review',''],'TECHNICAL_REVIEW'=>[$ar?'مراجعة فنية':'Technical Review',''],
        'OPERATIONS_REVIEW'=>[$ar?'مراجعة العمليات':'Operations Review',''],'EMERGENCY_DISPATCH'=>[$ar?'توجيه طارئ':'Emergency Dispatch','red'],
        'ASSIGNED'=>[$ar?'تم الإسناد':'Assigned',''],'IN_PROGRESS'=>[$ar?'جاري التنفيذ':'In Progress','amber'],'CUSTOMER_ACCEPTANCE'=>[$ar?'بانتظار اعتماد العميل':'Customer Acceptance','red'],
        'MAINTENANCE'=>[$ar?'صيانة':'Maintenance',''],'QUOTATION'=>[$ar?'عرض سعر':'Quotation',''],'CONSULTATION'=>[$ar?'استشارة فنية':'Consultation',''],
        'NORMAL'=>[$ar?'عادية':'Normal',''],'HIGH'=>[$ar?'مرتفعة':'High','amber'],'EMERGENCY'=>[$ar?'طارئة':'Emergency','red'],
    ];
    $label = fn($value) => $labels[strtoupper((string)$value)][0] ?? str_replace('_',' ',(string)$value ?: '—');
    $tone = fn($value) => $labels[strtoupper((string)$value)][1] ?? '';
    $buckets = [
        'all'=>[$ar?'كل الطلبات':'All requests',$summary['all']],
        'open'=>[$ar?'المفتوحة':'Open',$summary['open']],
        'emergency'=>[$ar?'الطارئة':'Emergency',$summary['emergency']],
        'overdue'=>[$ar?'متجاوزة الموعد':'Overdue',$summary['overdue']],
        'in_progress'=>[$ar?'قيد التنفيذ':'In progress',$summary['in_progress']],
        'awaiting_customer'=>[$ar?'بانتظار العميل':'Awaiting customer',$summary['awaiting_customer']],
        'completed'=>[$ar?'المكتملة':'Completed',$summary['completed']],
    ];
@endphp

@include('customer.partials.portal-shell-open', [
    'activeSection' => 'requests',
    'pageTitle' => $ar ? 'طلبات الخدمة' : 'Service Requests',
    'pageDescription' => $ar ? 'جميع طلبات الشركة ومراحل تنفيذها في حساب موحد.' : 'Every company request and delivery stage in one unified account.',
])

@push('late-styles')
<style>
    .request-portfolio{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin-bottom:12px}.request-kpi{padding:14px;position:relative;overflow:hidden}.request-kpi:before{content:"";position:absolute;inset-block:0;inset-inline-start:0;width:3px;background:var(--blue)}.request-kpi.danger:before{background:var(--red)}.request-kpi span{font-size:8px;color:var(--muted)}.request-kpi b{display:block;font-size:23px;margin:6px 0 3px}.request-kpi small{display:block;font-size:8px;color:var(--muted);min-height:18px}.request-buckets{display:flex;align-items:center;gap:7px;padding:10px 12px;margin-bottom:12px;overflow:auto}.request-buckets strong{font-size:9px;white-space:nowrap;margin-inline-end:4px}.request-bucket{display:inline-flex;align-items:center;gap:6px;padding:8px 10px;border-radius:8px;background:#f2f6fb;color:#52627a;font-size:9px;font-weight:800;white-space:nowrap}.request-bucket.active{background:var(--navy);color:#fff}.request-bucket em{font-style:normal;min-width:18px;height:18px;padding:0 5px;border-radius:9px;background:#fff;color:var(--navy);display:grid;place-items:center}.request-filter{padding:14px;margin-bottom:12px}.request-filters{display:grid;grid-template-columns:minmax(230px,2fr) repeat(5,minmax(120px,1fr)) auto;gap:9px;align-items:end}.request-field{display:grid;gap:5px;font-size:8px;font-weight:800;color:#5e6e85;text-transform:uppercase}.request-field input,.request-field select{height:38px;border:1px solid #d6dee8;border-radius:8px;padding:0 10px;background:#fff;color:var(--ink)}.request-summary{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px}.request-summary-main,.request-summary-help{display:flex;gap:7px;flex-wrap:wrap}.request-summary span{padding:7px 10px;border:1px solid var(--line);border-radius:8px;background:#fff;font-size:9px;color:var(--muted)}.request-summary-help span{background:#eaf2fc;color:#285e9d;border-color:#d7e5f6}.request-table-card{padding:8px}.request-row{cursor:pointer}.request-row:hover{background:#f8fbff}.request-link{font-weight:900;color:#0a4f99}.request-primary{min-width:210px;max-width:320px;white-space:normal!important}.request-primary b{display:block;font-size:10px;line-height:1.4}.request-primary small,.request-stage small{display:block;color:var(--muted);font-size:8px;margin-top:4px;line-height:1.4}.request-stage{min-width:190px;white-space:normal!important}.request-stage .portal-pill{margin-bottom:4px}.request-due{min-width:120px}.request-due strong{display:block;font-size:9px}.request-due small{display:block;font-size:8px;color:var(--muted);margin-top:4px}.request-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 4px 3px;font-size:10px;color:var(--muted)}.request-pages{display:flex;gap:6px}.request-pages a,.request-pages span{min-width:32px;height:32px;display:grid;place-items:center;border:1px solid var(--line);border-radius:7px;background:#fff}.request-pages .current{background:var(--navy);color:#fff;border-color:var(--navy)}@media(max-width:1280px){.request-portfolio{grid-template-columns:repeat(3,1fr)}.request-filters{grid-template-columns:repeat(3,1fr)}}@media(max-width:760px){.request-portfolio{grid-template-columns:repeat(2,1fr)}.request-filters{grid-template-columns:1fr}.request-pagination{align-items:flex-start;flex-direction:column}}@media(max-width:480px){.request-portfolio{grid-template-columns:1fr}}
</style>
@endpush

<div class="portal-page-head"><div><h2>{{ $ar ? 'طلبات الخدمة' : 'Service Requests' }}</h2><p>{{ $ar ? 'تابع كل طلبات العميل، الأولويات، المرحلة الحالية والخطوة التالية.' : 'Track every customer request, priority, current stage and next action.' }}</p></div><a class="portal-btn red" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">＋ {{ $ar ? 'إنشاء طلب خدمة' : 'New Service Request' }}</a></div>

<section class="request-portfolio" aria-label="{{ $ar?'ملخص محفظة الطلبات':'Request portfolio' }}">
    <div class="portal-card request-kpi"><span>{{ $ar?'إجمالي الطلبات':'Request portfolio' }}</span><b>{{ $summary['all'] }}</b><small>{{ $ar?'كامل حساب العميل':'Full customer account' }}</small></div>
    <div class="portal-card request-kpi"><span>{{ $ar?'طلبات مفتوحة':'Open requests' }}</span><b>{{ $summary['open'] }}</b><small>{{ $ar?'ما زالت ضمن دورة التنفيذ':'Still in the delivery workflow' }}</small></div>
    <div class="portal-card request-kpi danger"><span>{{ $ar?'طلبات طارئة':'Emergency requests' }}</span><b>{{ $summary['emergency'] }}</b><small>{{ $ar?'أولوية استجابة فورية':'Immediate response priority' }}</small></div>
    <div class="portal-card request-kpi danger"><span>{{ $ar?'متجاوزة الموعد':'Overdue stages' }}</span><b>{{ $summary['overdue'] }}</b><small>{{ $ar?'تجاوزت موعد المرحلة الحالية':'Past current-stage due date' }}</small></div>
    <div class="portal-card request-kpi"><span>{{ $ar?'قيد التنفيذ':'In progress' }}</span><b>{{ $summary['in_progress'] }}</b><small>{{ $ar?'أعمال يجري تنفيذها':'Work currently being executed' }}</small></div>
    <div class="portal-card request-kpi"><span>{{ $ar?'تنتظر العميل':'Awaiting customer' }}</span><b>{{ $summary['awaiting_customer'] }}</b><small>{{ $ar?'تحتاج اعتمادًا أو متابعة':'Needs acceptance or follow-up' }}</small></div>
</section>

<nav class="portal-card request-buckets" aria-label="{{ $ar?'حالة الطلبات':'Request status shortcuts' }}"><strong>{{ $ar?'عرض سريع':'Quick view' }}</strong>@foreach($buckets as $key=>[$text,$count])<a class="request-bucket {{ (($filters['bucket']?:'all')===$key)?'active':'' }}" href="{{ route('customer.service-requests.index',['bucket'=>$key]) }}">{{ $text }} <em>{{ $count }}</em></a>@endforeach</nav>

<form class="portal-card request-filter" method="GET"><input type="hidden" name="bucket" value="{{ $filters['bucket'] }}"><div class="request-filters">
    <label class="request-field">{{ $ar?'بحث':'Search' }}<input name="q" value="{{ $filters['q'] }}" placeholder="{{ $ar?'رقم الطلب، الموضوع، الموقع أو الخدمة':'Request no, subject, site or service' }}"></label>
    <label class="request-field">{{ $ar?'النوع':'Type' }}<select name="type"><option value="">{{ $ar?'كل الأنواع':'All Types' }}</option>@foreach($types as $value)<option value="{{ $value }}" @selected($filters['type']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">{{ $ar?'الأولوية':'Priority' }}<select name="priority"><option value="">{{ $ar?'كل الأولويات':'All Priorities' }}</option>@foreach($priorities as $value)<option value="{{ $value }}" @selected($filters['priority']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">{{ $ar?'المرحلة':'Stage' }}<select name="stage"><option value="">{{ $ar?'كل المراحل':'All Stages' }}</option>@foreach($stages as $value)<option value="{{ $value }}" @selected($filters['stage']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">{{ $ar?'الحالة':'Status' }}<select name="status"><option value="">{{ $ar?'كل الحالات':'All Statuses' }}</option>@foreach($statuses as $value)<option value="{{ $value }}" @selected($filters['status']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">{{ $ar?'الموقع':'Site' }}<select name="site_id"><option value="">{{ $ar?'كل المواقع':'All Sites' }}</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected((int)$filters['site_id']===(int)$site->id)>{{ $site->name }}</option>@endforeach</select></label>
    <div style="display:flex;gap:7px"><button class="portal-btn">{{ $ar?'تطبيق':'Apply' }}</button><a class="portal-btn soft" href="{{ route('customer.service-requests.index') }}">{{ $ar?'إعادة ضبط':'Reset' }}</a></div>
</div></form>

<div class="request-summary"><div class="request-summary-main"><span><b>{{ $requests->total() }}</b> {{ $ar?'طلب مطابق':'matching requests' }}</span><span>{{ $ar?'صفحة':'Page' }} {{ $requests->currentPage() }} / {{ max(1,$requests->lastPage()) }}</span></div><div class="request-summary-help"><span>{{ $ar?'مرتبة حسب التجاوز ثم الأولوية':'Ordered by overdue status, then priority' }}</span><span>{{ $ar?'اضغط أي صف لفتح Request 360':'Click any row to open Request 360' }}</span></div></div>

<div class="portal-card request-table-card"><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>{{ $ar?'الطلب':'Request' }}</th><th>{{ $ar?'التفاصيل':'Details' }}</th><th>{{ $ar?'النوع':'Type' }}</th><th>{{ $ar?'الأولوية':'Priority' }}</th><th>{{ $ar?'المرحلة والخطوة التالية':'Stage & next action' }}</th><th>{{ $ar?'الاستحقاق':'Due' }}</th><th>{{ $ar?'الحالة':'Status' }}</th><th>{{ $ar?'آخر تحديث':'Last update' }}</th><th></th></tr></thead><tbody>
@forelse($requests as $requestItem)
    @php
        $url=route('customer.service-requests.show',$requestItem);
        $isClosed=in_array(strtoupper((string)$requestItem->status),['COMPLETED','CLOSED','CANCELLED','REJECTED'],true);
        $isOverdue=!$isClosed && $requestItem->current_stage_due_at?->isPast();
        $site=$sitesById->get($requestItem->customer_site_id);
        $asset=$assetsById->get($requestItem->asset_id);
    @endphp
    <tr class="request-row" onclick="window.location.href='{{ $url }}'">
        <td><a class="request-link" href="{{ $url }}" onclick="event.stopPropagation()">{{ $requestItem->request_no }}</a><small style="display:block;color:var(--muted);margin-top:4px">{{ $requestItem->created_at?->format('Y-m-d H:i') }}</small></td>
        <td class="request-primary"><b>{{ $requestItem->subject ?: $requestItem->service_category ?: ($ar?'طلب خدمة':'Service request') }}</b><small>{{ $site?->name ?: ($requestItem->site_city ?: ($ar?'لا يوجد موقع مرتبط':'No linked site')) }}@if($asset) · {{ $asset->asset_code }}@endif</small></td>
        <td><span class="portal-pill">{{ $label($requestItem->request_type) }}</span></td>
        <td><span class="portal-pill {{ $tone($requestItem->priority) }}">{{ $label($requestItem->priority) }}</span></td>
        <td class="request-stage"><span class="portal-pill {{ $tone($requestItem->workflow_stage) }}">{{ $label($requestItem->workflow_stage) }}</span><small>{{ $requestItem->next_action ?: ($ar?'يونيفكو تتابع معالجة الطلب':'UNIFCO is processing the request') }}</small></td>
        <td class="request-due">@if($isOverdue)<span class="portal-pill red">{{ $ar?'متجاوز':'Overdue' }}</span><small>{{ $requestItem->current_stage_due_at?->format('Y-m-d H:i') }}</small>@elseif($requestItem->current_stage_due_at)<strong>{{ $requestItem->current_stage_due_at->format('Y-m-d H:i') }}</strong><small>{{ $requestItem->current_stage_due_at->diffForHumans() }}</small>@else<span>—</span>@endif</td>
        <td><span class="portal-pill {{ $tone($requestItem->status) }}">{{ $label($requestItem->status) }}</span></td>
        <td>{{ $requestItem->updated_at?->format('Y-m-d H:i') }}</td>
        <td><a class="portal-btn soft" href="{{ $url }}" onclick="event.stopPropagation()">{{ $ar?'عرض التفاصيل':'View Details' }} · 360</a></td>
    </tr>
@empty<tr><td colspan="9"><div class="portal-empty"><strong>{{ $ar?'لا توجد طلبات مطابقة':'No matching service requests' }}</strong>{{ $ar?'جرّب إزالة فلتر أو أكثر.':'Try clearing one or more filters.' }}</div></td></tr>@endforelse
</tbody></table></div>
@if($requests->hasPages())<div class="request-pagination"><span>{{ $ar?'عرض':'Showing' }} {{ $requests->firstItem() }}–{{ $requests->lastItem() }} {{ $ar?'من':'of' }} {{ $requests->total() }}</span><div class="request-pages">@if($requests->onFirstPage())<span>‹</span>@else<a href="{{ $requests->previousPageUrl() }}">‹</a>@endif @foreach(range(max(1,$requests->currentPage()-2),min($requests->lastPage(),$requests->currentPage()+2)) as $page)@if($page===$requests->currentPage())<span class="current">{{ $page }}</span>@else<a href="{{ $requests->url($page) }}">{{ $page }}</a>@endif @endforeach @if($requests->hasMorePages())<a href="{{ $requests->nextPageUrl() }}">›</a>@else<span>›</span>@endif</div></div>@endif
</div>

@include('customer.partials.portal-shell-close')
