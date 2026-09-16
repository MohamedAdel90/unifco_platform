@include('customer.section-base')

@if(($section ?? null) === 'dashboard')
<style>
    /* Dashboard final QA: compact empty-state rows instead of stretching to the tallest card. */
    .lower-grid{align-items:start!important}
    .lower-grid>.card{align-self:start!important}
    .lower-grid .empty{padding:12px 16px!important;min-height:62px!important}
    .action-center.operational-attention{border-left-color:var(--amber)!important}
    .action-center.operational-attention .action-total{background:#fff3d9!important;color:#9b6500!important}
    .action-center.operational-attention .quick-icon{color:var(--amber)!important}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const customerActions = {{ (int) ($actionRequiredCount ?? 0) }};
    const overdueWorkOrders = {{ (int) ($overdueCount ?? 0) }};
    const activeSlaRisks = {{ (int) ($requestStageCounts['overdue'] ?? 0) }};
    const stoppedAssets = {{ (int) ($stoppedAssetCount ?? 0) }};
    const operationalAttention = overdueWorkOrders + activeSlaRisks + stoppedAssets;

    // Keep "Action Required" strictly for decisions the customer can actually take.
    // When UNIFCO has operational exceptions but the customer has no decision pending,
    // show a distinct operational-attention state instead of the contradictory green clear state.
    const actionPanel = document.querySelector('[data-action-center-panel]');
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
        if (total) {
            total.classList.remove('green');
            total.textContent = '!';
        }
    }

    // "SLA Performance" is a completed/eligible measurement. Current overdue workflow stages
    // are risks, not a measured compliance percentage, so label them separately.
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

    // Rename dashboard badges so active stage deadlines are not confused with measured SLA breaches.
    document.querySelectorAll('.pill,.portal-pill,.comparison,.badge').forEach(function (node) {
        const text = (node.textContent || '').trim();
        const match = text.match(/^(\d+)\s+SLA\s+overdue$/i);
        if (match) node.textContent = match[1] + ' active SLA risk' + (match[1] === '1' ? '' : 's');
    });

    // Final information architecture wording agreed for the unified customer account.
    document.querySelectorAll('.nav-label').forEach(function (node) {
        if ((node.textContent || '').trim().toUpperCase() === 'MY WORK') node.textContent = 'SERVICE & MAINTENANCE';
    });

    // The dashboard already explains the unified scope in its executive header; remove the duplicate banner.
    const roleNote = document.querySelector('.role-note');
    if (roleNote) roleNote.style.display = 'none';
});
</script>
@endif
