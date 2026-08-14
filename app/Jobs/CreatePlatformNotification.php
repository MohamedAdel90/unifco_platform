<?php

namespace App\Jobs;

use App\Models\PlatformNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreatePlatformNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries=3;
    public function __construct(public int $tenantId, public int $userId, public string $title, public ?string $message=null, public string $type='INFO', public ?string $actionUrl=null) {}
    public function handle(): void
    {
        PlatformNotification::create(['tenant_id'=>$this->tenantId,'user_id'=>$this->userId,'type'=>$this->type,'title'=>$this->title,'message'=>$this->message,'action_url'=>$this->actionUrl]);
    }
}
