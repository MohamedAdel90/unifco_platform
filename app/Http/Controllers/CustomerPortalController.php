<?php

namespace App\Http\Controllers;

use App\Models\{Asset, CrmQuotation, Customer, CustomerActivityEvent, CustomerSite, FinancialDocument, MaintenancePlan, MaintenanceVisitReport, ServiceContract, ServiceRequest, User, WorkOrder};
use App\Services\{AuthorizationService, CustomerPortalAccessService};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Http\Response;
use Illuminate\Support\Facades\{DB, Schema};

class CustomerPortalController extends Controller
{
    private const WORKFLOW_ROLES = [
        'MAINTENANCE_ENGINEER','MAINTENANCE_MANAGER','PROCUREMENT','TENDERS_CONTRACTS','FINANCE_MANAGER','PROJECT_MANAGER','CEO',
    ];

    public function __construct(private AuthorizationService $authorization) {}

    public function __invoke(Request $request, CustomerPortalAccessService $access, ?string $section = null): Response|RedirectResponse
    {
        $user = $request->user();
        if (! $user || $user->role !== 'CUSTOMER' || ! $user->customer_id) {
            if ($user && ($home = $this->homeRoute($user))) {
                return redirect()->route($home)->with('status', 'The customer portal is available to customer accounts only.');
            }
            abort(403, 'Customer portal access is not configured for this user.');
        }

        $customer = Customer::whereKey($user->customer_id)->where('tenant_id', $user->tenant_id)->firstOrFail();

        // Keep one locale for the complete customer portal. Explicit ?lang=ar|en
        // changes the preference; ordinary sidebar navigation inherits it.
        $requestedLocale = $request->query('lang');
        if (in_array($requestedLocale, ['ar', 'en'], true)) {
            $request->session()->put('customer_portal_locale', $requestedLocale);
        }
        $locale = in_array($requestedLocale, ['ar', 'en'], true)
            ? $requestedLocale
            : $request->session()->get('customer_portal_locale', 'en');
        app()->setLocale($locale);

        $section = $section ?: 'dashboard';
        abort_unless($access->canSection($user, $section), 403, 'This section is not available for this customer account.');

        $portalRole = $access->role($user);
        $allowedSections = $access->allowedSections($user);
        $canCreateRequest = $access->canCreateServiceRequest($user);
        $canDecideQuotation = $access->canDecideQuotation($user);
        $canManageUsers = $access->canManageUsers($user);
        $readOnly = $access->isReadOnly($user);

        $scopedSiteIds = $access->accessibleSiteIds($user);
        $scopedContractIds = $access->accessibleContractIds($user);
        $scopedAssetIds = $access->accessibleAssetIds($user);

        $siteFilter = $request->integer('site_id') ?: null;
        $contractFilter = $request->integer('contract_id') ?: null;
        $assetFilter = $request->integer('asset_id') ?: null;
        $locationFilter = trim((string) $request->query('location', '')) ?: null;
        $statusFilter = trim((string) $request->query('status', '')) ?: null;
        $priorityFilter = trim((string) $request->query('priority', '')) ?: null;
        $searchFilter = trim((string) $request->query('q', '')) ?: null;
        $days = in_array($request->integer('days'), [7, 30, 90, 365], true) ? $request->integer('days') : 30;

        if ($contractFilter) {
            $access->assertContract($user, $contractFilter);
        }
        if ($assetFilter) {
            $access->assertAsset($user, $assetFilter);
        }
        if ($siteFilter && $scopedSiteIds !== null) {
            abort_unless($scopedSiteIds->contains($siteFilter), 404);
        }

        $sitesQuery = CustomerSite::where('customer_id', $customer->id);
        if ($scopedSiteIds !== null) {
            $sitesQuery->whereIn('id', $scopedSiteIds);
        }
        $sites = $sitesQuery->orderBy('name')->get();
        if ($siteFilter) {
            abort_unless($sites->contains('id', $siteFilter), 404);
        }

        $contractsQuery = ServiceContract::where('customer_id', $customer->id);
        if ($scopedContractIds !== null) {
            $contractsQuery->whereIn('id', $scopedContractIds);
        }
        $contracts = $contractsQuery->orderByDesc('starts_on')->get();

        $assetsQuery = Asset::with('site')->where('customer_id', $customer->id);
        if ($scopedAssetIds !== null) {
            $assetsQuery->whereIn('id', $scopedAssetIds);
        }
        $assetsQuery->when($siteFilter, fn ($q) => $q->where('customer_site_id', $siteFilter))
            ->when($assetFilter, fn ($q) => $q->whereKey($assetFilter))
            ->when($locationFilter, fn ($q) => $q->where('location_code', $locationFilter));
        $assets = $assetsQuery->orderBy('asset_code')->get();
        $assetIds = $assets->pluck('id');

        $plans = MaintenancePlan::with('asset.site')->whereIn('asset_id', $assetIds)
            ->when($contractFilter, fn ($q) => $q->where('service_contract_id', $contractFilter))
            ->when($scopedContractIds !== null, fn ($q) => $q->where(function ($inner) use ($scopedContractIds) {
                $inner->whereNull('service_contract_id')->orWhereIn('service_contract_id', $scopedContractIds);
            }))
            ->orderBy('next_due_date')->get();

        $trendStart = now()->copy()->startOfMonth()->subMonths(5);
        $workOrdersQuery = WorkOrder::with('asset.site')->whereIn('asset_id', $assetIds)
            ->when($contractFilter, fn ($q) => $q->where('service_contract_id', $contractFilter))
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->when($priorityFilter, fn ($q) => $q->where('priority', $priorityFilter))
            ->when($searchFilter, fn ($q) => $q->where(function ($inner) use ($searchFilter) {
                $inner->where('work_order_no', 'like', '%'.$searchFilter.'%')
                    ->orWhereHas('asset', fn ($asset) => $asset->where('asset_code', 'like', '%'.$searchFilter.'%')->orWhere('name', 'like', '%'.$searchFilter.'%'));
            }))
            ->when($scopedContractIds !== null, fn ($q) => $q->where(function ($inner) use ($scopedContractIds) {
                $inner->whereNull('service_contract_id')->orWhereIn('service_contract_id', $scopedContractIds);
            }));
        $currentWorkOrderPeriodCount = (clone $workOrdersQuery)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
        $previousWorkOrderCount = (clone $workOrdersQuery)
            ->whereBetween('created_at', [now()->subDays($days * 2), now()->subDays($days)])
            ->count();
        $monthlyWorkOrderActivity = $section === 'dashboard'
            ? (clone $workOrdersQuery)->where('created_at', '>=', $trendStart)->get(['id', 'created_at'])
            : collect();
        if ($section !== 'dashboard' && $request->filled('days')) {
            $workOrdersQuery->where('created_at', '>=', now()->subDays($days));
        }
        $workOrders = $workOrdersQuery->latest('created_at')->limit(100)->get();
        $workOrders->each(function (WorkOrder $workOrder): void {
            foreach (['labor_cost', 'material_cost', 'external_cost', 'total_cost'] as $field) {
                $workOrder->setAttribute($field, null);
            }
        });

        $requestQuery = ServiceRequest::where('customer_id', $customer->id);
        if ($scopedAssetIds !== null) {
            $requestQuery->where(fn ($q) => $q->whereNull('asset_id')->orWhereIn('asset_id', $scopedAssetIds));
        }
        if ($scopedContractIds !== null) {
            $requestQuery->where(fn ($q) => $q->whereNull('service_contract_id')->orWhereIn('service_contract_id', $scopedContractIds));
        }
        $requestQuery->when($siteFilter, fn ($q) => $q->where('customer_site_id', $siteFilter))
            ->when($contractFilter, fn ($q) => $q->where('service_contract_id', $contractFilter))
            ->when($assetFilter, fn ($q) => $q->where('asset_id', $assetFilter));
        $currentRequestPeriodCount = (clone $requestQuery)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
        $previousRequestCount = (clone $requestQuery)
            ->whereBetween('created_at', [now()->subDays($days * 2), now()->subDays($days)])
            ->count();
        $monthlyRequestActivity = $section === 'dashboard'
            ? (clone $requestQuery)->where('created_at', '>=', $trendStart)->get(['id', 'created_at'])
            : collect();
        if ($section !== 'dashboard' && $request->filled('days')) {
            $requestQuery->where('created_at', '>=', now()->subDays($days));
        }
        $requests = $requestQuery->latest()->limit(100)->get();

        $monthlyActivity = collect(range(5, 0))->map(function (int $offset) use ($requests, $workOrders, $monthlyRequestActivity, $monthlyWorkOrderActivity, $section) {
            $month = now()->copy()->subMonths($offset);
            $requestActivity = $section === 'dashboard' ? $monthlyRequestActivity : $requests;
            $workOrderActivity = $section === 'dashboard' ? $monthlyWorkOrderActivity : $workOrders;

            return [
                'label' => $month->format('M'),
                'requests' => $requestActivity->filter(fn ($item) => optional($item->created_at)->format('Y-m') === $month->format('Y-m'))->count(),
                'work_orders' => $workOrderActivity->filter(fn ($item) => optional($item->created_at)->format('Y-m') === $month->format('Y-m'))->count(),
            ];
        })->values();

        $quotations = CrmQuotation::where('customer_id', $customer->id)->latest('quotation_date')->limit(50)->get();
        $invoices = FinancialDocument::where('customer_id', $customer->id)->where('document_type', 'AR_INVOICE')->latest('document_date')->limit(100)->get();
        $payments = DB::table('payments')->join('financial_documents', 'financial_documents.id', '=', 'payments.financial_document_id')
            ->where('financial_documents.customer_id', $customer->id)->select('payments.*', 'financial_documents.document_no')
            ->orderByDesc('payments.payment_date')->limit(100)->get();
        $materials = DB::table('maintenance_materials')->join('work_orders', 'work_orders.id', '=', 'maintenance_materials.work_order_id')
            ->join('assets', 'assets.id', '=', 'work_orders.asset_id')->join('items', 'items.id', '=', 'maintenance_materials.item_id')
            ->whereIn('assets.id', $assetIds)
            ->select('maintenance_materials.id', 'maintenance_materials.work_order_id', 'maintenance_materials.item_id', 'maintenance_materials.warehouse_code', 'maintenance_materials.quantity', 'maintenance_materials.created_at', 'work_orders.work_order_no', 'assets.asset_code', 'assets.name as asset_name', 'items.item_code', 'items.name as item_name', 'items.uom')
            ->orderByDesc('maintenance_materials.created_at')->limit(100)->get();

        $timeline = Schema::hasTable('customer_activity_events')
            ? CustomerActivityEvent::where('customer_id', $customer->id)->whereIn('visibility', ['BOTH', 'CUSTOMER'])->latest()->limit(100)->get()
            : collect();
        $visitReports = MaintenanceVisitReport::where('customer_id', $customer->id)->whereIn('asset_id', $assetIds)
            ->when($contractFilter, fn ($q) => $q->where('service_contract_id', $contractFilter))
            ->latest('visit_date')->limit(100)->get();
        $attachments = DB::table('maintenance_attachments')->where('customer_id', $customer->id)->whereIn('asset_id', $assetIds)->latest()->limit(100)->get();

        $alerts = collect();
        foreach ($plans->whereNotNull('next_due_date')->filter(fn ($plan) => $plan->next_due_date->lte(now()->addDays(30))) as $plan) {
            $alerts->push((object) ['type' => 'MAINTENANCE_DUE', 'title' => 'Maintenance due: '.$plan->plan_no, 'due_date' => $plan->next_due_date, 'severity' => $plan->next_due_date->isPast() ? 'HIGH' : 'INFO']);
        }
        foreach ($invoices->filter(fn ($invoice) => $invoice->open_amount > 0 && $invoice->due_date && $invoice->due_date->lte(now()->addDays(14))) as $invoice) {
            $alerts->push((object) ['type' => 'INVOICE_DUE', 'title' => 'Invoice due: '.$invoice->document_no, 'due_date' => $invoice->due_date, 'severity' => $invoice->due_date->isPast() ? 'HIGH' : 'INFO']);
        }
        foreach ($contracts->filter(fn ($contract) => $contract->ends_on && $contract->ends_on->lte(now()->addDays(60))) as $contract) {
            $alerts->push((object) ['type' => 'CONTRACT_EXPIRY', 'title' => 'Contract expiring: '.$contract->contract_no, 'due_date' => $contract->ends_on, 'severity' => 'INFO']);
        }

        $completeStatuses = ['COMPLETED', 'CLOSED'];
        $inProgressStatuses = ['IN_PROGRESS', 'IN PROGRESS', 'STARTED', 'ASSIGNED'];
        $openWorkOrders = $workOrders->filter(fn ($workOrder) => ! in_array(strtoupper((string) $workOrder->status), $completeStatuses, true))->count();
        $inProgressCount = $workOrders->filter(fn ($workOrder) => in_array(strtoupper((string) $workOrder->status), $inProgressStatuses, true))->count();
        $completedCount = $workOrders->filter(fn ($workOrder) => in_array(strtoupper((string) $workOrder->status), $completeStatuses, true))->count();
        $overdueCount = $workOrders->filter(fn ($workOrder) => $workOrder->planned_start && $workOrder->planned_start->isPast() && ! in_array(strtoupper((string) $workOrder->status), $completeStatuses, true))->count();
        $criticalWorkOrders = $workOrders->filter(function ($workOrder) use ($completeStatuses) {
            $isOpen = ! in_array(strtoupper((string) $workOrder->status), $completeStatuses, true);
            $isCritical = in_array(strtoupper((string) $workOrder->priority), ['HIGH', 'URGENT', 'EMERGENCY', 'CRITICAL'], true);
            $isOverdue = $workOrder->planned_start && $workOrder->planned_start->isPast();

            return $isOpen && ($isCritical || $isOverdue);
        })->sortBy(fn ($workOrder) => $workOrder->planned_start?->timestamp ?? PHP_INT_MAX)->take(5);

        $recentWorkOrders = $workOrders->take(5);
        $recentRequests = $requests->take(6);
        $upcomingPlans = $plans->whereNotNull('next_due_date')->filter(fn ($plan) => $plan->next_due_date->gte(today()))->take(5);
        $visitsDue7Count = $plans->whereNotNull('next_due_date')->filter(fn ($plan) => $plan->next_due_date->between(today(), today()->addDays(7)))->count();
        $visitsDue30Count = $plans->whereNotNull('next_due_date')->filter(fn ($plan) => $plan->next_due_date->between(today(), today()->addDays(30)))->count();
        $openInvoiceAmount = $invoices->sum(fn ($invoice) => (float) $invoice->open_amount);
        $openRequestCount = $requests->whereNotIn('status', ['CLOSED', 'REJECTED', 'CANCELLED'])->count();
        $pendingQuotationCount = $quotations->whereIn('status', ['DRAFT', 'SENT', 'UNDER_REVIEW', 'REVISION_REQUESTED'])->count();
        $activeContractCount = $contracts->where('status', 'ACTIVE')->count();
        $preventiveCount = $workOrders->where('maintenance_type', 'PREVENTIVE')->count();
        $correctiveCount = $workOrders->where('maintenance_type', 'CORRECTIVE')->count();

        $slaChecks = collect();
        foreach ($requests as $serviceRequest) {
            if ($serviceRequest->response_sla_minutes && $serviceRequest->responded_at) {
                $slaChecks->push($serviceRequest->responded_at->lte($serviceRequest->created_at->copy()->addMinutes($serviceRequest->response_sla_minutes)));
            }
            if ($serviceRequest->resolution_sla_minutes && $serviceRequest->resolved_at) {
                $slaChecks->push($serviceRequest->resolved_at->lte($serviceRequest->created_at->copy()->addMinutes($serviceRequest->resolution_sla_minutes)));
            }
        }
        $slaPerformance = $slaChecks->isEmpty() ? null : (int) round(($slaChecks->filter()->count() / $slaChecks->count()) * 100);
        $slaBreachCount = $slaChecks->reject(fn ($passed) => $passed)->count();

        $requestStageCounts = [
            'new' => $requests->filter(fn ($item) => in_array(strtoupper((string) $item->workflow_stage), ['NEW', 'SUBMITTED'], true))->count(),
            'review' => $requests->filter(fn ($item) => str_contains(strtoupper((string) $item->workflow_stage), 'REVIEW'))->count(),
            'assigned' => $requests->filter(fn ($item) => strtoupper((string) $item->workflow_stage) === 'ASSIGNED')->count(),
            'scheduled' => $requests->filter(fn ($item) => in_array(strtoupper((string) $item->workflow_stage), ['SCHEDULED', 'VISIT_SCHEDULED'], true))->count(),
            'dispatch' => $requests->filter(fn ($item) => str_contains(strtoupper((string) $item->workflow_stage), 'DISPATCH') || str_contains(strtoupper((string) $item->workflow_stage), 'ON_THE_WAY'))->count(),
            'progress' => $requests->filter(fn ($item) => in_array(strtoupper((string) $item->workflow_stage), ['IN_PROGRESS', 'EXECUTION'], true))->count(),
            'customer' => $requests->filter(fn ($item) => str_contains(strtoupper((string) $item->next_action), 'CUSTOMER'))->count(),
            'closed' => $requests->filter(fn ($item) => in_array(strtoupper((string) $item->status), ['COMPLETED', 'CLOSED'], true))->count(),
            'overdue' => $requests->filter(fn ($item) => $item->current_stage_due_at && $item->current_stage_due_at->isPast() && ! in_array(strtoupper((string) $item->status), ['COMPLETED', 'CLOSED', 'CANCELLED', 'REJECTED'], true))->count(),
        ];

        $activeAssetCount = $assets->filter(fn ($asset) => in_array(strtoupper((string) ($asset->operational_status ?: $asset->status)), ['ACTIVE', 'REGISTERED', 'OPERATIONAL', 'IN_SERVICE', 'RUNNING'], true))->count();
        $maintenanceAssetCount = $assets->filter(fn ($asset) => str_contains(strtoupper((string) ($asset->operational_status ?: $asset->status)), 'MAINTENANCE'))->count();
        $stoppedAssetCount = $assets->filter(fn ($asset) => in_array(strtoupper((string) ($asset->operational_status ?: $asset->status)), ['STOPPED', 'FAILED', 'OUT_OF_SERVICE', 'DOWN'], true))->count();
        $criticalAssetCount = $assets->filter(fn ($asset) => in_array(strtoupper((string) $asset->criticality), ['HIGH', 'CRITICAL'], true))->count();
        $warrantyExpiringCount = $assets->filter(fn ($asset) => $asset->warranty_expiry && $asset->warranty_expiry->between(today(), today()->addDays(60)))->count();

        $quotationActionCount = $canDecideQuotation ? $quotations->whereIn('status', ['SENT', 'UNDER_REVIEW', 'REVISION_REQUESTED'])->count() : 0;
        $workAcceptanceActionCount = 0;
        if ($access->canAcceptWork($user)) {
            $workAcceptanceQuery = WorkOrder::whereIn('asset_id', $assetIds)->where('status', 'COMPLETED')
                ->whereNull('customer_accepted_at')->whereNull('customer_rejected_at');
            if ($contractFilter) {
                $workAcceptanceQuery->where('service_contract_id', $contractFilter);
            }
            $workAcceptanceActionCount = $workAcceptanceQuery->count();
        }
        $invoiceActionCount = $invoices->filter(fn ($invoice) => $invoice->open_amount > 0 && $invoice->due_date && $invoice->due_date->lte(now()->addDays(14)))->count();
        $renewalActionCount = $contracts->filter(fn ($contract) => $contract->status === 'ACTIVE' && $contract->ends_on && $contract->ends_on->lte(now()->addDays(60)))->count();

        $unreadInbox = 0;
        $inboxReady = Schema::hasTable('customer_messages') && Schema::hasTable('customer_conversations');
        if ($inboxReady) {
            $unreadInbox = DB::table('customer_messages')->join('customer_conversations', 'customer_conversations.id', '=', 'customer_messages.conversation_id')
                ->where('customer_conversations.customer_id', $customer->id)->where('customer_messages.sender_side', 'UNIFCO')->whereNull('customer_messages.read_at')->count();
        }
        $actionRequiredCount = $quotationActionCount + $workAcceptanceActionCount + $invoiceActionCount + $renewalActionCount + $unreadInbox;

        $requestVolumeDelta = $currentRequestPeriodCount - $previousRequestCount;
        $workOrderVolumeDelta = $currentWorkOrderPeriodCount - $previousWorkOrderCount;
        $requestVolumeChange = $previousRequestCount === 0
            ? ($currentRequestPeriodCount === 0 ? 0 : null)
            : (int) round(($requestVolumeDelta / $previousRequestCount) * 100);
        $workOrderVolumeChange = $previousWorkOrderCount === 0
            ? ($currentWorkOrderPeriodCount === 0 ? 0 : null)
            : (int) round(($workOrderVolumeDelta / $previousWorkOrderCount) * 100);
        $lastUpdatedAt = collect([$timeline->max('created_at'), $requests->max('updated_at'), $workOrders->max('updated_at')])
            ->filter()->map(fn ($date) => \Illuminate\Support\Carbon::parse($date))->sortByDesc(fn ($date) => $date->timestamp)->first();
        $dashboardHealth = $overdueCount > 0 || $stoppedAssetCount > 0 || $requestStageCounts['overdue'] > 0
            ? 'NEEDS ATTENTION'
            : ($actionRequiredCount > 0 ? 'FOLLOW-UP REQUIRED' : 'STABLE');

        $locations = $assets->pluck('location_code')->filter()->unique()->sort()->values();
        $warrantyParts = $assets->sortBy('warranty_expiry')->values();

        return response()->view('customer.section', compact(
            'section', 'customer', 'sites', 'contracts', 'assets', 'plans', 'workOrders', 'invoices', 'payments', 'materials',
            'requests', 'quotations', 'timeline', 'visitReports', 'attachments', 'alerts', 'locations', 'warrantyParts',
            'openInvoiceAmount', 'openWorkOrders', 'openRequestCount', 'pendingQuotationCount', 'activeContractCount',
            'inProgressCount', 'completedCount', 'overdueCount', 'criticalWorkOrders', 'recentWorkOrders', 'recentRequests',
            'upcomingPlans', 'slaPerformance', 'preventiveCount', 'correctiveCount', 'siteFilter', 'contractFilter',
            'assetFilter', 'locationFilter', 'days', 'unreadInbox', 'inboxReady', 'portalRole', 'allowedSections',
            'canCreateRequest', 'canDecideQuotation', 'canManageUsers', 'readOnly', 'requestStageCounts', 'activeAssetCount',
            'maintenanceAssetCount', 'stoppedAssetCount', 'criticalAssetCount', 'warrantyExpiringCount',
            'quotationActionCount', 'workAcceptanceActionCount', 'invoiceActionCount', 'renewalActionCount', 'actionRequiredCount',
            'statusFilter', 'priorityFilter', 'searchFilter', 'previousRequestCount', 'previousWorkOrderCount',
            'requestVolumeDelta', 'workOrderVolumeDelta', 'requestVolumeChange', 'workOrderVolumeChange', 'monthlyActivity', 'slaBreachCount', 'visitsDue7Count', 'visitsDue30Count',
            'lastUpdatedAt', 'dashboardHealth', 'locale'
        ))->header('X-UNIFCO-Customer-Portal-Release', 'customer-portal-rbac-phase1-20260827; customer-command-center-20260914; customer-unified-account-20260915; customer-dashboard-v2-20260915')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Best-home routing for authenticated users who are not a customer account.
     * Mirrors the post-login routing in AuthController so internal roles always
     * land somewhere they can use instead of a dead-end 403.
     */
    private function homeRoute(User $user): ?string
    {
        if ($this->authorization->allows($user, 'system.dashboard.view')) {
            return 'system-admin.dashboard';
        }
        if ($user->hasRole('OPERATIONS_MANAGER')) {
            return 'operations-manager.dashboard';
        }
        if ($user->role === 'STOREKEEPER') {
            return 'inventory.warehouse.index';
        }
        if (in_array($user->role, self::WORKFLOW_ROLES, true)) {
            return 'workflow.workspace';
        }
        return 'dashboard';
    }
}
