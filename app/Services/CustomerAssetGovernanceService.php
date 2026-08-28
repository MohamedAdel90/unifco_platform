<?php

namespace App\Services;

use App\Models\{Asset,User};
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
        if(!empty($data['serial_no'])){
            abort_if(Asset::where('customer_id',$customerId)->whereRaw('LOWER(serial_no)=?',[mb_strtolower(trim($data['serial_no']))])->exists(),422,'This serial number already exists in the customer asset register.');
            abort_if(DB::table('customer_asset_submissions')->where('customer_id',$customerId)->whereIn('status',['PENDING_VERIFICATION','APPROVED'])->whereRaw('LOWER(serial_no)=?',[mb_strtolower(trim($data['serial_no']))])->exists(),422,'This serial number already has a pending or approved submission.');
        }
        $id=DB::table('customer_asset_submissions')->insertGetId([
            'tenant_id'=>$customerUser->tenant_id,'organization_id'=>$customerUser->organization_id,'customer_id'=>$customerId,'customer_site_id'=>$data['customer_site_id']??null,
            'submitted_by'=>$customerUser->id,'source'=>$source,'import_batch'=>$batch,'name'=>$data['name'],'customer_asset_code'=>$data['customer_asset_code']??null,
            'serial_no'=>$data['serial_no']??null,'asset_category'=>$data['asset_category'],'asset_type'=>$data['asset_type']??null,'manufacturer'=>$data['manufacturer']??null,'model_no'=>$data['model_no']??null,
            'criticality'=>$data['criticality']??'MEDIUM','ownership_type'=>'CUSTOMER_OWNED','physical_location'=>$data['physical_location']??null,
            'technical_specifications'=>isset($data['technical_specifications'])?json_encode($data['technical_specifications']):null,'status'=>'PENDING_VERIFICATION','created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->event($id,(int)$customerUser->tenant_id,'SUBMITTED',null,'PENDING_VERIFICATION','Customer asset submitted for UNIFCO verification',(int)$customerUser->id);
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
            'asset_category'=>$submission->asset_category,'asset_type'=>$submission->asset_type,'manufacturer'=>$submission->manufacturer,'model_no'=>$submission->model_no,'criticality'=>$submission->criticality,
            'ownership_type'=>'CUSTOMER_OWNED','physical_location'=>$submission->physical_location,'technical_specifications'=>$spec,'maintenance_strategy'=>'PREVENTIVE'];
        $master=app(AssetMasterService::class);
        $asset=$master->create((int)$checker->tenant_id,(int)$checker->organization_id,(int)$checker->id,$data);
        $asset=$master->verify($asset,(int)$checker->id,$notes ?: 'Approved from customer asset governance workflow.');
        DB::table('customer_asset_submissions')->where('id',$submissionId)->update(['status'=>'APPROVED','verification_notes'=>$notes,'reviewed_by'=>$checker->id,'reviewed_at'=>now(),'asset_id'=>$asset->id,'updated_at'=>now()]);
        $this->event($submissionId,(int)$checker->tenant_id,'APPROVED','PENDING_VERIFICATION','APPROVED',$notes,(int)$checker->id);
        return $asset;
    }

    public function import(User $customerUser,UploadedFile $file): array
    {
        abort_unless($customerUser->customer_id,403);
        $ext=strtolower($file->getClientOriginalExtension()); abort_unless(in_array($ext,['csv','xlsx'],true),422,'Bulk asset import accepts CSV or XLSX files.');
        $rows=$ext==='xlsx'?$this->xlsxRows($file->getRealPath()):$this->csvRows($file->getRealPath());
        abort_if(count($rows)>500,422,'Bulk import is limited to 500 assets per file.');
        $batch=(string)Str::uuid(); $created=[]; $errors=[];
        foreach($rows as $i=>$row){
            try{
                $created[]=$this->submit($customerUser,$this->normalizeRow($row),'EXCEL',$batch);
            }catch(\Throwable $e){ $errors[]=['row'=>$i+2,'message'=>$e->getMessage()]; }
        }
        return ['batch'=>$batch,'created'=>$created,'errors'=>$errors];
    }

    private function normalizeRow(array $row): array
    {
        $r=[]; foreach($row as $k=>$v) $r[strtolower(trim(str_replace(' ','_',(string)$k)))]=is_string($v)?trim($v):$v;
        abort_if(empty($r['name'])||empty($r['asset_category']),422,'name and asset_category are required.');
        return ['name'=>$r['name'],'asset_category'=>$r['asset_category'],'customer_site_id'=>!empty($r['customer_site_id'])?(int)$r['customer_site_id']:null,'customer_asset_code'=>$r['customer_asset_code']??null,'serial_no'=>$r['serial_no']??null,
            'asset_type'=>$r['asset_type']??null,'manufacturer'=>$r['manufacturer']??null,'model_no'=>$r['model_no']??null,'criticality'=>strtoupper($r['criticality']??'MEDIUM'),'ownership_type'=>'CUSTOMER_OWNED','physical_location'=>$r['physical_location']??null];
    }

    private function csvRows(string $path): array
    {
        $h=fopen($path,'r'); $headers=fgetcsv($h)?:[]; $rows=[]; while(($values=fgetcsv($h))!==false){ if(!array_filter($values,fn($v)=>trim((string)$v)!=='')) continue; $rows[]=array_combine($headers,array_pad($values,count($headers),null)); } fclose($h); return $rows;
    }

    private function xlsxRows(string $path): array
    {
        abort_unless(class_exists(ZipArchive::class),500,'XLSX support requires the PHP zip extension.');
        $zip=new ZipArchive(); abort_unless($zip->open($path)===true,422,'Invalid XLSX file.');
        $shared=[]; if(($xml=$zip->getFromName('xl/sharedStrings.xml'))!==false){ $sx=simplexml_load_string($xml); foreach($sx->si as $si) $shared[]=(string)($si->t??$si->r->t??''); }
        $sheet=$zip->getFromName('xl/worksheets/sheet1.xml'); $zip->close(); abort_if($sheet===false,422,'XLSX sheet1 is missing.');
        $xml=simplexml_load_string($sheet); $matrix=[];
        foreach($xml->sheetData->row as $row){ $cells=[]; foreach($row->c as $c){ $ref=(string)$c['r']; preg_match('/([A-Z]+)/',$ref,$m); $col=$this->columnIndex($m[1]??'A'); $v=(string)$c->v; $cells[$col]=((string)$c['t']==='s')?($shared[(int)$v]??''):$v; } if($cells){ ksort($cells); $matrix[]=array_values(array_replace(array_fill(0,max(array_keys($cells))+1,''),$cells)); } }
        if(!$matrix) return []; $headers=array_shift($matrix); return array_map(fn($values)=>array_combine($headers,array_pad($values,count($headers),null)),$matrix);
    }

    private function columnIndex(string $letters): int { $n=0; foreach(str_split($letters) as $c) $n=$n*26+(ord($c)-64); return $n-1; }

    private function event(int $submissionId,int $tenantId,string $type,?string $from,?string $to,?string $notes,int $actorId): void
    {
        DB::table('customer_asset_submission_events')->insert(['customer_asset_submission_id'=>$submissionId,'tenant_id'=>$tenantId,'event_type'=>$type,'from_status'=>$from,'to_status'=>$to,'notes'=>$notes,'performed_by'=>$actorId,'performed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
    }
}
