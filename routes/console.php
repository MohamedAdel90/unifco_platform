<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('unifco:status', function (): void {
    $this->info('UNIFCO Platform runtime is available.');
})->purpose('Show the UNIFCO runtime status');
