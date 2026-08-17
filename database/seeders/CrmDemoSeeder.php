<?php

namespace Database\Seeders;

use App\Models\{CrmLead,CrmOpportunity,CrmQuotation,Customer,Organization,Tenant,User};
use Illuminate\Database\Seeder;

class CrmDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'UNIFCO')->firstOrFail();
        $org = Organization::where('tenant_id', $tenant->id)->where('code', 'HQ')->firstOrFail();
        $admin = User::where('tenant_id', $tenant->id)->where('email', 'admin@unifco.local')->firstOrFail();

        if (Customer::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        foreach ([
            ['CUS-0001','Northwind Traders','billing@northwind.test'],
            ['CUS-0002','Globex Corporation','ap@globex.test'],
            ['CUS-0003','Initech Ltd','accounts@initech.test'],
        ] as [$code,$name,$email]) {
            Customer::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_code'=>$code,'name'=>$name,'email'=>$email,'status'=>'ACTIVE']);
        }

        $customer = static fn (string $code) => Customer::where('tenant_id',$tenant->id)->where('customer_code',$code)->firstOrFail();

        $lead1 = CrmLead::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'lead_no'=>'LD-0001','name'=>'Frank Moore','company'=>'Umbrella Corp','email'=>'frank@umbrella.test','status'=>'NEW','created_by'=>$admin->id]);
        CrmLead::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'lead_no'=>'LD-0002','name'=>'Grace Lee','company'=>'Stark Industries','email'=>'grace@stark.test','status'=>'CONTACTED','created_by'=>$admin->id]);

        $opp1 = CrmOpportunity::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'customer_id'=>$customer('CUS-0001')->id,
            'opportunity_no'=>'OP-0001','name'=>'Widget supply agreement','stage'=>'PROPOSAL',
            'expected_value'=>48000,'probability'=>60,'expected_close'=>'2026-09-30','status'=>'OPEN','created_by'=>$admin->id,
        ]);
        CrmOpportunity::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'lead_id'=>$lead1->id,
            'opportunity_no'=>'OP-0002','name'=>'Maintenance services retainer','stage'=>'QUALIFICATION',
            'expected_value'=>15000,'probability'=>30,'expected_close'=>'2026-10-31','status'=>'OPEN','created_by'=>$admin->id,
        ]);

        CrmQuotation::create([
            'tenant_id'=>$tenant->id,'organization_id'=>$org->id,'opportunity_id'=>$opp1->id,
            'quotation_no'=>'QT-0001','quotation_date'=>'2026-08-15','currency'=>'USD',
            'amount'=>48000,'status'=>'ISSUED','created_by'=>$admin->id,
        ]);
    }
}