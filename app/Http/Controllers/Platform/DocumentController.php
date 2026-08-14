<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\AuditService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(): View
    {
        return view('platform.documents.index',['documents'=>Document::latest()->paginate(30)]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $tenant=$request->user()->tenant_id;
        $data=$request->validate([
            'document_no'=>['required','string','max:60',Rule::unique('documents')->where(fn($q)=>$q->where('tenant_id',$tenant))],
            'title'=>['required','string','max:180'],
            'file'=>['required','file','max:20480'],
        ]);
        $file=$request->file('file');
        $path=$file->store('tenants/'.$tenant.'/documents');
        $document=Document::create([
            'organization_id'=>$request->user()->organization_id,'uploaded_by'=>$request->user()->id,
            'document_no'=>$data['document_no'],'title'=>$data['title'],'original_name'=>$file->getClientOriginalName(),
            'storage_path'=>$path,'mime_type'=>$file->getMimeType(),'size_bytes'=>$file->getSize(),'status'=>'ACTIVE',
        ]);
        $audit->record('platform.document.uploaded',$document,[],$document->toArray(),(string)Str::uuid());
        return back()->with('status','Document uploaded.');
    }

    public function download(Document $document): StreamedResponse
    {
        abort_unless(Storage::exists($document->storage_path),404);
        return Storage::download($document->storage_path,$document->original_name);
    }
}
