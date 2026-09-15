@include('customer.partials.portal-shell-open',[
    'customer'=>$customer,
    'activeSection'=>'assets',
    'pageTitle'=>$asset->asset_code.' · Asset 360',
    'pageDescription'=>'السجل الكامل للأصل والصيانة والأعطال والمستندات',
])

<section class="asset-hero"><span>ASSET 360 · سجل الأصل الكامل</span><h2>{{ $asset->asset_code }} · {{ $asset->name }}</h2><p>{{ $asset->site?->name ?: 'بدون موقع' }} · {{ $asset->asset_category ?: 'غير مصنف' }} {{ $asset->asset_subcategory ? ' / '.$asset->asset_subcategory : '' }}</p></section>
<section class="asset-kpis">
    @foreach([
        ['الحالة التشغيلية',$asset->operational_status ?: '—'],['صحة الأصل',$asset->health_score===null?'لا توجد بيانات':$asset->health_score.'/100'],['تصنيف الصحة',str_replace('_',' ',$asset->health_band ?: 'PENDING')],['العمر المتبقي',$asset->remaining_life_months===null?'—':$asset->remaining_life_months.' شهر'],
        ['توصية دورة الحياة',str_replace('_',' ',$asset->replacement_recommendation ?: 'PENDING')],['الالتزام بالصيانة',$metrics['pmCompliance'].'%'],['MTBF',$metrics['mtbf']===null?'—':round($metrics['mtbf']/60,1).' ساعة'],['MTTR',$metrics['mttr']===null?'—':round($metrics['mttr']/60,1).' ساعة']
    ] as [$label,$value])<article class="portal-card"><small>{{ $label }}</small><strong>{{ $value }}</strong></article>@endforeach
</section>

<div class="portal-grid-2">
    <section class="portal-card portal-panel"><h3>بيانات الأصل · Asset Profile</h3><div class="portal-kv">
        @foreach([['الشركة المصنعة',$asset->manufacturer],['الموديل',$asset->model_no],['الرقم التسلسلي',$asset->serial_no],['الأهمية',$asset->criticality],['حالة دورة الحياة',$asset->lifecycle_status],['انتهاء الضمان',$asset->warranty_expiry?->format('Y-m-d')],['تاريخ التركيب',$asset->installation_date?->format('Y-m-d')],['تاريخ التشغيل',$asset->commission_date?->format('Y-m-d')]] as [$label,$value])<div><small>{{ $label }}</small><strong>{{ $value ?: '—' }}</strong></div>@endforeach
    </div></section>
    <section class="portal-card portal-panel"><h3>المواصفات الفنية · Technical Specifications</h3><div class="portal-kv">@forelse($specifications as $specification)<div><small>{{ $specification->spec_label }}</small><strong>{{ $specification->spec_value ?: '—' }} {{ $specification->unit }}</strong></div>@empty<div class="portal-empty">لا توجد مواصفات مسجلة.</div>@endforelse</div></section>
</div>

<div class="portal-grid-2 asset-gap">
    <section class="portal-card portal-panel"><h3>خطط الصيانة</h3><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>الخطة</th><th>الاستراتيجية</th><th>التكرار</th><th>الاستحقاق القادم</th><th>الحالة</th></tr></thead><tbody>@forelse($plans as $plan)<tr><td>{{ $plan->plan_no }}<br><small>{{ $plan->name }}</small></td><td>{{ $plan->maintenance_strategy }}</td><td>{{ $plan->frequency_value }} {{ $plan->frequency_type }}</td><td>{{ $plan->next_due_date ?: $plan->next_due_meter ?: '—' }}</td><td>{{ $plan->status }}</td></tr>@empty<tr><td colspan="5" class="portal-empty">لا توجد خطط صيانة.</td></tr>@endforelse</tbody></table></div></section>
    <section class="portal-card portal-panel"><h3>سجل الأعطال</h3><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>العطل</th><th>الخطورة</th><th>التاريخ</th><th>التوقف</th><th>الحالة</th></tr></thead><tbody>@forelse($failures as $failure)<tr><td>{{ $failure->failure_mode }}</td><td>{{ $failure->severity }}</td><td>{{ $failure->failed_at }}</td><td>{{ $failure->downtime_minutes }} دقيقة</td><td><span class="portal-pill {{ $failure->status==='OPEN'?'red':'green' }}">{{ $failure->status }}</span></td></tr>@empty<tr><td colspan="5" class="portal-empty">لا توجد أعطال مسجلة.</td></tr>@endforelse</tbody></table></div></section>
</div>

<section class="portal-card portal-panel asset-gap"><h3>سجل أوامر العمل</h3><div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>أمر العمل</th><th>النوع</th><th>الأولوية</th><th>المخطط</th><th>الحالة</th><th></th></tr></thead><tbody>@forelse($workOrders as $workOrder)<tr><td><strong>{{ $workOrder->work_order_no }}</strong></td><td>{{ $workOrder->maintenance_type }}</td><td>{{ $workOrder->priority }}</td><td>{{ $workOrder->planned_start?->format('Y-m-d H:i') ?: '—' }}</td><td>{{ $workOrder->status }}</td><td><a class="portal-btn soft" href="{{ route('customer.work-orders.show',$workOrder) }}">تقرير الخدمة</a></td></tr>@empty<tr><td colspan="6" class="portal-empty">لا توجد أوامر عمل.</td></tr>@endforelse</tbody></table></div></section>

@include('customer.asset-part-history')

<div class="portal-grid-2 asset-gap"><section class="portal-card portal-panel"><h3>المستندات الفنية</h3>@forelse($documents as $document)<div class="asset-record"><strong>{{ $document->document_type }}</strong><span>{{ $document->title }}</span></div>@empty<div class="portal-empty">لا توجد مستندات ظاهرة للعميل.</div>@endforelse</section><section class="portal-card portal-panel"><h3>صور وتقارير الصيانة</h3>@forelse($attachments as $attachment)<a class="asset-record" href="{{ route('customer.work-orders.attachments.download',[$attachment->work_order_id,$attachment->id]) }}"><strong>{{ $attachment->attachment_type }}</strong><span>{{ $attachment->title ?: $attachment->original_name }}</span></a>@empty<div class="portal-empty">لا توجد أدلة صيانة ظاهرة للعميل.</div>@endforelse</section></div>

@push('late-styles')<style>
.asset-hero{background:linear-gradient(135deg,var(--navy-deep),#164b82);color:#fff;border-radius:14px;padding:20px;margin-bottom:12px}.asset-hero span{font-size:8px;color:#bcd0e6}.asset-hero h2{font-size:23px;margin:6px 0}.asset-hero p{font-size:9px;color:#d2deec;margin:0}.asset-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px;margin-bottom:12px}.asset-kpis article{padding:13px}.asset-kpis small,.asset-kpis strong{display:block}.asset-kpis small{font-size:8px;color:var(--muted)}.asset-kpis strong{font-size:15px;margin-top:6px}.asset-gap{margin-top:12px}.asset-record{display:block;padding:9px 0;border-bottom:1px solid #edf1f5;font-size:9px}.asset-record strong,.asset-record span{display:block}.asset-record span{color:var(--muted);margin-top:3px}.box{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);padding:16px}.box h3{font-size:13px;margin:0 0 13px}.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse;font-size:10px}.table th,.table td{padding:11px 9px;text-align:start;border-bottom:1px solid #edf0f4;white-space:nowrap}.table th{font-size:8px;color:var(--muted)}.btn.secondary{display:inline-flex;padding:7px 9px;border-radius:7px;background:#edf3fb;color:var(--navy);font-size:8px;font-weight:800}@media(max-width:1000px){.asset-kpis{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.asset-kpis{grid-template-columns:1fr}}
</style>@endpush
@include('customer.partials.portal-shell-close')
