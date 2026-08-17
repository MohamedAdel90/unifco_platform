<?php

namespace Database\Seeders;

use App\Models\{Journal,JournalLine,Organization,Tenant,User};
use Illuminate\Database\Seeder;

class FinanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('code', 'UNIFCO')->firstOrFail();
        $org = Organization::where('tenant_id', $tenant->id)->where('code', 'HQ')->firstOrFail();
        $admin = User::where('tenant_id', $tenant->id)->where('email', 'admin@unifco.local')->firstOrFail();

        if (Journal::where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $journals = [
            [
                'journal_no' => 'GL-2026-0001', 'journal_date' => '2026-08-01',
                'description' => 'Opening balances', 'status' => 'POSTED', 'posted_at' => '2026-08-01 09:00:00',
                'lines' => [
                    ['account_code' => '1300', 'debit' => 50000, 'credit' => 0, 'description' => 'Opening inventory'],
                    ['account_code' => '1500', 'debit' => 200000, 'credit' => 0, 'description' => 'Opening PPE'],
                    ['account_code' => '1000', 'debit' => 150000, 'credit' => 0, 'description' => 'Opening cash'],
                    ['account_code' => '3000', 'debit' => 0, 'credit' => 400000, 'description' => 'Opening retained earnings'],
                ],
            ],
            [
                'journal_no' => 'GL-2026-0002', 'journal_date' => '2026-08-15',
                'description' => 'August sales cycle', 'status' => 'DRAFT', 'posted_at' => null,
                'lines' => [
                    ['account_code' => '1000', 'debit' => 25000, 'credit' => 0, 'description' => 'Cash received'],
                    ['account_code' => '4000', 'debit' => 0, 'credit' => 25000, 'description' => 'Sales revenue'],
                ],
            ],
            [
                'journal_no' => 'GL-2026-0003', 'journal_date' => '2026-08-31',
                'description' => 'August expense accrual', 'status' => 'DRAFT', 'posted_at' => null,
                'lines' => [
                    ['account_code' => '6000', 'debit' => 8000, 'credit' => 0, 'description' => 'Operating expenses'],
                    ['account_code' => '2100', 'debit' => 0, 'credit' => 8000, 'description' => 'Accrued liabilities'],
                ],
            ],
        ];

        foreach ($journals as $data) {
            $journal = Journal::create([
                'tenant_id'=>$tenant->id,'organization_id'=>$org->id,
                'created_by'=>$admin->id,'posted_by'=>$data['status']==='POSTED' ? $admin->id : null,
                'journal_no'=>$data['journal_no'],'journal_date'=>$data['journal_date'],
                'description'=>$data['description'],'status'=>$data['status'],'posted_at'=>$data['posted_at'],
            ]);
            foreach ($data['lines'] as $i => $line) {
                JournalLine::create([
                    'journal_id'=>$journal->id,'line_no'=>$i + 1,
                    'account_code'=>$line['account_code'],'debit'=>$line['debit'],
                    'credit'=>$line['credit'],'description'=>$line['description'],
                ]);
            }
        }
    }
}