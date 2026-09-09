<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function (): void {
            $tenantId = DB::table('tenants')->where('code', 'UNIFCO')->value('id');
            if (! $tenantId) {
                return;
            }

            $organizationId = DB::table('organizations')
                ->where('tenant_id', $tenantId)
                ->where('code', 'HQ')
                ->value('id');

            $now = now();

            $customerData = [
                'tenant_id' => $tenantId,
                'organization_id' => $organizationId,
                'name' => 'UNIFCO Maintenance Test Customer',
                'email' => 'maintenance.test@unifco.local',
                'status' => 'ACTIVE',
                'updated_at' => $now,
            ];

            $optionalCustomer = [
                'contact_name' => 'Maintenance Test Contact',
                'phone' => '+966500000001',
                'city' => 'Riyadh',
                'address' => 'Riyadh - UNIFCO UAT Test Site',
                'onboarding_status' => 'ACTIVE',
                'country' => 'Saudi Arabia',
            ];
            foreach ($optionalCustomer as $column => $value) {
                if (Schema::hasColumn('customers', $column)) {
                    $customerData[$column] = $value;
                }
            }

            $customer = DB::table('customers')
                ->where('tenant_id', $tenantId)
                ->where('customer_code', 'TEST-CUST-001')
                ->first();

            if ($customer) {
                DB::table('customers')->where('id', $customer->id)->update($customerData);
                $customerId = $customer->id;
            } else {
                $customerId = DB::table('customers')->insertGetId(array_merge($customerData, [
                    'customer_code' => 'TEST-CUST-001',
                    'created_at' => $now,
                ]));
            }

            DB::table('customer_sites')->updateOrInsert(
                ['customer_id' => $customerId, 'site_code' => 'SITE-RUH-001'],
                [
                    'name' => 'Riyadh Maintenance Test Site',
                    'city' => 'Riyadh',
                    'address' => 'Riyadh - UNIFCO Maintenance UAT Site',
                    'latitude' => 24.7135517,
                    'longitude' => 46.6752957,
                    'contact_name' => 'Maintenance Test Contact',
                    'contact_mobile' => '+966500000001',
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $siteId = DB::table('customer_sites')
                ->where('customer_id', $customerId)
                ->where('site_code', 'SITE-RUH-001')
                ->value('id');

            DB::table('service_contracts')->updateOrInsert(
                ['tenant_id' => $tenantId, 'contract_no' => 'CNT-TEST-001'],
                [
                    'organization_id' => $organizationId,
                    'customer_id' => $customerId,
                    'title' => 'UNIFCO Maintenance UAT Contract',
                    'starts_on' => '2026-01-01',
                    'ends_on' => '2027-12-31',
                    'contract_value' => 120000,
                    'currency' => 'SAR',
                    'billing_cycle' => 'MONTHLY',
                    'scope' => 'UAT maintenance coverage for registered test assets.',
                    'sla_summary' => 'UAT contract for validating customer, site, contract, asset and QR request flows.',
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $contractId = DB::table('service_contracts')
                ->where('tenant_id', $tenantId)
                ->where('contract_no', 'CNT-TEST-001')
                ->value('id');

            if (Schema::hasTable('projects')) {
                DB::table('projects')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'project_no' => 'PRJ-TEST-001'],
                    [
                        'organization_id' => $organizationId,
                        'name' => 'UNIFCO Maintenance UAT Project',
                        'customer_id' => $customerId,
                        'planned_start' => '2026-01-01',
                        'planned_finish' => '2027-12-31',
                        'budget' => 120000,
                        'status' => 'ACTIVE',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $assets = [
                [
                    'asset_code' => 'TEST-AST-001',
                    'customer_asset_code' => 'CUST-GEN-001',
                    'name' => 'مولد كهربائي تجريبي',
                    'asset_category' => 'POWER',
                    'asset_type' => 'Generator',
                    'manufacturer' => 'Cummins',
                    'model_no' => 'C250D5',
                    'serial_no' => 'UAT-GEN-SN-001',
                    'manufacturer_asset_number' => 'MFG-GEN-001',
                    'qr_token' => 'UNIFCO-UAT-QR-GEN-001',
                    'operational_status' => 'RUNNING',
                ],
                [
                    'asset_code' => 'TEST-AST-002',
                    'customer_asset_code' => 'CUST-PUMP-001',
                    'name' => 'مضخة مياه تجريبية',
                    'asset_category' => 'PUMPS',
                    'asset_type' => 'Water Pump',
                    'manufacturer' => 'Grundfos',
                    'model_no' => 'CR-32',
                    'serial_no' => 'UAT-PUMP-SN-001',
                    'manufacturer_asset_number' => 'MFG-PUMP-001',
                    'qr_token' => 'UNIFCO-UAT-QR-PUMP-001',
                    'operational_status' => 'RUNNING',
                ],
            ];

            foreach ($assets as $assetSeed) {
                $assetData = [
                    'organization_id' => $organizationId,
                    'customer_id' => $customerId,
                    'customer_site_id' => $siteId,
                    'name' => $assetSeed['name'],
                    'acquisition_cost' => 0,
                    'status' => 'REGISTERED',
                    'contract_reference' => 'CNT-TEST-001',
                    'updated_at' => $now,
                ];

                foreach ([
                    'customer_asset_code','asset_category','asset_type','manufacturer','model_no','serial_no',
                    'manufacturer_asset_number','qr_token','operational_status'
                ] as $column) {
                    if (Schema::hasColumn('assets', $column)) {
                        $assetData[$column] = $assetSeed[$column];
                    }
                }
                if (Schema::hasColumn('assets', 'lifecycle_status')) {
                    $assetData['lifecycle_status'] = 'ACTIVE';
                }
                if (Schema::hasColumn('assets', 'physical_location')) {
                    $assetData['physical_location'] = 'Riyadh Maintenance Test Site';
                }

                $asset = DB::table('assets')
                    ->where('tenant_id', $tenantId)
                    ->where('asset_code', $assetSeed['asset_code'])
                    ->first();

                if ($asset) {
                    DB::table('assets')->where('id', $asset->id)->update($assetData);
                    $assetId = $asset->id;
                } else {
                    $assetId = DB::table('assets')->insertGetId(array_merge($assetData, [
                        'tenant_id' => $tenantId,
                        'asset_code' => $assetSeed['asset_code'],
                        'created_at' => $now,
                    ]));
                }

                if ($contractId && Schema::hasTable('contract_assets')) {
                    DB::table('contract_assets')->updateOrInsert(
                        ['service_contract_id' => $contractId, 'asset_id' => $assetId],
                        [
                            'covered_from' => '2026-01-01',
                            'covered_until' => '2027-12-31',
                            'coverage_level' => 'FULL',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $tenantId = DB::table('tenants')->where('code', 'UNIFCO')->value('id');
            if (! $tenantId) {
                return;
            }

            $assetIds = DB::table('assets')
                ->where('tenant_id', $tenantId)
                ->whereIn('asset_code', ['TEST-AST-001', 'TEST-AST-002'])
                ->pluck('id');

            if (Schema::hasTable('contract_assets') && $assetIds->isNotEmpty()) {
                DB::table('contract_assets')->whereIn('asset_id', $assetIds)->delete();
            }
            DB::table('assets')->whereIn('id', $assetIds)->delete();

            if (Schema::hasTable('projects')) {
                DB::table('projects')->where('tenant_id', $tenantId)->where('project_no', 'PRJ-TEST-001')->delete();
            }

            DB::table('service_contracts')->where('tenant_id', $tenantId)->where('contract_no', 'CNT-TEST-001')->delete();

            $customerId = DB::table('customers')
                ->where('tenant_id', $tenantId)
                ->where('customer_code', 'TEST-CUST-001')
                ->value('id');
            if ($customerId) {
                DB::table('customer_sites')->where('customer_id', $customerId)->where('site_code', 'SITE-RUH-001')->delete();
                DB::table('customers')->where('id', $customerId)->delete();
            }
        });
    }
};
