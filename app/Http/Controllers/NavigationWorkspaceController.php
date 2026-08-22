<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NavigationWorkspaceController extends Controller
{
    private const WORKSPACES = [
        'customer-onboarding' => ['title'=>'Customer Onboarding','group'=>'Customers & Service','description'=>'Customer onboarding checklist, account readiness and handoff workspace.','primary'=>'crm.customers.index'],
        'contracts' => ['title'=>'Contracts','group'=>'Customers & Service','description'=>'Customer contract visibility, service scope and operational handoff workspace.','primary'=>'crm.customers.index'],
        'customer-portal' => ['title'=>'Customer Portal','group'=>'Customers & Service','description'=>'Customer-facing service access, profile and request entry workspace.','primary'=>'customer.profile.edit'],
        'preventive-maintenance' => ['title'=>'Preventive Maintenance','group'=>'Maintenance & Field Service','description'=>'Preventive maintenance plans, generation and execution readiness.','primary'=>'maintenance.operations.index'],
        'parts-consumption' => ['title'=>'Parts Requests / Consumption','group'=>'Maintenance & Field Service','description'=>'Parts requests, issue, consume-on-asset and unused-return workflow.','primary'=>'maintenance.operations.index'],
        'asset-360' => ['title'=>'Asset 360','group'=>'Assets & EAM','description'=>'Unified asset lifecycle, maintenance history and installed-parts context.','primary'=>'eam.assets.index'],
        'meters-readings' => ['title'=>'Meters & Readings','group'=>'Assets & EAM','description'=>'Asset meter readings and usage-driven maintenance inputs.','primary'=>'maintenance.operations.index'],
        'reliability' => ['title'=>'Reliability','group'=>'Assets & EAM','description'=>'Asset reliability, health signals and maintenance decision support.','primary'=>'maintenance.operations.index'],
        'spare-parts-reorder' => ['title'=>'Spare Parts / Reorder','group'=>'Assets & EAM','description'=>'Spare-parts availability and reorder decision workspace.','primary'=>'inventory.warehouse.index'],
        'maintenance-plans' => ['title'=>'Maintenance Plans','group'=>'Assets & EAM','description'=>'Maintenance plan authoring, schedule generation and execution readiness.','primary'=>'maintenance.operations.index'],
        'transfers' => ['title'=>'Transfers','group'=>'Inventory & Warehouses','description'=>'Warehouse and field inventory transfer control workspace.','primary'=>'inventory.operations.index'],
        'bins-locations' => ['title'=>'Bins & Locations','group'=>'Inventory & Warehouses','description'=>'Warehouse, bin and storage-location organization workspace.','primary'=>'inventory.operations.index'],
        'receiving' => ['title'=>'Receiving','group'=>'Inventory & Warehouses','description'=>'Inbound material receiving and stock availability workspace.','primary'=>'inventory.warehouse.index'],
        'material-issues-returns' => ['title'=>'Material Issues / Returns','group'=>'Inventory & Warehouses','description'=>'Material issue, consumption and return workflow workspace.','primary'=>'inventory.warehouse.index'],
        'inventory-movements' => ['title'=>'Inventory Movements','group'=>'Inventory & Warehouses','description'=>'Inventory movement, transfer and count visibility workspace.','primary'=>'inventory.operations.index'],
        'purchase-requisitions' => ['title'=>'Purchase Requisitions','group'=>'Procurement','description'=>'Purchase requisition creation, approval and conversion workspace.','primary'=>'procurement.operations.index'],
        'goods-receipts' => ['title'=>'Goods Receipts','group'=>'Procurement','description'=>'Purchase-order receiving and goods-receipt control workspace.','primary'=>'procurement.purchase-orders.index'],
        'suppliers' => ['title'=>'Suppliers','group'=>'Procurement','description'=>'Supplier master and procurement relationship workspace.','primary'=>'procurement.operations.index'],
        'procurement-analytics' => ['title'=>'Procurement Analytics','group'=>'Procurement','description'=>'Procurement pipeline, requisition and supplier decision workspace.','primary'=>'procurement.operations.index'],
        'finance-dashboard' => ['title'=>'Finance Dashboard','group'=>'Finance','description'=>'Financial operations overview and navigation workspace.','primary'=>'finance.core.index'],
        'chart-of-accounts' => ['title'=>'Accounts / Chart of Accounts','group'=>'Finance','description'=>'Account structure and finance-core configuration workspace.','primary'=>'finance.core.index'],
        'receivables' => ['title'=>'AR / Receivables','group'=>'Finance','description'=>'Accounts receivable and outstanding customer balance workspace.','primary'=>'finance.core.index'],
        'payables' => ['title'=>'AP / Payables','group'=>'Finance','description'=>'Accounts payable and supplier obligation workspace.','primary'=>'finance.core.index'],
        'payments' => ['title'=>'Payments','group'=>'Finance','description'=>'Payment execution and financial document settlement workspace.','primary'=>'finance.core.index'],
        'financial-documents' => ['title'=>'Financial Documents','group'=>'Finance','description'=>'Financial document creation, posting and settlement workspace.','primary'=>'finance.core.index'],
        'project-tasks' => ['title'=>'Project Tasks / Activities','group'=>'Projects','description'=>'Project activity, task and execution tracking workspace.','primary'=>'projects.projects.index'],
        'project-assets' => ['title'=>'Project Assets','group'=>'Projects','description'=>'Project-to-asset association and delivery context workspace.','primary'=>'projects.projects.index'],
        'project-costs' => ['title'=>'Project Costs','group'=>'Projects','description'=>'Project cost visibility and operational finance context.','primary'=>'projects.projects.index'],
        'materials-bom' => ['title'=>'Materials / BOM','group'=>'Manufacturing','description'=>'Bills of material, material readiness and production structure workspace.','primary'=>'manufacturing.operations.index'],
        'production-tracking' => ['title'=>'Production Tracking','group'=>'Manufacturing','description'=>'Production order progress, confirmation, quality and completion workspace.','primary'=>'manufacturing.operations.index'],
        'teams-technicians' => ['title'=>'Teams / Technicians','group'=>'People','description'=>'Field-team, technician and assignment readiness workspace.','primary'=>'hr.operations.index'],
        'skills-certifications' => ['title'=>'Skills / Certifications','group'=>'People','description'=>'Employee skill and certification readiness workspace.','primary'=>'hr.operations.index'],
        'operations-analytics' => ['title'=>'Operations Analytics','group'=>'Insights & Intelligence','description'=>'Cross-functional operating signals and management decision workspace.','primary'=>'dashboard'],
        'predictive-analytics' => ['title'=>'Predictive Analytics','group'=>'Insights & Intelligence','description'=>'Predictive maintenance, risk and forward-looking operational insight workspace.','primary'=>'dashboard'],
        'users' => ['title'=>'Users','group'=>'Administration','description'=>'User access and security administration workspace.','primary'=>'admin.permissions.index'],
        'system-settings' => ['title'=>'System Settings','group'=>'Administration','description'=>'Platform configuration and administrative settings workspace.','primary'=>'admin.permissions.index'],
    ];

    public function show(Request $request, string $workspace)
    {
        abort_unless(isset(self::WORKSPACES[$workspace]), 404);
        $item = self::WORKSPACES[$workspace];
        $item['key'] = $workspace;
        $item['primary_url'] = route($item['primary']);

        return view('navigation.workspace', ['workspace' => $item]);
    }
}
