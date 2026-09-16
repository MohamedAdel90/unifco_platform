@include('customer.section-base')

@if(($section ?? null) === 'dashboard')
@php
    $customerLogo = data_get($customer, 'logo_url') ?: data_get($customer, 'logo') ?: data_get($customer, 'logo_path');
    if ($customerLogo && !filter_var($customerLogo, FILTER_VALIDATE_URL)) {
        $customerLogo = asset('storage/'.ltrim((string) $customerLogo, '/'));
    }
    $requestTotalForChart = max(1,
        (int)($requestStageCounts['new'] ?? 0) + (int)($requestStageCounts['review'] ?? 0) +
        (int)($requestStageCounts['assigned'] ?? 0) + (int)($requestStageCounts['scheduled'] ?? 0) +
        (int)($requestStageCounts['dispatch'] ?? 0) + (int)($requestStageCounts['progress'] ?? 0) +
        (int)($requestStageCounts['customer'] ?? 0) + (int)($requestStageCounts['closed'] ?? 0)
    );
    $requestSegments = [
        ['New', (int)($requestStageCounts['new'] ?? 0), '#1475d1'],
        ['Under review', (int)($requestStageCounts['review'] ?? 0), '#2f91ed'],
        ['Assigned', (int)($requestStageCounts['assigned'] ?? 0), '#7d6df2'],
        ['Visit scheduled', (int)($requestStageCounts['scheduled'] ?? 0), '#f29a52'],
        ['Team dispatched', (int)($requestStageCounts['dispatch'] ?? 0), '#a95ee8'],
        ['In progress', (int)($requestStageCounts['progress'] ?? 0), '#ec536d'],
        ['Awaiting customer', (int)($requestStageCounts['customer'] ?? 0), '#82c9a3'],
        ['Completed', (int)($requestStageCounts['closed'] ?? 0), '#20a365'],
    ];
    $requestGradient = [];
    $requestCursor = 0;
    foreach ($requestSegments as $segment) {
        $end = $requestCursor + (($segment[1] / $requestTotalForChart) * 100);
        $requestGradient[] = $segment[2].' '.$requestCursor.'% '.$end.'%';
        $requestCursor = $end;
    }
    if ($requestCursor < 100) $requestGradient[] = '#e9eef5 '.$requestCursor.'% 100%';
    $workTotalForChart = max(1, (int)$openWorkOrders);
    $workOverduePct = min(100, ((int)$overdueCount / $workTotalForChart) * 100);
    $workProgressPct = min(100 - $workOverduePct, ((int)$inProgressCount / $workTotalForChart) * 100);
    $assetTotalForChart = max(1, (int)$assets->count());
    $assetOperationalPct = min(100, ((int)$activeAssetCount / $assetTotalForChart) * 100);
    $assetMaintenancePct = min(100 - $assetOperationalPct, ((int)$maintenanceAssetCount / $assetTotalForChart) * 100);
@endphp
<style>
    /* Customer Dashboard V3 — approved full-width command center. Sidebar navigation stays intact. */
    .content{max-width:none!important;padding-top:14px!important}
    .page-head,.executive-strip,.activity-panel{display:none!important}
    .top-actions .status-pill{display:none!important}
    .lower-grid,.command-grid{display:none!important}
    .stats{gap:10px!important;margin-top:0!important}
    .stat{min-height:96px!important;padding:12px 14px!important}
    .stat .value{font-size:22px!important;margin:8px 0 4px!important}
    .stat .comparison{margin-top:5px!important}
    .quick-actions{margin:0!important;grid-template-columns:repeat(5,minmax(0,1fr))!important}
    .quick-action{min-height:50px!important;padding:9px 10px!important}
    .quick-action .quick-icon{width:29px!important;height:29px!important}
    .action-center{margin:0!important;min-height:82px;display:flex;align-items:center}
    .action-center .action-head{width:100%}
    .dashboard-bottom{display:grid;grid-template-columns:minmax(330px,.9fr) minmax(0,1.65fr);gap:10px;margin-top:10px}
    .dashboard-bottom .quick-actions{height:100%}

    /* Customer identity belongs at the top of the sidebar, not in a large dashboard banner. */
    .sidebar .account{padding:4px 8px 14px!important;text-align:center}
    .sidebar .account-mark{display:flex!important;flex-direction:column!important;gap:7px!important;align-items:center!important}
    .sidebar .account-logo{width:100%!important;height:58px!important;border-radius:9px!important;background:#fff!important;overflow:hidden!important;font-size:15px!important}
    .sidebar .account-logo img{width:100%;height:100%;object-fit:contain;padding:6px}
    .sidebar .account strong{font-size:11px!important;max-width:210px}
    .sidebar .account small{font-size:8px!important;margin-top:2px!important}
    .sidebar .customer-side-status{display:inline-flex;align-items:center;gap:5px;margin-top:5px;padding:4px 8px;border-radius:999px;background:#dff7e9;color:#117044;font-size:7px;font-weight:900}
    .sidebar .customer-side-status:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
    .sidebar .role-badge{margin-top:6px!important;font-size:7px!important}

    .dashboard-insights{display:grid;grid-template-columns:1.15fr 1.15fr 1fr;gap:10px;margin-top:10px}
    .insight-card{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);padding:14px;min-width:0}
    .insight-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}
    .insight-head h3{margin:0;font-size:12px;padding-left:9px;border-left:3px solid var(--blue)}
    .insight-head a{font-size:8px;color:var(--blue);font-weight:800}
    .donut-layout{display:grid;grid-template-columns:150px minmax(0,1fr);gap:14px;align-items:center}
    .donut{width:126px;height:126px;border-radius:50%;position:relative;display:grid;place-items:center;margin:auto}
    .donut:after{content:'';position:absolute;inset:19px;border-radius:50%;background:#fff}
    .donut-center{position:relative;z-index:1;text-align:center}.donut-center b{font-size:23px;display:block}.donut-center span{font-size:7px;color:var(--muted)}
    .chart-legend{display:grid;gap:6px}.legend-row{display:grid;grid-template-columns:8px 1fr auto;gap:7px;align-items:center;font-size:8px;color:#53647a}.legend-row i{width:7px;height:7px;border-radius:50%}.legend-row b{font-size:9px;color:var(--ink)}
    .insight-row{display:grid;grid-template-columns:1.15fr .9fr .9fr;gap:10px;margin-top:10px}
    .metric-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px}.metric-box{background:#f7f9fc;border:1px solid #edf1f5;border-radius:9px;padding:10px}.metric-box b{display:block;font-size:16px}.metric-box span{font-size:7px;color:var(--muted);display:block;margin-top:4px}
    .workflow-bars{display:grid;gap:8px}.workflow-bar{display:grid;grid-template-columns:88px minmax(0,1fr) 26px;gap:8px;align-items:center;font-size:8px;color:#607086}.bar-track{height:8px;border-radius:5px;background:#edf2f7;overflow:hidden}.bar-fill{height:100%;border-radius:5px;background:linear-gradient(90deg,#1475d1,#5aa8ee)}.workflow-bar.alert .bar-fill{background:linear-gradient(90deg,#e20b24,#f16a7d)}.workflow-bar b{text-align:right;font-size:8px;color:var(--ink)}
    .finance-line{display:grid;grid-template-columns:repeat(3,1fr);gap:7px}.finance-metric{padding:11px;border-radius:9px;background:#f7f9fc;border:1px solid #edf1f5}.finance-metric b{font-size:14px;display:block}.finance-metric span{font-size:7px;color:var(--muted);display:block;margin-top:4px}
    .operational-note{font-size:8px;color:var(--muted);line-height:1.6;margin-top:9px}
    .recent-nav-promoted{margin-top:2px!important}
    @media(max-width:1350px){.dashboard-insights,.insight-row{grid-template-columns:1fr 1fr}.dashboard-insights>.insight-card:last-child,.insight-row>.insight-card:first-child{grid-column:1/-1}.dashboard-bottom{grid-template-columns:1fr}.donut-layout{grid-template-columns:130px 1fr}}
    @media(max-width:980px){.dashboard-insights,.insight-row{grid-template-columns:1fr}.dashboard-insights>.insight-card:last-child,.insight-row>.insight-card:first-child{grid-column:auto}.donut-layout{grid-template-columns:120px 1fr}.quick-actions{grid-template-columns:repeat(2,1fr)!important}}
</style>

<template id="customer-dashboard-insights-template">
    <section class="dashboard-insights">
        <div class="insight-card">
            <div class="insight-head"><h3>Service Request Status</h3><a href="{{ route('customer.section','requests') }}">View all requests →</a></div>
            <div class="donut-layout">
                <div class="donut" style="background:conic-gradient({{ implode(',', $requestGradient) }})"><div class="donut-center"><b>{{ $openRequestCount }}</b><span>Open Requests</span></div></div>
                <div class="chart-legend">
                    @foreach($requestSegments as $segment)
                        <div class="legend-row"><i style="background:{{ $segment[2] }}"></i><span>{{ $segment[0] }}</span><b>{{ $segment[1] }}</b></div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="insight-card">
            <div class="insight-head"><h3>Work Order Status</h3><a href="{{ route('customer.section','work-orders') }}">View all work orders →</a></div>
            <div class="donut-layout">
                <div class="donut" style="background:conic-gradient(#e20b24 0 {{ $workOverduePct }}%,#f3a52a {{ $workOverduePct }}% {{ $workOverduePct + $workProgressPct }}%,#dce6f2 {{ $workOverduePct + $workProgressPct }}% 100%)"><div class="donut-center"><b>{{ $openWorkOrders }}</b><span>Open Work Orders</span></div></div>
                <div class="chart-legend">
                    <div class="legend-row"><i style="background:#e20b24"></i><span>Overdue</span><b>{{ $overdueCount }}</b></div>
                    <div class="legend-row"><i style="background:#f3a52a"></i><span>In progress</span><b>{{ $inProgressCount }}</b></div>
                    <div class="legend-row"><i style="background:#7f9fc4"></i><span>Other open</span><b>{{ max(0,$openWorkOrders-$overdueCount-$inProgressCount) }}</b></div>
                    <div class="operational-note">Operational exceptions are tracked by UNIFCO. Customer decisions remain separate under Action Required.</div>
                </div>
            </div>
        </div>
        <div class="insight-card">
            <div class="insight-head"><h3>Asset Health</h3><a href="{{ route('customer.section','assets') }}">View assets →</a></div>
            <div class="donut-layout">
                <div class="donut" style="background:conic-gradient(#14875a 0 {{ $assetOperationalPct }}%,#d88900 {{ $assetOperationalPct }}% {{ $assetOperationalPct + $assetMaintenancePct }}%,#e20b24 {{ $assetOperationalPct + $assetMaintenancePct }}% 100%)"><div class="donut-center"><b>{{ $assets->count() }}</b><span>Total Assets</span></div></div>
                <div class="chart-legend">
                    <div class="legend-row"><i style="background:#14875a"></i><span>Operational</span><b>{{ $activeAssetCount }}</b></div>
                    <div class="legend-row"><i style="background:#d88900"></i><span>Under maintenance</span><b>{{ $maintenanceAssetCount }}</b></div>
                    <div class="legend-row"><i style="background:#e20b24"></i><span>Stopped</span><b>{{ $stoppedAssetCount }}</b></div>
                    <div class="legend-row"><i style="background:#7d6df2"></i><span>Customer sites</span><b>{{ $sites->count() }}</b></div>
                </div>
            </div>
        </div>
    </section>

    <section class="insight-row">
        <div class="insight-card">
            <div class="insight-head"><h3>Request Workflow Distribution</h3><a href="{{ route('customer.section','requests') }}">Request 360 →</a></div>
            <div class="workflow-bars">
                @foreach($requestSegments as $segment)
                    @php($pct = min(100, ($segment[1] / $requestTotalForChart) * 100))
                    <div class="workflow-bar {{ $segment[0] === 'Awaiting customer' && $segment[1] ? 'alert' : '' }}"><span>{{ $segment[0] }}</span><div class="bar-track"><div class="bar-fill" style="width:{{ $pct }}%"></div></div><b>{{ $segment[1] }}</b></div>
                @endforeach
            </div>
        </div>
        <div class="insight-card">
            <div class="insight-head"><h3>Contracts & SLA</h3><a href="{{ route('customer.section','contracts') }}">Open contracts →</a></div>
            <div class="metric-grid">
                <div class="metric-box"><b>{{ $activeContractCount }}</b><span>Active contracts</span></div>
                <div class="metric-box"><b>{{ $nextContract?->ends_on?->format('d M Y') ?? '—' }}</b><span>Nearest expiry</span></div>
                <div class="metric-box"><b>{{ $slaPerformance === null ? 'N/A' : $slaPerformance.'%' }}</b><span>Measured SLA performance</span></div>
                <div class="metric-box"><b>{{ (int)($requestStageCounts['overdue'] ?? 0) }}</b><span>Active request SLA risks</span></div>
            </div>
        </div>
        <div class="insight-card">
            <div class="insight-head"><h3>Financial Overview</h3><a href="{{ route('customer.section','invoices') }}">Open finance →</a></div>
            <div class="finance-line">
                <div class="finance-metric"><b>SAR {{ number_format($openInvoiceAmount,2) }}</b><span>Open balance</span></div>
                <div class="finance-metric"><b>{{ $invoiceActionCount }}</b><span>Invoices due soon</span></div>
                <div class="finance-metric"><b>{{ $pendingQuotationCount }}</b><span>Pending quotations</span></div>
            </div>
            <div class="operational-note">Financial information is shown for the full unified customer account and follows the same server-authorized scope as the rest of the portal.</div>
        </div>
    </section>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const customerActions = {{ (int) ($actionRequiredCount ?? 0) }};
    const overdueWorkOrders = {{ (int) ($overdueCount ?? 0) }};
    const activeSlaRisks = {{ (int) ($requestStageCounts['overdue'] ?? 0) }};
    const stoppedAssets = {{ (int) ($stoppedAssetCount ?? 0) }};
    const operationalAttention = overdueWorkOrders + activeSlaRisks + stoppedAssets;

    // Sidebar customer identity: logo + name + number + status. No large duplicate customer block in dashboard body.
    const account = document.querySelector('.sidebar .account');
    if (account) {
        const logo = @json($customerLogo);
        const logoMarkup = logo
            ? '<img src="' + logo + '" alt="Customer logo">'
            : @json(strtoupper(substr($customer->name ?: 'CU', 0, 2)));
        account.innerHTML = '<div class="account-mark"><div class="account-logo">' + logoMarkup + '</div><div><strong>' + @json($customer->name) + '</strong><small>Customer ID: ' + @json((string)$customer->id) + ' · ' + @json($customer->customer_code) + '</small><span class="customer-side-status">' + @json($customer->status) + '</span></div></div><span class="role-badge">UNIFIED CUSTOMER ACCOUNT</span>';
    }

    // Promote Recent Activity to the sidebar and remove it from the dashboard body.
    const navLinks = Array.from(document.querySelectorAll('.sidebar .nav-link'));
    let recent = navLinks.find(link => /Recent Activity/i.test(link.textContent || ''));
    if (!recent && {{ in_array('timeline',$allowedSections,true) ? 'true' : 'false' }}) {
        recent = document.createElement('a');
        recent.className = 'nav-link recent-nav-promoted';
        recent.href = @json(route('customer.section','timeline'));
        recent.innerHTML = '<span style="width:18px;text-align:center">●</span><span>Recent Activity</span>';
    }
    if (recent) {
        recent.classList.add('recent-nav-promoted');
        const logout = document.querySelector('.sidebar .logout');
        if (logout) logout.parentNode.insertBefore(recent, logout);
        const emptyGroup = recent.closest('.nav-group');
        if (emptyGroup && !emptyGroup.querySelector('.nav-link')) emptyGroup.remove();
    }

    // Insert the approved graph-led command center immediately after KPI cards.
    const stats = document.querySelector('.stats');
    const template = document.getElementById('customer-dashboard-insights-template');
    if (stats && template) stats.insertAdjacentElement('afterend', template.content.cloneNode(true).firstElementChild);
    if (stats && template) {
        const firstInsights = stats.nextElementSibling;
        if (firstInsights) firstInsights.insertAdjacentElement('afterend', template.content.cloneNode(true).lastElementChild);
    }

    // Move attention and quick actions to the bottom so the visual hierarchy starts with customer KPIs and analytics.
    const actionPanel = document.querySelector('[data-action-center-panel]');
    const quickActions = document.querySelector('.quick-actions');
    const content = document.querySelector('.content');
    if (content && (actionPanel || quickActions)) {
        const bottom = document.createElement('section');
        bottom.className = 'dashboard-bottom';
        if (actionPanel) bottom.appendChild(actionPanel);
        if (quickActions) bottom.appendChild(quickActions);
        content.appendChild(bottom);
    }

    if (actionPanel && customerActions === 0 && operationalAttention > 0) {
        actionPanel.classList.remove('is-clear');
        actionPanel.classList.add('operational-attention');
        const title = actionPanel.querySelector('.action-copy strong');
        const copy = actionPanel.querySelector('.action-copy p');
        const total = actionPanel.querySelector('.action-total');
        if (title) title.textContent = 'Service attention is being tracked by UNIFCO';
        if (copy) {
            const parts = [];
            if (overdueWorkOrders) parts.push(overdueWorkOrders + ' overdue work order' + (overdueWorkOrders === 1 ? '' : 's'));
            if (activeSlaRisks) parts.push(activeSlaRisks + ' active request SLA risk' + (activeSlaRisks === 1 ? '' : 's'));
            if (stoppedAssets) parts.push(stoppedAssets + ' stopped asset' + (stoppedAssets === 1 ? '' : 's'));
            copy.textContent = 'No customer decision is required right now. Operational follow-up: ' + parts.join(' · ') + '.';
        }
        if (total) { total.classList.remove('green'); total.textContent = '!'; }
    }

    document.querySelectorAll('.stat').forEach(function (stat) {
        const label = stat.querySelector('.label');
        const value = stat.querySelector('.value');
        if (!label || !/SLA/i.test(label.textContent || '')) return;
        if (value && /N\/A/i.test(value.textContent || '')) {
            const trend = stat.querySelector('.trend');
            if (trend) trend.textContent = activeSlaRisks > 0
                ? activeSlaRisks + ' active stage SLA risk' + (activeSlaRisks === 1 ? '' : 's') + ' · performance not yet measurable'
                : 'No eligible completed SLA measurements yet';
        }
    });

    document.querySelectorAll('.pill,.portal-pill,.comparison,.badge').forEach(function (node) {
        const text = (node.textContent || '').trim();
        const match = text.match(/^(\d+)\s+SLA\s+overdue$/i);
        if (match) node.textContent = match[1] + ' active SLA risk' + (match[1] === '1' ? '' : 's');
    });

    document.querySelectorAll('.nav-label').forEach(function (node) {
        if ((node.textContent || '').trim().toUpperCase() === 'MY WORK') node.textContent = 'SERVICE & MAINTENANCE';
    });
    const roleNote = document.querySelector('.role-note');
    if (roleNote) roleNote.style.display = 'none';
});
</script>
@endif
