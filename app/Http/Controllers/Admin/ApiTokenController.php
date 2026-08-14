<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.api-tokens.index',['tokens'=>ApiToken::where('tenant_id',$request->user()->tenant_id)->where('user_id',$request->user()->id)->latest()->get()]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:80'],'abilities'=>['nullable','string','max:500'],'expires_in_days'=>['nullable','integer','min:1','max:365']]);
        $plain='unf_'.Str::random(64);
        $abilities=array_values(array_filter(array_map('trim',explode(',',$data['abilities'] ?? 'status,summary'))));
        $token=ApiToken::create([
            'tenant_id'=>$request->user()->tenant_id,'user_id'=>$request->user()->id,'name'=>$data['name'],
            'token_hash'=>hash('sha256',$plain),'abilities'=>$abilities ?: ['status','summary'],
            'expires_at'=>isset($data['expires_in_days'])?now()->addDays((int)$data['expires_in_days']):null,
        ]);
        $audit->record('security.api_token.created',$token,[],['name'=>$token->name,'abilities'=>$token->abilities]);
        return back()->with('new_api_token',$plain)->with('status','API token created. Copy it now; it will not be shown again.');
    }

    public function destroy(Request $request, ApiToken $apiToken, AuditService $audit): RedirectResponse
    {
        abort_unless($apiToken->tenant_id===$request->user()->tenant_id && $apiToken->user_id===$request->user()->id,404);
        $apiToken->update(['revoked_at'=>now()]);
        $audit->record('security.api_token.revoked',$apiToken,[],['name'=>$apiToken->name]);
        return back()->with('status','API token revoked.');
    }
}
