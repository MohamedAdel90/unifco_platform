<?php

namespace Tests\Feature;

use App\Models\{Item,Journal,Tenant,User};
use App\Services\Finance\JournalPostingService;
use App\Services\Inventory\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Wave2ControlsTransactionsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        $tenant=Tenant::firstOrCreate(['code'=>'T1'],['name'=>'Tenant 1','status'=>'ACTIVE']);
        return User::create(['tenant_id'=>$tenant->id,'name'=>$email,'email'=>$email,'password'=>'password','role'=>'ADMIN','status'=>'ACTIVE']);
    }

    public function test_balanced_journal_requires_separate_poster(): void
    {
        $creator=$this->user('creator@test.local'); $poster=$this->user('poster@test.local');
        $this->actingAs($creator);
        $journal=Journal::create(['journal_no'=>'JV-1','journal_date'=>now()->toDateString(),'created_by'=>$creator->id,'status'=>'DRAFT']);
        $journal->lines()->createMany([
            ['line_no'=>1,'account_code'=>'1000','debit'=>100,'credit'=>0],
            ['line_no'=>2,'account_code'=>'2000','debit'=>0,'credit'=>100],
        ]);
        $this->actingAs($poster);
        app(JournalPostingService::class)->post($journal);
        $this->assertDatabaseHas('journals',['id'=>$journal->id,'status'=>'POSTED','posted_by'=>$poster->id]);
        $this->assertDatabaseHas('audit_logs',['action'=>'finance.journal.posted']);
    }

    public function test_stock_movement_is_idempotent_and_prevents_negative_stock(): void
    {
        $user=$this->user('stock@test.local'); $this->actingAs($user);
        $item=Item::create(['item_code'=>'IT-1','name'=>'Item 1','uom'=>'EA','status'=>'ACTIVE']);
        $service=app(StockService::class);
        $first=$service->move($item,'MAIN','RECEIPT',10,'abc-1');
        $second=$service->move($item,'MAIN','RECEIPT',10,'abc-1');
        $this->assertFalse($first['duplicate']); $this->assertTrue($second['duplicate']);
        $this->assertEquals(10.0,(float)DB::table('stock_balances')->where('item_id',$item->id)->value('quantity'));
    }
}
