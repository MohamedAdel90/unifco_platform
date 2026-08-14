<?php

use App\Jobs\CreatePlatformNotification;
use App\Models\{ApiToken,User};
use Illuminate\Support\Facades\{Artisan,Schedule};

Artisan::command('unifco:status', function (): void { $this->info('UNIFCO Platform runtime is available.'); })->purpose('Show the UNIFCO runtime status');

Artisan::command('unifco:notify-admins {title} {--message=}', function (): void {
    User::where('status','ACTIVE')->where('role','ADMIN')->each(fn(User $u)=>CreatePlatformNotification::dispatch($u->tenant_id,$u->id,$this->argument('title'),$this->option('message')));
    $this->info('Admin notifications queued.');
})->purpose('Queue a platform notification for all active tenant administrators');

Schedule::call(fn()=>ApiToken::whereNotNull('expires_at')->where('expires_at','<',now()->subDays(30))->delete())->dailyAt('02:10')->name('prune-expired-api-tokens')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->dailyAt('02:20');
