<?php

namespace App\Services;

use App\Models\{Asset,CustomerSite,User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class CustomerAssetGovernanceService
{
    public function submit(User $customerUser,array $data,string $source='MANUAL',?string $batch=null): int
    {
        abort_unless($customerUser->customer_id,403,'Only a customer portal user can submit customer assets.');
        $customerId=(int)$customerUser->customer_id;
        abort_if(($data['ownership_type']??'CUSTOMER_OWNED')!=='CUSTOMER_OWNED',422,'Customer submissions can only declare CUSTOMER_OWNED assets.');
        $site=CustomerSite::with('customer')->find($data['customer_site_id']??null);
        abort_unless($site && (int)$site->customer_id===$customerId && $site->customer && (int)$site->customer->tenant_id===(int)$customerUser->tenant_id,422,'Customer asset site must belong to the signed-in customer and tenant.');
        $this->assertNoStrongDuplicate($customerUser,$site->id,$data);

        $id=DB::table('customer_asset_submissions')->insertGetId([
            'tenant_id'=>$customerUser->tenant_id,'organization_id'=>$customerUser->organization_id,'customer_id'=>$customerId,'customer_site_id'=>$site->id,
            'submitted_by'=>$customerUser->id,'source'=>$source,'import_batch'=>$batch,'name'=>$data['name'],'customer_asset_code'=>$data['customer_asset_code']??null,
            'serial_no'=>$data['serial_no']??null,'asset_category'=>$data['asset_category'],'asset_type'=>$data['asset_type']??null,'manufacturer'=>$data['manufacturer']??null,'model_no'=>$data['model_no']??null,
            'criticality'=>$data['criticality']??'MEDIUM','ownership_type'=>'CUSTOMER_OWNED','maintenance_strategy'=>$data['maintenance_strategy']??null,'installation_date'=>$data['installation_date']??null,
            'physical_location'=>$data['physical_location']??null,'technical_specifications'=>isset($data['technical_specifications'])?json_encode($data['technical_specifications']):null,
            'status'=>'PENDING_VERIFICATION','created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->event($id,(int)$customerUser->tenant_id,$source==='EXCEL'?'IMPORTED':'SUBMITTED',null,$source==='EXCEL'?'IMPORTED':'PENDING_VERIFICATION',$source==='EXCEL'?'Imported from Excel':'Customer asset submitted for UNIFCO verification',(int)$customerUser->id);
        if($source==='EXCEL'){
            $this->event($id,(int)$customerUser->tenant_id,'VALIDATION_QUEUE','IMPORTED','VALIDATION_QUEUE','Required registration fields validated',(int)$customerUser->id);
            $this->event($id,(int)$customerUser->tenant_id,'DUPLICATE_CHECK','VALIDATION_QUEUE','PENDING_VERIFICATION','Duplicate check passed; queued for approval',(int)$customerUser->id);
        }
        return $id;
    }

    public function review(User $checker,int $submissionId,bool $approve,?string $notes=null): ?Asset
    {
        abort_unless(in_array($checker->role,['ADMIN','MAINTENANCE_MANAGER'],true),403);
        $submission=DB::table('customer_asset_submissions')->where('tenant_id',$checker->tenant_id)->where('id',$submissionId)->first();
        abort_unless($submission,404); abort_unless($submission->status==='PENDING_VERIFICATION',422,'Submission is not pending verification.');
        abort_if((int)$submission->submitted_by===(int)$checker->id,422,'Maker/checker control: submitter cannot approve their own asset submission.');
        if(!$approve){
            DB::table('customer_asset_submissions')->where('id',$submissionId)->update(['status'=>'REJECTED','verification_notes'=>$notes,'reviewed_by'=>$checker->id,'reviewed_at'=>now(),'updated_at'=>now()]);
            $this->event($submissionId,(int)$checker->tenant_id,'REJECTED','PENDING_VERIFICATION','REJECTED',$notes,(int)$checker->id); return null;
        }
        abort_if($submission->ownership_type!=='CUSTOMER_OWNED',422,'Customer/UNIFCO ownership boundary violation.');
        $spec=$submission->technical_specifications?json_decode($submission->technical_specifications,true):null;
        $data=['customer_id'=>$submission->customer_id,'customer_site_id'=>$submission->customer_site_id,'name'=>$submission->name,'customer_asset_code'=>$submission->customer_asset_code,'serial_no'=>$submission->serial_no,
            'asset_category'=>$submission->asset_category,'asset_type'=>$submission->asset_type,'manufacturer'=>$submission->manufacturer,'model_no'=>$submission->model_no,'criticality'=>$submission->criticality ?: 'MEDIUM',
            'ownership_type'=>'CUSTOMER_OWNED','physical_location'=>$submission->physical_location,'installation_date'=>$submission->installation_date,'technical_specifications'=>$spec,
            'maintenance_strategy'=>$submission->maintenance_strategy];
        $asset=app(AssetMasterService::class)->create((int)$checker->tenant_id,(int)$checker->organization_id,(int)$checker->id,$data);
        DB::table('customer_asset_submissions')->where('id',$submissionId)->update(['status'=>'APPROVED','verification_notes'=>$notes,'reviewed_by'=>$checker->id,'reviewed_at'=>now(),'asset_id'=>$asset->id,'updated_at'=>now()]);
        $this->event($submissionId,(int)$checker->tenant_id,'APPROVED','PENDING_VERIFICATION','APPROVED',$notes ?: 'Approved into Asset Master; independent Verify & Activate is still required.',(int)$checker->id);
        return $asset->fresh();
    }

    public function import(User $customerUser,UploadedFile $file): array
    {
        abort_unless($customerUser->customer_id,403);
        $ext=strtolower($file->getClientOriginalExtension()); abort_unless(in_array($ext,['csv','xlsx'],true),422,'Bulk asset import accepts CSV or XLSX files.');
        $rows=$ext==='xlsx'?$this->xlsxRows($file->getRealPath()):$this->csvRows($file->getRealPath());
        abort_if(count($rows)>5000,422,'Bulk import is limited to 5,000 assets per file.');
        $batch=(string)Str::uuid(); $created=[]; $errors=[];
        foreach($rows as $i=>$row){ try{ $created[]=$this->submit($customerUser,$this->normalizeRow($row),'EXCEL',$batch); }catch(\Throwable $e){ $errors[]=['row'=>$i+2,'message'=>$e->getMessage()]; } }
        return ['batch'=>$batch,'created'=>$created,'errors'=>$errors,'workflow'=>['IMPORTED','VALIDATION_QUEUE','DUPLICATE_CHECK','APPROVED']];
    }

    private function assertNoStrongDuplicate(User $user,int $siteId,array $data): void
    {
        $base=Asset::where('tenant_id',$user->tenant_id)->where('customer_id',$user->customer_id)->where('customer_site_id',$siteId);
        $serial=mb_strtolower(trim((string)($data['serial_no']??''))); $manufacturer=mb_strtolower(trim((string)($data['manufacturer']??''))); $model=mb_strtolower(trim((string)($data['model_no']??''))); $code=mb_strtolower(trim((string)($data['customer_asset_code']??'')));
        $assetDuplicate=(clone $base)->where(function($q) use($serial,$manufacturer,$model,$code){
            if($code!=='') $q->orWhereRaw('LOWER(customer_asset_code)=?',[$code]);
            if($serial!=='') $q->orWhere(function($x) use($serial,$manufacturer,$model){ $x->whereRaw('LOWER(serial_no)=?',[$serial]); if($manufacturer!=='')$x->whereRaw('LOWER(manufacturer)=?',[$manufacturer]); if($model!=='')$x->whereRaw('LOWER(model_no)=?',[$model]); });
        })->first();
        abort_if($assetDuplicate,422,'Possible Duplicate Asset: '.$assetDuplicate->asset_code.' · '.$assetDuplicate->name);

        $pending=DB::table('customer_asset_submissions')->where('tenant_id',$user->tenant_id)->where('customer_id',$user->customer_id)->where('customer_site_id',$siteId)->whereIn('status',['PENDING_VERIFICATION','APPROVED'])->where(function($q) use($serial,$manufacturer,$model,$code){
            if($code!=='') $q->orWhereRaw('LOWER(customer_asset_code)=?',[$code]);
            if($serial!=='') $q->orWhere(function($x) use($serial,$manufacturer,$model){ $x->whereRaw('LOWER(serial_no)=?',[$serial]); if($manufacturer!=='')$x->whereRaw('LOWER(manufacturer)=?',[$manufacturer]); if($model!=='')$x->whereRaw('LOWER(model_no)=?',[$model]); });
        })->first();
        abort_if($pending,422,'Possible Duplicate Asset: matching customer/site identity is already pending or approved.');
    }

    private function normalizeRow(array $row): array
    {
        $r=[]; foreach($row as $k=>$v) $r[strtolower(trim(str_replace(' ','_',(string)$k)))]=is_string($v)?trim($v):$v;
        foreach(['name','asset_category','customer_site_id'] as $required) abort_if(empty($r[$required]),422,$required.' is required for initial registration.');
        $spec=[]; if(!empty($r['technical_specifications'])){ $spec=json_decode((string)$r['technical_specifications'],true); if(!is_array($spec)) $spec=['customer_specification'=>(string)$r['technical_specifications']]; }
        return ['name'=>$r['name'],'asset_category'=>$r['asset_category'],'customer_site_id'=>(int)$r['customer_site_id'],'customer_asset_code'=>$r['customer_asset_code']??null,'serial_no'=>$r['serial_no']??null,
            'asset_type'=>$r['asset_type']??null,'manufacturer'=>$r['manufacturer']??null,'model_no'=>$r['model_no']??null,'criticality'=>strtoupper($r['criticality']??'MEDIUM'),'ownership_type'=>'CUSTOMER_OWNED',
            'maintenance_strategy'=>!empty($r['maintenance_strategy'])?strtoupper($r['maintenance_strategy']):null,'installation_date'=>$r['installation_date']??null,'physical_location'=>$r['physical_location']??null,'technical_specifications'=>$spec];
    }

    private function csvRows(string $path): array
    {
        $h=fopen($path,'r'); $headers=fgetcsv($h)?:[]; $rows=[]; while(($values=fgetcsv($h))!==false){ if(!array_filter($values,fn($v)=>trim((string)$v)!=='')) continue; $rows[]=array_combine($headers,array_pad($values,count($headers),null)); } fclose($h); return $rows;
    }

    private function xlsxRows(string $path): array
    {
        abort_unless(class_exists(ZipArchive::class),500,'XLSX support requires the PHP zip extension.'); abort_unless(class_exists(\DOMDocument::class),500,'XLSX support requires the PHP DOM extension.');
        $zip=new ZipArchive(); abort_unless($zip->open($path)===true,422,'Invalid XLSX file.'); $shared=[];
        if(($raw=$zip->getFromName('xl/sharedStrings.xml'))!==false){ $doc=new \DOMDocument(); abort_unless(@$doc->loadXML($raw),422,'Invalid XLSX shared strings.'); $xp=new \DOMXPath($doc); foreach($xp->query('//*[local-name()="si"]') as $si){ $text=''; foreach($xp->query('.//*[local-name()="t"]',$si) as $t)$text.=$t->textContent; $shared[]=$text; } }
        $sheetRaw=$zip->getFromName('xl/worksheets/sheet1.xml'); $zip->close(); abort_if($sheetRaw===false,422,'XLSX sheet1 is missing.'); $doc=new \DOMDocument(); abort_unless(@$doc->loadXML($sheetRaw),422,'Invalid XLSX worksheet.'); $xp=new \DOMXPath($doc); $matrix=[];
        foreach($xp->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row){ $cells=[]; foreach($xp->query('./*[local-name()="c"]',$row) as $c){ $ref=$c->attributes?->getNamedItem('r')?->nodeValue ?: 'A'; preg_match('/([A-Z]+)/',$ref,$m); $col=$this->columnIndex($m[1]??'A'); $valueNode=$xp->query('./*[local-name()="v"]',$c)->item(0); $v=$valueNode?->textContent ?? ''; $type=$c->attributes?->getNamedItem('t')?->nodeValue; $cells[$col]=$type==='s'?($shared[(int)$v]??''):$v; } if($cells){$width=max(array_keys($cells))+1;$matrix[]=array_values(array_replace(array_fill(0,$width,''),$cells));} }
        if(!$matrix)return []; $headers=array_map('trim',array_shift($matrix)); $rows=[]; foreach($matrix as $values){$values=array_pad($values,count($headers),null);if(count($values)>count($headers))$values=array_slice($values,0,count($headers));$rows[]=array_combine($headers,$values);} return $rows;
    }

    private function columnIndex(string $letters): int { $n=0; foreach(str_split($letters) as $c)$n=$n*26+(ord($c)-64); return $n-1; }
    private function event(int $submissionId,int $tenantId,string $type,?string $from,?string $to,?string $notes,int $actorId): void { DB::table('customer_asset_submission_events')->insert(['customer_asset_submission_id'=>$submissionId,'tenant_id'=>$tenantId,'event_type'=>$type,'from_status'=>$from,'to_status'=>$to,'notes'=>$notes,'performed_by'=>$actorId,'performed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]); }
}
