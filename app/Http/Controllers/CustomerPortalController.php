<?php

namespace App\Http\Controllers;

use App\Models\{Asset,CrmQuotation,Customer,CustomerActivityEvent,FinancialDocument,MaintenancePlan,MaintenanceVisitReport,ServiceContract,ServiceRequest,WorkOrder};
use App\Services\CustomerPortalAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\{DB,Schema};

class CustomerPortalController extends Controller
{
    public function __invoke(Request $request, CustomerPortalAccessService $access, ?string $section=null): Response
    {
        $user=$request->user();
        abort_unless($user && $user->role==='CUSTOMER' && $user->customer_id,403,'Customer portal access is not configured for this user.');
        $customer=Customer::findOrFail($user->customer_id);
        $section=$section?:'dashboard';
        abort_unless($access->canSection($user,$section),403,'This section is not available for your customer portal role.');

        $portalRole=$access->role($user);
        $allowedSections=$access->allowedSections($user);
        $canCreateRequest=$access->canCreateServiceRequest($user);
        $canDecideQuotation=$access->canDecideQuotation($user);
        $canManageUsers=$access->canManageUsers($user);
        $readOnly=$access->isReadOnly($user);

        $scopedSiteIds=$access->accessibleSiteIds($user);
        $scopedContractIds=$access->accessibleContractIds($user);
        $scopedAssetIds=$access->accessibleAssetIds($user);

        $contractFilter=$request->integer('contract_id')?:null;
        $assetFilter=$request->integer('asset_id')?:null;
        $locationFilter=trim((string)$request->query('location',''))?:null;
        if($contractFilter) $access->assertContract($user,$contractFilter);
        if($assetFilter) $access->assertAsset($user,$assetFilter);

        $contractsQuery=ServiceContract::where('customer_id',$customer->id);
        if($scopedContractIds!==null) $contractsQuery->whereIn('id',$scopedContractIds);
        $contracts=$contractsQuery->orderByDesc('starts_on')->get();

        $assetsQuery=Asset::with('site')->where('customer_id',$customer->id);
        if($scopedAssetIds!==null) $assetsQuery->whereIn('id',$scopedAssetIds);
        if($assetFilter) $assetsQuery->whereKey($assetFilter);
        if($locationFilter) $assetsQuery->where('location_code',$locationFilter);
        $assets=$assetsQuery->orderBy('asset_code')->get();
        $assetIds=$assets->pluck('id');

        $plans=MaintenancePlan::with('asset')->whereIn('asset_id',$assetIds)
            ->when($contractFilter,fn($q)=>$q->where('service_contract_id',$contractFilter))
            ->when($scopedContractIds!==null,fn($q)=>$q->where(function($inner)use($scopedContractIds){$inner->whereNull('service_contract_id')->orWhereIn('service_contract_id',$scopedContractIds);}))
            ->orderBy('next_due_date')->get();

        $workOrders=WorkOrder::with('asset')->whereIn('asset_id',$assetIds)
            ->when($contractFilter,fn($q)=>$q->where('service_contract_id',$contractFilter))
            ->when($scopedContractIds!==null,fn($q)=>$q->where(function($inner)use($scopedContractIds){$inner->whereNull('service_contract_id')->orWhereIn('service_contract_id',$scopedContractIds);}))
            ->latest('created_at')->limit(100)->get();
        $workOrders->each(function(WorkOrder $wo):void{$wo->setAttribute('labor_cost',null);$wo->setAttribute('material_cost',null);$wo->setAttribute('external_cost',null);$wo->setAttribute('total_cost',null);});

        $requestQuery=ServiceRequest::where('customer_id',$customer->id);
        if($scopedAssetIds!==null) $requestQuery->where(function($q)use($scopedAssetIds){$q->whereNull('asset_id')->orWhereIn('asset_id',$scopedAssetIds);});
        if($scopedContractIds!==null) $requestQuery->where(function($q)use($scopedContractIds){$q->whereNull('service_contract_id')->orWhereIn('service_contract_id',$scopedContractIds);});
        $requests=$requestQuery->when($contractFilter,fn($q)=>$q->where('service_contract_id',$contractFilter))->when($assetFilter,fn($q)=>$q->where('asset_id',$assetFilter))->latest()->limit(100)->get();

        $quotations=CrmQuotation::where('customer_id',$customer->id)->latest('quotation_date')->limit(50)->get();
        $invoices=FinancialDocument::where('customer_id',$customer->id)->where('document_type','AR_INVOICE')->latest('document_date')->limit(100)->get();
        $payments=DB::table('payments')->join('financial_documents','financial_documents.id','=','payments.financial_document_id')->where('financial_documents.customer_id',$customer->id)->select('payments.*','financial_documents.document_no')->orderByDesc('payments.payment_date')->limit(100)->get();
        $materials=DB::table('maintenance_materials')->join('work_orders','work_orders.id','=','maintenance_materials.work_order_id')->join('assets','assets.id','=','work_orders.asset_id')->join('items','items.id','=','maintenance_materials.item_id')->whereIn('assets.id',$assetIds)->select('maintenance_materials.id','maintenance_materials.work_order_id','maintenance_materials.item_id','maintenance_materials.warehouse_code','maintenance_materials.quantity','maintenance_materials.created_at','work_orders.work_order_no','assets.asset_code','assets.name as asset_name','items.item_code','items.name as item_name','items.uom')->orderByDesc('maintenance_materials.created_at')->limit(100)->get();

        $timeline=Schema::hasTable('customer_activity_events')?CustomerActivityEvent::where('customer_id',$customer->id)->whereIn('visibility',['BOTH','CUSTOMER'])->latest()->limit(100)->get():collect();
        $visitReports=MaintenanceVisitReport::where('customer_id',$customer->id)->whereIn('asset_id',$assetIds)->when($contractFilter,fn($q)=>$q->where('service_contract_id',$contractFilter))->latest('visit_date')->limit(100)->get();
        $attachments=DB::table('maintenance_attachments')->where('customer_id',$customer->id)->whereIn('asset_id',$assetIds)->latest()->limit(100)->get();

        $alerts=collect();
        foreach($plans->whereNotNull('next_due_date')->filter(fn($p)=>$p->next_due_date->lte(now()->addDays(30))) as $p)$alerts->push((object)['type'=>'MAINTENANCE_DUE','title'=>'Maintenance due: '.$p->plan_no,'due_date'=>$p->next_due_date,'severity'=>$p->next_due_date->isPast()?'HIGH':'INFO']);
        if(in_array($portalRole,['CUSTOMER_ADMIN','FINANCE','VIEWER'],true)) foreach($invoices->filter(fn($i)=>$i->open_amount>0&&$i->due_date&&$i->due_date->lte(now()->addDays(14))) as $i)$alerts->push((object)['type'=>'INVOICE_DUE','title'=>'Invoice due: '.$i->document_no,'due_date'=>$i->due_date,'severity'=>$i->due_date->isPast()?'HIGH':'INFO']);
        foreach($contracts->filter(fn($c)=>$c->ends_on&&$c->ends_on->lte(now()->addDays(60))) as $c)$alerts->push((object)['type'=>'CONTRACT_EXPIRY','title'=>'Contract expiring: '.$c->contract_no,'due_date'=>$c->ends_on,'severity'=>'INFO']);

        $complete=['COMPLETED','CLOSED'];$inProgress=['IN_PROGRESS','IN PROGRESS','STARTED','ASSIGNED'];
        $openWorkOrders=$workOrders->whereNotIn('status',$complete)->count();
        $inProgressCount=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),$inProgress,true))->count();
        $completedCount=$workOrders->filter(fn($w)=>in_array(strtoupper((string)$w->status),$complete,true))->count();
        $overdueCount=$workOrders->filter(fn($w)=>$w->planned_start&&$w->planned_start->isPast()&&!in_array(strtoupper((string)$w->status),$complete,true))->count();
        $recentWorkOrders=$workOrders->take(4);$recentRequests=$requests->take(5);$upcomingPlans=$plans->whereNotNull('next_due_date')->filter(fn($p)=>$p->next_due_date->gte(today()))->take(4);
        $slaPerformance=$workOrders->isEmpty()?100:(int)round(($completedCount/max(1,$workOrders->count()))*100);
        $preventiveCount=$workOrders->where('maintenance_type','PREVENTIVE')->count();$correctiveCount=$workOrders->where('maintenance_type','CORRECTIVE')->count();
        $openInvoiceAmount=$invoices->sum(fn($i)=>(float)$i->open_amount);$openRequestCount=$requests->whereNotIn('status',['CLOSED','REJECTED','CANCELLED'])->count();$pendingQuotationCount=$quotations->whereIn('status',['DRAFT','SENT','UNDER_REVIEW','REVISION_REQUESTED'])->count();$activeContractCount=$contracts->where('status','ACTIVE')->count();
        $locations=$assets->pluck('location_code')->filter()->unique()->sort()->values();$warrantyParts=$assets->sortBy('warranty_expiry')->values();

        $unreadInbox=0;$inboxReady=Schema::hasTable('customer_messages')&&Schema::hasTable('customer_conversations');
        if($inboxReady)$unreadInbox=DB::table('customer_messages')->join('customer_conversations','customer_conversations.id','=','customer_messages.conversation_id')->where('customer_conversations.customer_id',$customer->id)->where('customer_messages.sender_side','UNIFCO')->whereNull('customer_messages.read_at')->count();

        $html=view('customer.section',compact('section','customer','contracts','assets','plans','workOrders','invoices','payments','materials','requests','quotations','timeline','visitReports','attachments','alerts','locations','warrantyParts','openInvoiceAmount','openWorkOrders','openRequestCount','pendingQuotationCount','activeContractCount','inProgressCount','completedCount','overdueCount','recentWorkOrders','recentRequests','upcomingPlans','slaPerformance','preventiveCount','correctiveCount','contractFilter','assetFilter','locationFilter','unreadInbox','inboxReady','portalRole','allowedSections','canCreateRequest','canDecideQuotation','canManageUsers','readOnly'))->render();

        if($canManageUsers){
            $accessLink='<a href="'.e(route('customer.access.index')).'"><span class="ico">♙</span><span>Users &amp; Access</span></a>';
            $html=str_replace('<a href="'.e(route('customer.profile.edit')).'">',$accessLink.'<a href="'.e(route('customer.profile.edit')).'">',$html);
        }

        return response($html)->header('X-UNIFCO-Customer-Portal-Release','customer-portal-rbac-phase1-20260827')->header('Cache-Control','no-cache, no-store, must-revalidate');
    }
}
