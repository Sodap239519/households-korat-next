<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

// Public landing redirects to the SPA's public dashboard
Route::get('/', fn() => redirect('/app'));

// Serve the Vue SPA at /app and any sub-path
Route::get('/app/{any?}', [SpaController::class, 'index'])
    ->where('any', '.*')
    ->name('spa');

