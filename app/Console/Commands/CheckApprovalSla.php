<?php

namespace App\Console\Commands;

use App\Jobs\CreatePlatformNotification;
use App\Models\{ApprovalRequest,User};
use Illuminate\Console\Command;

class CheckApprovalSla extends Command
{
    protected $signature = 'unifco:check-approval-sla';
    protected $description = 'Send approval SLA reminders at 80 percent and escalate breached approvals';

    public function handle(): int
    {
        $reminded = 0;
        $escalated = 0;

        ApprovalRequest::where('status', 'PENDING')
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->chunkById(200, function ($approvals) use (&$reminded, &$escalated): void {
                foreach ($approvals as $approval) {
                    $sla = max(1, (int) ($approval->sla_minutes ?: 120));
                    $startedAt = $approval->due_at->copy()->subMinutes($sla);
                    $warningAt = $startedAt->copy()->addMinutes((int) ceil($sla * 0.8));
                    $label = str_replace('_', ' ', (string) ($approval->approval_role ?: $approval->action));

                    if (! $approval->reminded_at && now()->gte($warningAt) && now()->lt($approval->due_at)) {
                        $this->notifyTenant($approval->tenant_id, 'Approval SLA warning', $label.' for '.class_basename($approval->entity_type).' #'.$approval->entity_id.' is approaching its deadline.');
                        $approval->update(['reminded_at'=>now()]);
                        $reminded++;
                    }

                    if (! $approval->escalated_at && now()->gte($approval->due_at)) {
                        $this->notifyTenant($approval->tenant_id, 'Approval SLA breached', $label.' for '.class_basename($approval->entity_type).' #'.$approval->entity_id.' exceeded its SLA and requires escalation.');
                        $approval->update(['escalated_at'=>now()]);
                        $escalated++;
                    }
                }
            });

        $this->info("Approval SLA check complete. Reminders: {$reminded}; escalations: {$escalated}.");
        return self::SUCCESS;
    }

    private function notifyTenant(int $tenantId, string $title, string $message): void
    {
        User::where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->whereIn('role', ['ADMIN','CEO','MANAGER'])
            ->each(fn (User $user) => CreatePlatformNotification::dispatch(
                $user->tenant_id,
                $user->id,
                $title,
                $message,
                'WORKFLOW',
                route('workflow.approvals.index')
            ));
    }
}
