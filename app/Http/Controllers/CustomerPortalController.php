<?php

namespace App\Http\Controllers;

use App\Models\{Asset,Customer,FinancialDocument,MaintenancePlan,ServiceContract,WorkOrder};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerPortalController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        abort_unless($user && $user->role === 'CUSTOMER' && $user->customer_id, 403, 'Customer portal access is not configured for this user.');

        $customer = Customer::findOrFail($user->customer_id);

        $contracts = ServiceContract::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('starts_on')
            ->get();

        $assets = Asset::query()
            ->where('customer_id', $customer->id)
            ->orderBy('asset_code')
            ->get();

        $assetIds = $assets->pluck('id');

        $plans = MaintenancePlan::query()
            ->whereIn('asset_id', $assetIds)
            ->orderBy('next_due_date')
            ->get();

        $workOrders = WorkOrder::query()
            ->whereIn('asset_id', $assetIds)
            ->latest('created_at')
            ->limit(100)
            ->get();

        $invoices = FinancialDocument::query()
            ->where('customer_id', $customer->id)
            ->where('document_type', 'AR_INVOICE')
            ->latest('document_date')
            ->limit(100)
            ->get();

        $payments = DB::table('payments')
            ->join('financial_documents', 'financial_documents.id', '=', 'payments.financial_document_id')
            ->where('financial_documents.customer_id', $customer->id)
            ->select('payments.*', 'financial_documents.document_no')
            ->orderByDesc('payments.payment_date')
            ->limit(100)
            ->get();

        $materials = DB::table('maintenance_materials')
            ->join('work_orders', 'work_orders.id', '=', 'maintenance_materials.work_order_id')
            ->join('assets', 'assets.id', '=', 'work_orders.asset_id')
            ->join('items', 'items.id', '=', 'maintenance_materials.item_id')
            ->where('assets.customer_id', $customer->id)
            ->select(
                'maintenance_materials.*',
                'work_orders.work_order_no',
                'assets.asset_code',
                'assets.name as asset_name',
                'items.item_code',
                'items.name as item_name'
            )
            ->orderByDesc('maintenance_materials.created_at')
            ->limit(100)
            ->get();

        $openInvoiceAmount = $invoices->sum(fn ($invoice) => (float) $invoice->open_amount);
        $openWorkOrders = $workOrders->whereNotIn('status', ['COMPLETED','CLOSED'])->count();
        $preventiveCount = $workOrders->where('maintenance_type', 'PREVENTIVE')->count();
        $correctiveCount = $workOrders->where('maintenance_type', 'CORRECTIVE')->count();

        return view('customer.portal', compact(
            'customer','contracts','assets','plans','workOrders','invoices','payments','materials',
            'openInvoiceAmount','openWorkOrders','preventiveCount','correctiveCount'
        ));
    }
}
