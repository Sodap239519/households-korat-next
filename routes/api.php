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
use App\Http\Controllers\Api\Market\CategoryController as MarketCategoryController;
use App\Http\Controllers\Api\Market\CommentController as MarketCommentController;
use App\Http\Controllers\Api\Market\DashboardController as MarketDashboardController;
use App\Http\Controllers\Api\Market\OrderController as MarketOrderController;
use App\Http\Controllers\Api\Market\PaymentController as MarketPaymentController;
use App\Http\Controllers\Api\Market\ProductController as MarketProductController;
use App\Http\Controllers\Api\Market\ReturnController as MarketReturnController;
use App\Http\Controllers\Api\Market\ReviewController as MarketReviewController;
use App\Http\Controllers\Api\Market\SellerGroupController as MarketSellerGroupController;
use App\Http\Controllers\Api\Shop\CatalogController as ShopCatalogController;
use App\Http\Controllers\Api\Shop\CheckoutController as ShopCheckoutController;
use App\Http\Controllers\Api\Shop\CustomerAuthController as ShopCustomerAuthController;
use App\Http\Controllers\Api\Shop\FeedbackController as ShopFeedbackController;
use App\Http\Controllers\Api\Shop\OrderController as ShopOrderController;
use App\Http\Controllers\Api\LineWebhookController;
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

// ===== LINE Webhook (public — ต้องไม่มี auth middleware) =====
Route::post('/line/webhook',       [LineWebhookController::class, 'handle']);
Route::middleware('auth:sanctum')->get('/line/targets', [LineWebhookController::class, 'recentTargets']);

// ===== Storefront (public, no auth) =====
Route::get('/shop/groups',            [ShopCatalogController::class, 'groups']);
Route::get('/shop/categories',        [ShopCatalogController::class, 'categories']);
Route::get('/shop/products',          [ShopCatalogController::class, 'products']);
Route::get('/shop/products/{slug}',   [ShopCatalogController::class, 'product']);
Route::post('/customer/register',     [ShopCustomerAuthController::class, 'register']);
Route::get('/shop/products/{slug}/reviews',  [ShopCatalogController::class, 'productReviews']);
Route::get('/shop/products/{slug}/comments', [ShopCatalogController::class, 'productComments']);

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

    // ===== Storefront (customer, ต้อง login) =====
    Route::post('/shop/checkout',                  [ShopCheckoutController::class, 'store']);
    Route::get('/shop/my/orders',                  [ShopOrderController::class, 'index']);
    Route::get('/shop/my/orders/{orderNo}',        [ShopOrderController::class, 'show']);
    Route::post('/shop/orders/{orderNo}/payment',  [ShopOrderController::class, 'submitPayment']);
    Route::post('/shop/orders/{orderNo}/receive',  [ShopOrderController::class, 'receive']);
    Route::post('/shop/orders/{orderNo}/returns',  [ShopOrderController::class, 'requestReturn']);

    // Feedback (รีวิว + คอมเมนต์ — ต้อง login)
    Route::get('/shop/products/{slug}/eligibility', [ShopFeedbackController::class, 'eligibility']);
    Route::post('/shop/products/{slug}/reviews',    [ShopFeedbackController::class, 'storeReview']);
    Route::post('/shop/products/{slug}/comments',   [ShopFeedbackController::class, 'storeComment']);

    // ===== Marketplace backoffice (/market) =====
    Route::prefix('market')->group(function () {
        // Payments (ยืนยันการชำระเงิน)
        Route::get('payments',                    [MarketPaymentController::class, 'index']);
        Route::post('payments/{payment}/confirm', [MarketPaymentController::class, 'confirm']);
        Route::post('payments/{payment}/reject',  [MarketPaymentController::class, 'reject']);

        // Orders (จัดการคำสั่งซื้อ + จัดส่ง)
        Route::get('orders',                  [MarketOrderController::class, 'index']);
        Route::get('orders/{order}',          [MarketOrderController::class, 'show']);
        Route::post('orders/{order}/ship',    [MarketOrderController::class, 'ship']);
        Route::post('orders/{order}/status',  [MarketOrderController::class, 'setStatus']);

        // Returns (คืน/เคลม)
        Route::get('returns',                          [MarketReturnController::class, 'index']);
        Route::post('returns/{returnRequest}/resolve', [MarketReturnController::class, 'resolve']);

        // Dashboard (KPI + กราฟ)
        Route::get('dashboard', [MarketDashboardController::class, 'index']);

        // Reviews (ตอบกลับ/ซ่อน)
        Route::get('reviews',                        [MarketReviewController::class, 'index']);
        Route::post('reviews/{review}/reply',        [MarketReviewController::class, 'reply']);
        Route::post('reviews/{review}/status',       [MarketReviewController::class, 'setStatus']);

        // Comments (ตอบกลับ/ซ่อน)
        Route::get('comments',                       [MarketCommentController::class, 'index']);
        Route::post('comments/{comment}/reply',      [MarketCommentController::class, 'reply']);
        Route::post('comments/{comment}/status',     [MarketCommentController::class, 'setStatus']);

        // Seller groups (admin จัดการเต็ม / เจ้าหน้าที่กลุ่มแก้ข้อมูลติดต่อ+LINE ของตัวเอง)
        Route::post('seller-groups/{sellerGroup}/members', [MarketSellerGroupController::class, 'setMembers']);
        Route::apiResource('seller-groups', MarketSellerGroupController::class)
            ->parameters(['seller-groups' => 'sellerGroup']);

        // Categories
        Route::apiResource('categories', MarketCategoryController::class)->except(['show']);

        // Products (+ image upload)
        Route::post('products/{product}/images',                [MarketProductController::class, 'uploadImages']);
        Route::delete('products/{product}/images/{image}',      [MarketProductController::class, 'deleteImage']);
        Route::apiResource('products', MarketProductController::class);
    });

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
