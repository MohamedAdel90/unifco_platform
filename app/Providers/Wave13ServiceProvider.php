<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class Wave13ServiceProvider extends ServiceProvider
{
    public function boot(): void { Route::middleware('web')->group(base_path('routes/wave13.php')); }
}
