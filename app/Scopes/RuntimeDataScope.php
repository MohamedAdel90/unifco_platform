<?php

namespace App\Scopes;

use App\Models\{Asset, Project};
use App\Services\ScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

/**
 * Runtime tenant + data-scope enforcement for operational Eloquent models.
 *
 * The scope is intentionally resolved against the current authenticated user
 * at query time (rather than capturing a user in a static closure) so it is
 * safe for long-running workers such as Octane.  Models that do not expose a
 * supported scope dimension are left untouched; tenant isolation is still
 * applied whenever the model has tenant_id.
 */
class RuntimeDataScope implements Scope
{
    public const DIMENSION_COLUMNS = [
        'organization_id',
        'department_id',
        'branch_id',
        'project_id',
        'customer_site_id',
        'customer_id',
        'asset_id',
        'contract_id',
        'created_by',
        'assigned_to',
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

        // Tenant isolation is mandatory for every tenant-owned operational model.
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
        if ($model instanceof Project || $model instanceof Asset) {
            return true;
        }

        $table = $model->getTable();
        foreach (self::DIMENSION_COLUMNS as $column) {
            if (Schema::hasColumn($table, $column)) {
                return true;
            }
        }

        return false;
    }
}
