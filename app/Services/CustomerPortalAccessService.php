<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class CustomerPortalAccessService
{
    public const ROLE = 'CUSTOMER_ACCOUNT';

    /**
     * A customer has one shared company portal account. Every customer portal login sees the
     * complete customer workspace; authorization boundaries remain customer_id and tenant_id.
     */
    private const SECTIONS = [
        'dashboard', 'requests', 'quotations', 'timeline', 'contracts', 'sites', 'assets',
        'work-orders', 'visits', 'maintenance', 'spare-parts', 'invoices', 'reports',
        'sla', 'documents', 'notifications',
    ];

    public function role(User $user): string
    {
        return self::ROLE;
    }

    public function canSection(User $user,string $section): bool
    {
        return in_array($section, self::SECTIONS, true);
    }

    public function allowedSections(User $user): array
    {
        return self::SECTIONS;
    }

    public function canCreateServiceRequest(User $user): bool
    {
        return true;
    }

    public function canDecideQuotation(User $user): bool
    {
        return true;
    }

    public function canAcceptWork(User $user): bool
    {
        return true;
    }

    public function canManageUsers(User $user): bool
    {
        return false;
    }

    public function isReadOnly(User $user): bool
    {
        return false;
    }

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
        $ids=$this->accessibleAssetIds($user);
        if($ids!==null) abort_unless($ids->contains($assetId),404);
    }

    public function assertContract(User $user,int $contractId): void
    {
        $ids=$this->accessibleContractIds($user);
        if($ids!==null) abort_unless($ids->contains($contractId),404);
    }
}
