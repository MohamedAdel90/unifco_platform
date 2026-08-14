<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublishOutbox extends Command
{
    protected $signature='unifco:outbox-publish {--limit=100}';
    protected $description='Publish pending UNIFCO transactional outbox events to Laravel event consumers';

    public function handle(): int
    {
        $events=DB::table('outbox_events')->whereNull('published_at')->orderBy('created_at')->limit((int)$this->option('limit'))->get();
        foreach ($events as $row) {
            try {
                event('unifco.domain.'.$row->event_type,[json_decode($row->payload,true),$row->correlation_id]);
                DB::table('outbox_events')->where('id',$row->id)->update(['published_at'=>now(),'attempts'=>$row->attempts+1,'last_error'=>null,'updated_at'=>now()]);
                $this->line("Published {$row->event_type} {$row->id}");
            } catch (\Throwable $e) {
                DB::table('outbox_events')->where('id',$row->id)->update(['attempts'=>$row->attempts+1,'last_error'=>$e->getMessage(),'updated_at'=>now()]);
                $this->error("Failed {$row->id}: {$e->getMessage()}");
            }
        }
        return self::SUCCESS;
    }
}
