<?php

namespace App\Http\Controllers;

use App\Models\{Asset,CrmQuotation,Customer,FinancialDocument,MaintenancePlan,MaintenanceVisitReport,ServiceContract,ServiceRequest,WorkOrder};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerPortalController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = auth()->user();
        abort_unless($user && $user->role === 'CUSTOMER' && $user->customer_id, 403, 'Customer portal access is not configured for this user.');
        $customer = Customer::findOrFail($user->customer_id);

        $contractFilter = $request->integer('contract_id') ?: null;
        $assetFilter = $request->integer('asset_id') ?: null;
        $locationFilter = trim((string) $request->query('location', '')) ?: null;

        $contracts = ServiceContract::where('customer_id', $customer->id)->orderByDesc('starts_on')->get();
        $assetsQuery = Asset::where('customer_id', $customer->id);
        if ($assetFilter) $assetsQuery->whereKey($assetFilter);
        if ($locationFilter) $assetsQuery->where('location_code', $locationFilter);
        $assets = $assetsQuery->orderBy('asset_code')->get();
        $allCustomerAssetIds = Asset::where('customer_id', $customer->id)->pluck('id');
        $assetIds = $assets->pluck('id');

        $plans = MaintenancePlan::with('asset')->whereIn('asset_id', $assetIds)
            ->when($contractFilter, fn ($q) => $q->where('service_contract_id', $contractFilter))
            ->orderBy('next_due_date')->get();
        $workOrders = WorkOrder::with('asset')->whereIn('asset_id', $assetIds)
            ->when($contractFilter, fn ($q) => $q->where('service_contract_id', $contractFilter))
            ->latest('created_at')->limit(100)->get();
        $invoices = FinancialDocument::where('customer_id', $customer->id)->where('document_type', 'AR_INVOICE')->latest('document_date')->limit(100)->get();
        $payments = DB::table('payments')->join('financial_documents', 'financial_documents.id', '=', 'payments.financial_document_id')
            ->where('financial_documents.customer_id', $customer->id)->select('payments.*', 'financial_documents.document_no')->orderByDesc('payments.payment_date')->limit(100)->get();
        $materials = DB::table('maintenance_materials')->join('work_orders', 'work_orders.id', '=', 'maintenance_materials.work_order_id')
            ->join('assets', 'assets.id', '=', 'work_orders.asset_id')->join('items', 'items.id', '=', 'maintenance_materials.item_id')
            ->whereIn('assets.id', $assetIds)->select('maintenance_materials.*', 'work_orders.work_order_no', 'assets.asset_code', 'assets.name as asset_name', 'items.item_code', 'items.name as item_name')
            ->orderByDesc('maintenance_materials.created_at')->limit(100)->get();

        $requests = ServiceRequest::where('customer_id', $customer->id)
            ->when($contractFilter, fn ($q) => $q->where('service_contract_id', $contractFilter))
            ->when($assetFilter, fn ($q) => $q->where('asset_id', $assetFilter))
            ->latest()->limit(100)->get();
        $quotations = CrmQuotation::where('customer_id', $customer->id)->latest('quotation_date')->limit(50)->get();
        $visitReports = MaintenanceVisitReport::where('customer_id', $customer->id)
            ->whereIn('asset_id', $assetIds)->when($contractFilter, fn ($q) => $q->where('service_contract_id', $contractFilter))
            ->latest('visit_date')->limit(100)->get();
        $attachments = DB::table('maintenance_attachments')->where('customer_id', $customer->id)->whereIn('asset_id', $assetIds)->latest()->limit(100)->get();

        $alerts = collect();
        foreach ($plans->whereNotNull('next_due_date')->filter(fn ($p) => $p->next_due_date->lte(now()->addDays(30))) as $p) {
            $alerts->push((object) ['type' => 'MAINTENANCE_DUE', 'title' => 'صيانة مستحقة · Maintenance due: '.$p->plan_no, 'due_date' => $p->next_due_date, 'severity' => $p->next_due_date->isPast() ? 'HIGH' : 'INFO']);
        }
        foreach ($invoices->filter(fn ($i) => $i->open_amount > 0 && $i->due_date && $i->due_date->lte(now()->addDays(14))) as $i) {
            $alerts->push((object) ['type' => 'INVOICE_DUE', 'title' => 'Invoice due: '.$i->document_no, 'due_date' => $i->due_date, 'severity' => $i->due_date->isPast() ? 'HIGH' : 'INFO']);
        }
        foreach ($contracts->filter(fn ($c) => $c->ends_on && $c->ends_on->lte(now()->addDays(60))) as $c) {
            $alerts->push((object) ['type' => 'CONTRACT_EXPIRY', 'title' => 'Contract expiring: '.$c->contract_no, 'due_date' => $c->ends_on, 'severity' => 'INFO']);
        }

        $completeStatuses = ['COMPLETED', 'CLOSED'];
        $inProgressStatuses = ['IN_PROGRESS', 'IN PROGRESS', 'STARTED', 'ASSIGNED'];
        $openWorkOrders = $workOrders->whereNotIn('status', $completeStatuses)->count();
        $inProgressCount = $workOrders->filter(fn ($w) => in_array(strtoupper((string) $w->status), $inProgressStatuses, true))->count();
        $completedCount = $workOrders->filter(fn ($w) => in_array(strtoupper((string) $w->status), $completeStatuses, true))->count();
        $overdueCount = $workOrders->filter(fn ($w) => $w->planned_start && $w->planned_start->isPast() && ! in_array(strtoupper((string) $w->status), $completeStatuses, true))->count();
        $recentWorkOrders = $workOrders->take(4);
        $upcomingPlans = $plans->whereNotNull('next_due_date')->filter(fn ($p) => $p->next_due_date->gte(today()))->take(2);
        $workOrderTotal = max(1, $workOrders->count());
        $slaPerformance = $workOrders->isEmpty() ? 100 : (int) round(($completedCount / $workOrderTotal) * 100);
        $preventiveCount = $workOrders->where('maintenance_type', 'PREVENTIVE')->count();
        $correctiveCount = $workOrders->where('maintenance_type', 'CORRECTIVE')->count();
        $openInvoiceAmount = $invoices->sum(fn ($i) => (float) $i->open_amount);
        $locations = Asset::where('customer_id', $customer->id)->whereNotNull('location_code')->distinct()->orderBy('location_code')->pluck('location_code');
        $warrantyParts = Asset::whereIn('id', $allCustomerAssetIds)->orderBy('warranty_expiry')->get();

        return view('customer.portal', compact(
            'customer', 'contracts', 'assets', 'plans', 'workOrders', 'invoices', 'payments', 'materials', 'requests', 'quotations', 'visitReports', 'attachments', 'alerts', 'locations', 'warrantyParts',
            'openInvoiceAmount', 'openWorkOrders', 'inProgressCount', 'completedCount', 'overdueCount', 'recentWorkOrders', 'upcomingPlans', 'slaPerformance', 'preventiveCount', 'correctiveCount',
            'contractFilter', 'assetFilter', 'locationFilter'
        ));
    }
}
