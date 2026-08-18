<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Customer,Employee,Journal,ProductionOrder,Project,PurchaseOrder,WorkOrder};
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExecutiveReportController extends Controller
{
    public function index(): View
    {
        $data=$this->data();
        return view('reporting.executive',$data);
    }

    public function csv(): Response
    {
        $data=$this->data();
        $lines=[['Metric','Value']];
        foreach($data['metrics'] as $metric=>$value)$lines[]=[$metric,$value];
        $lines[]=['Posted Debit',$data['finance']->debit ?? 0];
        $lines[]=['Posted Credit',$data['finance']->credit ?? 0];
        $csv=implode("\n",array_map(fn($row)=>implode(',',array_map(fn($v)=>'"'.str_replace('"','""',(string)$v).'"',$row)),$lines));
        return response($csv,200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="unifco-executive-report-'.now()->format('Ymd').'.csv"']);
    }

    private function data(): array
    {
        $posted=Journal::where('status','POSTED');
        return [
            'metrics'=>[
                'Posted Journals'=>$posted->count(),
                'Active Employees'=>Employee::where('status','ACTIVE')->count(),
                'Approved POs'=>PurchaseOrder::where('status','APPROVED')->count(),
                'Active Customers'=>Customer::where('status','ACTIVE')->count(),
                'Active Projects'=>Project::where('status','ACTIVE')->count(),
                'Open Work Orders'=>WorkOrder::where('status','OPEN')->count(),
                'Released Production'=>ProductionOrder::where('status','RELEASED')->count(),
                'Capitalized Assets'=>Asset::where('status','CAPITALIZED')->count(),
            ],
            'finance'=>DB::table('journal_lines')->join('journals','journals.id','=','journal_lines.journal_id')
                ->where('journals.tenant_id',auth()->user()->tenant_id)->where('journals.status','POSTED')
                ->selectRaw('COALESCE(SUM(debit),0) debit, COALESCE(SUM(credit),0) credit')->first(),
        ];
    }
}
