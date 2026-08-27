<?php

namespace App\Services;

use App\Models\{Customer,CustomerActivityEvent,Organization,Tenant};
use Illuminate\Support\Facades\DB;

class CustomerLifecycleService
{
    public function resolveForPublicRequest(object $public, Tenant $tenant, Organization $organization): Customer
    {
        return DB::transaction(function () use ($public, $tenant, $organization) {
            $query = Customer::query()->where('tenant_id', $tenant->id);
            $customer = null;

            if (! empty($public->commercial_registration)) {
                $customer = (clone $query)->where('commercial_registration', $public->commercial_registration)->first();
            }
            if (! $customer && ! empty($public->email)) {
                $customer = (clone $query)->whereRaw('LOWER(email) = ?', [mb_strtolower($public->email)])->first();
            }
            if (! $customer && ! empty($public->mobile)) {
                $customer = (clone $query)->where('phone', $public->mobile)->first();
            }
            if (! $customer && ! empty($public->company_name)) {
                $customer = (clone $query)->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($public->company_name))])->first();
            }

            if (! $customer) {
                $next = ((int) Customer::where('tenant_id', $tenant->id)->max('id')) + 1;
                $customer = Customer::create([
                    'tenant_id' => $tenant->id,
                    'organization_id' => $organization->id,
                    'customer_code' => 'CUS-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT),
                    'name' => $public->company_name ?: ($public->responsible_person ?: 'Public Request Customer'),
                    'commercial_registration' => $public->commercial_registration,
                    'email' => $public->email,
                    'contact_name' => $public->responsible_person,
                    'phone' => $public->mobile,
                    'city' => $public->site_city,
                    'address' => $public->site_address,
                    'country' => 'Saudi Arabia',
                    'status' => 'ONBOARDING',
                    'onboarding_status' => 'PENDING',
                    'acquisition_source' => 'WEBSITE',
                    'first_touch_at' => now(),
                ]);

                $this->record($customer, 'CUSTOMER_ONBOARDING_STARTED', 'Customer onboarding started', 'Created automatically from the first public service request.', null, ['acquisition_source'=>'WEBSITE']);
            }

            return $customer;
        });
    }

    public function record(Customer $customer, string $type, string $title, ?string $description = null, ?object $reference = null, array $metadata = [], string $visibility = 'BOTH'): CustomerActivityEvent
    {
        return CustomerActivityEvent::create([
            'tenant_id' => $customer->tenant_id,
            'organization_id' => $customer->organization_id,
            'customer_id' => $customer->id,
            'event_type' => $type,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
            'title' => $title,
            'description' => $description,
            'visibility' => $visibility,
            'metadata' => $metadata,
        ]);
    }
}
