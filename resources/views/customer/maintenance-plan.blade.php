@php
    $ar = app()->getLocale()==='ar';
    $planCollection = collect($plans ?? []);
    $assetCollection = collect($assets ?? []);
    $today = now()->startOfDay();
    $monthEnd = now()->copy()->endOfMonth()->endOfDay();
    $overduePlans = $planCollection->filter(fn($plan) => $plan->next_due_date && $plan->next_due_date->copy()->startOfDay()->lt($today));
    $dueThisMonth = $planCollection->filter(fn($plan) => $plan->next_due_date && $plan->next_due_date->between($today, $monthEnd));
    $upcoming = $planCollection->filter(fn($plan) => $plan->next_due_date && $plan->next_due_date->gte($today))->sortBy('next_due_date');
    $coveredAssetIds = $planCollection->pluck('asset_id')->filter()->unique();
    $coveredAssets = $coveredAssetIds->count();
    $assetTotal = $assetCollection->count();
    $coverage = $assetTotal > 0 ? (int) round(($coveredAssets / $assetTotal) * 100) : 0;
    $uncoveredAssets = max(0, $assetTotal - $coveredAssets);
@endphp

@include('customer.partials.portal-shell-open', [
    'activeSection' => 'maintenance',
    'pageTitle' => $ar ? 'خطة الصيانة' : 'Maintenance Plan',
    'pageDescription' => $ar ? 'خطط الصيانة الوقائية لجميع أصول العميل.' : 'Preventive plans for all customer assets.',
])

@push('late-styles')
<style>
    .portal-content{max-width:none}
    .maintenance-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:16px}.maintenance-head h2{font-size:26px;margin:0 0 5px}.maintenance-head p{font-size:10px;color:var(--muted);margin:0}
    .maintenance-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:12px}.maintenance-kpi{padding:16px;display:flex;align-items:center;gap:13px;min-height:104px}.maintenance-kpi-icon{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;background:#edf4fd;color:var(--blue);font-size:20px;font-weight:900;flex:0 0 auto}.maintenance-kpi.red .maintenance-kpi-icon{background:#fdebed;color:var(--red)}.maintenance-kpi.green .maintenance-kpi-icon{background:#e7f7ee;color:var(--green)}.maintenance-kpi-copy span{display:block;font-size:9px;font-weight:850;color:#52627a}.maintenance-kpi-copy b{display:block;font-size:26px;line-height:1;margin:7px 0 5px}.maintenance-kpi-copy small{display:block;font-size:8px;color:var(--muted);line-height:1.45}
    .maintenance-status{display:flex;gap:8px;align-items:center;overflow:auto;padding:8px;margin-bottom:12px}.maintenance-chip{display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border-radius:8px;background:#f1f5fb;color:#52627a;font-size:9px;font-weight:850;white-space:nowrap}.maintenance-chip.active{background:var(--navy);color:#fff}.maintenance-chip em{font-style:normal;min-width:20px;height:20px;padding:0 6px;border-radius:10px;background:#fff;color:var(--navy);display:grid;place-items:center;font-size:8px}
    .maintenance-grid{display:grid;grid-template-columns:minmax(0,1.75fr) minmax(300px,.75fr);gap:12px;margin-bottom:12px}.maintenance-panel{padding:16px}.maintenance-panel-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:13px}.maintenance-panel-head h3{font-size:13px;margin:0}.maintenance-panel-head span{font-size:8px;color:var(--muted)}
    .upcoming-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.upcoming-card{padding:13px;border:1px solid #e5ebf2;border-radius:10px;background:#fff;min-height:142px}.upcoming-card-top{display:flex;justify-content:space-between;gap:8px;align-items:flex-start}.upcoming-card strong{font-size:11px}.upcoming-card small{display:block;color:var(--muted);font-size:8px;margin-top:4px}.upcoming-meta{display:grid;gap:7px;margin-top:14px}.upcoming-meta span{font-size:9px;color:#40546c}.upcoming-meta b{color:var(--ink)}
    .coverage{display:grid;grid-template-columns:130px 1fr;gap:18px;align-items:center}.coverage-ring{width:118px;height:118px;border-radius:50%;background:conic-gradient(var(--navy) 0 calc(var(--coverage)*1%),#e6ebf2 calc(var(--coverage)*1%) 100%);display:grid;place-items:center;position:relative}.coverage-ring:after{content:"";position:absolute;inset:17px;background:#fff;border-radius:50%}.coverage-ring div{position:relative;z-index:1;text-align:center}.coverage-ring b{display:block;font-size:24px}.coverage-ring span{font-size:8px;color:var(--muted)}.coverage-copy strong{font-size:12px;display:block;margin-bottom:10px}.coverage-bar{height:9px;border-radius:999px;background:#e6ebf2;overflow:hidden;margin-bottom:12px}.coverage-bar i{display:block;height:100%;width:calc(var(--coverage)*1%);background:var(--navy);border-radius:inherit}.coverage-legend{display:grid;gap:8px}.coverage-legend span{display:flex;justify-content:space-between;gap:12px;font-size:9px;color:#52627a}.coverage-note{margin-top:13px;padding:10px;border-radius:8px;background:#edf4fd;color:#355f8f;font-size:8px;line-height:1.5}
    .maintenance-table-card{padding:0;overflow:hidden}.maintenance-table-head{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:13px 14px;border-bottom:1px solid var(--line)}.maintenance-table-head h3{font-size:13px;margin:0}.maintenance-table-head p{font-size:8px;color:var(--muted);margin:3px 0 0}.maintenance-search{height:36px;min-width:260px;border:1px solid var(--line);border-radius:8px;padding:0 10px;font-size:9px;background:#fff}.maintenance-table-wrap{overflow:auto}.maintenance-table{width:100%;border-collapse:collapse;font-size:10px}.maintenance-table th,.maintenance-table td{padding:11px 10px;text-align:start;border-bottom:1px solid #edf0f4;white-space:nowrap}.maintenance-table th{color:#68758a;font-size:8px;text-transform:uppercase;letter-spacing:.04em}.maintenance-plan-name{font-weight:850;color:var(--ink)}.maintenance-empty{padding:44px 24px;text-align:center}.maintenance-empty-icon{width:52px;height:52px;border-radius:15px;background:#edf4fd;color:var(--blue);display:grid;place-items:center;margin:0 auto 12px;font-size:22px}.maintenance-empty strong{display:block;font-size:13px;margin-bottom:6px}.maintenance-empty p{margin:0 auto;max-width:430px;font-size:9px;line-height:1.7;color:var(--muted)}
    @media(max-width:1250px){.maintenance-kpis{grid-template-columns:repeat(2,1fr)}.maintenance-grid{grid-template-columns:1fr}.upcoming-cards{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:820px){.upcoming-cards{grid-template-columns:1fr}.coverage{grid-template-columns:1fr}.coverage-ring{margin:auto}.maintenance-table-head{align-items:flex-start;flex-direction:column}.maintenance-search{width:100%;min-width:0}}
    @media(max-width:600px){.maintenance-kpis{grid-template-columns:1fr}.maintenance-head{align-items:flex-start;flex-direction:column}}
</style>
@endpush

<div class="maintenance-head">
    <div><h2>{{ $ar ? 'خطة الصيانة' : 'Maintenance Plan' }}</h2><p>{{ $ar ? 'خطط الصيانة الوقائية ومواعيد الاستحقاق لجميع الأصول ضمن نطاقك.' : 'Preventive maintenance plans and due dates for all assets in your scope.' }}</p></div>
    <a class="portal-btn" href="{{ route('customer.section','visits') }}">▣ {{ $ar ? 'عرض الجدول' : 'View Schedule' }}</a>
</div>

<section class="maintenance-kpis">
    <div class="portal-card maintenance-kpi"><div class="maintenance-kpi-icon">▣</div><div class="maintenance-kpi-copy"><span>{{ $ar?'الزيارات المخططة':'Planned Visits' }}</span><b>{{ $planCollection->count() }}</b><small>{{ $ar?'إجمالي خطط الصيانة الوقائية':'Total preventive maintenance plans' }}</small></div></div>
    <div class="portal-card maintenance-kpi"><div class="maintenance-kpi-icon">◷</div><div class="maintenance-kpi-copy"><span>{{ $ar?'مستحق هذا الشهر':'Due This Month' }}</span><b>{{ $dueThisMonth->count() }}</b><small>{{ $ar?'خطط تستحق قبل نهاية الشهر':'Plans due before month end' }}</small></div></div>
    <div class="portal-card maintenance-kpi red"><div class="maintenance-kpi-icon">!</div><div class="maintenance-kpi-copy"><span>{{ $ar?'صيانة متأخرة':'Overdue PM' }}</span><b>{{ $overduePlans->count() }}</b><small>{{ $ar?'تحتاج متابعة فورية':'Requires immediate attention' }}</small></div></div>
    <div class="portal-card maintenance-kpi green"><div class="maintenance-kpi-icon">◇</div><div class="maintenance-kpi-copy"><span>{{ $ar?'الأصول المشمولة':'Assets Covered' }}</span><b>{{ $coveredAssets }}</b><small>{{ $ar?'من أصل '.$assetTotal.' أصل':'of '.$assetTotal.' total assets' }}</small></div></div>
</section>

<div class="portal-card maintenance-status">
    <span class="maintenance-chip active">{{ $ar?'كل الخطط':'All Plans' }} <em>{{ $planCollection->count() }}</em></span>
    <span class="maintenance-chip">{{ $ar?'قادمة':'Upcoming' }} <em>{{ $upcoming->count() }}</em></span>
    <span class="maintenance-chip">{{ $ar?'هذا الشهر':'Due This Month' }} <em>{{ $dueThisMonth->count() }}</em></span>
    <span class="maintenance-chip">{{ $ar?'متأخرة':'Overdue' }} <em>{{ $overduePlans->count() }}</em></span>
</div>

<section class="maintenance-grid">
    <div class="portal-card maintenance-panel">
        <div class="maintenance-panel-head"><div><h3>{{ $ar?'الصيانة الوقائية القادمة':'Upcoming Preventive Maintenance' }}</h3><span>{{ $ar?'أقرب مواعيد الصيانة المجدولة':'Next scheduled maintenance dates' }}</span></div><a class="portal-btn soft" href="{{ route('customer.section','visits') }}">{{ $ar?'عرض الكل':'View All' }} →</a></div>
        @if($upcoming->isNotEmpty())
            <div class="upcoming-cards">
                @foreach($upcoming->take(3) as $plan)
                    @php($dueSoon = $plan->next_due_date && $plan->next_due_date->lte(now()->copy()->addDays(14)))
                    <div class="upcoming-card">
                        <div class="upcoming-card-top"><div><strong>{{ $plan->asset?->asset_code ?: ($plan->plan_no ?: 'PM') }}</strong><small>{{ $plan->asset?->name ?: $plan->name }}</small></div><span class="portal-pill {{ $dueSoon?'amber':'green' }}">{{ $dueSoon?($ar?'قريب':'Due Soon'):($ar?'مجدول':'Scheduled') }}</span></div>
                        <div class="upcoming-meta"><span>⌖ <b>{{ $plan->asset?->site?->name ?: ($ar?'لا يوجد موقع':'No site') }}</b></span><span>▣ <b>{{ $plan->next_due_date?->format('d M Y') ?: '—' }}</b></span><span>↻ <b>{{ trim(($plan->frequency_type ?: '').' '.($plan->frequency_value ?: '')) ?: '—' }}</b></span></div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="maintenance-empty"><div class="maintenance-empty-icon">▣</div><strong>{{ $ar?'لا توجد صيانة وقائية مجدولة':'No preventive maintenance scheduled' }}</strong><p>{{ $ar?'ستظهر هنا أقرب زيارات الصيانة بمجرد ربط الأصول بخطة صيانة وقائية.' : 'Upcoming maintenance visits will appear here once assets are linked to preventive maintenance plans.' }}</p></div>
        @endif
    </div>

    <div class="portal-card maintenance-panel">
        <div class="maintenance-panel-head"><div><h3>{{ $ar?'نظرة على التغطية':'Coverage Overview' }}</h3><span>{{ $ar?'تغطية الأصول بخطط الصيانة':'Asset preventive-maintenance coverage' }}</span></div></div>
        <div class="coverage" style="--coverage:{{ $coverage }}">
            <div class="coverage-ring"><div><b>{{ $coverage }}%</b><span>{{ $ar?'مغطى':'Covered' }}</span></div></div>
            <div class="coverage-copy"><strong>{{ $coveredAssets }} {{ $ar?'من':'of' }} {{ $assetTotal }} {{ $ar?'أصل مشمول':'assets covered' }}</strong><div class="coverage-bar"><i></i></div><div class="coverage-legend"><span><span>{{ $ar?'أصول لديها خطة PM':'Assets with PM plan' }}</span><b>{{ $coveredAssets }}</b></span><span><span>{{ $ar?'أصول بدون خطة':'Assets without plan' }}</span><b>{{ $uncoveredAssets }}</b></span></div>@if($uncoveredAssets>0)<div class="coverage-note">ⓘ {{ $ar?$uncoveredAssets.' أصل غير مرتبط بخطة صيانة وقائية حتى الآن.':$uncoveredAssets.' assets do not have a preventive maintenance plan yet.' }}</div>@endif</div>
        </div>
    </div>
</section>

<section class="portal-card maintenance-table-card">
    <div class="maintenance-table-head"><div><h3>{{ $ar?'خطط الصيانة':'Maintenance Plans' }}</h3><p>{{ $ar?'جميع خطط الصيانة الوقائية للأصول ضمن نطاق العميل.':'All preventive maintenance plans for assets in your scope.' }}</p></div><input class="maintenance-search" type="search" placeholder="{{ $ar?'بحث في الخطط أو الأصول أو المواقع...':'Search plans, assets or sites...' }}" oninput="const q=this.value.toLowerCase();document.querySelectorAll('[data-maint-row]').forEach(r=>r.hidden=!r.innerText.toLowerCase().includes(q))"></div>
    @if($planCollection->isNotEmpty())
        <div class="maintenance-table-wrap"><table class="maintenance-table"><thead><tr><th>{{ $ar?'الخطة':'Plan' }}</th><th>{{ $ar?'الأصل':'Asset' }}</th><th>{{ $ar?'الموقع':'Site' }}</th><th>{{ $ar?'التكرار':'Frequency' }}</th><th>{{ $ar?'آخر صيانة':'Last Service' }}</th><th>{{ $ar?'الاستحقاق القادم':'Next Due' }}</th><th>{{ $ar?'الحالة':'Status' }}</th></tr></thead><tbody>
        @foreach($planCollection as $plan)
            @php($isOverdue = $plan->next_due_date && $plan->next_due_date->copy()->startOfDay()->lt($today))
            @php($isDueSoon = !$isOverdue && $plan->next_due_date && $plan->next_due_date->lte(now()->copy()->addDays(14)))
            <tr data-maint-row><td><span class="maintenance-plan-name">{{ $plan->plan_no }} · {{ $plan->name }}</span></td><td>{{ $plan->asset?->asset_code ?: '—' }}@if($plan->asset?->name) · {{ $plan->asset->name }}@endif</td><td>{{ $plan->asset?->site?->name ?: '—' }}</td><td>{{ trim(($plan->frequency_type ?: '').' '.($plan->frequency_value ?: '')) ?: '—' }}</td><td>{{ $plan->last_service_date?->format('Y-m-d') ?: '—' }}</td><td>{{ $plan->next_due_date?->format('Y-m-d') ?: '—' }}</td><td><span class="portal-pill {{ $isOverdue?'red':($isDueSoon?'amber':'green') }}">{{ $isOverdue?($ar?'متأخر':'Overdue'):($isDueSoon?($ar?'قريب':'Due Soon'):($plan->status ?: ($ar?'مجدول':'Scheduled'))) }}</span></td></tr>
        @endforeach
        </tbody></table></div>
    @else
        <div class="maintenance-empty"><div class="maintenance-empty-icon">◇</div><strong>{{ $ar?'لا توجد خطط صيانة وقائية حتى الآن':'No preventive maintenance plans yet' }}</strong><p>{{ $ar?'ستظهر الخطط هنا تلقائيًا عند ربط أصول العميل بخطط الصيانة الوقائية المعتمدة.' : 'Plans will appear here automatically when customer assets are linked to approved preventive maintenance plans.' }}</p></div>
    @endif
</section>

@include('customer.partials.portal-shell-close')
