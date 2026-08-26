<?php

namespace App\Http\Controllers;

use App\Models\{Asset,CrmQuotation,Customer,CustomerActivityEvent,FinancialDocument,MaintenancePlan,MaintenanceVisitReport,ServiceContract,ServiceRequest,WorkOrder};
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerPortalController extends Controller
{
    public function __invoke(Request $request, ?string $section = null): Response
    {
        $user = auth()->user();
        abort_unless($user && $user->role === 'CUSTOMER' && $user->customer_id, 403, 'Customer portal access is not configured for this user.');
        $customer = Customer::findOrFail($user->customer_id);
        $section = $section ?: 'dashboard';

        $allowedSections = ['dashboard','requests','quotations','timeline','contracts','assets','work-orders','maintenance','invoices','reports','sla','documents','notifications'];
        abort_unless(in_array($section, $allowedSections, true), 404);

        $contractFilter = $request->integer('contract_id') ?: null;
        $assetFilter = $request->integer('asset_id') ?: null;
        $locationFilter = trim((string) $request->query('location', '')) ?: null;

        $contracts = ServiceContract::where('customer_id', $customer->id)->orderByDesc('starts_on')->get();
        $assetsQuery = Asset::with('site')->where('customer_id', $customer->id);
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

        $workOrders->each(function (WorkOrder $workOrder): void {
            $workOrder->setAttribute('labor_cost', null);
            $workOrder->setAttribute('material_cost', null);
            $workOrder->setAttribute('external_cost', null);
            $workOrder->setAttribute('total_cost', null);
        });

        $invoices = FinancialDocument::where('customer_id', $customer->id)->where('document_type', 'AR_INVOICE')->latest('document_date')->limit(100)->get();
        $payments = DB::table('payments')->join('financial_documents', 'financial_documents.id', '=', 'payments.financial_document_id')
            ->where('financial_documents.customer_id', $customer->id)->select('payments.*', 'financial_documents.document_no')->orderByDesc('payments.payment_date')->limit(100)->get();
        $materials = DB::table('maintenance_materials')->join('work_orders', 'work_orders.id', '=', 'maintenance_materials.work_order_id')
            ->join('assets', 'assets.id', '=', 'work_orders.asset_id')->join('items', 'items.id', '=', 'maintenance_materials.item_id')
            ->whereIn('assets.id', $assetIds)
            ->select('maintenance_materials.id','maintenance_materials.work_order_id','maintenance_materials.item_id','maintenance_materials.warehouse_code','maintenance_materials.quantity','maintenance_materials.created_at',
                'work_orders.work_order_no', 'assets.asset_code', 'assets.name as asset_name', 'items.item_code', 'items.name as item_name','items.uom')
            ->orderByDesc('maintenance_materials.created_at')->limit(100)->get();

        $requests = ServiceRequest::where('customer_id', $customer->id)
            ->when($contractFilter, fn ($q) => $q->where('service_contract_id', $contractFilter))
            ->when($assetFilter, fn ($q) => $q->where('asset_id', $assetFilter))
            ->latest()->limit(100)->get();
        $quotations = CrmQuotation::where('customer_id', $customer->id)->latest('quotation_date')->limit(50)->get();
        $timeline = Schema::hasTable('customer_activity_events')
            ? CustomerActivityEvent::where('customer_id', $customer->id)->whereIn('visibility', ['BOTH','CUSTOMER'])->latest()->limit(100)->get()
            : collect();
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
        $recentRequests = $requests->take(5);
        $upcomingPlans = $plans->whereNotNull('next_due_date')->filter(fn ($p) => $p->next_due_date->gte(today()))->take(4);
        $workOrderTotal = max(1, $workOrders->count());
        $slaPerformance = $workOrders->isEmpty() ? 100 : (int) round(($completedCount / $workOrderTotal) * 100);
        $preventiveCount = $workOrders->where('maintenance_type', 'PREVENTIVE')->count();
        $correctiveCount = $workOrders->where('maintenance_type', 'CORRECTIVE')->count();
        $openInvoiceAmount = $invoices->sum(fn ($i) => (float) $i->open_amount);
        $openRequestCount = $requests->whereNotIn('status', ['CLOSED','REJECTED','CANCELLED'])->count();
        $pendingQuotationCount = $quotations->whereIn('status', ['DRAFT','SENT','UNDER_REVIEW','REVISION_REQUESTED'])->count();
        $activeContractCount = $contracts->where('status', 'ACTIVE')->count();
        $locations = Asset::where('customer_id', $customer->id)->whereNotNull('location_code')->distinct()->orderBy('location_code')->pluck('location_code');
        $warrantyParts = Asset::whereIn('id', $allCustomerAssetIds)->orderBy('warranty_expiry')->get();

        $inboxReady = Schema::hasTable('customer_messages') && Schema::hasTable('customer_conversations');
        $unreadInbox = 0;
        if ($inboxReady) {
            $unreadInbox = DB::table('customer_messages')
                ->join('customer_conversations','customer_conversations.id','=','customer_messages.conversation_id')
                ->where('customer_conversations.customer_id',$customer->id)
                ->where('customer_messages.sender_side','UNIFCO')
                ->whereNull('customer_messages.read_at')
                ->count();
        }

        $html = view('customer.section', compact(
            'section', 'customer', 'contracts', 'assets', 'plans', 'workOrders', 'invoices', 'payments', 'materials', 'requests', 'quotations', 'timeline', 'visitReports', 'attachments', 'alerts', 'locations', 'warrantyParts',
            'openInvoiceAmount', 'openWorkOrders', 'openRequestCount', 'pendingQuotationCount', 'activeContractCount', 'inProgressCount', 'completedCount', 'overdueCount', 'recentWorkOrders', 'recentRequests', 'upcomingPlans', 'slaPerformance', 'preventiveCount', 'correctiveCount',
            'contractFilter', 'assetFilter', 'locationFilter', 'unreadInbox', 'inboxReady'
        ))->render();

        if ($section === 'dashboard') {
            $emergencyUrl = route('customer.section', 'work-orders').'?priority=EMERGENCY#request-service';
            $partsUrl = route('customer.section', 'work-orders').'?service_category=Spare%20Parts&priority=HIGH#request-service';
            $quickCss = '<style>.quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}.quick-action{display:flex;align-items:center;gap:12px;padding:13px 15px;border-radius:10px;background:linear-gradient(135deg,#e20b24,#b8071b);color:#fff;box-shadow:0 8px 18px rgba(226,11,36,.16)}.quick-action.parts{background:linear-gradient(135deg,#a8071a,#e20b24)}.quick-icon{width:38px;height:38px;border-radius:9px;background:#ffffff18;display:grid;place-items:center;font-size:18px}.quick-action b{display:block;font-size:12px}.quick-action small{display:block;margin-top:2px;color:#ffe5e9;font-size:9px}@media(max-width:760px){.quick-actions{grid-template-columns:1fr}}</style>';
            $quickActions = '<section class="quick-actions"><a class="quick-action" href="'.$emergencyUrl.'"><span class="quick-icon">⚡</span><span><b>طلب صيانة طارئة · Emergency Maintenance</b><small>Open a high-priority maintenance request</small></span></a><a class="quick-action parts" href="'.$partsUrl.'"><span class="quick-icon">⚙</span><span><b>طلب قطع غيار · Spare Parts Request</b><small>Request parts for an assigned asset</small></span></a></section>';
            $html = str_replace('</head>', $quickCss.'</head>', $html);
            $html = str_replace('<section class="stats">', $quickActions.'<section class="stats">', $html);
        }

        if ($section === 'assets') {
            $assetLinks = $assets->mapWithKeys(fn (Asset $asset) => [$asset->asset_code => route('customer.asset.show',$asset)]);
            $json = json_encode($assetLinks, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $script = '<script>document.addEventListener("DOMContentLoaded",function(){const links='.$json.';document.querySelectorAll(".asset").forEach(function(card){const title=card.querySelector("strong");if(!title)return;const code=title.textContent.split("·")[0].trim();if(!links[code])return;card.style.cursor="pointer";card.setAttribute("role","link");card.addEventListener("click",function(){location.href=links[code]});const a=document.createElement("a");a.className="pill";a.href=links[code];a.textContent="Asset 360 · تفاصيل الأصل";a.addEventListener("click",function(e){e.stopPropagation()});card.appendChild(a);});});</script>';
            $html = str_replace('</body>', $script.'</body>', $html);
        }

        if ($section === 'work-orders') {
            $workOrderLinks = $workOrders->mapWithKeys(fn (WorkOrder $wo) => [$wo->work_order_no => route('customer.work-orders.show',$wo)]);
            $json = json_encode($workOrderLinks, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $script = '<script>document.addEventListener("DOMContentLoaded",function(){const links='.$json.';document.querySelectorAll("table.table tbody tr").forEach(function(row){const cell=row.querySelector("td");if(!cell)return;const no=cell.textContent.trim();if(!links[no])return;cell.innerHTML="<a class=\"pill\" href=\""+links[no]+"\">"+no+" · View Report</a>";});const table=document.querySelector("table.table");if(table){const heads=table.querySelectorAll("thead th");if(heads.length>=6)heads[5].style.display="none";table.querySelectorAll("tbody tr").forEach(function(row){const cells=row.querySelectorAll("td");if(cells.length>=6)cells[5].style.display="none";});}});</script>';
            $html = str_replace('</body>', $script.'</body>', $html);
        }

        if ($section === 'work-orders' && ($request->filled('priority') || $request->filled('service_category'))) {
            $priority = json_encode((string) $request->query('priority', ''));
            $category = json_encode((string) $request->query('service_category', ''));
            $script = '<script>document.addEventListener("DOMContentLoaded",function(){var p=document.querySelector("select[name=priority]");var c=document.querySelector("input[name=service_category]");if(p&&'.$priority.')p.value='.$priority.';if(c&&'.$category.')c.value='.$category.';});</script>';
            $html = str_replace('</body>', $script.'</body>', $html);
        }

        return response($html)
            ->header('X-UNIFCO-Customer-Portal-Release','customer-portal-20260826-workflow-1')
            ->header('Cache-Control','no-cache, no-store, must-revalidate');
    }
}
