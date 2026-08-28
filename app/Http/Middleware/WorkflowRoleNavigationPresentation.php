<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkflowRoleNavigationPresentation
{
    private const ROLES=[
        'MAINTENANCE_ENGINEER','MAINTENANCE_MANAGER','PROCUREMENT','TENDERS_CONTRACTS','FINANCE','PROJECT_MANAGER','CEO',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response=$next($request);
        $user=$request->user();
        if(!$user || !in_array($user->role,self::ROLES,true) || !method_exists($response,'getContent') || !method_exists($response,'setContent')) return $response;

        $html=(string)$response->getContent();
        if($html==='' || !str_contains($html,'<aside class="side" id="side-menu">')) return $response;

        $start=strpos($html,'<aside class="side" id="side-menu">');
        $end=strpos($html,'</aside>',$start);
        if($start===false || $end===false) return $response;
        $end+=strlen('</aside>');

        $sidebar=$this->sidebar($user->role);
        $response->setContent(substr($html,0,$start).$sidebar.substr($html,$end));
        return $response;
    }

    private function sidebar(string $role): string
    {
        $home=route('workflow.workspace');
        $approvals=route('workflow.approvals.index');
        $customerActions=route('workflow.customer-actions.index');
        $notifications=route('platform.notifications.index');
        $roleLabel=str_replace('_',' ',$role);

        $items=match($role){
            'MAINTENANCE_ENGINEER'=>[
                ['⚙','My Workspace',$home],['✓','My Technical Approvals',$approvals],['▣','Work Orders',route('maintenance.work-orders.index')],['◇','Assets & EAM',route('asset-master.index')],['⌁','Maintenance Overview',route('modules.index','maintenance')],
            ],
            'MAINTENANCE_MANAGER'=>[
                ['⚙','Manager Workspace',$home],['✓','My Approvals',$approvals],['⚑','Customer Revisit Requests',$customerActions],['▣','Work Orders',route('maintenance.work-orders.index')],['⌁','Maintenance Operations',route('maintenance.operations.index')],['◇','Assets & EAM',route('asset-master.index')],['▥','Executive Reports',route('reporting.executive')],
            ],
            'PROCUREMENT'=>[
                ['▦','Procurement Workspace',$home],['✓','My Cost Approvals',$approvals],['▤','Purchase Orders',route('procurement.purchase-orders.index')],['◫','Stock Balances',route('inventory.stock.index')],['◎','Customer Context',route('crm.customers.index')],
            ],
            'TENDERS_CONTRACTS'=>[
                ['▥','Commercial Workspace',$home],['✓','My Commercial Approvals',$approvals],['⚑','Customer Renewal Requests',$customerActions],['◎','CRM & Opportunities',route('modules.index','crm')],['▤','Customers & Contracts',route('crm.customers.index')],['▥','Commercial Reports',route('reporting.executive')],
            ],
            'FINANCE'=>[
                ['◉','Finance Workspace',$home],['✓','My Financial Approvals',$approvals],['⚑','Customer Finance Actions',$customerActions],['▥','Finance Core',route('finance.core.index')],['▤','Journals',route('finance.journals.index')],['▥','Executive Reports',route('reporting.executive')],['◎','Customer Context',route('crm.customers.index')],
            ],
            'PROJECT_MANAGER'=>[
                ['▱','Projects Workspace',$home],['✓','My Execution Approvals',$approvals],['▱','Projects',route('projects.projects.index')],['▣','Work Orders',route('maintenance.work-orders.index')],['◎','Customer Context',route('crm.customers.index')],['▥','Executive Reports',route('reporting.executive')],
            ],
            'CEO'=>[
                ['★','Executive Workspace',$home],['✓','Executive Approvals',$approvals],['▥','Executive Reports',route('reporting.executive')],['◉','Finance Overview',route('finance.core.index')],['▱','Projects Overview',route('projects.projects.index')],['▤','Procurement Overview',route('procurement.purchase-orders.index')],
            ],
        };

        $links='';
        foreach($items as [$icon,$label,$url]){
            $active=request()->fullUrlIs($url) || request()->url()===$url ? ' active' : '';
            $links.='<a class="nav-link'.$active.'" href="'.e($url).'"><span class="nav-icon">'.$icon.'</span><span class="nav-label">'.e($label).'</span></a>';
        }

        return '<aside class="side" id="side-menu">'
            .'<div class="brand"><div class="brand-logo-wrap"><img class="brand-logo" src="'.e(route('brand.logo')).'" alt="UNIFCO"><div class="brand-wordmark"><b>UNIFCO</b><small>ONE FACILITY SHOP</small></div></div><button class="side-collapse" type="button" data-collapse-side aria-label="Collapse sidebar">‹</button><button class="close-side" type="button" data-close-side aria-label="Close menu">×</button></div>'
            .'<div class="side-search"><input id="side-nav-search" type="search" placeholder="Search my workspace…" autocomplete="off"></div>'
            .'<div class="nav-section-label">'.$roleLabel.'</div>'
            .$links
            .'<div class="nav-section-label">Platform</div>'
            .'<a class="nav-link" href="'.e($notifications).'"><span class="nav-icon">●</span><span class="nav-label">Notifications</span></a>'
            .'<div class="side-footer"><span style="display:block;color:#9fb0c7;font-size:9px;padding:7px 9px">Navigation is limited to responsibilities for this role.</span></div>'
            .'<div class="nav-empty" id="side-nav-empty">No matching items.</div>'
            .'</aside>';
    }
}
