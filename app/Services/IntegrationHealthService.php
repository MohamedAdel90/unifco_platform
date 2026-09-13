<?php

namespace App\Services;

use App\Models\{ApiToken,SystemIntegration};
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\{DB,Schema,Storage};
use Throwable;

class IntegrationHealthService
{
    public function refresh(int $tenantId): array
    {
        $rows = SystemIntegration::where('tenant_id',$tenantId)->orderBy('category')->orderBy('name')->get();
        foreach ($rows as $row) {
            $started = microtime(true);
            [$status,$provider,$detail] = $this->check($row->code,$tenantId);
            $elapsed = (int) round((microtime(true)-$started)*1000);
            $row->update([
                'status'=>$row->is_enabled ? $status : 'DISABLED',
                'provider'=>$provider ?: $row->provider,
                'response_ms'=>$elapsed,
                'last_checked_at'=>now(),
                'last_success_at'=>in_array($status,['OPERATIONAL','READY'],true) ? now() : $row->last_success_at,
                'last_error'=>in_array($status,['OPERATIONAL','READY'],true) ? null : $detail,
                'metadata'=>array_merge($row->metadata ?: [],['detail'=>$detail]),
            ]);
        }
        return $this->summary($tenantId);
    }

    public function summary(int $tenantId): array
    {
        $rows = SystemIntegration::where('tenant_id',$tenantId)->orderBy('category')->orderBy('name')->get();
        $enabled = $rows->where('is_enabled',true);
        return [
            'rows'=>$rows,
            'total'=>$enabled->count(),
            'healthy'=>$enabled->whereIn('status',['OPERATIONAL','READY'])->count(),
            'degraded'=>$enabled->whereIn('status',['DEGRADED','ERROR','PENDING'])->count(),
            'all_healthy'=>$enabled->count() > 0 && $enabled->every(fn($row)=>in_array($row->status,['OPERATIONAL','READY'],true)),
        ];
    }

    private function check(string $code,int $tenantId): array
    {
        try {
            return match ($code) {
                'DATABASE' => $this->database(),
                'MAIL' => $this->mail(),
                'QUEUE' => $this->queue(),
                'SCHEDULER' => $this->scheduler(),
                'STORAGE' => $this->storage(),
                'API_ACCESS' => $this->api($tenantId),
                default => ['READY',null,'Registry entry is enabled; no active probe is configured.'],
            };
        } catch (Throwable $e) {
            return ['ERROR',null,$e->getMessage()];
        }
    }

    private function database(): array
    {
        DB::select('select 1');
        return ['OPERATIONAL',config('database.default'),'Database connection is responding.'];
    }

    private function mail(): array
    {
        $driver=(string)config('mail.default');
        $placeholder=in_array($driver,['log','array','null'],true);
        return [$placeholder?'PENDING':'READY',$driver,$placeholder?'Production email transport is not configured.':'Mail transport is configured.'];
    }

    private function queue(): array
    {
        $driver=(string)config('queue.default');
        return [$driver==='sync'?'DEGRADED':'READY',$driver,$driver==='sync'?'Queue is using synchronous execution.':'Queue driver is configured.'];
    }

    private function scheduler(): array
    {
        $count=collect(app(Schedule::class)->events())->count();
        return [$count>0?'READY':'PENDING','Laravel Scheduler',$count.' scheduled task(s) registered.'];
    }

    private function storage(): array
    {
        $disk=(string)config('filesystems.default');
        Storage::disk($disk)->exists('__unifco_health_probe__');
        return ['OPERATIONAL',$disk,'Storage disk is reachable.'];
    }

    private function api(int $tenantId): array
    {
        $count=Schema::hasTable('api_tokens') ? ApiToken::where('tenant_id',$tenantId)->whereNull('revoked_at')->count() : 0;
        return ['READY','UNIFCO API',$count.' active API token(s).'];
    }
}
