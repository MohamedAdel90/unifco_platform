<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class Wave11ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(base_path('routes/wave11.php'));
    }
}
