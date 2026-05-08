<?php

use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HouseholdApiController;
use App\Http\Controllers\Api\HouseholdExportController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\LoginHistoryController;
use App\Http\Controllers\Api\MushroomAllocationController;
use App\Http\Controllers\Api\MushroomFollowupController;
use App\Http\Controllers\Api\MushroomQuotaController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PublicDashboardController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\UsersAdminController;
use Illuminate\Support\Facades\Route;

// ===== Public (no auth) =====
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [RegisterController::class, 'store']);
Route::get('/public/dashboard', [PublicDashboardController::class, 'index']);
Route::get('/public/years',     [PublicDashboardController::class, 'years']);
Route::get('/locations/districts',     [LocationController::class, 'districts']);
Route::get('/locations/sub-districts', [LocationController::class, 'subDistricts']);
Route::get('/locations/villages',      [LocationController::class, 'villages']);
Route::get('/locations/provinces',     [LocationController::class, 'provinces']);

// ===== Protected =====
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/system/last-updated', [SystemController::class, 'lastUpdated']);
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

    // Admin: notifications + approval
    Route::get('/admin/notifications/counts',                   [AdminNotificationController::class, 'counts']);
    Route::get('/admin/notifications',                          [AdminNotificationController::class, 'list']);
    Route::post('/admin/notifications/read-all',                [AdminNotificationController::class, 'markAllRead']);
    Route::post('/admin/notifications/{notification}/read',     [AdminNotificationController::class, 'markRead']);
    Route::get('/admin/notifications/pending-users',            [AdminNotificationController::class, 'pendingUsers']);
    Route::post('/admin/users/{user}/approve',                  [AdminNotificationController::class, 'approve']);
    Route::post('/admin/users/{user}/reject',                   [AdminNotificationController::class, 'reject']);

    // Households
    Route::get('/households/export',           [HouseholdExportController::class, 'csv']);
    Route::get('/households/{household}/tracking', [HouseholdApiController::class, 'tracking']);
    Route::apiResource('households', HouseholdApiController::class);

    // Mushroom Quotas / Allocations / Followups
    // NOTE: the quota controller uses $mushroomQuotaDistrict but the resource name
    // is "mushroom-quotas" so Laravel's default binding name {mushroom_quota}
    // wouldn't match the variable. Override the parameter name explicitly.
    Route::apiResource('mushroom-quotas', MushroomQuotaController::class)
        ->parameters(['mushroom-quotas' => 'mushroomQuotaDistrict']);
    // Group allocation: split bags evenly across many households (must be declared
    // BEFORE the apiResource so /group is not mistaken for {mushroomAllocation}).
    Route::post('mushroom-allocations/group', [MushroomAllocationController::class, 'storeGroup']);
    Route::apiResource('mushroom-allocations', MushroomAllocationController::class);
    // Group followup: split production / revenue evenly across all members
    Route::post('mushroom-followups/group', [MushroomFollowupController::class, 'storeGroup']);
    // Autocomplete suggestions for free-text fields (must come before resource)
    Route::get('mushroom-followups/suggestions', [MushroomFollowupController::class, 'suggestions']);
    Route::apiResource('mushroom-followups', MushroomFollowupController::class);

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/households-overview', [ReportController::class, 'householdsOverview'])->name('households-overview');
        Route::get('/dashboard',          [ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/by-district',        [ReportController::class, 'byDistrict'])->name('by-district');
        Route::get('/quota-vs-allocated', [ReportController::class, 'quotaVsAllocated'])->name('quota-vs-allocated');
        Route::get('/household-revenue',  [ReportController::class, 'householdRevenue'])->name('household-revenue');
        Route::get('/by-enterprise',      [ReportController::class, 'byEnterprise'])->name('by-enterprise');
        Route::get('/by-group',           [ReportController::class, 'byGroup'])->name('by-group');
        Route::get('/income-comparison',  [ReportController::class, 'incomeComparison'])->name('income-comparison');
        Route::get('/years',              [ReportController::class, 'years'])->name('years');
        Route::get('/districts',          [ReportController::class, 'districts'])->name('districts');
    });
});
