<?php

namespace App\Services\Finance;

use App\Models\{ChartAccount,FinancialDocument,FiscalPeriod,Journal,Payment};
use App\Services\AuditService;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class FinancialPostingService
{
    public function __construct(private AuditService $audit) {}

    public function assertOpenPeriod(string $date): void
    {
        $open = FiscalPeriod::where('status','OPEN')->whereDate('starts_on','<=',$date)->whereDate('ends_on','>=',$date)->exists();
        if (! $open) throw ValidationException::withMessages(['document_date'=>'No open fiscal period covers this date.']);
    }

    private function assertPostingAccounts(array $codes): void
    {
        $count=ChartAccount::whereIn('code',$codes)->where('status','ACTIVE')->where('posting_allowed',true)->count();
        if ($count !== count(array_unique($codes))) throw ValidationException::withMessages(['accounts'=>'All posting accounts must exist, be active, and allow posting.']);
    }

    public function postDocument(FinancialDocument $document): FinancialDocument
    {
        return DB::transaction(function () use ($document) {
            if ($document->status !== 'DRAFT') throw ValidationException::withMessages(['document'=>'Only DRAFT documents can be posted.']);
            if ((int)$document->created_by === (int)Auth::id()) throw ValidationException::withMessages(['document'=>'Segregation of duties: creator cannot post the same document.']);
            $this->assertOpenPeriod($document->document_date->toDateString());
            $this->assertPostingAccounts([$document->control_account_code,$document->offset_account_code]);

            $journal=Journal::create([
                'organization_id'=>$document->organization_id,'created_by'=>Auth::id(),
                'journal_no'=>'DOC-'.$document->document_no,'journal_date'=>$document->document_date,
                'description'=>$document->document_type.' '.$document->document_no,'status'=>'POSTED','posted_by'=>Auth::id(),'posted_at'=>now(),
            ]);
            if ($document->document_type==='AP_INVOICE') {
                $journal->lines()->create(['line_no'=>1,'account_code'=>$document->offset_account_code,'debit'=>$document->amount,'credit'=>0,'description'=>$document->counterparty_name]);
                $journal->lines()->create(['line_no'=>2,'account_code'=>$document->control_account_code,'debit'=>0,'credit'=>$document->amount,'description'=>$document->counterparty_name]);
            } else {
                $journal->lines()->create(['line_no'=>1,'account_code'=>$document->control_account_code,'debit'=>$document->amount,'credit'=>0,'description'=>$document->counterparty_name]);
                $journal->lines()->create(['line_no'=>2,'account_code'=>$document->offset_account_code,'debit'=>0,'credit'=>$document->amount,'description'=>$document->counterparty_name]);
            }
            $before=$document->toArray();
            $document->update(['status'=>'POSTED','posted_by'=>Auth::id(),'posted_at'=>now(),'journal_id'=>$journal->id,'open_amount'=>$document->amount]);
            $this->audit->record('finance.document.posted',$document,$before,$document->fresh()->toArray());
            return $document->fresh('journal');
        });
    }

    public function applyPayment(FinancialDocument $document, array $data): Payment
    {
        return DB::transaction(function () use ($document,$data) {
            if ($document->status !== 'POSTED' || (float)$document->open_amount <= 0) throw ValidationException::withMessages(['document'=>'Only open posted documents can be settled.']);
            $amount=(float)$data['amount'];
            if ($amount <= 0 || $amount > (float)$document->open_amount) throw ValidationException::withMessages(['amount'=>'Payment must be positive and cannot exceed the open amount.']);
            $this->assertOpenPeriod($data['payment_date']);
            $this->assertPostingAccounts([$document->control_account_code,$data['cash_account_code']]);

            $payment=Payment::create([
                'organization_id'=>$document->organization_id,'financial_document_id'=>$document->id,'payment_no'=>$data['payment_no'],
                'payment_date'=>$data['payment_date'],'amount'=>$amount,'cash_account_code'=>$data['cash_account_code'],'created_by'=>Auth::id(),
            ]);
            $journal=Journal::create([
                'organization_id'=>$document->organization_id,'created_by'=>Auth::id(),'journal_no'=>'PAY-'.$payment->payment_no,
                'journal_date'=>$payment->payment_date,'description'=>'Settlement '.$document->document_no,'status'=>'POSTED','posted_by'=>Auth::id(),'posted_at'=>now(),
            ]);
            if ($document->document_type==='AP_INVOICE') {
                $journal->lines()->create(['line_no'=>1,'account_code'=>$document->control_account_code,'debit'=>$amount,'credit'=>0]);
                $journal->lines()->create(['line_no'=>2,'account_code'=>$data['cash_account_code'],'debit'=>0,'credit'=>$amount]);
            } else {
                $journal->lines()->create(['line_no'=>1,'account_code'=>$data['cash_account_code'],'debit'=>$amount,'credit'=>0]);
                $journal->lines()->create(['line_no'=>2,'account_code'=>$document->control_account_code,'debit'=>0,'credit'=>$amount]);
            }
            $payment->update(['journal_id'=>$journal->id]);
            $remaining=round((float)$document->open_amount-$amount,2);
            $document->update(['open_amount'=>$remaining,'status'=>$remaining<=0?'SETTLED':'POSTED']);
            $this->audit->record('finance.payment.recorded',$payment,[],['document_id'=>$document->id,'amount'=>$amount,'remaining'=>$remaining]);
            return $payment->fresh('journal');
        });
    }
}
