<?php

namespace App\Services;

use App\Models\{Customer,CustomerActivityEvent,Organization,Tenant};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerLifecycleService
{
    public function resolveForPublicRequest(object $public, Tenant $tenant, Organization $organization): Customer
    {
        return DB::transaction(function () use ($public, $tenant, $organization) {
            $query = Customer::query()->where('tenant_id', $tenant->id);
            $customer = null;
            $matchedBy = null;

            if (! empty($public->commercial_registration)) {
                $customer = (clone $query)->where('commercial_registration', trim((string) $public->commercial_registration))->first();
                $matchedBy = $customer ? 'commercial_registration' : null;
            }
            if (! $customer && ! empty($public->email)) {
                $customer = (clone $query)->whereRaw('LOWER(email) = ?', [mb_strtolower(trim((string) $public->email))])->first();
                $matchedBy = $customer ? 'email' : null;
            }
            if (! $customer && ! empty($public->mobile)) {
                $mobile = $this->normalizePhone((string) $public->mobile);
                $customer = (clone $query)->get()->first(fn (Customer $candidate) => $this->normalizePhone((string) $candidate->phone) === $mobile && $mobile !== '');
                $matchedBy = $customer ? 'mobile' : null;
            }
            if (! $customer && ! empty($public->company_name)) {
                $name = mb_strtolower(trim((string) $public->company_name));
                $customer = (clone $query)->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first();
                $matchedBy = $customer ? 'company_name' : null;
            }

            if ($customer) {
                $this->record($customer, 'PUBLIC_REQUEST_CUSTOMER_MATCHED', 'Existing customer matched to public request', null, null, ['matched_by' => $matchedBy]);
                return $customer;
            }

            $customer = Customer::create([
                'tenant_id' => $tenant->id,
                'organization_id' => $organization->id,
                // The schema requires a unique code. PROS-* is an intake identifier only;
                // the permanent UN-* code is assigned after CRM verification/activation.
                'customer_code' => 'PROS-'.strtoupper(Str::random(10)),
                'name' => $public->company_name ?: ($public->responsible_person ?: 'Public Request Prospect'),
                'commercial_registration' => $public->commercial_registration,
                'email' => $public->email,
                'contact_name' => $public->responsible_person,
                'contact_email' => $public->email,
                'contact_phone' => $public->mobile,
                'phone' => $public->mobile,
                'city' => $public->site_city,
                'address' => $public->site_address,
                'country' => 'Saudi Arabia',
                'status' => 'PROSPECT',
                'onboarding_status' => 'PENDING_VERIFICATION',
                'acquisition_source' => 'WEBSITE',
                'first_touch_at' => now(),
            ]);

            $this->record($customer, 'PROSPECT_CREATED', 'Prospect created from public service request', 'Pending CRM / Customer Service verification before activation.', null, [
                'acquisition_source' => 'WEBSITE',
                'permanent_customer_code_assigned' => false,
            ]);

            return $customer;
        });
    }

    public function activateProspect(Customer $customer, ?int $actorId = null): Customer
    {
        return DB::transaction(function () use ($customer, $actorId) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            if ($customer->status === 'ACTIVE' && str_starts_with((string) $customer->customer_code, 'UN-')) return $customer;

            $highest = 100;
            foreach (Customer::query()->where('tenant_id', $customer->tenant_id)->where('customer_code', 'like', 'UN-%')->lockForUpdate()->pluck('customer_code') as $code) {
                if (preg_match('/^UN-(\d+)$/', (string) $code, $matches)) $highest = max($highest, (int) $matches[1]);
            }

            do {
                $code = 'UN-'.(++$highest);
            } while (Customer::query()->where('tenant_id', $customer->tenant_id)->where('customer_code', $code)->exists());

            $oldCode = $customer->customer_code;
            $customer->update([
                'customer_code' => $code,
                'status' => 'ACTIVE',
                'onboarding_status' => 'ONBOARDING',
                'onboarding_review_status' => 'APPROVED',
                'onboarding_reviewed_by' => $actorId,
                'onboarding_reviewed_at' => now(),
            ]);

            $this->record($customer, 'PROSPECT_ACTIVATED', 'Prospect verified and activated as customer', 'Permanent customer code assigned after verification.', $customer, [
                'provisional_code' => $oldCode,
                'customer_code' => $code,
                'actor_id' => $actorId,
            ]);

            return $customer->fresh();
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

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
