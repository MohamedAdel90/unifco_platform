<?php

namespace App\Services;

use App\Models\{Asset,Customer,CustomerSite,ServiceContract};
use Illuminate\Database\Eloquent\Builder;

class ServiceContractCoverageResolver
{
    /**
     * Resolve contract coverage conservatively.
     *
     * A customer-level active contract is not enough to prove that a specific
     * asset is covered. Until contract scope is fully normalized, the asset's
     * explicit contract_reference is the authoritative link. This prevents an
     * unrelated active customer contract from making an uncovered asset appear
     * IN_CONTRACT and leaking into downstream finance/SLA decisions.
     */
    public function resolve(Customer $customer, ?CustomerSite $site = null, ?Asset $asset = null): ?ServiceContract
    {
        if (! $asset || (int) $asset->customer_id !== (int) $customer->id) {
            return null;
        }

        if ($site && $asset->customer_site_id && (int) $asset->customer_site_id !== (int) $site->id) {
            return null;
        }

        $reference = trim((string) $asset->contract_reference);
        if ($reference === '') {
            return null;
        }

        return $this->activeContracts($customer)
            ->where('contract_no', $reference)
            ->orderByDesc('starts_on')
            ->first();
    }

    public function isCovered(Customer $customer, ?CustomerSite $site = null, ?Asset $asset = null): bool
    {
        return (bool) $this->resolve($customer, $site, $asset);
    }

    private function activeContracts(Customer $customer): Builder
    {
        return ServiceContract::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'ACTIVE')
            ->where(fn (Builder $query) => $query->whereNull('starts_on')->orWhere('starts_on', '<=', today()))
            ->where(fn (Builder $query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', today()));
    }
}
