<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HouseholdApiController;
use App\Http\Controllers\Api\MushroomAllocationController;
use App\Http\Controllers\Api\MushroomFollowupController;
use App\Http\Controllers\Api\MushroomQuotaController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (Sanctum cookie auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Households
    Route::apiResource('households', HouseholdApiController::class);

    // Mushroom Quotas
    Route::apiResource('mushroom-quotas', MushroomQuotaController::class);

    // Mushroom Allocations
    Route::apiResource('mushroom-allocations', MushroomAllocationController::class);

    // Mushroom Followups
    Route::apiResource('mushroom-followups', MushroomFollowupController::class);

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/by-district', [ReportController::class, 'byDistrict'])->name('by-district');
        Route::get('/quota-vs-allocated', [ReportController::class, 'quotaVsAllocated'])->name('quota-vs-allocated');
        Route::get('/household-revenue', [ReportController::class, 'householdRevenue'])->name('household-revenue');
        Route::get('/by-enterprise', [ReportController::class, 'byEnterprise'])->name('by-enterprise');
        Route::get('/years', [ReportController::class, 'years'])->name('years');
        Route::get('/districts', [ReportController::class, 'districts'])->name('districts');
    });
});
