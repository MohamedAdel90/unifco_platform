<?php

namespace App\Scopes;

use App\Models\{Asset,Customer,CustomerSite,Project,ServiceContract};
use App\Services\ScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Runtime tenant + data-scope enforcement for operational Eloquent models.
 *
 * The authenticated user is resolved at query time so the scope remains safe
 * for long-running workers. Tenant isolation applies to every tenant-owned
 * operational model; business-data scope enforcement applies to direct scope
 * dimensions and one-level parent-derived dimensions.
 */
class RuntimeDataScope implements Scope
{
    public const DIMENSION_COLUMNS = [
        'organization_id',
        'department_id',
        'branch_id',
        'project_id',
        'customer_site_id',
        'site_id',
        'customer_id',
        'asset_id',
        'contract_id',
        'created_by',
        'user_id',
        'employee_id',
        'assigned_to',
        'assigned_user_id',
    ];

    private const PARENT_SCOPE_RELATIONS = [
        'asset','project','customer','customerSite','site','contract','serviceContract','workOrder','employee',
        'purchaseOrder','purchaseRequisition','warehouse','productionOrder',
    ];

    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('auth')) {
            return;
        }

        $user = auth()->user();
        if (! $user) {
            return;
        }

        $table = $model->getTable();
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasColumn($table, 'tenant_id')) {
            $builder->where($model->qualifyColumn('tenant_id'), $user->tenant_id);
        }

        if (! $this->isScopeCapable($model)) {
            return;
        }

        app(ScopeService::class)->apply($builder, $user);
    }

    public function isScopeCapable(Model $model): bool
    {
        if ($this->hasDirectScopeCapability($model)) {
            return true;
        }

        foreach (self::PARENT_SCOPE_RELATIONS as $relationName) {
            if (! method_exists($model, $relationName)) {
                continue;
            }

            try {
                $relation = $model->{$relationName}();
                $related = $relation->getRelated();
                if ($this->hasDirectScopeCapability($related)) {
                    return true;
                }
            } catch (Throwable) {
                // Ignore non-Eloquent helper methods that happen to share a name.
            }
        }

        return false;
    }

    private function hasDirectScopeCapability(Model $model): bool
    {
        if ($model instanceof Project || $model instanceof Asset || $model instanceof Customer || $model instanceof CustomerSite || $model instanceof ServiceContract) {
            return true;
        }

        $table = $model->getTable();
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach (self::DIMENSION_COLUMNS as $column) {
            if (Schema::hasColumn($table, $column)) {
                return true;
            }
        }

        return false;
    }
}
