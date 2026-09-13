<?php

namespace App\Services;

use App\Models\User;
use App\Models\{Asset,Customer,CustomerSite,Project,ServiceContract};
use App\Scopes\RuntimeDataScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ScopeService
{
    private const PARENT_SCOPE_RELATIONS = [
        'asset','project','customer','customerSite','site','contract','serviceContract','workOrder','employee',
        'purchaseOrder','purchaseRequisition','warehouse','productionOrder',
    ];

    public function forUser(User $user): Collection
    {
        if (! Schema::hasTable('user_scopes')) return collect();
        return DB::table('user_scopes')->join('access_scopes', 'access_scopes.id', '=', 'user_scopes.access_scope_id')
            ->where('user_scopes.tenant_id', $user->tenant_id)->where('user_scopes.user_id', $user->id)
            ->where('access_scopes.is_active', true)
            ->where(fn ($query) => $query->whereNull('user_scopes.expires_at')->orWhere('user_scopes.expires_at', '>', now()))
            ->get(['access_scopes.id', 'access_scopes.scope_type', 'access_scopes.scope_id', 'access_scopes.name', 'access_scopes.constraints']);
    }

    public function allows(User $user, Model|array $resource): bool
    {
        if($this->isLegacyUnassigned($user)) return true;
        $scopes = $this->forUser($user);
        if ($scopes->isEmpty()) return false;
        if ($scopes->contains(fn ($scope) => strtoupper((string) $scope->scope_type) === 'GLOBAL')) return true;
        $context = $resource instanceof Model ? $this->contextFromModel($resource) : $resource;
        foreach ($scopes as $scope) {
            $type = strtoupper((string) $scope->scope_type);
            if ($type === 'OWN_RECORDS' && isset($context['owner_user_id']) && (int) $context['owner_user_id'] === (int) $user->id) return true;
            if ($type === 'OWN_RECORDS' && isset($context['employee_id']) && $user->employee_id && (int) $context['employee_id'] === (int) $user->employee_id) return true;
            if ($type === 'ASSIGNED_RECORDS' && isset($context['assigned_user_id']) && (int) $context['assigned_user_id'] === (int) $user->id) return true;
            $key = strtolower($type).'_id';
            if (isset($context[$key]) && (int) $context[$key] === (int) $scope->scope_id) return true;
        }
        return false;
    }

    public function apply(Builder $query, User $user): Builder
    {
        if($this->isLegacyUnassigned($user)) return $query;
        $scopes = $this->forUser($user);
        if ($scopes->contains(fn ($scope) => strtoupper((string) $scope->scope_type) === 'GLOBAL')) return $query;
        if ($scopes->isEmpty()) return $query->whereRaw('1 = 0');

        $model = $query->getModel();
        $predicates = collect();

        foreach ($scopes as $scope) {
            $type = strtoupper((string) $scope->scope_type);
            foreach ($this->directColumns($model, $type, $user) as [$column,$value]) {
                if ($value !== null && Schema::hasColumn($model->getTable(), $column)) {
                    $predicates->push(['column', $model->qualifyColumn($column), $value]);
                }
            }

            $root = $this->rootIdentityPredicate($model, $type, $scope->scope_id);
            if ($root) {
                $predicates->push(['column', $model->qualifyColumn($model->getKeyName()), $root]);
            }

            if ($type === 'DEPARTMENT') {
                $department = trim((string) ($scope->name ?? ''));
                if ($department !== '') {
                    if (method_exists($model, 'position')) $predicates->push(['department-relation','position',$department]);
                    if (method_exists($model, 'employee')) $predicates->push(['employee-department','employee',$department]);
                }
            }
        }

        // Child/detail models inherit visibility from a scope-capable parent. This
        // closes direct child endpoints without duplicating every parent's scope columns.
        foreach (self::PARENT_SCOPE_RELATIONS as $relationName) {
            if (! method_exists($model, $relationName)) continue;
            try {
                $relation = $model->{$relationName}();
                $related = $relation->getRelated();
                if ((new RuntimeDataScope())->isScopeCapable($related)) {
                    $predicates->push(['parent-relation', $relationName, null]);
                }
            } catch (Throwable) {
                // A non-Eloquent helper method with a matching name is ignored.
            }
        }

        if($predicates->isEmpty()) return $query->whereRaw('1 = 0');

        return $query->where(function (Builder $builder) use ($predicates): void {
            foreach ($predicates->unique(fn($p)=>implode('|',array_map(fn($v)=>is_scalar($v)||$v===null?(string)$v:gettype($v),$p))) as $predicate) {
                [$kind,$target,$value] = $predicate;
                if ($kind === 'column') {
                    $builder->orWhere($target,$value);
                } elseif ($kind === 'department-relation') {
                    $builder->orWhereHas($target,fn(Builder $q)=>$q->where('department',$value));
                } elseif ($kind === 'employee-department') {
                    $builder->orWhereHas($target,fn(Builder $q)=>$q->whereHas('position',fn(Builder $p)=>$p->where('department',$value)));
                } elseif ($kind === 'parent-relation') {
                    // The related model's RuntimeDataScope is applied automatically.
                    $builder->orWhereHas($target);
                }
            }
        });
    }

    private function directColumns(Model $model, string $type, User $user): array
    {
        return match ($type) {
            'COMPANY' => [['organization_id', null]],
            'DEPARTMENT' => [['department_id', null]],
            'BRANCH' => [['branch_id', null]],
            'PROJECT' => $model instanceof Project ? [] : [['project_id', null]],
            'SITE' => $model instanceof CustomerSite ? [] : [['customer_site_id', null],['site_id', null]],
            'CUSTOMER' => $model instanceof Customer ? [] : [['customer_id', null]],
            'ASSET' => $model instanceof Asset ? [] : [['asset_id', null]],
            'CONTRACT' => $model instanceof ServiceContract ? [] : [['contract_id', null]],
            'OWN_RECORDS' => [
                ['created_by',$user->id],
                ['user_id',$user->id],
                ['employee_id',$user->employee_id],
            ],
            'ASSIGNED_RECORDS' => [
                ['assigned_to',$user->id],
                ['assigned_user_id',$user->id],
            ],
            default => [],
        };
    }

    private function rootIdentityPredicate(Model $model, string $type, mixed $scopeId): ?int
    {
        if ($scopeId === null) return null;
        return match (true) {
            $type === 'PROJECT' && $model instanceof Project,
            $type === 'ASSET' && $model instanceof Asset,
            $type === 'CUSTOMER' && $model instanceof Customer,
            $type === 'SITE' && $model instanceof CustomerSite,
            $type === 'CONTRACT' && $model instanceof ServiceContract => (int) $scopeId,
            default => null,
        };
    }

    private function contextFromModel(Model $model): array
    {
        $context = [];
        if($model instanceof Project) $context['project_id']=$model->getKey();
        if($model instanceof Asset) $context['asset_id']=$model->getKey();
        if($model instanceof Customer) $context['customer_id']=$model->getKey();
        if($model instanceof CustomerSite) $context['site_id']=$model->getKey();
        if($model instanceof ServiceContract) $context['contract_id']=$model->getKey();
        foreach (['organization_id' => 'company_id', 'department_id' => 'department_id', 'branch_id' => 'branch_id', 'project_id' => 'project_id', 'customer_site_id' => 'site_id', 'site_id' => 'site_id', 'customer_id' => 'customer_id', 'contract_id' => 'contract_id', 'employee_id' => 'employee_id', 'created_by' => 'owner_user_id', 'user_id' => 'owner_user_id', 'assigned_to' => 'assigned_user_id', 'assigned_user_id' => 'assigned_user_id'] as $attribute => $key) {
            if ($model->getAttribute($attribute) !== null) $context[$key] = $model->getAttribute($attribute);
        }
        if($model->getAttribute('asset_id') && method_exists($model,'asset')) {
            $asset=$model->relationLoaded('asset')?$model->getRelation('asset'):$model->asset()->first();
            if($asset) $context=array_merge($context,$this->contextFromModel($asset));
        }
        return $context;
    }

    private function isLegacyUnassigned(User $user): bool
    {
        return !Schema::hasTable('user_roles') || !DB::table('user_roles')->where('user_id',$user->id)->whereNull('revoked_at')->exists();
    }
}
