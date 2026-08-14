<?php

namespace App\Http\Controllers;

use App\Models\{Asset,Customer,Employee,Item,Journal,Project,PurchaseOrder,ProductionOrder,WorkOrder};
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', ['stats' => [
            'Finance' => Journal::count(), 'HR' => Employee::count(), 'Procurement' => PurchaseOrder::count(),
            'Inventory' => Item::count(), 'CRM' => Customer::count(), 'Projects' => Project::count(),
            'Manufacturing' => ProductionOrder::count(), 'Maintenance' => WorkOrder::count(), 'EAM' => Asset::count(),
        ]]);
    }
}
