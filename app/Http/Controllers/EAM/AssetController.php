<?php

namespace App\Http\Controllers\EAM;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(): View { return view('eam.assets.index',['assets'=>Asset::orderBy('asset_code')->paginate(25)]); }
    public function create(): View { return view('eam.assets.form',['asset'=>new Asset()]); }
    public function edit(Asset $asset): View { return view('eam.assets.form',compact('asset')); }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $asset=Asset::create([...$this->validated($request),'organization_id'=>Auth::user()->organization_id,'status'=>'REGISTERED']);
        $audit->record('eam.asset.registered',$asset,[],$asset->toArray());
        return redirect()->route('eam.assets.index')->with('status','Asset registered.');
    }

    public function update(Request $request, Asset $asset, AuditService $audit): RedirectResponse
    {
        if ($asset->status === 'CAPITALIZED') throw ValidationException::withMessages(['asset'=>'Capitalized asset master data is controlled and cannot be edited here.']);
        $before=$asset->toArray(); $asset->update($this->validated($request,$asset));
        $audit->record('eam.asset.updated',$asset,$before,$asset->fresh()->toArray());
        return redirect()->route('eam.assets.index')->with('status','Asset updated.');
    }

    public function capitalize(Asset $asset, AuditService $audit): RedirectResponse
    {
        if ($asset->status !== 'REGISTERED') throw ValidationException::withMessages(['asset'=>'Only REGISTERED assets can be capitalized.']);
        if (! $asset->commission_date) throw ValidationException::withMessages(['asset'=>'Commission date is required before capitalization.']);
        if ((float)$asset->acquisition_cost <= 0) throw ValidationException::withMessages(['asset'=>'Acquisition cost must be positive before capitalization.']);
        $before=$asset->toArray(); $asset->update(['status'=>'CAPITALIZED']);
        $audit->record('eam.asset.capitalized',$asset,$before,$asset->fresh()->toArray());
        return back()->with('status','Asset capitalized.');
    }

    private function validated(Request $request, ?Asset $asset=null): array
    {
        return $request->validate([
            'asset_code'=>['required','string','max:50',Rule::unique('assets')->where(fn($q)=>$q->where('tenant_id',Auth::user()->tenant_id))->ignore($asset?->id)],
            'name'=>['required','string','max:180'],'acquisition_cost'=>['required','numeric','min:0'],'commission_date'=>['nullable','date'],
        ]);
    }
}
