<?php

use App\Jobs\CreatePlatformNotification;
use App\Models\{ApiToken,Asset,MaintenancePlan,ReportSubscription,User,WorkOrder};
use App\Services\EAM\{AssetHealthService,AssetSparePartReorderService};
use Illuminate\Support\Facades\{Artisan,DB,Mail,Schedule,Schema};

Artisan::command('unifco:status', function (): void { $this->info('UNIFCO Platform runtime is available.'); })->purpose('Show the UNIFCO runtime status');

Artisan::command('unifco:notify-admins {title} {--message=}', function (): void {
    User::where('status','ACTIVE')->where('role','ADMIN')->each(fn(User $u)=>CreatePlatformNotification::dispatch($u->tenant_id,$u->id,$this->argument('title'),$this->option('message')));
    $this->info('Admin notifications queued.');
})->purpose('Queue a platform notification for all active tenant administrators');

Artisan::command('unifco:deliver-reports', function (): void {
    ReportSubscription::where('is_active',true)->whereNotNull('next_delivery_at')->where('next_delivery_at','<=',now())->get()->each(function(ReportSubscription $s): void {
        $url=route('reporting.executive');
        if($s->delivery_channel==='EMAIL' && $s->recipient){
            Mail::raw('Your scheduled UNIFCO Executive Summary is ready: '.$url,fn($m)=>$m->to($s->recipient)->subject('UNIFCO Executive Summary'));
        } else {
            CreatePlatformNotification::dispatch($s->tenant_id,$s->user_id,'Scheduled Executive Report','Your scheduled executive summary is ready.','REPORT',$url);
        }
        $next=match($s->frequency){'DAILY'=>now()->addDay(),'MONTHLY'=>now()->addMonth(),default=>now()->addWeek()};
        $s->update(['last_delivered_at'=>now(),'next_delivery_at'=>$next]);
    });
    $this->info('Scheduled reports delivered.');
})->purpose('Deliver due executive report subscriptions');

Artisan::command('unifco:generate-pm-work-orders', function (): void {
    $created=0;
    $skipped=0;

    MaintenancePlan::with('asset')
        ->where('status','ACTIVE')
        ->where('auto_generate_work_orders',true)
        ->orderBy('id')
        ->get()
        ->each(function(MaintenancePlan $plan) use (&$created,&$skipped): void {
            $asset=$plan->asset;
            if(!$asset){$skipped++;return;}

            $isMeter=$plan->frequency_type==='METER';
            $due=false;
            if($isMeter){
                $due=$plan->next_due_meter!==null && (float)$asset->meter_value >= (float)$plan->next_due_meter;
            } elseif($plan->next_due_date){
                $leadDays=(int)($plan->lead_days ?? 7);
                $due=$plan->next_due_date->lte(today()->addDays($leadDays));
            }
            if(!$due){$skipped++;return;}

            $alreadyOpen=WorkOrder::where('maintenance_plan_id',$plan->id)
                ->whereNotIn('status',['COMPLETED','CLOSED','CANCELLED'])
                ->exists();
            if($alreadyOpen){$skipped++;return;}

            $dueToken=$isMeter
                ? 'M'.str_replace('.','-',(string)$plan->next_due_meter)
                : $plan->next_due_date->format('Ymd');
            $workOrderNo='PM-'.$plan->id.'-'.$dueToken;

            $attributes=[
                'tenant_id'=>$plan->tenant_id,
                'organization_id'=>$plan->organization_id,
                'work_order_no'=>$workOrderNo,
                'asset_id'=>$plan->asset_id,
                'maintenance_plan_id'=>$plan->id,
                'maintenance_type'=>'PREVENTIVE',
                'priority'=>$plan->priority,
                'status'=>'OPEN',
                'planned_start'=>$isMeter ? now() : $plan->next_due_date->startOfDay(),
            ];
            if(Schema::hasColumn('work_orders','service_contract_id')) $attributes['service_contract_id']=$plan->service_contract_id;

            WorkOrder::firstOrCreate(['tenant_id'=>$plan->tenant_id,'work_order_no'=>$workOrderNo],$attributes);

            if($isMeter){
                $plan->update(['next_due_meter'=>(float)$plan->next_due_meter + (int)$plan->frequency_value]);
            } else {
                $next=$plan->next_due_date->copy();
                $value=max(1,(int)$plan->frequency_value);
                $next=match($plan->frequency_type){
                    'DAYS'=>$next->addDays($value),
                    'WEEKS'=>$next->addWeeks($value),
                    'YEARS'=>$next->addYears($value),
                    default=>$next->addMonths($value),
                };
                $plan->update(['next_due_date'=>$next]);
            }
            $created++;
        });

    $this->info("PM generation complete. Created: {$created}; skipped/not due: {$skipped}.");
})->purpose('Generate preventive maintenance work orders for date-based and meter-based plans');

Artisan::command('unifco:recalculate-asset-health', function (AssetHealthService $health): void {
    $count=0;
    Asset::whereNotIn('lifecycle_status',['DISPOSED','RETIRED'])->orderBy('id')->chunkById(200,function($assets) use($health,&$count): void {
        foreach($assets as $asset){$health->recalculate($asset);$count++;}
    });
    $this->info("Asset health recalculation complete. Assets processed: {$count}.");
})->purpose('Recalculate lifecycle health score and replacement recommendation for active assets');

Artisan::command('unifco:check-spare-reorder-alerts', function (AssetSparePartReorderService $reorder): void {
    $alerts=$reorder->syncStatuses()->filter(fn($a)=>$a->computed_alert_status!=='OK');
    $notified=0;
    foreach($alerts as $alert){
        $last=$alert->last_reorder_notified_at ? \Carbon\Carbon::parse($alert->last_reorder_notified_at) : null;
        if($last && $last->gt(now()->subHours(24))) continue;
        $title=$alert->computed_alert_status==='OUT_OF_STOCK'?'Critical Spare Out of Stock':'Spare Part Reorder Alert';
        $message=$alert->asset_code.' · '.$alert->item_code.' · '.str_replace('_',' ',$alert->computed_alert_status).' · Available '.number_format($alert->available_quantity,2).' '.$alert->uom.' · Reorder level '.number_format((float)$alert->reorder_level,2);
        User::where('tenant_id',$alert->tenant_id)->where('status','ACTIVE')->where('role','ADMIN')->each(function(User $user) use($title,$message): void {
            CreatePlatformNotification::dispatch($user->tenant_id,$user->id,$title,$message,'EAM',route('eam.health.index'));
        });
        DB::table('asset_spare_parts')->where('id',$alert->id)->update(['last_reorder_notified_at'=>now(),'reorder_alert_status'=>$alert->computed_alert_status,'updated_at'=>now()]);
        $notified++;
    }
    $this->info("Spare reorder check complete. Active alerts: {$alerts->count()}; notifications refreshed: {$notified}.");
})->purpose('Evaluate asset spare part availability and notify administrators when reorder action is required');

Schedule::call(fn()=>ApiToken::whereNotNull('expires_at')->where('expires_at','<',now()->subDays(30))->delete())->dailyAt('02:10')->name('prune-expired-api-tokens')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->dailyAt('02:20');
Schedule::command('unifco:deliver-reports')->hourly()->withoutOverlapping();
Schedule::command('unifco:generate-pm-work-orders')->hourly()->withoutOverlapping();
Schedule::command('unifco:recalculate-asset-health')->dailyAt('03:10')->withoutOverlapping();
Schedule::command('unifco:check-spare-reorder-alerts')->dailyAt('07:30')->withoutOverlapping();
