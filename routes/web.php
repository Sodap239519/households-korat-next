<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Serve the Vue SPA at /app and any sub-path
Route::get('/app/{any?}', [SpaController::class, 'index'])
    ->where('any', '.*')
    ->name('spa');

