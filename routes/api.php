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
use App\Http\Controllers\Api\Market\SellerShippingController as MarketSellerShippingController;
use App\Http\Controllers\Api\Market\SellerApplicationController as MarketSellerApplicationController;
use App\Http\Controllers\Api\Market\FlashSaleEventController as MarketFlashSaleEventController;
use App\Http\Controllers\Api\Shop\SellerApplicationController as ShopSellerApplicationController;
use App\Http\Controllers\Api\Shop\CustomerAddressController as ShopAddressController;
use App\Http\Controllers\Api\Shop\CatalogController as ShopCatalogController;
use App\Http\Controllers\Api\Shop\CheckoutController as ShopCheckoutController;
use App\Http\Controllers\Api\Shop\CustomerAuthController as ShopCustomerAuthController;
use App\Http\Controllers\Api\Shop\FeedbackController as ShopFeedbackController;
use App\Http\Controllers\Api\Shop\OrderController as ShopOrderController;
use App\Http\Controllers\Api\Shop\WishlistController as ShopWishlistController;
use App\Http\Controllers\Api\Shop\CustomerNotificationController as ShopCustomerNotificationController;
use App\Http\Controllers\Api\Shop\CartSyncController as ShopCartSyncController;
use App\Http\Controllers\Api\LineWebhookController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Market\ChatController as MarketChatController;
use App\Http\Controllers\Api\Market\ShopBannerController;
use App\Http\Controllers\Api\Shop\ChatController as ShopChatController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\UsersAdminController;
use Illuminate\Support\Facades\Route;

// ===== Public (no auth) =====
Route::get('/address/search', [AddressController::class, 'search']);
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
Route::get('/shop/sellers/{slug}',    [ShopCatalogController::class, 'seller']);
Route::get('/shop/groups',            [ShopCatalogController::class, 'groups']);
Route::get('/shop/categories',        [ShopCatalogController::class, 'categories']);
Route::get('/shop/banners',           [ShopBannerController::class, 'public']);
Route::get('/shop/products',             [ShopCatalogController::class, 'products']);
Route::get('/shop/products/stock',       [ShopCatalogController::class, 'stockBatch']);
Route::get('/shop/products/{slug}',      [ShopCatalogController::class, 'product']);
Route::post('/customer/register',        [ShopCustomerAuthController::class, 'register']);
Route::post('/customer/forgot-password', [ShopCustomerAuthController::class, 'forgotPassword']);
Route::get('/shop/products/{slug}/reviews',  [ShopCatalogController::class, 'productReviews']);
Route::get('/shop/products/{slug}/comments', [ShopCatalogController::class, 'productComments']);
Route::get('/shop/shipping',                 [ShopCatalogController::class, 'globalShipping']);
Route::get('/shop/shipping/by-groups',       [ShopCatalogController::class, 'shippingByGroups']);
Route::get('/shop/flash-sale-events',        [ShopCatalogController::class, 'flashSaleEvents']);
Route::get('/shop/flash-sale-events/{id}/products', [ShopCatalogController::class, 'flashSaleEventProducts']);
Route::get('/shop/flash-sale/current',       [ShopCatalogController::class, 'currentFlashSale']);

// ===== Seller Applications (public — สมัครขาย + ติดตามสถานะ) =====
Route::get('/seller/apply/groups',              [ShopSellerApplicationController::class, 'groups']);
Route::post('/seller/apply',                    [ShopSellerApplicationController::class, 'store']);
Route::get('/seller/apply/status/{token}',      [ShopSellerApplicationController::class, 'status']);
Route::post('/seller/apply/{token}/reveal-password', [ShopSellerApplicationController::class, 'revealPassword']);

// ===== Protected =====
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/seller/my-applications', [ShopSellerApplicationController::class, 'mine']);
    Route::put('/seller/apply/{token}', [ShopSellerApplicationController::class, 'update']);
    Route::get('/seller/apply/{token}/edit', [ShopSellerApplicationController::class, 'edit']);

    Route::get('/system/last-updated', [SystemController::class, 'lastUpdated']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Profile (own)
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'changePassword']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);

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
    // รายการโปรด
    Route::get('/shop/my/wishlist-ids',                [ShopWishlistController::class, 'ids']);
    Route::get('/shop/my/wishlist',                    [ShopWishlistController::class, 'index']);
    Route::post('/shop/wishlist/{productId}/toggle',   [ShopWishlistController::class, 'toggle']);

    Route::post('/shop/checkout',                  [ShopCheckoutController::class, 'store']);

    // Customer notifications (โปรโมชั่น / ตะกร้าค้าง / แจ้งเตือนจัดส่ง)
    Route::get('/shop/my/customer-notifications/count',                            [ShopCustomerNotificationController::class, 'count']);
    Route::get('/shop/my/customer-notifications',                                  [ShopCustomerNotificationController::class, 'index']);
    Route::post('/shop/my/customer-notifications/read-all',                        [ShopCustomerNotificationController::class, 'markAllRead']);
    Route::post('/shop/my/customer-notifications/{notification}/read',             [ShopCustomerNotificationController::class, 'markRead']);

    // Cart sync (ซิงค์ตะกร้า localStorage → server สำหรับ abandoned cart tracking)
    Route::post('/shop/cart/sync',  [ShopCartSyncController::class, 'sync']);
    Route::post('/shop/cart/clear', [ShopCartSyncController::class, 'clear']);

    Route::get('/shop/my/notifications',            [ShopOrderController::class, 'notificationCount']);
    Route::get('/shop/my/orders',                  [ShopOrderController::class, 'index']);
    Route::get('/shop/my/orders/summary',          [ShopOrderController::class, 'summary']);
    Route::get('/shop/my/orders/savings',          [ShopOrderController::class, 'savings']);
    Route::get('/shop/my/orders/{orderNo}',        [ShopOrderController::class, 'show']);
    Route::post('/shop/orders/{orderNo}/payment',  [ShopOrderController::class, 'submitPayment']);
    Route::post('/shop/orders/{orderNo}/receive',  [ShopOrderController::class, 'receive']);
    Route::post('/shop/orders/{orderNo}/cancel',   [ShopOrderController::class, 'cancel']);
    Route::post('/shop/orders/{orderNo}/returns',  [ShopOrderController::class, 'requestReturn']);

    // ที่อยู่จัดส่งของลูกค้า
    Route::get('/shop/my/addresses',                             [ShopAddressController::class, 'index']);
    Route::post('/shop/my/addresses',                            [ShopAddressController::class, 'store']);
    Route::put('/shop/my/addresses/{customerAddress}',           [ShopAddressController::class, 'update']);
    Route::delete('/shop/my/addresses/{customerAddress}',        [ShopAddressController::class, 'destroy']);
    Route::post('/shop/my/addresses/{customerAddress}/default',  [ShopAddressController::class, 'setDefault']);

    // Chat (ลูกค้า ↔ ผู้ขาย)
    Route::get('/shop/chat/unread',                                [ShopChatController::class, 'unread']);
    Route::get('/shop/chat/conversations',                         [ShopChatController::class, 'index']);
    Route::post('/shop/chat/start/{slug}',                         [ShopChatController::class, 'start']);
    Route::get('/shop/chat/conversations/{id}/messages',           [ShopChatController::class, 'messages']);
    Route::post('/shop/chat/conversations/{id}/messages',          [ShopChatController::class, 'send']);
    Route::delete('/shop/chat/conversations/{id}/messages/{message}', [ShopChatController::class, 'deleteMessage']);

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

        // Dashboard (KPI + กราฟ + badges)
        Route::get('dashboard/badges', [MarketDashboardController::class, 'badges']);
        Route::get('dashboard', [MarketDashboardController::class, 'index']);

        // Reviews (ตอบกลับ/ซ่อน)
        Route::get('reviews',                        [MarketReviewController::class, 'index']);
        Route::post('reviews/{review}/reply',        [MarketReviewController::class, 'reply']);
        Route::post('reviews/{review}/status',       [MarketReviewController::class, 'setStatus']);

        // Comments (ตอบกลับ/ซ่อน)
        Route::get('comments',                       [MarketCommentController::class, 'index']);
        Route::post('comments/{comment}/reply',      [MarketCommentController::class, 'reply']);
        Route::post('comments/{comment}/status',     [MarketCommentController::class, 'setStatus']);

        // Chat backoffice (ผู้ขายตอบลูกค้า)
        Route::get('chat/unread',                          [MarketChatController::class, 'unread']);
        Route::get('chat/conversations',                   [MarketChatController::class, 'index']);
        Route::get('chat/conversations/{id}/messages',     [MarketChatController::class, 'messages']);
        Route::post('chat/conversations/{id}/messages',    [MarketChatController::class, 'send']);

        // Seller groups (admin จัดการเต็ม / เจ้าหน้าที่กลุ่มแก้ข้อมูลติดต่อ+LINE ของตัวเอง)
        Route::post('seller-groups/{sellerGroup}/members',  [MarketSellerGroupController::class, 'setMembers']);
        Route::post('seller-groups/{sellerGroup}/logo',    [MarketSellerGroupController::class, 'uploadLogo']);
        Route::delete('seller-groups/{sellerGroup}/logo',  [MarketSellerGroupController::class, 'deleteLogo']);
        Route::post('seller-groups/{sellerGroup}/ban',     [MarketSellerGroupController::class, 'ban']);
        Route::post('seller-groups/{sellerGroup}/suspend', [MarketSellerGroupController::class, 'suspend']);
        Route::post('seller-groups/{sellerGroup}/restore', [MarketSellerGroupController::class, 'restore']);
        // Global shipping options (ไม่ผูกกับกลุ่มผู้ขาย)
        Route::get('shipping',              [MarketSellerShippingController::class, 'index']);
        Route::post('shipping',             [MarketSellerShippingController::class, 'store']);
        Route::put('shipping/{option}',     [MarketSellerShippingController::class, 'update']);
        Route::delete('shipping/{option}',  [MarketSellerShippingController::class, 'destroy']);
        Route::apiResource('seller-groups', MarketSellerGroupController::class)
            ->parameters(['seller-groups' => 'sellerGroup']);

        // Banner management (superadmin/admin) — reorder/image ต้องประกาศก่อน apiResource
        Route::post('banners/reorder',                   [ShopBannerController::class, 'reorder']);
        Route::post('banners/{shopBanner}/image',        [ShopBannerController::class, 'uploadImage']);
        Route::patch('banners/{shopBanner}/toggle',      [ShopBannerController::class, 'toggle']);
        Route::apiResource('banners', ShopBannerController::class)
            ->except(['show'])
            ->parameters(['banners' => 'shopBanner']);

        // Categories
        Route::apiResource('categories', MarketCategoryController::class)->except(['show']);

        // Seller Applications (admin/superadmin พิจารณาคำขอสมัครขาย)
        Route::get('seller-applications/counts',                                    [MarketSellerApplicationController::class, 'counts']);
        Route::get('seller-applications',                                           [MarketSellerApplicationController::class, 'index']);
        Route::get('seller-applications/{sellerApplication}',                       [MarketSellerApplicationController::class, 'show']);
        Route::post('seller-applications/{sellerApplication}/admin-review',         [MarketSellerApplicationController::class, 'adminReview']);
        Route::post('seller-applications/{sellerApplication}/final-review',         [MarketSellerApplicationController::class, 'finalReview']);
        Route::post('seller-applications/{sellerApplication}/request-revision',     [MarketSellerApplicationController::class, 'requestRevision']);

        // Flash Sale Events (event-based: superadmin สร้าง → ร้านเพิ่มสินค้า)
        Route::get('flash-sale-events',                                          [MarketFlashSaleEventController::class, 'index']);
        Route::post('flash-sale-events',                                         [MarketFlashSaleEventController::class, 'store']);
        Route::get('flash-sale-events/{flashSaleEvent}',                         [MarketFlashSaleEventController::class, 'show']);
        Route::put('flash-sale-events/{flashSaleEvent}',                         [MarketFlashSaleEventController::class, 'update']);
        Route::delete('flash-sale-events/{flashSaleEvent}',                      [MarketFlashSaleEventController::class, 'destroy']);
        Route::get('flash-sale-events/{flashSaleEvent}/items',                   [MarketFlashSaleEventController::class, 'items']);
        Route::post('flash-sale-events/{flashSaleEvent}/items',                  [MarketFlashSaleEventController::class, 'addItems']);
        Route::delete('flash-sale-events/{flashSaleEvent}/items/{item}',         [MarketFlashSaleEventController::class, 'removeItem']);

        // Products (+ image upload + flash sale)
        Route::get('products/flash-sale',                       [MarketProductController::class, 'flashSaleList']);
        Route::post('products/flash-sale/batch',                [MarketProductController::class, 'flashSaleBatch']);
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
