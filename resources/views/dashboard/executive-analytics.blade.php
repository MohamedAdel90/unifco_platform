<style>
.analytics-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:12px}.analytics-box{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:15px}.analytics-box h3{margin:0 0 4px;color:#071f4d;font-size:12px}.analytics-box>small{display:block;color:#78869a;font-size:8px;margin-bottom:12px}.metric-row{display:grid;grid-template-columns:90px 1fr 34px;gap:7px;align-items:center;margin:8px 0;font-size:8px}.metric-row strong{text-align:right;color:#071f4d}.metric-track{height:7px;border-radius:999px;background:#edf2f7;overflow:hidden}.metric-fill{height:100%;border-radius:999px;background:#2569ad}.metric-fill.green{background:#1e9b62}.metric-fill.amber{background:#d99119}.metric-fill.red{background:#df1730}.metric-fill.darkred{background:#9f1126}.sla-line{display:grid;grid-template-columns:72px 1fr 55px;gap:7px;align-items:center;padding:7px 0;border-bottom:1px solid #edf1f5}.sla-line:last-child{border-bottom:0}.priority{font-size:8px;font-weight:900}.sla-bar{height:7px;background:#edf2f7;border-radius:99px;overflow:hidden}.sla-bar i{display:block;height:100%;background:#1d9a62}.sla-line.bad .sla-bar i{background:#e20b24}.sla-line em{font-style:normal;font-size:8px;text-align:right}.rank{display:grid;grid-template-columns:1fr auto;gap:8px;padding:7px 0;border-bottom:1px solid #edf1f5}.rank:last-child{border-bottom:0}.rank b{font-size:9px}.rank small{display:block;color:#7a889b;font-size:7px}.rank-count{font-size:12px;font-weight:900;color:#071f4d}.exception-list{display:grid;gap:7px}.exception{display:grid;grid-template-columns:8px 1fr auto;gap:8px;padding:9px;border:1px solid #edf1f5;border-radius:9px;align-items:center}.exception .sev{width:8px;height:100%;min-height:38px;border-radius:99px;background:#d99119}.exception.CRITICAL .sev{background:#e20b24}.exception b{font-size:9px;display:block}.exception small{font-size:7px;color:#77869a}.exception .go{font-size:15px;color:#7b899c}.expiry{display:grid;grid-template-columns:repeat(4,1fr);gap:7px}.expiry-card{background:#f7f9fc;border-radius:9px;padding:9px;text-align:center}.expiry-card b{display:block;font-size:18px;color:#071f4d}.expiry-card span{font-size:7px;color:#758399}.analytics-wide{grid-column:span 2}@media(max-width:1100px){.analytics-grid{grid-template-columns:1fr 1fr}.analytics-wide{grid-column:span 2}}@media(max-width:700px){.analytics-grid{grid-template-columns:1fr}.analytics-wide{grid-column:auto}.expiry{grid-template-columns:1fr 1fr}}
</style>

<div class="section-title"><h3>Executive Analytics · التحليلات التنفيذية</h3><small>Exception-driven operational intelligence</small></div>
<div class="analytics-grid">
@if($capabilities['maintenance'])
<section class="analytics-box"><h3>Backlog Aging · عمر الأعمال المفتوحة</h3><small>Older backlog receives immediate management attention.</small>@php($agingMax=max(1,max($analytics['backlogAging'])))@foreach($analytics['backlogAging'] as $label=>$value)<div class="metric-row"><span>{{ $label }}</span><div class="metric-track"><div class="metric-fill {{ $label==='31+ Days'?'red':($label==='8-30 Days'?'amber':'') }}" style="width:{{ ($value/$agingMax)*100 }}%"></div></div><strong>{{ $value }}</strong></div>@endforeach</section>
@endif

@if($capabilities['eam'])
<section class="analytics-box"><h3>Asset Health Distribution · توزيع صحة الأصول</h3><small>Portfolio condition based on the current scope.</small>@php($healthMax=max(1,max($analytics['healthDistribution'])))@foreach($analytics['healthDistribution'] as $label=>$value)<div class="metric-row"><span>{{ $label }}</span><div class="metric-track"><div class="metric-fill {{ $label==='HEALTHY'?'green':($label==='WATCH'?'amber':(in_array($label,['ATTENTION','CRITICAL'])?'red':'')) }}" style="width:{{ ($value/$healthMax)*100 }}%"></div></div><strong>{{ $value }}</strong></div>@endforeach</section>
@endif

@if($capabilities['crm'])
<section class="analytics-box"><h3>SLA by Priority · SLA حسب الأولوية</h3><small>Performance and breach concentration by request priority.</small>@forelse($analytics['slaByPriority'] as $row)<div class="sla-line {{ $row->performance<90?'bad':'' }}"><span class="priority">{{ $row->priority }}</span><div class="sla-bar"><i style="width:{{ $row->performance }}%"></i></div><em>{{ $row->performance }}% · {{ $row->breaches }}</em></div>@empty<div class="empty">No SLA-bearing requests in this scope.</div>@endforelse</section>
@endif

@if($capabilities['maintenance'] && !$analytics['topCustomers']->isEmpty())
<section class="analytics-box"><h3>Top Customer Backlog · أعلى العملاء</h3><small>Ranked by critical and open work orders.</small>@foreach($analytics['topCustomers'] as $row)<div class="rank"><span><b>{{ $row['name'] }}</b><small>{{ $row['critical'] }} critical / emergency</small></span><span class="rank-count">{{ $row['open'] }}</span></div>@endforeach</section>
@endif

@if($capabilities['maintenance'] && !$analytics['topSites']->isEmpty())
<section class="analytics-box"><h3>Top Site Backlog · أعلى المواقع</h3><small>Sites carrying the heaviest operational workload.</small>@foreach($analytics['topSites'] as $row)<div class="rank"><span><b>{{ $row['name'] }}</b><small>{{ $row['critical'] }} critical / emergency</small></span><span class="rank-count">{{ $row['open'] }}</span></div>@endforeach</section>
@endif

@if($capabilities['crm'])
<section class="analytics-box"><h3>Contract Expiry Timeline · انتهاء العقود</h3><small>Forward visibility for renewals and account planning.</small><div class="expiry">@foreach($analytics['contractExpiry'] as $label=>$value)<div class="expiry-card"><b>{{ $value }}</b><span>{{ $label }}</span></div>@endforeach</div></section>
@endif

@if(!$analytics['exceptions']->isEmpty())
<section class="analytics-box analytics-wide"><h3>Executive Exceptions · الاستثناءات التنفيذية</h3><small>The highest-impact conditions that deserve management attention now.</small><div class="exception-list">@foreach($analytics['exceptions'] as $item)<a class="exception {{ $item->severity }}" href="{{ $item->url }}"><i class="sev"></i><span><b>{{ $item->type }} · {{ $item->title }}</b><small>{{ $item->detail }}</small></span><span class="go">›</span></a>@endforeach</div></section>
@endif
</div>

@include('dashboard.predictive-operations')
