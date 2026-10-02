<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\ProjectUserAssignment;
use App\Models\ServiceRequest;
use App\Models\Tenant;
use App\Services\ServiceRequestWorkflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProductionRequestLifecycleSmoke extends Command
{
    protected $signature = 'unifco:production-request-smoke {--tenant=UNIFCO} {--customer-id=100} {--confirm-production}';

    protected $description = 'Run a rollback-only production smoke for the real service-request lifecycle.';

    public function handle(ServiceRequestWorkflowService $workflow): int
    {
        if (! $this->option('confirm-production')) {
            $this->error('Refusing to run without --confirm-production.');
            return self::FAILURE;
        }

        if (app()->environment('testing')) {
            $this->error('This command is intended for deployed environment smoke verification, not automated test databases.');
            return self::FAILURE;
        }

        foreach (['tenants', 'customers', 'assets', 'projects', 'project_user_assignments', 'operational_domains', 'user_operational_domains', 'service_requests', 'service_request_status_histories'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Required table [{$table}] is missing.");
                return self::FAILURE;
            }
        }

        $tenant = Tenant::query()->where('code', strtoupper((string) $this->option('tenant')))->first();
        if (! $tenant) {
            $this->error('Target tenant not found.');
            return self::FAILURE;
        }

        $customerId = (int) $this->option('customer-id');
        $customerQuery = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->whereRaw("UPPER(COALESCE(status, 'ACTIVE')) = 'ACTIVE'");

        if ($customerId > 0) {
            $customerQuery->where('id', $customerId);
        }

        $customer = $customerQuery->first();
        if (! $customer) {
            $this->error($customerId > 0
                ? "Active customer [{$customerId}] not found in tenant [{$tenant->code}]."
                : 'No active customer found for the target tenant.');
            return self::FAILURE;
        }

        $routing = Asset::query()
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->whereNotNull('project_id')
            ->whereNotNull('operational_domain_id')
            ->whereRaw("UPPER(COALESCE(status, 'ACTIVE')) = 'ACTIVE'")
            ->orderBy('id')
            ->get()
            ->map(function (Asset $asset): ?array {
                $operationsManagerId = DB::table('user_operational_domains')
                    ->where('operational_domain_id', $asset->operational_domain_id)
                    ->where('assignment_type', 'PRIMARY')
                    ->orderBy('priority')
                    ->orderBy('user_id')
                    ->value('user_id');

                $projectManagerId = ProjectUserAssignment::query()
                    ->where('project_id', $asset->project_id)
                    ->where('project_role', 'PROJECT_MANAGER')
                    ->where('status', 'ACTIVE')
                    ->orderBy('id')
                    ->value('user_id');

                if (! $operationsManagerId || ! $projectManagerId) {
                    return null;
                }

                return [
                    'asset' => $asset,
                    'operations_manager_id' => (int) $operationsManagerId,
                    'project_manager_id' => (int) $projectManagerId,
                ];
            })
            ->filter()
            ->first();

        if (! $routing) {
            $this->error("Customer [{$customer->id}] has no active asset with complete project + operational-domain routing (PRIMARY Operations Manager and active Project Manager).");
            return self::FAILURE;
        }

        /** @var Asset $asset */
        $asset = $routing['asset'];
        $reference = 'SMOKE-'.now()->format('YmdHis').'-'.strtoupper(Str::random(6));

        DB::beginTransaction();

        try {
            $request = ServiceRequest::query()->create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'asset_id' => $asset->id,
                'project_id' => $asset->project_id,
                'operational_domain_id' => $asset->operational_domain_id,
                'operations_manager_id' => $routing['operations_manager_id'],
                'project_manager_id' => $routing['project_manager_id'],
                'request_number' => $reference,
                'type' => 'CORRECTIVE',
                'priority' => 'NORMAL',
                'status' => 'SUBMITTED',
                'source' => 'SYSTEM',
                'title' => 'Production request lifecycle smoke',
                'description' => 'Rollback-only production lifecycle verification.',
                'requested_by_name' => 'UNIFCO Production Smoke',
                'requested_at' => now(),
            ]);

            $steps = [
                ['TRIAGE', 'IN_PROGRESS'],
                ['IN_PROGRESS', 'WAITING_QUOTATION'],
                ['WAITING_QUOTATION', 'WAITING_CUSTOMER_APPROVAL'],
                ['WAITING_CUSTOMER_APPROVAL', 'IN_PROGRESS'],
                ['IN_PROGRESS', 'WAITING_CUSTOMER_CONFIRMATION'],
                ['WAITING_CUSTOMER_CONFIRMATION', 'CLOSED'],
            ];

            $expectedSequence = 1;

            foreach ($steps as [$toStatus, $expectedCanonical]) {
                $request = $workflow->transition($request, $toStatus, null, 'Production smoke transition.');
                $request->refresh();

                $latest = $request->statusHistories()->orderByDesc('sequence')->first();
                if (! $latest || (int) $latest->sequence !== $expectedSequence) {
                    throw new RuntimeException("Unexpected history sequence after {$toStatus}.");
                }

                if (strtoupper((string) $request->status) !== $expectedCanonical) {
                    throw new RuntimeException("Unexpected canonical status after {$toStatus}: {$request->status}.");
                }

                if (! $request->assigned_user_id) {
                    throw new RuntimeException("No resolved owner for {$toStatus}.");
                }

                $this->line(sprintf(
                    '%d. %-31s canonical=%-29s owner=%s role=%s reason=%s',
                    $expectedSequence,
                    $toStatus,
                    $request->status,
                    $request->assigned_user_id,
                    $request->assigned_role ?: '-',
                    $request->routing_reason ?: '-'
                ));

                $expectedSequence++;
            }

            if ($request->statusHistories()->count() !== count($steps)) {
                throw new RuntimeException('Lifecycle history row count mismatch.');
            }

            $this->info("Production request lifecycle smoke passed for {$reference}; transaction will be rolled back.");
            $this->line(sprintf(
                'Routing fixture: customer=%s asset=%s project=%s domain=%s operations_manager=%s project_manager=%s',
                $customer->id,
                $asset->id,
                $asset->project_id,
                $asset->operational_domain_id,
                $routing['operations_manager_id'],
                $routing['project_manager_id']
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Production request lifecycle smoke failed: '.$exception->getMessage());
            return self::FAILURE;
        } finally {
            DB::rollBack();
        }
    }
}
