<?php

namespace App\Services\CRM;

use App\Models\{CrmOpportunity,Customer,Project};
use App\Services\AuditService;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class CrmProjectFlowService
{
    public function __construct(private AuditService $audit) {}

    public function markWonAndCreateProject(CrmOpportunity $opportunity, array $data): Project
    {
        if ($opportunity->status !== 'OPEN') throw ValidationException::withMessages(['opportunity'=>'Only OPEN opportunities can be won.']);
        $customer=Customer::findOrFail($data['customer_id']);
        return DB::transaction(function () use ($opportunity,$customer,$data) {
            $before=$opportunity->toArray();
            $opportunity->update(['status'=>'WON','stage'=>'CLOSED_WON','customer_id'=>$customer->id,'probability'=>100]);
            $project=Project::create([
                'organization_id'=>Auth::user()->organization_id,'project_no'=>$data['project_no'],'name'=>$data['project_name'],
                'customer_id'=>$customer->id,'opportunity_id'=>$opportunity->id,'planned_start'=>$data['planned_start']??null,
                'planned_finish'=>$data['planned_finish']??null,'budget'=>$data['budget']??$opportunity->expected_value,'actual_cost'=>0,'status'=>'PLANNED',
            ]);
            $this->audit->record('crm.opportunity.won',$opportunity,$before,$opportunity->fresh()->toArray());
            $this->audit->record('projects.project.created_from_opportunity',$project,[],$project->toArray());
            return $project;
        });
    }
}
