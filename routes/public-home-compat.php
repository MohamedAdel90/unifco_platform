<?php

use Illuminate\Support\Facades\Route;

Route::get('/en', function () {
    return redirect()->route('public.home', ['lang' => 'en']);
})->name('public.home.en');
