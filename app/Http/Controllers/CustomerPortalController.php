<?php

namespace App\Http\Controllers;

use App\Models\{Asset,CrmQuotation,Customer,FinancialDocument,MaintenancePlan,MaintenanceVisitReport,ServiceContract,ServiceRequest,WorkOrder};
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CustomerPortalController extends Controller
{
    public function __invoke(Request $request, ?string $section = null): Response
    {
        $user = auth()->user();
        abort_unless($user && $user->role === 'CUSTOMER' && $user->customer_id, 403, 'Customer portal access is not configured for this user.');
        $customer = Customer::findOrFail($user->customer_id);
        $section = $section ?: 'dashboard';

        $allowedSections = ['dashboard','contracts','assets','work-orders','maintenance','invoices','reports','sla','documents','notifications'];
        abort_unless(in_array($section, $allowedSections, true), 404);

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
        $upcomingPlans = $plans->whereNotNull('next_due_date')->filter(fn ($p) => $p->next_due_date->gte(today()))->take(4);
        $workOrderTotal = max(1, $workOrders->count());
        $slaPerformance = $workOrders->isEmpty() ? 100 : (int) round(($completedCount / $workOrderTotal) * 100);
        $preventiveCount = $workOrders->where('maintenance_type', 'PREVENTIVE')->count();
        $correctiveCount = $workOrders->where('maintenance_type', 'CORRECTIVE')->count();
        $openInvoiceAmount = $invoices->sum(fn ($i) => (float) $i->open_amount);
        $locations = Asset::where('customer_id', $customer->id)->whereNotNull('location_code')->distinct()->orderBy('location_code')->pluck('location_code');
        $warrantyParts = Asset::whereIn('id', $allCustomerAssetIds)->orderBy('warranty_expiry')->get();

        $html = view('customer.section', compact(
            'section', 'customer', 'contracts', 'assets', 'plans', 'workOrders', 'invoices', 'payments', 'materials', 'requests', 'quotations', 'visitReports', 'attachments', 'alerts', 'locations', 'warrantyParts',
            'openInvoiceAmount', 'openWorkOrders', 'inProgressCount', 'completedCount', 'overdueCount', 'recentWorkOrders', 'upcomingPlans', 'slaPerformance', 'preventiveCount', 'correctiveCount',
            'contractFilter', 'assetFilter', 'locationFilter'
        ))->render();

        $unreadInbox = DB::table('customer_messages')
            ->join('customer_conversations','customer_conversations.id','=','customer_messages.conversation_id')
            ->where('customer_conversations.customer_id',$customer->id)
            ->where('customer_messages.sender_side','UNIFCO')
            ->whereNull('customer_messages.read_at')
            ->count();

        $customerBrand = $customer->logo_path
            ? '<img class="customer-sidebar-logo" src="'.e(asset('storage/'.$customer->logo_path)).'" alt="'.e($customer->name).'">'
            : '<div class="customer-sidebar-fallback">'.e($customer->name).'</div>';

        $portalUiCss = '<style id="customer-portal-branding">.customer-brand-wrap{height:112px;background:#fff;border-radius:14px;padding:12px;margin-bottom:15px;display:flex;align-items:center;justify-content:center}.customer-sidebar-logo{max-width:100%;max-height:86px;object-fit:contain}.customer-sidebar-fallback{color:#06275c;text-align:center;font-size:12px;font-weight:900}.portal-icons{display:flex;align-items:center;gap:8px}.portal-icon{position:relative;width:38px;height:38px;border-radius:50%;border:1px solid #dce4ee;background:#fff;display:grid;place-items:center;color:#06275c;text-decoration:none;font-size:17px}.portal-icon:hover{background:#f2f6fb}.portal-badge{position:absolute;top:-5px;right:-4px;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:#e20b24;color:#fff;font-size:9px;display:grid;place-items:center;font-weight:900}.quick-actions{gap:10px!important;margin-bottom:14px!important}.quick-action{padding:13px 15px!important;border-radius:10px!important}.quick-icon{width:38px!important;height:38px!important;font-size:18px!important}.quick-action b{font-size:12px!important}.quick-action small{font-size:9px!important}</style>';
        $html = str_replace('</head>', $portalUiCss.'</head>', $html);

        $html = preg_replace('/<aside class="sidebar"><img class="logo"[^>]*>/', '<aside class="sidebar"><div class="customer-brand-wrap">'.$customerBrand.'</div>', $html, 1);

        $notificationsUrl = route('customer.section','notifications');
        $html = preg_replace('#<a[^>]+href="'.preg_quote($notificationsUrl,'#').'"[^>]*>.*?</a>#s', '', $html, 1);

        $profileUrl = route('customer.profile.edit');
        $inboxLink = '<a href="'.route('customer.inbox').'"><span class="ico">✉</span><span>Inbox'.($unreadInbox ? ' ('.$unreadInbox.')' : '').'</span></a>';
        $html = str_replace('<a href="'.$profileUrl.'">', $inboxLink.'<a href="'.$profileUrl.'">', $html);

        $topIcons = '<div class="portal-icons"><a class="portal-icon" href="'.$notificationsUrl.'" aria-label="Notifications">♢'.($alerts->count() ? '<span class="portal-badge">'.$alerts->count().'</span>' : '').'</a><a class="portal-icon" href="'.route('customer.inbox').'" aria-label="Inbox">✉'.($unreadInbox ? '<span class="portal-badge">'.$unreadInbox.'</span>' : '').'</a></div>';
        $html = preg_replace('/<div class="avatar">.*?<\/div>/', $topIcons, $html, 1);

        if ($section === 'dashboard') {
            $emergencyUrl = route('customer.section', 'work-orders').'?priority=EMERGENCY#request-service';
            $partsUrl = route('customer.section', 'work-orders').'?service_category=Spare%20Parts&priority=HIGH#request-service';
            $quickActions = '<section class="quick-actions"><a class="quick-action" href="'.$emergencyUrl.'"><span class="quick-icon">⚡</span><span><b>طلب صيانة طارئة · Emergency Maintenance</b><small>Open a high-priority maintenance request</small></span></a><a class="quick-action parts" href="'.$partsUrl.'"><span class="quick-icon">⚙</span><span><b>طلب قطع غيار · Spare Parts Request</b><small>Request parts for an assigned asset</small></span></a></section>';
            $html = str_replace('<section class="stats">', $quickActions.'<section class="stats">', $html);
        }

        if ($section === 'work-orders' && ($request->filled('priority') || $request->filled('service_category'))) {
            $priority = json_encode((string) $request->query('priority', ''));
            $category = json_encode((string) $request->query('service_category', ''));
            $script = '<script>document.addEventListener("DOMContentLoaded",function(){var p=document.querySelector("select[name=priority]");var c=document.querySelector("input[name=service_category]");if(p&&'.$priority.')p.value='.$priority.';if(c&&'.$category.')c.value='.$category.';});</script>';
            $html = str_replace('</body>', $script.'</body>', $html);
        }

        return response($html)
            ->header('X-UNIFCO-Customer-Portal-Release','customer-portal-20260821-2')
            ->header('Cache-Control','no-cache, no-store, must-revalidate');
    }
}
