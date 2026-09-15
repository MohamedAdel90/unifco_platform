@include('customer.partials.portal-shell-open', [
    'activeSection' => 'requests',
    'pageTitle' => app()->getLocale()==='ar' ? 'طلبات الخدمة' : 'Service Requests',
    'pageDescription' => app()->getLocale()==='ar' ? 'جميع طلبات الشركة ومراحل تنفيذها.' : 'Every company request and its delivery stage.',
])

@push('late-styles')
<style>
    .request-filter{padding:14px;margin-bottom:12px}.request-filters{display:grid;grid-template-columns:minmax(230px,2fr) repeat(5,minmax(120px,1fr)) auto;gap:9px;align-items:end}.request-field{display:grid;gap:5px;font-size:8px;font-weight:800;color:#5e6e85}.request-field input,.request-field select{height:38px;border:1px solid #d6dee8;border-radius:8px;padding:0 10px;background:#fff;color:var(--ink)}.request-summary{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}.request-summary span{padding:7px 10px;border:1px solid var(--line);border-radius:8px;background:#fff;font-size:9px;color:var(--muted)}.request-table-card{padding:10px}.request-row{cursor:pointer}.request-row:hover{background:#f8fbff}.request-link{font-weight:900;color:#0a4f99}.request-subject{max-width:330px!important;white-space:normal!important;line-height:1.4}.request-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 4px 3px;font-size:10px;color:var(--muted)}.request-pages{display:flex;gap:6px}.request-pages a,.request-pages span{min-width:32px;height:32px;display:grid;place-items:center;border:1px solid var(--line);border-radius:7px;background:#fff}.request-pages .current{background:var(--navy);color:#fff;border-color:var(--navy)}@media(max-width:1260px){.request-filters{grid-template-columns:repeat(3,1fr)}}@media(max-width:680px){.request-filters{grid-template-columns:1fr}.request-pagination{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@php
    $labels = [
        'OPEN'=>'Open · مفتوح','CLOSED'=>'Closed · مغلق','COMPLETED'=>'Completed · مكتمل','CANCELLED'=>'Cancelled · ملغي',
        'TRIAGE'=>'Triage · فرز الطلب','SALES_REVIEW'=>'Sales Review · مراجعة المبيعات','TECHNICAL_REVIEW'=>'Technical Review · مراجعة فنية',
        'OPERATIONS_REVIEW'=>'Operations Review · مراجعة العمليات','EMERGENCY_DISPATCH'=>'Emergency Dispatch · توجيه طارئ',
        'ASSIGNED'=>'Assigned · تم الإسناد','IN_PROGRESS'=>'In Progress · جاري التنفيذ','CUSTOMER_ACCEPTANCE'=>'Customer Acceptance · اعتماد العميل',
        'MAINTENANCE'=>'Maintenance · صيانة','QUOTATION'=>'Quotation · عرض سعر','CONSULTATION'=>'Consultation · استشارة فنية',
        'NORMAL'=>'Normal · عادية','HIGH'=>'High · مرتفعة','EMERGENCY'=>'Emergency · طارئة',
    ];
    $label = fn($value) => $labels[strtoupper((string)$value)] ?? str_replace('_',' ',(string)$value ?: '—');
@endphp

<div class="portal-page-head"><div><h2>{{ app()->getLocale()==='ar' ? 'طلبات الخدمة' : 'Service Requests' }}</h2><p>{{ app()->getLocale()==='ar' ? 'ابحث وتابع كل طلبات العميل من الإنشاء حتى الإغلاق.' : 'Search and follow every customer request from creation to closure.' }}</p></div><a class="portal-btn red" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">＋ {{ app()->getLocale()==='ar' ? 'إنشاء طلب خدمة' : 'New Service Request' }}</a></div>

<form class="portal-card request-filter" method="GET"><div class="request-filters">
    <label class="request-field">SEARCH<input name="q" value="{{ $filters['q'] }}" placeholder="Request no, subject, site or service"></label>
    <label class="request-field">TYPE<select name="type"><option value="">All Types</option>@foreach($types as $value)<option value="{{ $value }}" @selected($filters['type']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">PRIORITY<select name="priority"><option value="">All Priorities</option>@foreach($priorities as $value)<option value="{{ $value }}" @selected($filters['priority']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">STAGE<select name="stage"><option value="">All Stages</option>@foreach($stages as $value)<option value="{{ $value }}" @selected($filters['stage']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">STATUS<select name="status"><option value="">All Statuses</option>@foreach($statuses as $value)<option value="{{ $value }}" @selected($filters['status']===$value)>{{ $label($value) }}</option>@endforeach</select></label>
    <label class="request-field">SITE<select name="site_id"><option value="">All Sites</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected((int)$filters['site_id']===(int)$site->id)>{{ $site->name }}</option>@endforeach</select></label>
    <div style="display:flex;gap:7px"><button class="portal-btn">Apply</button><a class="portal-btn soft" href="{{ route('customer.service-requests.index') }}">Reset</a></div>
</div></form>

<div class="request-summary"><span><b>{{ $requests->total() }}</b> matching requests</span><span>Page {{ $requests->currentPage() }} of {{ max(1,$requests->lastPage()) }}</span><span>Click any row to open Request 360</span></div>
<div class="portal-card request-table-card"><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Request</th><th>Type</th><th>Subject</th><th>Priority</th><th>Stage</th><th>Status</th><th>Created</th><th>Last Update</th><th></th></tr></thead><tbody>
@forelse($requests as $requestItem)@php($url=route('customer.service-requests.show',$requestItem))<tr class="request-row" onclick="window.location.href='{{ $url }}'"><td><a class="request-link" href="{{ $url }}" onclick="event.stopPropagation()">{{ $requestItem->request_no }}</a></td><td><span class="portal-pill">{{ $label($requestItem->request_type) }}</span></td><td class="request-subject">{{ $requestItem->subject }}</td><td><span class="portal-pill {{ $requestItem->priority==='EMERGENCY'?'red':($requestItem->priority==='HIGH'?'amber':'') }}">{{ $label($requestItem->priority) }}</span></td><td><span class="portal-pill">{{ $label($requestItem->workflow_stage) }}</span></td><td><span class="portal-pill {{ $requestItem->status==='OPEN'?'green':($requestItem->status==='CLOSED'?'':'amber') }}">{{ $label($requestItem->status) }}</span></td><td>{{ $requestItem->created_at?->format('Y-m-d H:i') }}</td><td>{{ $requestItem->updated_at?->format('Y-m-d H:i') }}</td><td><a class="portal-btn soft" href="{{ $url }}" onclick="event.stopPropagation()">View Details · 360</a></td></tr>
@empty<tr><td colspan="9"><div class="portal-empty"><strong>No matching service requests</strong>Try clearing one or more filters.</div></td></tr>@endforelse
</tbody></table></div>
@if($requests->hasPages())<div class="request-pagination"><span>Showing {{ $requests->firstItem() }}–{{ $requests->lastItem() }} of {{ $requests->total() }}</span><div class="request-pages">@if($requests->onFirstPage())<span>‹</span>@else<a href="{{ $requests->previousPageUrl() }}">‹</a>@endif @foreach(range(max(1,$requests->currentPage()-2),min($requests->lastPage(),$requests->currentPage()+2)) as $page)@if($page===$requests->currentPage())<span class="current">{{ $page }}</span>@else<a href="{{ $requests->url($page) }}">{{ $page }}</a>@endif @endforeach @if($requests->hasMorePages())<a href="{{ $requests->nextPageUrl() }}">›</a>@else<span>›</span>@endif</div></div>@endif
</div>

@include('customer.partials.portal-shell-close')
