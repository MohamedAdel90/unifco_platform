<?php

namespace App\Http\Controllers;

use App\Models\{Asset,Customer,Employee,Item,Journal,Project,PurchaseOrder,ProductionOrder,WorkOrder};
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    private const MODULES = [
        'finance' => [Journal::class,'Finance','journal_no','journal_date'],
        'hr' => [Employee::class,'Human Resources','employee_no','email'],
        'procurement' => [PurchaseOrder::class,'Procurement','po_number','order_date'],
        'inventory' => [Item::class,'Inventory','item_code','uom'],
        'crm' => [Customer::class,'CRM','customer_code','email'],
        'projects' => [Project::class,'Projects','project_no','budget'],
        'manufacturing' => [ProductionOrder::class,'Manufacturing','order_no','planned_quantity'],
        'maintenance' => [WorkOrder::class,'Maintenance','work_order_no','priority'],
        'eam' => [Asset::class,'Enterprise Assets','asset_code','acquisition_cost'],
    ];

    public function index(Request $request, string $module): View
    {
        abort_unless(isset(self::MODULES[$module]), 404);
        [$model,$title,$key,$secondary] = self::MODULES[$module];
        $records = $model::query()->latest('id')->paginate(20)->withQueryString();
        return view('modules.index', compact('module','title','key','secondary','records'));
    }
}
