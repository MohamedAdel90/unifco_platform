<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Services\AuditService;
use App\Services\Finance\JournalPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(): View { return view('finance.journals.index', ['journals'=>Journal::with('lines')->latest()->paginate(20)]); }
    public function create(): View { return view('finance.journals.create'); }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'journal_no'=>['required','string','max:50'],'journal_date'=>['required','date'],'description'=>['nullable','string'],
            'lines'=>['required','array','min:2'],'lines.*.account_code'=>['required','string','max:80'],
            'lines.*.debit'=>['required','numeric','min:0'],'lines.*.credit'=>['required','numeric','min:0'],
        ]);
        $journal = DB::transaction(function () use ($data,$audit) {
            $journal = Journal::create([
                'organization_id'=>Auth::user()->organization_id,'created_by'=>Auth::id(),'journal_no'=>$data['journal_no'],
                'journal_date'=>$data['journal_date'],'description'=>$data['description'] ?? null,'status'=>'DRAFT',
            ]);
            foreach ($data['lines'] as $i=>$line) $journal->lines()->create([...$line,'line_no'=>$i+1]);
            $audit->record('finance.journal.created',$journal,[], $journal->fresh('lines')->toArray());
            return $journal;
        });
        return redirect()->route('finance.journals.index')->with('status',"Journal {$journal->journal_no} created.");
    }

    public function post(Journal $journal, JournalPostingService $service): RedirectResponse
    {
        $service->post($journal);
        return back()->with('status','Journal posted.');
    }
}
