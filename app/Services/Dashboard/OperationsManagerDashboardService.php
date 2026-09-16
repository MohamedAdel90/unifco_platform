<?php

namespace App\Services\Dashboard;

use App\Models\{Asset, Customer, CustomerSite, ServiceContract, ServiceRequest, User, WorkOrder};
use Illuminate\Database\Eloquent\Builder;

/**
 * Server-side data gateway for the Operations Manager command center.
 *
 * Every root query is scope constrained before optional UI filters are applied.
 * This keeps customer/site/contract selectors as presentation filters only.
 */
class OperationsManagerDashboardService
{
    public function __construct(private OperationsManagerScopeService $scope) {}

    public function customers(User $user): Builder
    {
        return $this->scope->scoped(Customer::query(), $user);
    }

    public function sites(User $user): Builder
    {
        return $this->scope->scoped(CustomerSite::query(), $user);
    }

    public function contracts(User $user): Builder
    {
        return $this->scope->scoped(ServiceContract::query(), $user);
    }

    public function assets(User $user): Builder
    {
        return $this->scope->scoped(Asset::query(), $user);
    }

    public function workOrders(User $user): Builder
    {
        return $this->scope->scoped(WorkOrder::query(), $user);
    }

    public function serviceRequests(User $user): Builder
    {
        return $this->scope->scoped(ServiceRequest::query(), $user);
    }
}
