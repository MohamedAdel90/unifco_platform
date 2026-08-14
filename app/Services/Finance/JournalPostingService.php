<?php

namespace App\Services\Finance;

use App\Models\Journal;
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

            $before = $journal->toArray();
            $journal->update(['status' => 'POSTED', 'posted_by' => Auth::id(), 'posted_at' => now()]);
            $this->audit->record('finance.journal.posted', $journal, $before, $journal->fresh()->toArray());
            return $journal->fresh('lines');
        });
    }
}
