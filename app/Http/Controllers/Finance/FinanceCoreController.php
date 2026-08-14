<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\{ChartAccount,FinancialDocument,FiscalPeriod,Journal};
use App\Services\AuditService;
use App\Services\Finance\FinancialPostingService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FinanceCoreController extends Controller
{
    public function index(): View
    {
        $trial=Journal::query()->where('status','POSTED')->with('lines')->get()->flatMap->lines->groupBy('account_code')->map(fn($lines)=>[
            'debit'=>$lines->sum('debit'),'credit'=>$lines->sum('credit'),'balance'=>$lines->sum('debit')-$lines->sum('credit'),
        ])->sortKeys();
        return view('finance.core.index',[
            'accounts'=>ChartAccount::orderBy('code')->get(),'periods'=>FiscalPeriod::orderByDesc('starts_on')->get(),
            'documents'=>FinancialDocument::latest()->limit(30)->get(),'trial'=>$trial,
        ]);
    }

    public function storeAccount(Request $request, AuditService $audit): RedirectResponse
    {
        $data=$request->validate(['code'=>['required','string','max:30'],'name'=>['required','string','max:120'],'type'=>['required','in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE'],'normal_balance'=>['required','in:DEBIT,CREDIT']]);
        $account=ChartAccount::create([...$data,'organization_id'=>$request->user()->organization_id,'posting_allowed'=>true,'status'=>'ACTIVE']);
        $audit->record('finance.account.created',$account,[],$account->toArray());
        return back()->with('status','Chart account created.');
    }

    public function storePeriod(Request $request, AuditService $audit): RedirectResponse
    {
        $data=$request->validate(['code'=>['required','string','max:30'],'starts_on'=>['required','date'],'ends_on'=>['required','date','after_or_equal:starts_on']]);
        $period=FiscalPeriod::create([...$data,'organization_id'=>$request->user()->organization_id,'status'=>'OPEN']);
        $audit->record('finance.period.created',$period,[],$period->toArray());
        return back()->with('status','Fiscal period opened.');
    }

    public function closePeriod(FiscalPeriod $period, AuditService $audit): RedirectResponse
    {
        abort_unless($period->status==='OPEN',422);
        $before=$period->toArray(); $period->update(['status'=>'CLOSED','closed_by'=>Auth::id(),'closed_at'=>now()]);
        $audit->record('finance.period.closed',$period,$before,$period->fresh()->toArray());
        return back()->with('status','Fiscal period closed.');
    }

    public function reopenPeriod(FiscalPeriod $period, AuditService $audit): RedirectResponse
    {
        abort_unless($period->status==='CLOSED',422);
        $before=$period->toArray(); $period->update(['status'=>'OPEN','closed_by'=>null,'closed_at'=>null]);
        $audit->record('finance.period.reopened',$period,$before,$period->fresh()->toArray());
        return back()->with('status','Fiscal period reopened.');
    }

    public function storeDocument(Request $request, AuditService $audit): RedirectResponse
    {
        $data=$request->validate([
            'document_no'=>['required','string','max:50'],'document_type'=>['required','in:AP_INVOICE,AR_INVOICE'],
            'counterparty_name'=>['required','string','max:160'],'document_date'=>['required','date'],'due_date'=>['nullable','date'],
            'currency'=>['required','string','size:3'],'amount'=>['required','numeric','gt:0'],
            'control_account_code'=>['required','string','max:30'],'offset_account_code'=>['required','string','max:30'],
        ]);
        $document=FinancialDocument::create([...$data,'organization_id'=>$request->user()->organization_id,'created_by'=>$request->user()->id,'status'=>'DRAFT','open_amount'=>0]);
        $audit->record('finance.document.created',$document,[],$document->toArray());
        return back()->with('status','Financial document created.');
    }

    public function postDocument(FinancialDocument $document, FinancialPostingService $service): RedirectResponse
    {
        $service->postDocument($document); return back()->with('status','Financial document posted.');
    }

    public function payDocument(Request $request, FinancialDocument $document, FinancialPostingService $service): RedirectResponse
    {
        $data=$request->validate(['payment_no'=>['required','string','max:50'],'payment_date'=>['required','date'],'amount'=>['required','numeric','gt:0'],'cash_account_code'=>['required','string','max:30']]);
        $service->applyPayment($document,$data); return back()->with('status','Payment/receipt recorded.');
    }
}
