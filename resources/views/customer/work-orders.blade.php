@php
    $ar = app()->getLocale() === 'ar';
    $visibleOrders = $workOrders;
    $completedMonth = $visibleOrders->filter(fn($wo) => in_array(strtoupper((string)$wo->status), ['COMPLETED','CLOSED'], true) && optional($wo->updated_at)->format('Y-m') === now()->format('Y-m'))->count();
    $overdueVisible = $visibleOrders->filter(fn($wo) => $wo->planned_start && $wo->planned_start->isPast() && !in_array(strtoupper((string)$wo->status), ['COMPLETED','CLOSED','CANCELLED'], true))->count();
    $inProgressVisible = $visibleOrders->filter(fn($wo) => strtoupper((string)$wo->status) === 'IN_PROGRESS')->count();
@endphp

@include('customer.partials.portal-shell-open', [
    'activeSection' => 'work-orders',
    'pageTitle' => $ar ? 'أوامر العمل' : 'Work Orders',
    'pageDescription' => $ar ? 'متابعة أوامر التنفيذ عبر مواقع وأصول العميل.' : 'Track execution records across your customer sites and assets.',
])

@push('late-styles')
<style>
    .portal-content{max-width:none;margin:0;padding-top:24px}.wo-head{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:18px}.wo-head h2{font-size:30px;line-height:1;margin:0 0 7px;letter-spacing:-.03em}.wo-head p{font-size:11px;color:var(--muted);margin:0}.wo-request{min-width:162px;height:44px;background:var(--red);font-size:11px;border-radius:9px}.wo-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:16px}.wo-kpi{min-height:98px;padding:18px;display:grid;grid-template-columns:48px 1fr 22px;gap:14px;align-items:center}.wo-kpi-icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#edf5ff;color:var(--blue)}.wo-kpi-icon .ui-icon{width:25px;height:25px}.wo-kpi:nth-child(2) .wo-kpi-icon{background:#eef5ff;color:#2876df}.wo-kpi:nth-child(3) .wo-kpi-icon{background:#fff0f2;color:var(--red)}.wo-kpi:nth-child(4) .wo-kpi-icon{background:#eaf8f1;color:var(--green)}.wo-kpi small{display:block;font-size:10px;color:#53657c;font-weight:700}.wo-kpi b{display:block;font-size:28px;line-height:1;margin:7px 0 4px}.wo-kpi span{font-size:9px;color:var(--muted)}.wo-kpi-arrow{color:var(--navy);font-size:20px}.wo-filters{padding:16px 18px;margin-bottom:14px;display:grid;grid-template-columns:minmax(230px,1.5fr) repeat(4,minmax(130px,.8fr)) minmax(130px,.8fr) auto auto;gap:12px;align-items:end}.wo-field{display:grid;gap:7px}.wo-field label{font-size:8px;font-weight:850;color:#53657c}.wo-field input,.wo-field select{height:40px;border:1px solid #d9e3ee;border-radius:8px;background:#fff;padding:0 11px;color:var(--ink);font-size:10px;outline:none}.wo-field input:focus,.wo-field select:focus{border-color:#8eb7e3;box-shadow:0 0 0 3px #1475d11a}.wo-apply,.wo-reset{height:40px;padding:0 18px;border-radius:8px;font-size:10px;font-weight:850}.wo-apply{border:0;background:var(--navy);color:#fff}.wo-reset{display:grid;place-items:center;background:#edf3fb;color:var(--navy)}.wo-tabs{display:flex;align-items:center;gap:8px;padding:0 18px;border-bottom:1px solid #e7edf4;background:#fff}.wo-tab{position:relative;display:inline-flex;align-items:center;gap:7px;padding:15px 9px 13px;color:#334b6c;font-size:10px;font-weight:800;cursor:pointer}.wo-tab.active{color:var(--blue)}.wo-tab.active:after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:var(--blue)}.wo-tab em{font-style:normal;min-width:20px;height:20px;padding:0 6px;border-radius:10px;background:#edf3fb;display:grid;place-items:center;font-size:8px}.wo-tab.alert em{background:#fdebed;color:var(--red)}.wo-table-card{padding:0;overflow:hidden}.wo-table-wrap{overflow:auto}.wo-table{width:100%;border-collapse:collapse;font-size:10px}.wo-table thead{background:#f7faff;position:sticky;top:0}.wo-table th,.wo-table td{padding:14px 14px;text-align:start;border-bottom:1px solid #edf1f5;white-space:nowrap}.wo-table th{font-size:8px;color:#5e6f86;text-transform:uppercase;letter-spacing:.05em}.wo-table tbody tr{transition:.15s}.wo-table tbody tr:hover{background:#f8fbff}.wo-link{color:#0878d1;font-weight:850;text-decoration:underline}.wo-badge{display:inline-flex;align-items:center;gap:5px;min-height:24px;padding:4px 9px;border-radius:999px;font-size:8px;font-weight:850;background:#edf3fb;color:#52667e}.wo-badge.priority-emergency,.wo-badge.priority-critical{background:#fdebed;color:#c82036}.wo-badge.priority-high{background:#fff1df;color:#bc6900}.wo-badge.status-open{background:#e9f3ff;color:#1670cf}.wo-badge.status-in_progress{background:#e7f1ff;color:#0d68d5}.wo-badge.status-completed,.wo-badge.status-closed{background:#e8f8f0;color:#138356}.wo-badge.status-assigned,.wo-badge.status-scheduled{background:#f0ebff;color:#6f42c1}.wo-badge.status-on_hold{background:#fff3d9;color:#946200}.wo-overdue{margin-inline-start:7px;background:#fdebed;color:#c82036}.wo-today{margin-inline-start:7px;background:#fff1df;color:#bc6900}.wo-action{width:30px;height:30px;border:1px solid #dbe5f0;border-radius:7px;display:grid;place-items:center;background:#fff;color:var(--navy);font-weight:900}.wo-foot{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;color:#65758a;font-size:9px}.wo-pager{display:flex;gap:6px}.wo-page{width:32px;height:32px;border-radius:8px;background:#f1f5fa;display:grid;place-items:center}.wo-page.active{background:#eaf3ff;color:var(--blue);font-weight:850}.wo-cta{margin-top:16px;padding:17px 18px;display:flex;align-items:center;justify-content:space-between;gap:16px}.wo-cta-main{display:flex;align-items:center;gap:14px}.wo-cta-icon{width:48px;height:48px;border-radius:12px;background:#eaf3ff;color:var(--blue);display:grid;place-items:center}.wo-cta-icon .ui-icon{width:25px;height:25px}.wo-cta h3{margin:0 0 5px;font-size:14px}.wo-cta p{margin:0;color:var(--muted);font-size:9px}
    @media(max-width:1350px){.wo-filters{grid-template-columns:repeat(4,minmax(140px,1fr))}.wo-field.search{grid-column:span 2}.wo-kpis{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:850px){.wo-head{align-items:flex-start;flex-direction:column}.wo-kpis{grid-template-columns:1fr}.wo-filters{grid-template-columns:1fr 1fr}.wo-field.search{grid-column:1/-1}.wo-cta{align-items:flex-start;flex-direction:column}.wo-tabs{overflow:auto}.wo-head h2{font-size:25px}}
    @media(max-width:560px){.wo-filters{grid-template-columns:1fr}.wo-field.search{grid-column:auto}.wo-kpi{grid-template-columns:44px 1fr 18px}.wo-table th,.wo-table td{padding:11px 10px}}
</style>
@endpush

<div class="wo-head">
    <div><h2>{{ $ar ? 'أوامر العمل' : 'Work Orders' }}</h2><p>{{ $ar ? 'متابعة أوامر التنفيذ عبر مواقع وأصول العميل.' : 'Track execution records across your customer sites and assets.' }}</p></div>
    <a class="portal-btn red wo-request" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">+ {{ $ar ? 'طلب خدمة' : 'Request Service' }}</a>
</div>

<section class="wo-kpis">
    <div class="portal-card wo-kpi"><div class="wo-kpi-icon">@include('customer.partials.icon',['name'=>'requests'])</div><div><small>{{ $ar ? 'أوامر العمل المفتوحة' : 'Open Work Orders' }}</small><b>{{ $openWorkOrders }}</b><span>{{ $ar ? 'تحتاج المتابعة' : 'Requiring attention' }}</span></div><div class="wo-kpi-arrow">›</div></div>
    <div class="portal-card wo-kpi"><div class="wo-kpi-icon">@include('customer.partials.icon',['name'=>'work-orders'])</div><div><small>{{ $ar ? 'قيد التنفيذ' : 'In Progress' }}</small><b>{{ $inProgressCount ?? $inProgressVisible }}</b><span>{{ $ar ? 'يتم تنفيذها حاليًا' : 'Currently being executed' }}</span></div><div class="wo-kpi-arrow">›</div></div>
    <div class="portal-card wo-kpi"><div class="wo-kpi-icon">@include('customer.partials.icon',['name'=>'actions'])</div><div><small>{{ $ar ? 'متأخرة' : 'Overdue' }}</small><b>{{ $overdueCount ?? $overdueVisible }}</b><span>{{ $ar ? 'تجاوزت التاريخ المخطط' : 'Past planned date' }}</span></div><div class="wo-kpi-arrow">›</div></div>
    <div class="portal-card wo-kpi"><div class="wo-kpi-icon">✓</div><div><small>{{ $ar ? 'مكتملة هذا الشهر' : 'Completed This Month' }}</small><b>{{ $completedMonth }}</b><span>{{ $ar ? 'أغلقت بنجاح' : 'Successfully closed' }}</span></div><div class="wo-kpi-arrow">›</div></div>
</section>

<form class="portal-card wo-filters" method="GET" action="{{ route('customer.section','work-orders') }}">
    <div class="wo-field search"><label>{{ $ar ? 'بحث' : 'Search' }}</label><input name="q" value="{{ $searchFilter }}" placeholder="{{ $ar ? 'رقم أمر العمل أو الأصل أو كلمة مفتاحية...' : 'Search work order, asset, or keyword...' }}"></div>
    <div class="wo-field"><label>{{ $ar ? 'الموقع' : 'Site' }}</label><select name="site_id"><option value="">{{ $ar ? 'كل المواقع' : 'All sites' }}</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected($siteFilter===$site->id)>{{ $site->name }}</option>@endforeach</select></div>
    <div class="wo-field"><label>{{ $ar ? 'العقد' : 'Contract' }}</label><select name="contract_id"><option value="">{{ $ar ? 'كل العقود' : 'All contracts' }}</option>@foreach($contracts as $contract)<option value="{{ $contract->id }}" @selected($contractFilter===$contract->id)>{{ $contract->contract_no }}</option>@endforeach</select></div>
    <div class="wo-field"><label>{{ $ar ? 'الأولوية' : 'Priority' }}</label><select name="priority"><option value="">{{ $ar ? 'كل الأولويات' : 'All priorities' }}</option>@foreach(['NORMAL','HIGH','EMERGENCY'] as $value)<option value="{{ $value }}" @selected($priorityFilter===$value)>{{ str_replace('_',' ',$value) }}</option>@endforeach</select></div>
    <div class="wo-field"><label>{{ $ar ? 'الحالة' : 'Status' }}</label><select name="status"><option value="">{{ $ar ? 'كل الحالات' : 'All statuses' }}</option>@foreach(['OPEN','ASSIGNED','IN_PROGRESS','COMPLETED','CLOSED'] as $value)<option value="{{ $value }}" @selected($statusFilter===$value)>{{ str_replace('_',' ',$value) }}</option>@endforeach</select></div>
    <div class="wo-field"><label>{{ $ar ? 'الترتيب' : 'Sort By' }}</label><select disabled><option>{{ $ar ? 'التاريخ المخطط' : 'Planned date' }}</option></select></div>
    <button class="wo-apply">{{ $ar ? 'تطبيق' : 'Apply' }}</button><a class="wo-reset" href="{{ route('customer.section','work-orders') }}">{{ $ar ? 'إعادة' : 'Reset' }}</a>
</form>

<div class="portal-card wo-table-card">
    <div class="wo-tabs" role="tablist">
        <button type="button" class="wo-tab active" data-filter="all">{{ $ar ? 'الكل' : 'All' }} <em>{{ $visibleOrders->count() }}</em></button>
        <button type="button" class="wo-tab" data-filter="open">{{ $ar ? 'مفتوحة' : 'Open' }} <em>{{ $visibleOrders->filter(fn($wo)=>strtoupper((string)$wo->status)==='OPEN')->count() }}</em></button>
        <button type="button" class="wo-tab" data-filter="in_progress">{{ $ar ? 'قيد التنفيذ' : 'In Progress' }} <em>{{ $inProgressVisible }}</em></button>
        <button type="button" class="wo-tab alert" data-filter="overdue">{{ $ar ? 'متأخرة' : 'Overdue' }} <em>{{ $overdueVisible }}</em></button>
        <button type="button" class="wo-tab" data-filter="completed">{{ $ar ? 'مكتملة' : 'Completed' }} <em>{{ $visibleOrders->filter(fn($wo)=>in_array(strtoupper((string)$wo->status),['COMPLETED','CLOSED'],true))->count() }}</em></button>
    </div>
    <div class="wo-table-wrap"><table class="wo-table"><thead><tr><th>{{ $ar ? 'أمر العمل' : 'Work Order' }}</th><th>{{ $ar ? 'الأصل' : 'Asset' }}</th><th>{{ $ar ? 'الموقع' : 'Site' }}</th><th>{{ $ar ? 'النوع' : 'Type' }}</th><th>{{ $ar ? 'الأولوية' : 'Priority' }}</th><th>{{ $ar ? 'الحالة' : 'Status' }}</th><th>{{ $ar ? 'المخطط' : 'Planned' }}</th><th>{{ $ar ? 'إجراء' : 'Action' }}</th></tr></thead><tbody>
    @forelse($visibleOrders as $item)
        @php
            $status = strtolower(strtoupper((string)$item->status));
            $priority = strtolower(strtoupper((string)$item->priority));
            $isClosed = in_array(strtoupper((string)$item->status), ['COMPLETED','CLOSED','CANCELLED'], true);
            $isOverdue = $item->planned_start && $item->planned_start->isPast() && !$isClosed;
            $lateDays = $isOverdue ? max(1,(int)ceil($item->planned_start->diffInDays(now()))) : 0;
            $isToday = $item->planned_start && $item->planned_start->isToday();
            $rowFilter = $isOverdue ? ' overdue' : '';
            $rowFilter .= ' '.strtolower(strtoupper((string)$item->status));
            if($isClosed) $rowFilter .= ' completed';
        @endphp
        <tr data-filter="{{ trim($rowFilter) }}"><td><a class="wo-link" href="{{ route('customer.work-orders.show',$item) }}">{{ $item->work_order_no }}</a></td><td>{{ $item->asset?->asset_code }}{{ $item->asset?->name ? ' — '.$item->asset->name : '' }}</td><td>{{ $item->asset?->site?->name ?: '—' }}</td><td>{{ ucfirst(strtolower(str_replace('_',' ',(string)$item->maintenance_type))) }}</td><td><span class="wo-badge priority-{{ $priority }}">{{ ucfirst(strtolower((string)$item->priority)) }}</span></td><td><span class="wo-badge status-{{ $status }}">{{ ucwords(strtolower(str_replace('_',' ',(string)$item->status))) }}</span></td><td>{{ $item->planned_start?->format('Y-m-d H:i') ?: '—' }} @if($lateDays)<span class="wo-badge wo-overdue">{{ $lateDays }}d overdue</span>@elseif($isToday)<span class="wo-badge wo-today">Today</span>@endif</td><td><a class="wo-action" href="{{ route('customer.work-orders.show',$item) }}" aria-label="View">•••</a></td></tr>
    @empty<tr><td colspan="8"><div class="portal-empty"><strong>{{ $ar ? 'لا توجد أوامر عمل' : 'No work orders found' }}</strong>{{ $ar ? 'لا توجد نتائج ضمن النطاق الحالي.' : 'No work orders match the current scope.' }}</div></td></tr>@endforelse
    </tbody></table></div>
    <div class="wo-foot"><span>{{ $ar ? 'عرض' : 'Showing' }} <strong id="wo-visible-count">{{ $visibleOrders->count() }}</strong> {{ $ar ? 'من' : 'of' }} {{ $visibleOrders->count() }} {{ $ar ? 'أوامر عمل' : 'work orders' }}</span><div class="wo-pager"><span class="wo-page">‹</span><span class="wo-page active">1</span><span class="wo-page">›</span></div></div>
</div>

<div class="portal-card wo-cta"><div class="wo-cta-main"><div class="wo-cta-icon">@include('customer.partials.icon',['name'=>'maintenance'])</div><div><h3>{{ $ar ? 'تحتاج خدمة أخرى؟' : 'Need another service?' }}</h3><p>{{ $ar ? 'استخدم نموذج طلب الخدمة الموحد للصيانة والطوارئ وعروض الأسعار وقطع الغيار والاستشارة الفنية.' : 'Use the approved service request form for maintenance, emergencies, quotations, spare parts and technical consultation.' }}</p></div></div><a class="portal-btn red wo-request" href="{{ route('public.request-service',['customer'=>$customer->customer_code]) }}">+ {{ $ar ? 'طلب خدمة' : 'Request Service' }}</a></div>

@push('scripts')
<script>
(()=>{const tabs=[...document.querySelectorAll('.wo-tab')],rows=[...document.querySelectorAll('.wo-table tbody tr[data-filter]')],count=document.getElementById('wo-visible-count');if(!tabs.length)return;tabs.forEach(tab=>tab.addEventListener('click',()=>{const filter=tab.dataset.filter;tabs.forEach(t=>t.classList.toggle('active',t===tab));let visible=0;rows.forEach(row=>{const show=filter==='all'||row.dataset.filter.split(' ').includes(filter);row.style.display=show?'':'none';if(show)visible++});if(count)count.textContent=visible}))})();
</script>
@endpush

@include('customer.partials.portal-shell-close')
