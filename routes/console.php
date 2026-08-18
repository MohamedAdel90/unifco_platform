<?php

use App\Jobs\CreatePlatformNotification;
use App\Models\{ApiToken,ReportSubscription,User};
use Illuminate\Support\Facades\{Artisan,Mail,Schedule};

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

Schedule::call(fn()=>ApiToken::whereNotNull('expires_at')->where('expires_at','<',now()->subDays(30))->delete())->dailyAt('02:10')->name('prune-expired-api-tokens')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->dailyAt('02:20');
Schedule::command('unifco:deliver-reports')->hourly()->withoutOverlapping();
