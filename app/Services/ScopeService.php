<?php

namespace App\Services;

use App\Models\User;
use App\Models\{Asset,Project};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScopeService
{
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
        if ($scopes->contains(fn ($scope) => $scope->scope_type === 'GLOBAL')) return true;
        $context = $resource instanceof Model ? $this->contextFromModel($resource) : $resource;
        foreach ($scopes as $scope) {
            $type = strtoupper((string) $scope->scope_type);
            if ($type === 'OWN_RECORDS' && isset($context['owner_user_id']) && (int) $context['owner_user_id'] === (int) $user->id) return true;
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
        $applicable=$scopes->map(function($scope) use($model,$user) {
            $type = strtoupper((string) $scope->scope_type);
            $column = match ($type) {
                    'COMPANY' => 'organization_id', 'DEPARTMENT' => 'department_id', 'BRANCH' => 'branch_id',
                    'PROJECT' => $model instanceof Project ? 'id' : 'project_id', 'SITE' => 'customer_site_id', 'CUSTOMER' => 'customer_id',
                    'ASSET' => $model instanceof Asset ? 'id' : 'asset_id',
                    'CONTRACT' => 'contract_id', 'OWN_RECORDS' => 'created_by', 'ASSIGNED_RECORDS' => 'assigned_to', default => null,
            };
            if(!$column || !Schema::hasColumn($model->getTable(),$column)) return null;
            return [$model->qualifyColumn($column),in_array($type,['OWN_RECORDS','ASSIGNED_RECORDS'],true)?$user->id:$scope->scope_id];
        })->filter();
        if($applicable->isEmpty()) return $query->whereRaw('1 = 0');
        return $query->where(function (Builder $builder) use ($applicable): void {
            foreach ($applicable as [$column,$value]) {
                $builder->orWhere($column,$value);
            }
        });
    }

    private function contextFromModel(Model $model): array
    {
        $context = [];
        if($model instanceof Project) $context['project_id']=$model->getKey();
        if($model instanceof Asset) $context['asset_id']=$model->getKey();
        foreach (['organization_id' => 'company_id', 'department_id' => 'department_id', 'branch_id' => 'branch_id', 'project_id' => 'project_id', 'customer_site_id' => 'site_id', 'site_id' => 'site_id', 'customer_id' => 'customer_id', 'contract_id' => 'contract_id', 'created_by' => 'owner_user_id', 'user_id' => 'owner_user_id', 'assigned_to' => 'assigned_user_id', 'assigned_user_id' => 'assigned_user_id'] as $attribute => $key) {
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
