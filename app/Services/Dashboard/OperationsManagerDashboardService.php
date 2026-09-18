<?php

namespace App\Services\Dashboard;

use App\Models\{Asset, Customer, CustomerSite, ServiceContract, ServiceRequest, User, WorkOrder};
use Illuminate\Database\Eloquent\Builder;

/**
 * Server-side data gateway for the Operations Manager command center.
 *
 * Scope is applied before any UI filter. Customer/site/contract dimensions are
 * derived from the user's visible assets as well as direct scopes so a SITE or
 * ASSET scoped manager cannot discover unrelated customers in filter options.
 */
class OperationsManagerDashboardService
{
    public function __construct(private OperationsManagerScopeService $scope) {}

    public function customers(User $user): Builder
    {
        $visibleCustomerIds = $this->assets($user)->select('customer_id');

        return $this->scope->scoped(Customer::query(), $user)
            ->orWhereIn('id', $visibleCustomerIds);
    }

    public function sites(User $user): Builder
    {
        $visibleSiteIds = $this->assets($user)->select('customer_site_id')->whereNotNull('customer_site_id');

        return $this->scope->scoped(CustomerSite::query(), $user)
            ->orWhereIn('id', $visibleSiteIds);
    }

    public function contracts(User $user): Builder
    {
        $visibleContractIds = $this->workOrders($user)->select('service_contract_id')->whereNotNull('service_contract_id');

        return $this->scope->scoped(ServiceContract::query(), $user)
            ->orWhereIn('id', $visibleContractIds);
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
