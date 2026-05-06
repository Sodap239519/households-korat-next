<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HouseholdApiController;
use App\Http\Controllers\Api\LoginHistoryController;
use App\Http\Controllers\Api\MushroomAllocationController;
use App\Http\Controllers\Api\MushroomFollowupController;
use App\Http\Controllers\Api\MushroomQuotaController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UsersAdminController;
use Illuminate\Support\Facades\Route;

// Public auth
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (Sanctum cookie auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Profile (own)
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'changePassword']);

    // Login history (own)
    Route::get('/login-history', [LoginHistoryController::class, 'mine']);

    // Admin: user management
    Route::get('/admin/users',                  [UsersAdminController::class, 'index']);
    Route::post('/admin/users',                 [UsersAdminController::class, 'store']);
    Route::get('/admin/users/{user}',           [UsersAdminController::class, 'show']);
    Route::put('/admin/users/{user}',           [UsersAdminController::class, 'update']);
    Route::delete('/admin/users/{user}',        [UsersAdminController::class, 'destroy']);
    Route::put('/admin/users/{user}/password',  [UsersAdminController::class, 'resetPassword']);
    Route::get('/admin/users/{user}/login-history', [LoginHistoryController::class, 'forUser']);

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
        Route::get('/households-overview', [ReportController::class, 'householdsOverview'])->name('households-overview');
        Route::get('/dashboard',          [ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/by-district',        [ReportController::class, 'byDistrict'])->name('by-district');
        Route::get('/quota-vs-allocated', [ReportController::class, 'quotaVsAllocated'])->name('quota-vs-allocated');
        Route::get('/household-revenue',  [ReportController::class, 'householdRevenue'])->name('household-revenue');
        Route::get('/by-enterprise',      [ReportController::class, 'byEnterprise'])->name('by-enterprise');
        Route::get('/years',              [ReportController::class, 'years'])->name('years');
        Route::get('/districts',          [ReportController::class, 'districts'])->name('districts');
    });
});
