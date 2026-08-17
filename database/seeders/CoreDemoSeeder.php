<?php

namespace Database\Seeders;

use App\Models\{ChartAccount,FiscalPeriod,Item,JobPosition,Organization,Tenant,Warehouse};
use Illuminate\Database\Seeder;

class CoreDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'UNIFCO')->firstOrFail();
        $org = Organization::where('tenant_id', $tenant->id)->where('code', 'HQ')->firstOrFail();

        if (! Item::where('tenant_id', $tenant->id)->exists()) {
            foreach ([
                ['RAW-001','Aluminium Sheet 2mm','KG'], ['RAW-002','Steel Rod 12mm','KG'],
                ['RAW-003','Copper Wire 1.5mm','M'], ['RAW-004','Fastener Kit','EA'],
                ['FG-001','Finished Widget','EA'], ['MRO-001','Industrial Lubricant','L'],
            ] as [$code,$name,$uom]) {
                Item::create(['tenant_id'=>$tenant->id,'item_code'=>$code,'name'=>$name,'uom'=>$uom,'status'=>'ACTIVE']);
            }
        }

        if (! Warehouse::where('tenant_id', $tenant->id)->exists()) {
            foreach ([['MAIN','Main Warehouse'],['RAW','Raw Materials Store'],['FG','Finished Goods Store']] as [$code,$name]) {
                Warehouse::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>$code,'name'=>$name,'status'=>'ACTIVE']);
            }
        }

        if (! JobPosition::where('tenant_id', $tenant->id)->exists()) {
            foreach ([
                ['DIR-01','Managing Director','Executive'], ['FIN-01','Finance Manager','Finance'],
                ['OPS-01','Operations Supervisor','Operations'], ['SAL-01','Sales Executive','Sales'],
                ['PROD-01','Production Technician','Manufacturing'],
            ] as [$code,$title,$dept]) {
                JobPosition::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>$code,'title'=>$title,'department'=>$dept,'status'=>'ACTIVE']);
            }
        }

        if (! ChartAccount::where('tenant_id', $tenant->id)->exists()) {
            foreach ([
                ['1000','Cash','ASSET','DEBIT'], ['1200','Accounts Receivable','ASSET','DEBIT'],
                ['1300','Inventory','ASSET','DEBIT'], ['1500','Property & Equipment','ASSET','DEBIT'],
                ['2000','Accounts Payable','LIABILITY','CREDIT'], ['2100','Accrued Liabilities','LIABILITY','CREDIT'],
                ['3000','Retained Earnings','EQUITY','CREDIT'], ['4000','Sales Revenue','REVENUE','CREDIT'],
                ['4100','Service Revenue','REVENUE','CREDIT'], ['5000','Cost of Goods Sold','EXPENSE','DEBIT'],
                ['6000','Operating Expenses','EXPENSE','DEBIT'], ['7000','Payroll Expense','EXPENSE','DEBIT'],
                ['8000','Depreciation Expense','EXPENSE','DEBIT'],
            ] as [$code,$name,$type,$balance]) {
                ChartAccount::create(['tenant_id'=>$tenant->id,'organization_id'=>$org->id,'code'=>$code,'name'=>$name,'type'=>$type,'normal_balance'=>$balance,'status'=>'ACTIVE']);
            }
        }

        if (! FiscalPeriod::where('tenant_id', $tenant->id)->exists()) {
            FiscalPeriod::create([
                'tenant_id'=>$tenant->id,'organization_id'=>$org->id,
                'code'=>'2026-08','starts_on'=>'2026-08-01','ends_on'=>'2026-08-31','status'=>'OPEN',
            ]);
        }
    }
}