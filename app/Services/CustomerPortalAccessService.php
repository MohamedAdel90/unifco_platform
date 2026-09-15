<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class CustomerPortalAccessService
{
    /**
     * Customer Portal access model (single-login policy)
     *
     * UNIFCO issues one portal login per customer. That login represents the
     * customer account itself, not an employee persona inside the customer.
     * Therefore the same authenticated customer user must be able to see and
     * act on the customer's technical, operational, commercial and financial
     * records. Internal UNIFCO authorization remains separate.
     */
    public const ROLES=['CUSTOMER_ACCOUNT'];

    private const SECTIONS=[
        'dashboard','requests','quotations','timeline','contracts','sites','assets',
        'work-orders','visits','maintenance','spare-parts','invoices','reports','sla',
        'documents','notifications',
    ];

    public function role(User $user): string
    {
        return 'CUSTOMER_ACCOUNT';
    }

    public function canSection(User $user,string $section): bool
    {
        return $user->role==='CUSTOMER'
            && !empty($user->customer_id)
            && in_array($section,self::SECTIONS,true);
    }

    public function allowedSections(User $user): array
    {
        return $user->role==='CUSTOMER' && !empty($user->customer_id)
            ? self::SECTIONS
            : [];
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
     * Customer users are no longer allowed to create additional portal users.
     * Account provisioning/reset is an internal UNIFCO administration action.
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
     * Single customer login always sees the complete customer scope.
     * Customer ownership checks in controllers/queries remain mandatory so no
     * data can cross from one customer_id to another.
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
        // Full scope inside this customer's account. Ownership is enforced by
        // the calling controller/query using customer_id.
    }

    public function assertContract(User $user,int $contractId): void
    {
        // Full scope inside this customer's account. Ownership is enforced by
        // the calling controller/query using customer_id.
    }

    private function isCustomerAccount(User $user): bool
    {
        return $user->role==='CUSTOMER' && !empty($user->customer_id);
    }
}
