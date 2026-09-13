<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\{ServiceRequest, User};
use App\Services\{AuthorizationService, ScopeService};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceRequestOperationsController extends Controller
{
    public function index(Request $request, ScopeService $scopes): View
    {
        $user = $request->user();
        $query = $scopes->apply(
            ServiceRequest::query()->where('tenant_id', $user->tenant_id),
            $user
        );

        $openStages = ['NEW','TRIAGE','ASSIGNED','IN_PROGRESS','WAITING_PARTS','ON_HOLD','ESCALATED'];
        $metrics = [
            'open' => (clone $query)->whereNotIn('status', ['CLOSED','COMPLETED','CANCELLED','RESOLVED'])->count(),
            'unassigned' => (clone $query)->whereNull('assigned_engineer_id')->whereIn('workflow_stage', $openStages)->count(),
            'emergency' => (clone $query)->whereIn('priority', ['EMERGENCY','CRITICAL','URGENT'])->whereIn('workflow_stage', $openStages)->count(),
            'sla_overdue' => (clone $query)->whereNotNull('current_stage_due_at')->where('current_stage_due_at', '<', now())->whereIn('workflow_stage', $openStages)->count(),
            'escalated' => (clone $query)->where('workflow_stage', 'ESCALATED')->count(),
        ];

        $requests = (clone $query)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->string('priority')))
            ->when($request->filled('stage'), fn ($q) => $q->where('workflow_stage', $request->string('stage')))
            ->orderByRaw("CASE priority WHEN 'EMERGENCY' THEN 1 WHEN 'CRITICAL' THEN 2 WHEN 'URGENT' THEN 3 WHEN 'HIGH' THEN 4 ELSE 5 END")
            ->orderByRaw('CASE WHEN current_stage_due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('current_stage_due_at')
            ->latest('id')
            ->limit(150)
            ->get();

        $assignees = User::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('status', 'ACTIVE')
            ->whereIn('role', ['MAINTENANCE_ENGINEER','TECHNICIAN','SUPERVISOR','MAINTENANCE_MANAGER'])
            ->orderBy('name')
            ->get(['id','name','role','employee_id']);

        return view('operations.service-requests', compact('metrics','requests','assignees'));
    }

    public function assign(Request $request, ServiceRequest $serviceRequest, AuthorizationService $authorization): RedirectResponse
    {
        $user = $request->user();
        abort_unless((int) $serviceRequest->tenant_id === (int) $user->tenant_id, 404);
        $authorization->authorize($user, 'service_requests.assign', $serviceRequest);

        $data = $request->validate([
            'assigned_engineer_id' => ['required','integer'],
        ]);
        $assignee = User::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereKey($data['assigned_engineer_id'])
            ->where('status', 'ACTIVE')
            ->whereIn('role', ['MAINTENANCE_ENGINEER','TECHNICIAN','SUPERVISOR','MAINTENANCE_MANAGER'])
            ->firstOrFail();

        $before = $serviceRequest->assigned_engineer_id;
        $serviceRequest->update([
            'assigned_engineer_id' => $assignee->id,
            'workflow_stage' => 'ASSIGNED',
            'workflow_started_at' => $serviceRequest->workflow_started_at ?: now(),
        ]);

        $this->activity($serviceRequest, 'OPERATIONS_ASSIGNMENT', 'Service request assigned',
            "Assigned to {$assignee->name} by {$user->name}.",
            ['previous_assignee_id'=>$before,'assigned_engineer_id'=>$assignee->id,'actor_user_id'=>$user->id]
        );

        return back()->with('status', 'Service request assigned successfully.');
    }

    public function escalate(Request $request, ServiceRequest $serviceRequest, AuthorizationService $authorization): RedirectResponse
    {
        $user = $request->user();
        abort_unless((int) $serviceRequest->tenant_id === (int) $user->tenant_id, 404);
        $authorization->authorize($user, 'service_requests.escalate', $serviceRequest);

        $data = $request->validate(['reason' => ['required','string','max:1000']]);
        $serviceRequest->update([
            'workflow_stage' => 'ESCALATED',
            'current_stage_due_at' => now(),
        ]);

        $this->activity($serviceRequest, 'OPERATIONS_ESCALATION', 'Service request escalated',
            $data['reason'],
            ['actor_user_id'=>$user->id,'priority'=>$serviceRequest->priority]
        );

        return back()->with('status', 'Service request escalated.');
    }

    private function activity(ServiceRequest $serviceRequest, string $type, string $title, string $description, array $metadata): void
    {
        if (!DB::getSchemaBuilder()->hasTable('customer_activity_events') || !$serviceRequest->customer_id) return;
        DB::table('customer_activity_events')->insert([
            'tenant_id'=>$serviceRequest->tenant_id,
            'organization_id'=>$serviceRequest->organization_id,
            'customer_id'=>$serviceRequest->customer_id,
            'event_type'=>$type,
            'reference_type'=>ServiceRequest::class,
            'reference_id'=>$serviceRequest->id,
            'title'=>$title,
            'description'=>$description,
            'visibility'=>'INTERNAL',
            'metadata'=>json_encode($metadata),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
    }
}
