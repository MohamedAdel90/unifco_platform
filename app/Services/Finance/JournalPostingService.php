<?php

namespace App\Services\Finance;

use App\Models\{ChartAccount,FiscalPeriod,Journal};
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalPostingService
{
    public function __construct(private AuditService $audit) {}

    public function post(Journal $journal): Journal
    {
        return DB::transaction(function () use ($journal) {
            $journal->load('lines');
            if ($journal->status !== 'DRAFT') throw ValidationException::withMessages(['journal' => 'Only DRAFT journals can be posted.']);
            if ((int) $journal->created_by === (int) Auth::id()) throw ValidationException::withMessages(['journal' => 'Segregation of duties: creator cannot post the same journal.']);
            $debit = $journal->lines->sum(fn ($line) => (float) $line->debit);
            $credit = $journal->lines->sum(fn ($line) => (float) $line->credit);
            if (abs($debit - $credit) > 0.0001 || $debit <= 0) throw ValidationException::withMessages(['journal' => 'Journal must be balanced with a positive total.']);

            $openPeriod=FiscalPeriod::where('status','OPEN')->whereDate('starts_on','<=',$journal->journal_date)->whereDate('ends_on','>=',$journal->journal_date)->exists();
            if (! $openPeriod) throw ValidationException::withMessages(['journal'=>'Journal date must belong to an open fiscal period.']);

            $codes=$journal->lines->pluck('account_code')->unique()->values();
            $valid=ChartAccount::whereIn('code',$codes)->where('status','ACTIVE')->where('posting_allowed',true)->count();
            if ($valid !== $codes->count()) throw ValidationException::withMessages(['journal'=>'Every journal account must exist in the active posting chart of accounts.']);

            $before = $journal->toArray();
            $journal->update(['status' => 'POSTED', 'posted_by' => Auth::id(), 'posted_at' => now()]);
            $this->audit->record('finance.journal.posted', $journal, $before, $journal->fresh()->toArray());
            return $journal->fresh('lines');
        });
    }
}
