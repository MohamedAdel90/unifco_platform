<?php

namespace App\Console\Commands;

use App\Models\{Customer,Organization,Tenant,User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProvisionDemoCustomer extends Command
{
    protected $signature = 'customer:provision-demo
        {--email=demo.customer@unifco.local : Demo customer login email}
        {--password= : Required password for the demo customer}
        {--name=UNIFCO Demo Customer : Demo customer display name}';

    protected $description = 'Provision or refresh a safe demo customer account for Customer Portal UAT.';

    public function handle(): int
    {
        $email = trim((string) $this->option('email'));
        $password = (string) $this->option('password');
        $name = trim((string) $this->option('name'));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid --email is required.');
            return self::FAILURE;
        }

        if (strlen($password) < 12) {
            $this->error('--password is required and must be at least 12 characters.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($email, $password, $name): void {
            $tenant = Tenant::firstOrCreate(
                ['code' => 'UNIFCO'],
                ['name' => 'UNIFCO', 'status' => 'ACTIVE']
            );

            $organization = Organization::firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'HQ'],
                ['name' => 'UNIFCO HQ', 'status' => 'ACTIVE']
            );

            $customer = Customer::updateOrCreate(
                ['tenant_id' => $tenant->id, 'customer_code' => 'DEMO-CUSTOMER'],
                [
                    'organization_id' => $organization->id,
                    'name' => $name,
                    'email' => $email,
                    'contact_name' => 'Demo Portal User',
                    'phone' => '+966500000000',
                    'city' => 'Riyadh',
                    'address' => 'UNIFCO Demo Customer',
                    'status' => 'ACTIVE',
                ]
            );

            User::updateOrCreate(
                ['email' => $email],
                [
                    'tenant_id' => $tenant->id,
                    'organization_id' => $organization->id,
                    'customer_id' => $customer->id,
                    'name' => $name,
                    'password' => Hash::make($password),
                    'role' => 'CUSTOMER',
                    'status' => 'ACTIVE',
                ]
            );
        });

        $this->info('Demo customer account provisioned successfully.');
        $this->line('Login URL: /login');
        $this->line('Email: '.$email);
        $this->line('Customer portal: /customer');

        return self::SUCCESS;
    }
}
