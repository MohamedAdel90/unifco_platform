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
    $attentionRequests = $requests->getCollection()->filter(function($item){
        $closed = in_array(strtoupper((string)$item->status),['COMPLETED','CLOSED','CANCELLED','REJECTED'],true);
        $overdue = !$closed && $item->current_stage_due_at?->isPast();
        $emergency = strtoupper((string)$item->priority)==='EMERGENCY';
        return $overdue || $emergency;
    })->take(3);
@endphp

@include('customer.partials.portal-shell-open', [
    'activeSection' => 'requests',
    'pageTitle' => $ar ? 'طلبات الخدمة' : 'Service Requests',
    'pageDescription' => $ar ? 'جميع طلبات الشركة ومراحل تنفيذها في حساب موحد.' : 'Every company request and delivery stage in one unified account.',
])

@push('late-styles')
<style>
    .portal-content{max-width:none}
    .portal-page-head{align-items:center;margin-bottom:16px}.portal-page-head h2{font-size:26px}.portal-page-head p{font-size:10px}
    .request-portfolio{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:12px}.request-kpi{padding:16px 16px 15px;position:relative;overflow:hidden;min-height:108px;display:flex;align-items:center;gap:13px}.request-kpi:before{content:"";position:absolute;inset-block:0;inset-inline-start:0;width:3px;background:var(--blue)}.request-kpi.danger:before{background:var(--red)}.request-kpi.amber:before{background:var(--amber)}.request-kpi.green:before{background:var(--green)}.request-kpi-icon{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;flex:0 0 auto;background:#edf4fd;color:#176cc1}.request-kpi.danger .request-kpi-icon{background:#fdebed;color:var(--red)}.request-kpi.amber .request-kpi-icon{background:#fff3d9;color:var(--amber)}.request-kpi.green .request-kpi-icon{background:#e7f7ee;color:var(--green)}.request-kpi-icon svg{width:21px;height:21px;stroke:currentColor;fill:none;stroke-width:1.8}.request-kpi-copy{min-width:0}.request-kpi span{font-size:9px;color:#52627a;font-weight:800}.request-kpi b{display:block;font-size:25px;line-height:1;margin:7px 0 5px;color:var(--ink)}.request-kpi small{display:block;font-size:8px;color:var(--muted);line-height:1.45}
    .request-buckets{display:flex;align-items:center;gap:8px;padding:8px;margin-bottom:12px;overflow:auto}.request-bucket{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:34px;padding:8px 14px;border-radius:8px;background:#f1f5fb;color:#52627a;font-size:9px;font-weight:850;white-space:nowrap}.request-bucket.active{background:var(--navy);color:#fff}.request-bucket em{font-style:normal;min-width:20px;height:20px;padding:0 6px;border-radius:10px;background:#fff;color:var(--navy);display:grid;place-items:center;font-size:8px}
    .request-filter{padding:14px 15px;margin-bottom:12px}.request-filters{display:grid;grid-template-columns:minmax(240px,1.7fr) repeat(5,minmax(120px,1fr)) auto;gap:10px;align-items:end}.request-field{display:grid;gap:6px;font-size:8px;font-weight:850;color:#5e6e85;text-transform:uppercase}.request-field input,.request-field select{height:40px;border:1px solid #d6dee8;border-radius:8px;padding:0 11px;background:#fff;color:var(--ink);outline:0}.request-field input:focus,.request-field select:focus{border-color:#8db8e4;box-shadow:0 0 0 3px #1475d112}.request-filter-actions{display:flex;gap:7px}.request-filter-actions .portal-btn{height:40px;min-width:72px}
    .request-attention{display:grid;grid-template-columns:210px 1fr auto;gap:12px;align-items:center;padding:12px 14px;margin-bottom:12px;border-color:#f3c6cc;background:linear-gradient(90deg,#fff7f8,#fff)}.request-attention-head{display:flex;align-items:center;gap:10px}.request-alert-icon{width:38px;height:38px;border-radius:50%;display:grid;place-items:center;background:#fff;border:1px solid #ef8090;color:var(--red);font-weight:900}.request-attention-head strong{display:block;font-size:11px;color:#a81429}.request-attention-head small{display:block;font-size:8px;color:#a75c68;margin-top:3px}.request-attention-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.attention-item{display:flex;align-items:center;gap:8px;padding:9px 11px;border:1px solid #f1dfe2;border-radius:9px;background:#fff;min-width:0}.attention-dot{width:22px;height:22px;border-radius:50%;display:grid;place-items:center;background:#fdebed;color:var(--red);font-size:10px;font-weight:900;flex:0 0 auto}.attention-copy{min-width:0;flex:1}.attention-copy b{display:block;font-size:8px;color:#0a4f99;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.attention-copy small{display:block;font-size:8px;color:#b42239;margin-top:3px}.attention-arrow{color:var(--navy);font-size:14px}.request-attention-link{font-size:9px;color:#0a4f99;font-weight:850;white-space:nowrap}
    .request-table-card{padding:0;overflow:hidden}.request-table-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:13px 14px;border-bottom:1px solid var(--line)}.request-table-title{display:flex;align-items:baseline;gap:10px}.request-table-title h3{font-size:13px;margin:0}.request-table-title span{font-size:9px;color:var(--muted)}.request-table-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.request-table-meta span{font-size:8px;color:var(--muted)}.request-row{cursor:pointer}.request-row:hover{background:#f8fbff}.request-link{font-weight:900;color:#0a4f99}.request-primary{min-width:220px;max-width:360px;white-space:normal!important}.request-primary b{display:block;font-size:10px;line-height:1.4}.request-primary small,.request-stage small{display:block;color:var(--muted);font-size:8px;margin-top:4px;line-height:1.4}.request-stage{min-width:190px;white-space:normal!important}.request-stage .portal-pill{margin-bottom:4px}.request-due{min-width:120px}.request-due strong{display:block;font-size:9px}.request-due small{display:block;font-size:8px;color:var(--muted);margin-top:4px}.request-view-btn{white-space:nowrap}.request-kebab{font-size:17px;color:#64748b;padding-inline-start:4px}.request-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 14px;font-size:10px;color:var(--muted);border-top:1px solid #edf0f4}.request-pages{display:flex;gap:6px}.request-pages a,.request-pages span{min-width:32px;height:32px;display:grid;place-items:center;border:1px solid var(--line);border-radius:7px;background:#fff}.request-pages .current{background:var(--navy);color:#fff;border-color:var(--navy)}
    @media(max-width:1450px){.request-portfolio{grid-template-columns:repeat(3,1fr)}.request-filters{grid-template-columns:repeat(3,1fr)}.request-attention{grid-template-columns:1fr}.request-attention-list{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:900px){.request-attention-list{grid-template-columns:1fr}.request-table-head{align-items:flex-start;flex-direction:column}}
    @media(max-width:760px){.request-portfolio{grid-template-columns:repeat(2,1fr)}.request-filters{grid-template-columns:1fr}.request-filter-actions{width:100%}.request-filter-actions .portal-btn{flex:1}.request-pagination{align-items:flex-start;flex-direction:column}.request-kpi{min-height:96px}}
    @media(max-width:480px){.request-portfolio{grid-template-columns:1fr}.request-kpi{min-height:auto}.portal-page-head .portal-btn{width:100%}}
</style>
@endpush

<div class="portal-page-head"><div><h2>{{ $ar ? 'طلبات الخدمة' : 'Service Requests' }}</h2><p>{{ $ar ? 'تابع كل طلبات العميل، الأولويات، المرحلة الحالية والخطوة التالية.' : 'Track every customer request, priority, current stage and next action.' }}</p></div><a class="portal-btn red" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">＋ {{ $ar ? 'إنشاء طلب خدمة' : 'New Service Request' }}</a></div>

<section class="request-portfolio" aria-label="{{ $ar?'ملخص محفظة الطلبات':'Request portfolio' }}">
    <div class="portal-card request-kpi"><div class="request-kpi-icon"><svg viewBox="0 0 24 24"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M10 12h6M10 16h6"/></svg></div><div class="request-kpi-copy"><span>{{ $ar?'طلبات مفتوحة':'Open Requests' }}</span><b>{{ $summary['open'] }}</b><small>{{ $ar?'ما زالت ضمن دورة التنفيذ':'Still in the delivery workflow' }}</small></div></div>
    <div class="portal-card request-kpi danger"><div class="request-kpi-icon"><svg viewBox="0 0 24 24"><path d="M12 3 2.8 19h18.4z"/><path d="M12 9v4M12 17h.01"/></svg></div><div class="request-kpi-copy"><span>{{ $ar?'طلبات طارئة':'Emergency Requests' }}</span><b>{{ $summary['emergency'] }}</b><small>{{ $ar?'أولوية استجابة فورية':'Immediate response priority' }}</small></div></div>
    <div class="portal-card request-kpi amber"><div class="request-kpi-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg></div><div class="request-kpi-copy"><span>{{ $ar?'متجاوزة الموعد':'Overdue Stages' }}</span><b>{{ $summary['overdue'] }}</b><small>{{ $ar?'تجاوزت موعد المرحلة الحالية':'Past current-stage due date' }}</small></div></div>
    <div class="portal-card request-kpi"><div class="request-kpi-icon"><svg viewBox="0 0 24 24"><path d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20c0-3 2-5 5-5s5 2 5 5M12 20c0-3 2-5 5-5s4 2 4 5"/></svg></div><div class="request-kpi-copy"><span>{{ $ar?'تنتظر العميل':'Awaiting Customer' }}</span><b>{{ $summary['awaiting_customer'] }}</b><small>{{ $ar?'تحتاج اعتمادًا أو متابعة':'Needs customer action or follow-up' }}</small></div></div>
    <div class="portal-card request-kpi"><div class="request-kpi-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.4-2.4 1a7 7 0 0 0-1.8-1L14.4 3h-4.8l-.3 3.1a7 7 0 0 0-1.8 1l-2.4-1-2 3.4L5.1 11a7 7 0 0 0 0 2l-2 1.5 2 3.4 2.4-1a7 7 0 0 0 1.8 1l.3 3.1h4.8l.3-3.1a7 7 0 0 0 1.8-1l2.4 1 2-3.4-2-1.5c.1-.3.1-.7.1-1z"/></svg></div><div class="request-kpi-copy"><span>{{ $ar?'قيد التنفيذ':'In Progress' }}</span><b>{{ $summary['in_progress'] }}</b><small>{{ $ar?'أعمال يجري تنفيذها':'Actively being worked' }}</small></div></div>
    <div class="portal-card request-kpi green"><div class="request-kpi-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16.5 9"/></svg></div><div class="request-kpi-copy"><span>{{ $ar?'المكتملة':'Completed' }}</span><b>{{ $summary['completed'] }}</b><small>{{ $ar?'طلبات تم إغلاقها':'Requests closed' }}</small></div></div>
</section>

<nav class="portal-card request-buckets" aria-label="{{ $ar?'حالة الطلبات':'Request status shortcuts' }}">@foreach($buckets as $key=>[$text,$count])<a class="request-bucket {{ (($filters['bucket']?:'all')===$key)?'active':'' }}" href="{{ route('customer.service-requests.index',['bucket'=>$key]) }}">{{ $text }} <em>{{ $count }}</em></a>@endforeach</nav>

<form class="portal-card request-filter" method="GET"><input type="hidden" name="bucket" value="{{ $filters['bucket'] }}"><div class="request-filters">
    <label class="request-field">{{ $ar?'بحث':'Search' }}<input name="q" value="{{ $filters['q'] }}" placeholder="{{ $ar?'رقم الطلب، الموضوع، الموقع أو الخدمة':'Request no, subject, site or service' }}"></label>
    <label class="request-field">{{ $ar?'النوع':'Type' }}<select name="type"><option value="">{{ $ar?'كل الأنواع':'All Types' }}</option>@foreach($types as $value)<option value="{{ $value }}" @selected($filters['type']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">{{ $ar?'الأولوية':'Priority' }}<select name="priority"><option value="">{{ $ar?'كل الأولويات':'All Priorities' }}</option>@foreach($priorities as $value)<option value="{{ $value }}" @selected($filters['priority']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">{{ $ar?'المرحلة':'Stage' }}<select name="stage"><option value="">{{ $ar?'كل المراحل':'All Stages' }}</option>@foreach($stages as $value)<option value="{{ $value }}" @selected($filters['stage']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">{{ $ar?'الحالة':'Status' }}<select name="status"><option value="">{{ $ar?'كل الحالات':'All Statuses' }}</option>@foreach($statuses as $value)<option value="{{ $value }}" @selected($filters['status']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">{{ $ar?'الموقع':'Site' }}<select name="site_id"><option value="">{{ $ar?'كل المواقع':'All Sites' }}</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected((int)$filters['site_id']===(int)$site->id)>{{ $site->name }}</option>@endforeach</select></label>
    <div class="request-filter-actions"><button class="portal-btn">{{ $ar?'تطبيق':'Apply' }}</button><a class="portal-btn soft" href="{{ route('customer.service-requests.index') }}">{{ $ar?'إعادة ضبط':'Reset' }}</a></div>
</div></form>

@if($attentionRequests->isNotEmpty())
<section class="portal-card request-attention" aria-label="{{ $ar?'طلبات تحتاج اهتمام':'Requests needing attention' }}">
    <div class="request-attention-head"><div class="request-alert-icon">!</div><div><strong>{{ $ar?'تحتاج اهتمام':'Needs Attention' }}</strong><small>{{ $ar?'طلبات عاجلة تحتاج متابعة':'Urgent requests require attention' }}</small></div></div>
    <div class="request-attention-list">
        @foreach($attentionRequests as $attention)
            @php $attentionUrl=route('customer.service-requests.show',$attention); $attentionOverdue=!in_array(strtoupper((string)$attention->status),['COMPLETED','CLOSED','CANCELLED','REJECTED'],true) && $attention->current_stage_due_at?->isPast(); @endphp
            <a class="attention-item" href="{{ $attentionUrl }}"><span class="attention-dot">!</span><span class="attention-copy"><b>{{ $attention->request_no }}</b><small>{{ $attentionOverdue ? ($ar?'متجاوز الموعد':'Overdue') : ($ar?'طلب طارئ':'Emergency') }}@if($attentionOverdue && $attention->current_stage_due_at) · {{ $attention->current_stage_due_at->diffForHumans() }}@endif</small></span><span class="attention-arrow">›</span></a>
        @endforeach
    </div>
    <a class="request-attention-link" href="{{ route('customer.service-requests.index',['bucket'=>'overdue']) }}">{{ $ar?'عرض كل الطلبات المتأخرة':'View all overdue requests' }} →</a>
</section>
@endif

<div class="portal-card request-table-card">
    <div class="request-table-head"><div class="request-table-title"><h3>{{ $ar?'طلبات الخدمة':'Service Requests' }} ({{ $requests->total() }})</h3><span>{{ $ar?'عرض':'Showing' }} {{ $requests->count() }} {{ $ar?'طلب':'requests' }}</span></div><div class="request-table-meta"><span>{{ $ar?'مرتبة حسب التجاوز ثم الأولوية':'Sorted by due date and priority' }}</span><span>{{ $ar?'صفحة':'Page' }} {{ $requests->currentPage() }} / {{ max(1,$requests->lastPage()) }}</span></div></div>
    <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>{{ $ar?'الطلب':'Request' }}</th><th>{{ $ar?'التفاصيل':'Details' }}</th><th>{{ $ar?'النوع':'Type' }}</th><th>{{ $ar?'الأولوية':'Priority' }}</th><th>{{ $ar?'المرحلة والخطوة التالية':'Current stage / next action' }}</th><th>{{ $ar?'الاستحقاق':'Due' }}</th><th>{{ $ar?'الحالة':'Status' }}</th><th>{{ $ar?'آخر تحديث':'Last update' }}</th><th>{{ $ar?'الإجراءات':'Actions' }}</th></tr></thead><tbody>
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
        <td><a class="portal-btn soft request-view-btn" href="{{ $url }}" onclick="event.stopPropagation()">◉ {{ $ar?'عرض 360':'View 360' }}</a><span class="request-kebab">⋮</span></td>
    </tr>
@empty<tr><td colspan="9"><div class="portal-empty"><strong>{{ $ar?'لا توجد طلبات مطابقة':'No matching service requests' }}</strong>{{ $ar?'جرّب إزالة فلتر أو أكثر.':'Try clearing one or more filters.' }}</div></td></tr>@endforelse
</tbody></table></div>
@if($requests->hasPages())<div class="request-pagination"><span>{{ $ar?'عرض':'Showing' }} {{ $requests->firstItem() }}–{{ $requests->lastItem() }} {{ $ar?'من':'of' }} {{ $requests->total() }}</span><div class="request-pages">@if($requests->onFirstPage())<span>‹</span>@else<a href="{{ $requests->previousPageUrl() }}">‹</a>@endif @foreach(range(max(1,$requests->currentPage()-2),min($requests->lastPage(),$requests->currentPage()+2)) as $page)@if($page===$requests->currentPage())<span class="current">{{ $page }}</span>@else<a href="{{ $requests->url($page) }}">{{ $page }}</a>@endif @endforeach @if($requests->hasMorePages())<a href="{{ $requests->nextPageUrl() }}">›</a>@else<span>›</span>@endif</div></div>@endif
</div>

@include('customer.partials.portal-shell-close')
