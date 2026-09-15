<?php

namespace App\Services;

use App\Models\{Asset,ServiceContract,User};
use Illuminate\Support\Collection;

class CustomerPortalAccessService
{
    /**
     * Customer Portal access model (single-login policy).
     *
     * UNIFCO issues one portal login per customer. The login represents the
     * customer account itself, not a department/persona inside the customer.
     * The same user therefore sees technical, operational, commercial and
     * financial customer data. CUSTOMER_ADMIN is retained only as a legacy
     * compatibility label for existing dashboard/action logic.
     */
    public const ROLES=['CUSTOMER_ADMIN'];

    private const SECTIONS=[
        'dashboard','requests','quotations','timeline','contracts','sites','assets',
        'work-orders','visits','maintenance','spare-parts','invoices','reports','sla',
        'documents','notifications',
    ];

    public function role(User $user): string
    {
        return 'CUSTOMER_ADMIN';
    }

    public function canSection(User $user,string $section): bool
    {
        return $this->isCustomerAccount($user) && in_array($section,self::SECTIONS,true);
    }

    public function allowedSections(User $user): array
    {
        return $this->isCustomerAccount($user) ? self::SECTIONS : [];
    }

    public function canCreateServiceRequest(User $user): bool
    {
        return $this->isCustomerAccount($user);
    }

    public function canDecideQuotation(User $user): bool
    {
        return $this->isCustomerAccount($user);
    }

    public function canAcceptWork(User $user): bool
    {
        return $this->isCustomerAccount($user);
    }

    /**
     * A customer login cannot create additional customer-portal users.
     * Provisioning, reset and replacement of the single login are internal
     * UNIFCO administration actions.
     */
    public function canManageUsers(User $user): bool
    {
        return false;
    }

    public function isReadOnly(User $user): bool
    {
        return false;
    }

    /**
     * Null means unrestricted inside the authenticated customer's root scope.
     * Every detail assertion below still verifies customer ownership server-side.
     */
    public function accessibleSiteIds(User $user): ?Collection
    {
        return null;
    }

    public function accessibleContractIds(User $user): ?Collection
    {
        return null;
    }

    public function accessibleAssetIds(User $user): ?Collection
    {
        return null;
    }

    public function assertAsset(User $user,int $assetId): void
    {
        abort_unless(
            $this->isCustomerAccount($user)
            && Asset::whereKey($assetId)->where('customer_id',$user->customer_id)->exists(),
            404
        );
    }

    public function assertContract(User $user,int $contractId): void
    {
        abort_unless(
            $this->isCustomerAccount($user)
            && ServiceContract::whereKey($contractId)->where('customer_id',$user->customer_id)->exists(),
            404
        );
    }

    private function isCustomerAccount(User $user): bool
    {
        return $user->role==='CUSTOMER' && !empty($user->customer_id);
    }
}
