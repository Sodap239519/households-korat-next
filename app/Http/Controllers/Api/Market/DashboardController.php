<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\ProductReview;
use App\Models\ReturnRequest;
use App\Models\SellerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * หลังบ้าน — แดชบอร์ดยอดขาย (scope ตามกลุ่ม)
 */
class DashboardController extends Controller
{
    /** สถานะที่ถือว่าขายได้แล้ว (ยืนยันชำระเงินเป็นต้นไป) */
    private const PAID = ['confirmed', 'processing', 'shipped', 'delivered', 'completed'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $scope = $user->sellerGroupScope();
        $groupId = $scope ?: ($user->isAdmin() ? $request->input('group_id') : null);

        $orderScope  = fn ($q) => $groupId ? $q->where('seller_group_id', $groupId) : $q;
        $productScope = fn ($q) => $groupId ? $q->where('seller_group_id', $groupId) : $q;

        // ===== KPI =====
        $salesTotal = $orderScope(Order::query())->whereIn('status', self::PAID)->sum('total');
        $ordersTotal = $orderScope(Order::query())->count();
        $awaiting   = $orderScope(Order::query())->where('status', 'awaiting_confirm')->count();
        $toShip     = $orderScope(Order::query())->whereIn('status', ['confirmed', 'processing'])->count();
        $productsCount = $productScope(Product::query())->count();
        $lowStock   = $productScope(Product::query())->where('stock_qty', '<', 10)->count();

        // ===== สถานะออเดอร์ (สำหรับ donut) =====
        $statusCounts = $orderScope(Order::query())
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->pluck('c', 'status');

        // ===== ยอดขาย 14 วันล่าสุด =====
        $from = Carbon::today()->subDays(13);
        $rows = $orderScope(Order::query())
            ->whereIn('status', self::PAID)
            ->where('created_at', '>=', $from)
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(total) as t'))
            ->groupBy('d')->pluck('t', 'd');

        $salesByDay = [];
        for ($i = 0; $i < 14; $i++) {
            $day = $from->copy()->addDays($i);
            $key = $day->toDateString();
            $salesByDay[] = [
                'date'  => $day->format('d/m'),
                'total' => (float) ($rows[$key] ?? 0),
            ];
        }

        // ===== สินค้าขายดี (top 5 ตามจำนวนที่ขาย) =====
        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($groupId, fn ($q) => $q->where('orders.seller_group_id', $groupId))
            ->whereIn('orders.status', self::PAID)
            ->select('order_items.product_name', DB::raw('SUM(order_items.qty) as qty'), DB::raw('SUM(order_items.line_total) as revenue'))
            ->groupBy('order_items.product_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        return response()->json([
            'summary' => [
                'sales_total'    => (float) $salesTotal,
                'orders_total'   => $ordersTotal,
                'awaiting'       => $awaiting,
                'to_ship'        => $toShip,
                'products_count' => $productsCount,
                'low_stock'      => $lowStock,
            ],
            'status_counts' => $statusCounts,
            'sales_by_day'  => $salesByDay,
            'top_products'  => $topProducts,
        ]);
    }

    /** GET /market/dashboard/badges — นับรายการที่ต้องดำเนินการ (lightweight, poll ได้บ่อย) */
    public function badges(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $scope = $user->sellerGroupScope();

        $orderScope   = fn ($q) => $scope ? $q->where('seller_group_id', $scope) : $q;
        $productScope = fn ($q) => $scope ? $q->where('seller_group_id', $scope) : $q;

        $orders = Order::query()->tap($orderScope)
            ->whereIn('status', ['pending_payment', 'awaiting_confirm'])->count();

        $payments = Payment::query()
            ->whereHas('order', $orderScope)->where('status', 'pending')->count();

        $shipments = Order::query()->tap($orderScope)
            ->whereIn('status', ['confirmed', 'processing'])->count();

        $returns = ReturnRequest::query()
            ->whereHas('order', $orderScope)->where('status', 'pending')->count();

        $chat = Conversation::query()
            ->when($scope, fn ($q) => $q->where('seller_group_id', $scope))
            ->where('is_read_by_seller', false)->count();

        $reviews = ProductReview::query()
            ->whereHas('product', $productScope)
            ->where('status', 'published')->whereNull('reply')->count();

        $comments = ProductComment::query()
            ->whereHas('product', $productScope)
            ->where('status', 'pending')->count();

        // คำขอสมัครขายที่รอดำเนินการ (superadmin เห็นทุกสถานะที่รอ, admin เห็นเฉพาะ pending)
        $sellerAppStatuses = $user->isSuperAdmin()
            ? ['pending', 'admin_approved', 'escalated']
            : ['pending'];
        $seller_apps = $user->isAdmin()
            ? SellerApplication::whereIn('status', $sellerAppStatuses)->count()
            : 0;

        return response()->json(compact('orders', 'payments', 'shipments', 'returns', 'chat', 'reviews', 'comments', 'seller_apps'));
    }
}
