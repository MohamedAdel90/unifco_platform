<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Models\{Asset,Customer,Employee,Journal,ProductionOrder,Project,PurchaseOrder,WorkOrder};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExecutiveReportController extends Controller
{
    public function index(): View
    {
        $posted=Journal::where('status','POSTED');
        return view('reporting.executive',[
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
        ]);
    }
}
