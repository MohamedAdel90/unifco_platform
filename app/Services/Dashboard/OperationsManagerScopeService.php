<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\ScopeService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centralises server-side data scoping for the Operations Manager dashboard.
 *
 * Dashboard filters must only narrow the user's authorised data set; they must
 * never be used as an access-control mechanism. Every dashboard query should
 * pass through this service before customer/site/contract filters are applied.
 */
class OperationsManagerScopeService
{
    public function __construct(private ScopeService $scopes) {}

    public function apply(Builder $query, User $user): Builder
    {
        return $this->scopes->apply($query, $user);
    }

    public function scoped(Builder $query, User $user): Builder
    {
        return $this->apply($query, $user);
    }
}
