<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Services\ApprovalService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(): View { return view('workflow.approvals.index',['approvals'=>ApprovalRequest::latest()->paginate(30)]); }
    public function decide(Request $http, ApprovalRequest $approval, ApprovalService $service): RedirectResponse
    {
        $data=$http->validate(['decision'=>['required','in:APPROVED,REJECTED'],'note'=>['nullable','string','max:1000']]);
        $service->decide($approval,$data['decision'],$data['note'] ?? null);
        return back()->with('status','Approval decision recorded.');
    }
}
