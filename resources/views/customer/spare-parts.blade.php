@php
    $activeSection = 'spare-parts';
    $pageTitle = 'Spare Parts';
    $pageDescription = 'Spare-parts activity, related work orders and asset usage in one customer workspace.';
    $parts = collect($materials ?? [])->sortByDesc('created_at')->values();
    $totalLines = $parts->count();
    $totalQuantity = $parts->sum(fn($p) => (float)($p->quantity ?? 0));
    $workOrderCount = $parts->pluck('work_order_no')->filter()->unique()->count();
    $assetCount = $parts->pluck('asset_code')->filter()->unique()->count();
    $recentParts = $parts->take(6);
    $topParts = $parts->groupBy(fn($p) => $p->item_code ?: ($p->item_name ?: 'Other'))->map(function($group){
        return [
            'name' => $group->first()->item_name ?: $group->first()->item_code ?: 'Part',
            'qty' => $group->sum(fn($p)=>(float)($p->quantity ?? 0)),
            'count' => $group->count(),
        ];
    })->sortByDesc('qty')->take(5)->values();
    $maxTopQty = max(1, (float)($topParts->max('qty') ?? 1));
@endphp

@push('styles')
<style>
.parts-page{display:grid;gap:14px}.parts-page .portal-page-head{align-items:center;margin-bottom:0}.parts-title{display:flex;align-items:center;gap:12px}.parts-title-icon{width:48px;height:48px;border-radius:14px;background:#fff0f2;color:var(--red);display:grid;place-items:center}.parts-title-icon .ui-icon{width:25px;height:25px}.parts-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.parts-kpi{padding:16px;display:grid;grid-template-columns:44px 1fr;gap:12px;align-items:center;min-height:96px}.parts-kpi-icon{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;background:#edf5ff;color:var(--blue)}.parts-kpi-icon.green{background:#e8f8f0;color:var(--green)}.parts-kpi-icon.amber{background:#fff4db;color:var(--amber)}.parts-kpi-icon.red{background:#fdebed;color:var(--red)}.parts-kpi-icon .ui-icon{width:21px;height:21px}.parts-kpi small{font-size:8px;color:var(--muted);font-weight:800}.parts-kpi strong{display:block;font-size:25px;line-height:1;margin:5px 0}.parts-kpi span{font-size:8px;color:var(--muted)}.parts-main-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(320px,.75fr);gap:12px}.parts-card{padding:16px}.parts-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:13px}.parts-card-head h3{font-size:13px;margin:0}.parts-card-head a{font-size:8px;color:var(--blue);font-weight:800}.parts-tools{display:flex;gap:7px;align-items:center}.parts-search{height:38px;border:1px solid #dfe7f0;border-radius:9px;padding:0 12px;width:100%;font-size:9px;color:var(--ink);outline:none;background:#fff}.parts-search:focus{border-color:#94bce6;box-shadow:0 0 0 3px #1475d112}.parts-table-wrap{overflow:auto;margin-top:10px}.parts-table{width:100%;border-collapse:collapse;font-size:9px}.parts-table th,.parts-table td{padding:11px 9px;text-align:left;border-bottom:1px solid #edf1f5;white-space:nowrap}.parts-table th{font-size:8px;color:#68758a;text-transform:uppercase;letter-spacing:.04em;background:#f8fafc}.parts-table tr:last-child td{border-bottom:0}.part-code{font-weight:900;color:var(--blue)}.part-name{font-weight:800}.asset-chip,.wo-chip{display:inline-flex;padding:5px 7px;border-radius:999px;font-size:8px;font-weight:800}.asset-chip{background:#f1f5fa;color:#526780}.wo-chip{background:#edf5ff;color:var(--blue)}.qty-chip{display:inline-flex;min-width:40px;justify-content:center;padding:5px 7px;border-radius:7px;background:#fff4db;color:#966300;font-weight:900}.parts-empty{padding:42px 20px;text-align:center;color:var(--muted);font-size:9px}.parts-empty strong{display:block;color:var(--ink);font-size:12px;margin-bottom:5px}.right-stack{display:grid;gap:12px}.summary-ring-wrap{display:grid;grid-template-columns:132px 1fr;gap:16px;align-items:center}.summary-ring{width:118px;height:118px;border-radius:50%;background:conic-gradient(var(--blue) 0 72%,#e7edf4 72% 100%);position:relative;display:grid;place-items:center}.summary-ring:before{content:"";position:absolute;inset:18px;background:#fff;border-radius:50%}.summary-ring div{position:relative;text-align:center}.summary-ring strong{display:block;font-size:25px}.summary-ring span{font-size:8px;color:var(--muted)}.summary-list{display:grid;gap:8px}.summary-list div{display:flex;justify-content:space-between;gap:10px;font-size:8px}.summary-list b{font-size:10px}.quick-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.quick-link{min-height:50px;border:1px solid #e3eaf2;border-radius:10px;background:#f8fbff;color:var(--blue);display:flex;align-items:center;gap:8px;padding:10px;font-size:9px;font-weight:800}.quick-link.red{background:#fff4f6;color:var(--red)}.quick-link .ui-icon{width:18px;height:18px}.parts-lower-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}.activity-row{display:grid;grid-template-columns:30px 1fr auto;gap:9px;align-items:center;padding:9px 0;border-bottom:1px solid #edf1f5}.activity-row:last-child{border-bottom:0}.activity-icon{width:30px;height:30px;border-radius:9px;background:#eef6ff;color:var(--blue);display:grid;place-items:center}.activity-icon .ui-icon{width:15px;height:15px}.activity-row strong{font-size:9px}.activity-row small{display:block;font-size:7px;color:var(--muted);margin-top:3px}.activity-row time{font-size:7px;color:var(--muted);white-space:nowrap}.top-part{display:grid;grid-template-columns:minmax(95px,1fr) 2fr 32px;gap:10px;align-items:center;margin:12px 0}.top-part span{font-size:9px;font-weight:800}.top-bar{height:8px;border-radius:99px;background:#edf1f5;overflow:hidden}.top-bar i{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,var(--blue),#559be1)}.top-part b{font-size:9px;text-align:right}.coverage-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.coverage-box{padding:13px;border:1px solid #edf1f5;border-radius:10px;background:#f8fafc}.coverage-box strong{display:block;font-size:19px}.coverage-box span{font-size:8px;color:var(--muted)}.parts-note{margin-top:10px;padding:11px 12px;border-radius:9px;background:#eef5ff;color:#4b6584;font-size:8px;line-height:1.55}
@media(max-width:1200px){.parts-kpis{grid-template-columns:1fr 1fr}.parts-main-grid{grid-template-columns:1fr}.parts-lower-grid{grid-template-columns:1fr 1fr}}
@media(max-width:760px){.parts-kpis,.parts-lower-grid{grid-template-columns:1fr}.quick-grid{grid-template-columns:1fr}.parts-page .portal-page-head{align-items:flex-start;flex-direction:column}.parts-page .portal-page-head .portal-btn{width:100%}}
</style>
@endpush

@include('customer.partials.portal-shell-open')

<div class="parts-page">
    <div class="portal-page-head">
        <div class="parts-title">
            <div class="parts-title-icon">@include('customer.partials.icon',['name'=>'parts'])</div>
            <div><h2>Spare Parts</h2><p>Track spare-parts activity, related work orders, assets and quantities across your customer account.</p></div>
        </div>
        <a class="portal-btn red no-print" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'quotation'=>1,'subtype'=>'parts']) }}">＋ Request Spare Parts</a>
    </div>

    <section class="parts-kpis">
        <div class="portal-card parts-kpi"><div class="parts-kpi-icon">@include('customer.partials.icon',['name'=>'parts'])</div><div><small>Part Activity</small><strong>{{ $totalLines }}</strong><span>Total recorded lines</span></div></div>
        <div class="portal-card parts-kpi"><div class="parts-kpi-icon amber">@include('customer.partials.icon',['name'=>'reports'])</div><div><small>Total Quantity</small><strong>{{ rtrim(rtrim(number_format($totalQuantity,2,'.',''), '0'), '.') }}</strong><span>Across all recorded parts</span></div></div>
        <div class="portal-card parts-kpi"><div class="parts-kpi-icon green">@include('customer.partials.icon',['name'=>'work-orders'])</div><div><small>Related Work Orders</small><strong>{{ $workOrderCount }}</strong><span>Unique work orders</span></div></div>
        <div class="portal-card parts-kpi"><div class="parts-kpi-icon red">@include('customer.partials.icon',['name'=>'assets'])</div><div><small>Assets Served</small><strong>{{ $assetCount }}</strong><span>Unique customer assets</span></div></div>
    </section>

    <section class="parts-main-grid">
        <div class="portal-card parts-card">
            <div class="parts-card-head"><h3>Parts Activity</h3><div class="parts-tools"><span class="portal-pill">{{ $totalLines }} records</span></div></div>
            <input id="parts-search" class="parts-search" type="search" placeholder="Search by part, work order or asset..." autocomplete="off">
            <div class="parts-table-wrap">
                <table class="parts-table" id="parts-table">
                    <thead><tr><th>Part</th><th>Description</th><th>Work Order</th><th>Asset</th><th>Quantity</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($parts as $part)
                        <tr>
                            <td><span class="part-code">{{ $part->item_code ?: '—' }}</span></td>
                            <td><span class="part-name">{{ $part->item_name ?: 'Spare part' }}</span></td>
                            <td><span class="wo-chip">{{ $part->work_order_no ?: '—' }}</span></td>
                            <td><span class="asset-chip">{{ $part->asset_code ?: '—' }}{{ $part->asset_name ? ' · '.$part->asset_name : '' }}</span></td>
                            <td><span class="qty-chip">{{ $part->quantity }} {{ $part->uom }}</span></td>
                            <td>{{ $part->created_at ? \Illuminate\Support\Carbon::parse($part->created_at)->format('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="parts-empty"><strong>No spare-parts activity</strong>No spare-parts records are currently available in your customer scope.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="right-stack">
            <div class="portal-card parts-card">
                <div class="parts-card-head"><h3>Activity Summary</h3></div>
                <div class="summary-ring-wrap">
                    <div class="summary-ring"><div><strong>{{ $totalLines }}</strong><span>Part records</span></div></div>
                    <div class="summary-list">
                        <div><span>Related work orders</span><b>{{ $workOrderCount }}</b></div>
                        <div><span>Assets served</span><b>{{ $assetCount }}</b></div>
                        <div><span>Total quantity</span><b>{{ rtrim(rtrim(number_format($totalQuantity,2,'.',''), '0'), '.') }}</b></div>
                    </div>
                </div>
            </div>

            <div class="portal-card parts-card no-print">
                <div class="parts-card-head"><h3>Quick Actions</h3></div>
                <div class="quick-grid">
                    <a class="quick-link red" href="{{ route('public.request-service',['customer'=>$customer->customer_code,'quotation'=>1,'subtype'=>'parts']) }}">@include('customer.partials.icon',['name'=>'parts'])<span>Request Spare Parts</span></a>
                    <a class="quick-link" href="{{ route('customer.section','work-orders') }}">@include('customer.partials.icon',['name'=>'work-orders'])<span>View Work Orders</span></a>
                    <a class="quick-link" href="{{ route('customer.section','documents') }}">@include('customer.partials.icon',['name'=>'documents'])<span>Document Library</span></a>
                    <a class="quick-link" href="{{ route('customer.inbox') }}">@include('customer.partials.icon',['name'=>'inbox'])<span>Contact Support</span></a>
                </div>
            </div>
        </div>
    </section>

    <section class="parts-lower-grid">
        <div class="portal-card parts-card">
            <div class="parts-card-head"><h3>Recent Activity</h3><span class="portal-pill">Latest</span></div>
            @forelse($recentParts as $part)
                <div class="activity-row"><div class="activity-icon">@include('customer.partials.icon',['name'=>'parts'])</div><div><strong>{{ $part->item_name ?: $part->item_code ?: 'Spare part' }}</strong><small>{{ $part->work_order_no ?: 'No work order' }} · {{ $part->asset_code ?: 'No asset' }}</small></div><time>{{ $part->created_at ? \Illuminate\Support\Carbon::parse($part->created_at)->diffForHumans() : '' }}</time></div>
            @empty
                <div class="parts-empty"><strong>No recent activity</strong></div>
            @endforelse
        </div>

        <div class="portal-card parts-card">
            <div class="parts-card-head"><h3>Top Requested Parts</h3><span class="portal-pill">By quantity</span></div>
            @forelse($topParts as $item)
                <div class="top-part"><span>{{ $item['name'] }}</span><div class="top-bar"><i style="width:{{ max(5, min(100, ($item['qty']/$maxTopQty)*100)) }}%"></i></div><b>{{ rtrim(rtrim(number_format($item['qty'],2,'.',''), '0'), '.') }}</b></div>
            @empty
                <div class="parts-empty"><strong>No usage data</strong></div>
            @endforelse
        </div>

        <div class="portal-card parts-card">
            <div class="parts-card-head"><h3>Coverage</h3></div>
            <div class="coverage-grid"><div class="coverage-box"><strong>{{ $workOrderCount }}</strong><span>Work orders with parts activity</span></div><div class="coverage-box"><strong>{{ $assetCount }}</strong><span>Assets with parts activity</span></div><div class="coverage-box"><strong>{{ $topParts->count() }}</strong><span>Top part categories shown</span></div><div class="coverage-box"><strong>{{ $totalLines }}</strong><span>Total history records</span></div></div>
            <div class="parts-note">Availability, delivery and supplier lead-time values are shown only when they exist in the operational source data; this page does not invent delivery statuses.</div>
        </div>
    </section>
</div>

@push('scripts')
<script>
(()=>{const input=document.getElementById('parts-search'),table=document.getElementById('parts-table');if(!input||!table)return;const rows=[...table.querySelectorAll('tbody tr')];input.addEventListener('input',()=>{const q=input.value.trim().toLowerCase();rows.forEach(row=>{row.style.display=!q||row.innerText.toLowerCase().includes(q)?'':'none';});});})();
</script>
@endpush

@include('customer.partials.portal-shell-close')
