<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerPortalAccessService
{
    public const ROLES=['CUSTOMER_ADMIN','SITE_MANAGER','FINANCE','VIEWER'];

    private const SECTIONS=[
        'CUSTOMER_ADMIN'=>['dashboard','requests','quotations','timeline','contracts','assets','work-orders','maintenance','invoices','reports','sla','documents','notifications'],
        'SITE_MANAGER'=>['dashboard','requests','timeline','assets','work-orders','maintenance','reports','sla','documents','notifications'],
        'FINANCE'=>['dashboard','quotations','timeline','contracts','invoices','reports','documents','notifications'],
        'VIEWER'=>['dashboard','requests','quotations','timeline','contracts','assets','work-orders','maintenance','invoices','reports','sla','documents','notifications'],
    ];

    public function role(User $user): string
    {
        // Existing CUSTOMER accounts predate customer_portal_role. Preserve their historical
        // full portal behavior until an administrator explicitly assigns a scoped role.
        $role=strtoupper(trim((string)$user->customer_portal_role));
        if($role==='') return 'CUSTOMER_ADMIN';
        return in_array($role,self::ROLES,true)?$role:'VIEWER';
    }

    public function canSection(User $user,string $section): bool
    {
        return in_array($section,self::SECTIONS[$this->role($user)]??[],true);
    }

    public function allowedSections(User $user): array
    {
        return self::SECTIONS[$this->role($user)]??self::SECTIONS['VIEWER'];
    }

    public function canCreateServiceRequest(User $user): bool
    {
        return in_array($this->role($user),['CUSTOMER_ADMIN','SITE_MANAGER'],true);
    }

    public function canDecideQuotation(User $user): bool
    {
        return in_array($this->role($user),['CUSTOMER_ADMIN','FINANCE'],true);
    }

    public function canAcceptWork(User $user): bool
    {
        return in_array($this->role($user),['CUSTOMER_ADMIN','SITE_MANAGER'],true);
    }

    public function canManageUsers(User $user): bool
    {
        return $this->role($user)==='CUSTOMER_ADMIN';
    }

    public function isReadOnly(User $user): bool
    {
        return $this->role($user)==='VIEWER';
    }

    public function scopedIds(User $user,string $type): Collection
    {
        return DB::table('customer_portal_user_scopes')->where('user_id',$user->id)->where('scope_type',strtoupper($type))->pluck('scope_id');
    }

    public function accessibleSiteIds(User $user): ?Collection
    {
        if($this->role($user)==='CUSTOMER_ADMIN') return null;
        return $this->scopedIds($user,'SITE');
    }

    public function accessibleContractIds(User $user): ?Collection
    {
        if($this->role($user)==='CUSTOMER_ADMIN') return null;
        return $this->scopedIds($user,'CONTRACT');
    }

    public function accessibleAssetIds(User $user): ?Collection
    {
        if($this->role($user)==='CUSTOMER_ADMIN') return null;
        $explicit=$this->scopedIds($user,'ASSET');
        $sites=$this->accessibleSiteIds($user);
        $query=DB::table('assets')->where('customer_id',$user->customer_id);
        if($sites && $sites->isNotEmpty()) $query->whereIn('customer_site_id',$sites);
        elseif($explicit->isEmpty()) return collect();
        $siteAssets=$query->pluck('id');
        return $explicit->merge($siteAssets)->unique()->values();
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
